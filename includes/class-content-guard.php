<?php

namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps UIkit component attributes out of content written by users who may not
 * publish arbitrary HTML.
 *
 * The bundled UIkit (bdt-uikit.min.js) boots a component from any element that
 * carries a `bdt-<component>` or `data-bdt-<component>` attribute, wherever that
 * element sits in the document, and its lightbox applies an `attrs` / `data-attrs`
 * value as arbitrary attributes. wp_kses_post(), which WordPress and Elementor apply
 * to content saved by users without `unfiltered_html`, lets every `data-*`
 * attribute through, so a Contributor could place `data-bdt-svg="src: ..."` in a
 * post, submit it for review, and have the component run when an administrator
 * opens the post on a page that loads the bundle. Elementor also prints link and
 * element "Custom Attributes" (`data-bdt-svg|src: ...`) as real attributes, and
 * that text holds no markup at all.
 *
 * The guard removes those attributes from the markup that is stored:
 *
 * - on save, it runs after kses (`content_save_pre`, `excerpt_save_pre` and
 *   `pre_comment_content` above priority 10) or normalises the value with
 *   wp_kses_post() itself first (Elementor document data, whose own kses pass runs
 *   after this filter), and it cleans `_elementor_data` as it is written, whichever
 *   route wrote it (the editor, the REST API's `meta._elementor_data`, a revision);
 * - on render, it strips the same attributes from post content, Elementor data and
 *   comments whose author lacks `unfiltered_html`, so content stored before the
 *   guard existed cannot boot a component either.
 *
 * Attributes are only ever removed with WordPress's HTML tag processor, never
 * rewritten with a pattern: a pattern cannot tell where a browser considers an
 * attribute value to start and end, and getting that wrong turns inert text into a
 * live attribute (Element Pack's Patchstack 39396). Removing a real attribute can
 * never give a tag one it did not have.
 *
 * Users with `unfiltered_html` are trusted by WordPress to write any HTML and are
 * left alone. The bundle itself also refuses SVG sources from other origins and
 * drops scripts, frames, event handlers and script URLs from every fragment it
 * parses.
 */
final class Content_Guard {

	/**
	 * Elementor settings that hold "Custom Attributes" as `key|value` pairs, and the
	 * delimiter Elementor splits them on: links (Utils::parse_custom_attributes() on
	 * a URL control's `custom_attributes`) and Elementor Pro's Advanced > Attributes
	 * (`_attributes`, one pair per line).
	 */
	const CUSTOM_ATTRIBUTE_SETTINGS = [
		'custom_attributes' => ',',
		'_attributes'       => "\n",
	];

	public static function init() {
		// Elementor: the document data is stripped as it is written to the
		// `_elementor_data` meta, after Elementor's own wp_kses_post() pass, so the
		// guard sees the exact data that is stored. The early filter on the
		// incoming data is kept as a first pass.
		add_filter( 'elementor/document/save/data', [ __CLASS__, 'filter_elementor_save_data' ], 5 );
		add_filter( 'update_post_metadata', [ __CLASS__, 'filter_elementor_meta_write' ], 10, 5 );
		add_filter( 'add_post_metadata', [ __CLASS__, 'filter_elementor_meta_write' ], 10, 5 );

		// Classic and block editor content and comments: after kses (priority 10),
		// so the guard sees the markup kses actually stored; it also runs kses itself
		// in case kses is not hooked on this request.
		add_filter( 'content_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 11 );
		add_filter( 'excerpt_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 11 );
		add_filter( 'pre_comment_content', [ __CLASS__, 'filter_untrusted_content' ], 11 );

		// Render time, for content stored before the guard existed. the_content runs
		// before Elementor replaces the content (priority 9) and before shortcodes,
		// so only the stored markup is touched, never widget output; Elementor
		// documents are cleaned through their data instead.
		add_filter( 'elementor/frontend/builder_content_data', [ __CLASS__, 'filter_elementor_render_data' ], 5, 2 );
		// The same for the data the editor loads (Elementor 3.35+), so an
		// administrator who opens such a document never previews the attributes.
		add_filter( 'elementor/document/load/data', [ __CLASS__, 'filter_elementor_load_data' ], 5, 2 );
		add_filter( 'the_content', [ __CLASS__, 'filter_rendered_content' ], 8 );
		add_filter( 'the_excerpt', [ __CLASS__, 'filter_rendered_content' ], 8 );
		add_filter( 'comment_text', [ __CLASS__, 'filter_rendered_comment' ], 11, 2 );
	}

	/**
	 * Whether the current user may write arbitrary HTML.
	 *
	 * @return bool
	 */
	public static function current_user_is_trusted() {
		return current_user_can( 'unfiltered_html' );
	}

	/**
	 * Whether a user may write arbitrary HTML.
	 *
	 * @param int $user_id User ID; 0 for an anonymous author.
	 * @return bool
	 */
	public static function user_is_trusted( $user_id ) {
		$user_id = (int) $user_id;

		return $user_id > 0 && user_can( $user_id, 'unfiltered_html' );
	}

	/**
	 * `elementor/document/save/data`: for users without `unfiltered_html`, normalise
	 * every markup string in the document data with wp_kses_post(), strip component
	 * attributes from the result and from Custom Attributes settings. Elementor
	 * applies wp_kses_post() again afterwards; kses is stable on its own output, so
	 * nothing reappears.
	 *
	 * @param mixed $data Document data.
	 * @return mixed
	 */
	public static function filter_elementor_save_data( $data ) {
		if ( self::current_user_is_trusted() ) {
			return $data;
		}

		return self::strip_elementor_data( $data, true );
	}

	/**
	 * `content_save_pre`, `excerpt_save_pre`, `pre_comment_content`, after kses.
	 *
	 * @param mixed $content Content being saved.
	 * @return mixed
	 */
	public static function filter_untrusted_content( $content ) {
		if ( self::current_user_is_trusted() || ! is_string( $content ) ) {
			return $content;
		}

		// These filters carry slashed data, like wp_filter_post_kses() they run after.
		$content = stripslashes( $content );

		// Normalise exactly as kses does for this filter (a no-op when kses already
		// ran), then strip from that output. Post content and excerpts are filtered
		// by wp_filter_post_kses() with the 'post' rules; comments by wp_filter_kses()
		// with the rules for the filter's own name. Asking wp_kses_allowed_html() for
		// 'content_save_pre' would return the comment rules and strip paragraphs,
		// images and headings from every post an Author or Contributor saves.
		if ( false !== strpos( $content, '<' ) ) {
			$context = 'pre_comment_content' === current_filter() ? 'pre_comment_content' : 'post';
			$content = wp_kses( $content, $context );
		}

		return addslashes( self::strip_from_value( $content ) );
	}

	/**
	 * `update_post_metadata`, `add_post_metadata`: strip component attributes from
	 * `_elementor_data` as it is written by a user without `unfiltered_html`. This
	 * runs after sanitize_meta() (Elementor's wp_kses_post() pass for the REST API)
	 * and after Document::save()'s own kses pass, on the exact JSON being stored, so
	 * a REST `meta._elementor_data` write is covered as well as an editor save.
	 *
	 * @param mixed  $check      Short-circuit value, null to proceed.
	 * @param int    $object_id  Post ID (a revision's own ID for autosaves).
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Unslashed meta value.
	 * @param mixed  $extra      `$prev_value` for an update, `$unique` for an add.
	 * @return mixed
	 */
	public static function filter_elementor_meta_write( $check, $object_id, $meta_key, $meta_value, $extra = null ) {
		static $writing = false;

		if ( $writing || '_elementor_data' !== $meta_key || null !== $check || self::current_user_is_trusted() ) {
			return $check;
		}

		$json = is_string( $meta_value ) ? $meta_value : wp_json_encode( $meta_value );

		// JSON may spell a letter as b, so only a value with neither the plain
		// text nor an escape can be skipped without decoding it.
		if ( ! is_string( $json ) || ( ! self::may_hold_removable_attribute( $json ) && false === strpos( $json, '\\u' ) ) ) {
			return $check;
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return $check;
		}

		$stripped = self::strip_elementor_data( $data );

		if ( $stripped === $data ) {
			return $check;
		}

		$encoded = wp_json_encode( $stripped );

		if ( false === $encoded ) {
			return $check;
		}

		// update_metadata() / add_metadata() on the same object ID: update_post_meta()
		// would move an autosave revision's data onto its parent post.
		$is_add  = 'add_post_metadata' === current_filter();
		$writing = true;
		$result  = $is_add
			? add_metadata( 'post', $object_id, $meta_key, wp_slash( $encoded ), (bool) $extra )
			: update_metadata( 'post', $object_id, $meta_key, wp_slash( $encoded ), null === $extra ? '' : $extra );
		$writing = false;

		if ( $is_add ) {
			return $result;
		}

		// Saving the same stripped data twice leaves the stored value as it is,
		// which update_metadata() reports as false. That is not a failed save.
		if ( false === $result && $encoded === get_metadata( 'post', $object_id, $meta_key, true ) ) {
			return true;
		}

		return false !== $result;
	}

	/**
	 * `elementor/frontend/builder_content_data`: strip component attributes from the
	 * data of a document whose author lacks `unfiltered_html`.
	 *
	 * @param mixed $data    Elementor data.
	 * @param int   $post_id Document ID.
	 * @return mixed
	 */
	public static function filter_elementor_render_data( $data, $post_id = 0 ) {
		$post = get_post( $post_id );

		if ( ! $post || self::user_is_trusted( $post->post_author ) ) {
			return $data;
		}

		return self::strip_elementor_data( $data );
	}

	/**
	 * `elementor/document/load/data`: the same as filter_elementor_render_data(),
	 * for a document's data as it is loaded (into the editor, among others).
	 *
	 * @param mixed $data     Elementor data.
	 * @param mixed $document Elementor document.
	 * @return mixed
	 */
	public static function filter_elementor_load_data( $data, $document = null ) {
		$post = is_object( $document ) && method_exists( $document, 'get_post' ) ? $document->get_post() : null;

		if ( ! $post instanceof \WP_Post ) {
			return $data;
		}

		return self::filter_elementor_render_data( $data, $post->ID );
	}

	/**
	 * `the_content`, `the_excerpt`: strip component attributes when the post's
	 * author lacks `unfiltered_html`.
	 *
	 * @param mixed $content Rendered content.
	 * @return mixed
	 */
	public static function filter_rendered_content( $content ) {
		$post = get_post();

		if ( ! $post || self::user_is_trusted( $post->post_author ) ) {
			return $content;
		}

		return self::strip_from_value( $content );
	}

	/**
	 * `comment_text`: strip component attributes unless the comment's author may
	 * write arbitrary HTML.
	 *
	 * @param mixed            $text    Comment text.
	 * @param \WP_Comment|null $comment Comment.
	 * @return mixed
	 */
	public static function filter_rendered_comment( $text, $comment = null ) {
		if ( $comment instanceof \WP_Comment && self::user_is_trusted( $comment->user_id ) ) {
			return $text;
		}

		return self::strip_from_value( $text );
	}

	/**
	 * Strip component attributes from Elementor data: from every markup string, from
	 * the `key|value` Custom Attributes settings, and from the attributes of atomic
	 * elements (Elementor 4), whose names are stored as plain strings.
	 *
	 * @param mixed $data      Elementor data (elements, settings or a single value).
	 * @param bool  $normalize Run wp_kses_post() on markup strings first.
	 * @return mixed
	 */
	public static function strip_elementor_data( $data, $normalize = false ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_string( $key ) && is_string( $value ) && isset( self::CUSTOM_ATTRIBUTE_SETTINGS[ $key ] ) ) {
					$data[ $key ] = self::strip_custom_attributes( $value, self::CUSTOM_ATTRIBUTE_SETTINGS[ $key ] );
				} else {
					$data[ $key ] = self::strip_elementor_data( $value, $normalize );
				}
			}

			return self::strip_attributes_prop( $data );
		}

		if ( is_object( $data ) ) {
			foreach ( get_object_vars( $data ) as $key => $value ) {
				$data->$key = self::strip_elementor_data( $value, $normalize );
			}

			return $data;
		}

		return $normalize ? self::normalize_and_strip( $data ) : self::strip_from_value( $data );
	}

	/**
	 * Remove component attributes from a Custom Attributes string.
	 *
	 * The name of each pair is read the way Elementor reads it when it prints the
	 * attribute (Utils::parse_custom_attributes(): lower-cased, then the first run
	 * of `[-_a-z0-9]`), so the guard judges exactly the name that would be printed.
	 * A string with nothing to remove is returned unchanged.
	 *
	 * @param string $attributes Custom Attributes setting.
	 * @param string $delimiter  Pair delimiter.
	 * @return string
	 */
	public static function strip_custom_attributes( $attributes, $delimiter = ',' ) {
		if ( ! is_string( $attributes ) || ! self::may_hold_removable_attribute( $attributes ) ) {
			return $attributes;
		}

		$kept    = [];
		$removed = false;

		foreach ( explode( $delimiter, $attributes ) as $pair ) {
			$parts = explode( '|', $pair );
			$name  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $parts[0] ) : strtolower( $parts[0] );

			if ( preg_match( '/[-_a-z0-9]+/', $name, $match ) && self::is_removable_attribute( $match[0] ) ) {
				$removed = true;
				continue;
			}

			$kept[] = $pair;
		}

		return $removed ? implode( $delimiter, $kept ) : $attributes;
	}

	/**
	 * Drop component attributes from an atomic element's `attributes` prop:
	 * `{"$$type":"attributes","value":[{"$$type":"key-value","value":{"key":{"$$type":"string","value":"data-bdt-svg"},...}}]}`.
	 *
	 * @param array $prop Any array; only an attributes prop is changed.
	 * @return array
	 */
	private static function strip_attributes_prop( array $prop ) {
		if ( 'attributes' !== ( $prop['$$type'] ?? null ) || ! isset( $prop['value'] ) || ! is_array( $prop['value'] ) ) {
			return $prop;
		}

		$kept    = [];
		$removed = false;

		foreach ( $prop['value'] as $item ) {
			$name = is_array( $item ) && isset( $item['value']['key'] ) ? $item['value']['key'] : null;
			$name = is_array( $name ) ? ( $name['value'] ?? null ) : $name;

			if ( is_string( $name ) && self::is_removable_attribute( trim( $name ) ) ) {
				$removed = true;
				continue;
			}

			$kept[] = $item;
		}

		if ( $removed ) {
			$prop['value'] = $kept;
		}

		return $prop;
	}

	/**
	 * Normalise markup with wp_kses_post() and strip component attributes from the
	 * result, so the guard walks exactly the tags kses keeps.
	 *
	 * @param mixed $value Any value; only strings containing a tag are changed.
	 * @return mixed
	 */
	public static function normalize_and_strip( $value ) {
		if ( ! is_string( $value ) || false === strpos( $value, '<' ) ) {
			return $value;
		}

		return self::strip_component_attributes( wp_kses_post( $value ) );
	}

	/**
	 * Strip component attributes from a value if it is a string holding markup.
	 *
	 * @param mixed $value Any value; only strings containing a tag are changed.
	 * @return mixed
	 */
	public static function strip_from_value( $value ) {
		if ( ! is_string( $value ) || false === strpos( $value, '<' ) ) {
			return $value;
		}

		return self::strip_component_attributes( $value );
	}

	/**
	 * Remove `bdt-*`, `data-bdt-*`, `attrs` and `data-attrs` attributes from the tags
	 * in a piece of HTML.
	 *
	 * The markup is walked with WordPress's HTML tag processor, the same parser
	 * wp_kses_post() uses to normalise a tag, so every attribute form it accepts is
	 * seen here too, including an attribute that follows a `/` with no whitespace.
	 * Only attribute names are compared: the words of a paragraph and other
	 * attributes are never changed, and nothing is ever rewritten.
	 *
	 * Inside <svg> or <math> a browser parses the content of <style>, <textarea>,
	 * <title> and the like as markup, while in HTML it is text, so markup holding
	 * either element is walked a second time with everything after the first one
	 * read as foreign content. A tag hidden in such text is seen and cleaned by
	 * that pass; reading too much as markup only ever removes more.
	 *
	 * Without the HTML API (WordPress < 6.2, below this plugin's minimum) the
	 * markup is returned unchanged rather than edited by a pattern.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function strip_component_attributes( $html ) {
		if ( ! is_string( $html ) || ! self::may_hold_removable_attribute( $html ) ) {
			return $html;
		}

		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$html = self::remove_attributes( $html, false );

		if ( self::may_hold_removable_attribute( $html ) && ( false !== stripos( $html, '<svg' ) || false !== stripos( $html, '<math' ) ) ) {
			$html = self::remove_attributes( $html, true );
		}

		return $html;
	}

	/**
	 * One tag processor pass removing every removable attribute.
	 *
	 * @param string $html    Markup.
	 * @param bool   $foreign Read everything after the first <svg> or <math> start
	 *                        tag as foreign content, where no element holds raw text.
	 * @return string
	 */
	private static function remove_attributes( $html, $foreign ) {
		$processor = new \WP_HTML_Tag_Processor( $html );

		if ( $foreign && ! ( method_exists( $processor, 'change_parsing_namespace' ) && method_exists( $processor, 'get_namespace' ) ) ) {
			return $html;
		}

		while ( $processor->next_tag() ) {
			foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
				if ( self::is_removable_attribute( $name ) ) {
					$processor->remove_attribute( $name );
				}
			}

			if ( $foreign && 'html' === $processor->get_namespace() ) {
				$tag = strtoupper( (string) $processor->get_tag() );

				if ( 'SVG' === $tag || 'MATH' === $tag ) {
					$processor->change_parsing_namespace( strtolower( $tag ) );
				}
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Cheap pre-check: whether a string could name a removable attribute at all.
	 *
	 * @param string $text Any text.
	 * @return bool
	 */
	private static function may_hold_removable_attribute( $text ) {
		return false !== stripos( $text, 'bdt-' ) || false !== stripos( $text, 'attrs' );
	}

	/**
	 * Whether an attribute should be removed from untrusted markup: a `bdt-*`
	 * component attribute, or UIkit's generic `attrs` carrier, whose value the
	 * lightbox applies as arbitrary attributes. Neither is ever emitted by a
	 * widget into content that passes through these filters.
	 *
	 * @param string $name Attribute name.
	 * @return bool
	 */
	public static function is_removable_attribute( $name ) {
		if ( self::is_component_attribute( $name ) ) {
			return true;
		}

		$name = strtolower( (string) $name );

		if ( 0 === strpos( $name, 'data-' ) ) {
			$name = substr( $name, 5 );
		}

		return 'attrs' === $name;
	}

	/**
	 * Whether an attribute name boots a UIkit component.
	 *
	 * UIkit drops a leading `data-` and then matches the `bdt-` prefix, so both
	 * forms count. Compared case-insensitively, as HTML attribute names are.
	 *
	 * @param string $name Attribute name.
	 * @return bool
	 */
	public static function is_component_attribute( $name ) {
		$name = strtolower( (string) $name );

		if ( 0 === strpos( $name, 'data-' ) ) {
			$name = substr( $name, 5 );
		}

		return 0 === strpos( $name, 'bdt-' );
	}
}
