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
 * element sits in the document. wp_kses_post(), which WordPress and Elementor apply
 * to content saved by users without `unfiltered_html`, lets every `data-*`
 * attribute through, so a Contributor could place `data-bdt-svg="src: ..."` in a
 * post, submit it for review, and have the component run when an administrator
 * opens the post on a page that loads the bundle.
 *
 * The guard removes those attributes from exactly the markup kses produces, so no
 * difference between two parsers can hide one:
 *
 * - on save, it runs after kses (`content_save_pre`, `excerpt_save_pre` and
 *   `pre_comment_content` above priority 10) or normalises the value with
 *   wp_kses_post() itself first (Elementor document data, whose own kses pass runs
 *   after this filter);
 * - on render, it strips the same attributes from post content, Elementor data and
 *   comments whose author lacks `unfiltered_html`, so content stored before the
 *   guard existed cannot boot a component either.
 *
 * Users with `unfiltered_html` are trusted by WordPress to write any HTML and are
 * left alone. The bundle itself also refuses SVG sources from other origins and
 * drops scripts, frames, event handlers and script URLs from every fragment it
 * parses.
 */
final class Content_Guard {

	/**
	 * A UIkit component attribute, with or without the `data-` prefix, inside a tag.
	 * Only used where WP_HTML_Tag_Processor is unavailable.
	 */
	const ATTRIBUTE_PATTERN = '/[\s\/]+(?:data-)?bdt-[^\s\/>=]*(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i';

	public static function init() {
		// Elementor: the document data is stripped as it is written to the
		// `_elementor_data` meta, after Elementor's own wp_kses_post() pass, so the
		// guard sees the exact markup that is stored. The early filter on the
		// incoming data is kept as a first pass.
		add_filter( 'elementor/document/save/data', [ __CLASS__, 'filter_elementor_save_data' ], 5 );
		add_filter( 'update_post_metadata', [ __CLASS__, 'filter_elementor_meta_write' ], 10, 4 );
		add_filter( 'add_post_metadata', [ __CLASS__, 'filter_elementor_meta_write' ], 10, 4 );

		// Classic and block editor content and comments: after kses (priority 10),
		// so the guard sees the markup kses actually stored; it also runs kses itself
		// in case kses is not hooked on this request.
		add_filter( 'content_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 11 );
		add_filter( 'excerpt_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 11 );
		add_filter( 'pre_comment_content', [ __CLASS__, 'filter_untrusted_content' ], 11 );

		// Render time, for content stored before the guard existed. the_content runs
		// before Elementor replaces the content (priority 9) and before shortcodes,
		// so only the stored markup is touched, never widget output.
		add_filter( 'elementor/frontend/builder_content_data', [ __CLASS__, 'filter_elementor_render_data' ], 5, 2 );
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
	 * every string in the document data with wp_kses_post() and strip component
	 * attributes from the result. Elementor applies wp_kses_post() again afterwards;
	 * kses is stable on its own output, so nothing reappears.
	 *
	 * @param mixed $data Document data.
	 * @return mixed
	 */
	public static function filter_elementor_save_data( $data ) {
		if ( self::current_user_is_trusted() ) {
			return $data;
		}

		return map_deep( $data, [ __CLASS__, 'normalize_and_strip' ] );
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
		// ran), then strip from that output.
		if ( false !== strpos( $content, '<' ) ) {
			$content = wp_kses( $content, wp_kses_allowed_html( current_filter() ) );
		}

		return addslashes( self::strip_from_value( $content ) );
	}

	/**
	 * `update_post_metadata`, `add_post_metadata`: strip component attributes from
	 * `_elementor_data` as it is written by a user without `unfiltered_html`. This
	 * runs after Elementor's wp_kses_post() pass, on the exact JSON being stored.
	 *
	 * @param mixed  $check      Short-circuit value, null to proceed.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Unslashed meta value.
	 * @return mixed
	 */
	public static function filter_elementor_meta_write( $check, $object_id, $meta_key, $meta_value ) {
		static $writing = false;

		if ( $writing || '_elementor_data' !== $meta_key || null !== $check || self::current_user_is_trusted() ) {
			return $check;
		}

		$json = is_string( $meta_value ) ? $meta_value : wp_json_encode( $meta_value );

		if ( ! is_string( $json ) || false === stripos( $json, 'bdt-' ) ) {
			return $check;
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return $check;
		}

		$stripped = map_deep( $data, [ __CLASS__, 'strip_from_value' ] );

		if ( $stripped === $data ) {
			return $check;
		}

		$writing = true;
		$result  = 'add_post_metadata' === current_filter()
			? add_post_meta( $object_id, $meta_key, wp_slash( wp_json_encode( $stripped ) ) )
			: update_post_meta( $object_id, $meta_key, wp_slash( wp_json_encode( $stripped ) ) );
		$writing = false;

		return false === $result ? false : true;
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

		return map_deep( $data, [ __CLASS__, 'strip_from_value' ] );
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
	 * Remove `bdt-*` and `data-bdt-*` attributes from the tags in a piece of HTML.
	 *
	 * The markup is walked with WordPress's HTML tag processor, the same parser
	 * wp_kses_post() uses to normalise a tag, so every attribute form it accepts is
	 * seen here too, including an attribute that follows a `/` with no whitespace.
	 * Only attribute names are compared: the words of a paragraph and other
	 * attributes are never changed.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function strip_component_attributes( $html ) {
		if ( false === stripos( $html, 'bdt-' ) ) {
			return $html;
		}

		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return self::strip_component_attributes_by_pattern( $html );
		}

		$processor = new \WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag() ) {
			foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
				if ( self::is_component_attribute( $name ) ) {
					$processor->remove_attribute( $name );
				}
			}
		}

		$html = $processor->get_updated_html();

		// The tag processor reads the inside of <style>, <script>, <textarea> and
		// similar elements as text, as an HTML page does. Inside <svg> or <math> a
		// browser parses that text as markup, so tags hidden there are stripped too.
		if ( false !== stripos( $html, 'bdt-' ) ) {
			$html = self::strip_component_attributes_by_pattern( $html );
		}

		return $html;
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

	/**
	 * Strip by pattern: every `<tag ...>` in the string, wherever it sits. Used for
	 * tags inside raw-text elements, and where the HTML API is unavailable.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function strip_component_attributes_by_pattern( $html ) {
		$stripped = preg_replace_callback(
			'/<[a-zA-Z][^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*>/',
			function ( $match ) {
				$tag = preg_replace( self::ATTRIBUTE_PATTERN, ' ', $match[0] );

				return null === $tag ? $match[0] : $tag;
			},
			$html
		);

		return null === $stripped ? $html : $stripped;
	}
}
