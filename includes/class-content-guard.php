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
 * value, and the objects of a `sources` / `data-sources` value, as arbitrary
 * attributes. wp_kses_post(), which WordPress and Elementor apply
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
 * Attributes are removed with WordPress's HTML tag processor, never rewritten with
 * a pattern: a pattern cannot tell where a browser considers an attribute value to
 * start and end, and getting that wrong turns inert text into a live attribute
 * (Element Pack's Patchstack 39396). Removing a real attribute can never give a tag
 * one it did not have. Where the tag processor and a browser may read the markup
 * differently (see strip_component_attributes()), a removable name that is left is
 * renamed by inserting one letter, which cannot move an attribute boundary either.
 *
 * Users with `unfiltered_html` are trusted by WordPress to write any HTML and are
 * left alone. The bundle itself also refuses SVG sources from other origins and
 * drops scripts, frames, event handlers and script URLs from every fragment it
 * parses.
 */
final class Content_Guard {

	/**
	 * A removable attribute name (see is_removable_attribute()) where a browser
	 * could start reading one: right after HTML whitespace, a `/` or a quote. Inside
	 * a tag a browser begins a new attribute name only after one of those (`<a/x>`,
	 * `<a b="c"x>`), so these are the only places where markup the tag processor
	 * did not read as a tag could hold a name a browser reads.
	 *
	 * `attrs` and `sources` only matter with a value (an empty one gives UIkit
	 * nothing to apply), so they count only when an `=` follows; the words alone,
	 * as in "the sources of", are prose. A `bdt-` name boots its component even
	 * without a value, so it always counts.
	 */
	const REMOVABLE_NAME_PATTERN = '~(?<=[\t\n\f\r /"\'])(?:data-)?(?:bdt-|(?:attrs|sources)(?=[\t\n\f\r ]*=))~i';

	/**
	 * Markup from which the tag processor and a browser may stop reading the same
	 * tags (see strip_component_attributes()): the start or end tag of an element
	 * whose content one of them reads as text and the other may read as markup, and
	 * a CDATA section.
	 */
	const DIVERGENT_MARKUP_PATTERN = '~<(?:/?(?:textarea|title|style|xmp|iframe|noembed|noframes|script|noscript)(?=[\t\n\f\r />]|\z)|!\[CDATA\[)~i';

	public static function init() {
		// Elementor: the document data is stripped as it is written to the
		// `_elementor_data` meta, after Elementor's own wp_kses_post() pass, so the
		// guard sees the exact data that is stored. The early filter on the
		// incoming data is kept as a first pass.
		add_filter( 'elementor/document/save/data', [ __CLASS__, 'filter_elementor_save_data' ], 5 );
		add_filter( 'update_post_metadata', [ __CLASS__, 'filter_elementor_meta_write' ], 10, 5 );
		add_filter( 'add_post_metadata', [ __CLASS__, 'filter_elementor_meta_write' ], 10, 5 );

		// Classic and block editor content and comments: after kses (priority 10),
		// so the guard sees the markup kses actually stored. It never runs kses
		// itself: code that unhooks kses on purpose (core's oEmbed cache, form
		// builders storing JSON, translation imports) must get its data back
		// untouched apart from the removed attributes.
		add_filter( 'content_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 11 );
		add_filter( 'excerpt_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 11 );
		add_filter( 'pre_comment_content', [ __CLASS__, 'filter_untrusted_content' ], 11 );

		// Render time, for content stored before the guard existed. the_content runs
		// before oEmbed turns URLs into provider iframes and scripts (priority 8),
		// before Elementor replaces the content (priority 9) and before shortcodes,
		// so only the stored markup is touched, never provider or widget output;
		// Elementor documents are cleaned through their data instead.
		add_filter( 'elementor/frontend/builder_content_data', [ __CLASS__, 'filter_elementor_render_data' ], 5, 2 );
		// The same for the data the editor loads (Elementor 3.35+), so an
		// administrator who opens such a document never previews the attributes.
		add_filter( 'elementor/document/load/data', [ __CLASS__, 'filter_elementor_load_data' ], 5, 2 );
		add_filter( 'the_content', [ __CLASS__, 'filter_rendered_content' ], 7 );
		add_filter( 'the_excerpt', [ __CLASS__, 'filter_rendered_content' ], 7 );
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
		$unslashed = stripslashes( $content );
		$stripped  = self::strip_from_value( $unslashed );

		// Only attributes are ever removed here, and kses is left to WordPress: when
		// kses ran at priority 10 a second pass changed nothing, and when a caller
		// unhooked it on purpose a second pass corrupted that caller's data. Content
		// with nothing to remove is handed back exactly as it came in.
		return $stripped === $unslashed ? $content : addslashes( $stripped );
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
				if ( is_string( $key ) && is_string( $value ) && self::is_custom_attributes_setting( $key ) ) {
					$data[ $key ] = self::strip_custom_attributes( $value, 'custom_attributes' === $key ? ',' : "\n" );
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
	 * Whether an Elementor setting holds "Custom Attributes" as `key|value` pairs: a
	 * link's `custom_attributes` (Elementor's URL control, read with commas between
	 * pairs), Elementor Pro's Advanced > Attributes (`_attributes`, one pair per
	 * line), and other plugins' textareas named like them (Element Pack's Price Table
	 * `price_custom_attributes` and `sale_custom_attributes`, among others). A switch
	 * such as `add_custom_attributes` matches too, harmlessly: its value names nothing.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	private static function is_custom_attributes_setting( $key ) {
		return '_attributes' === $key || 'custom_attributes' === substr( $key, -17 );
	}

	/**
	 * Remove component attributes from a Custom Attributes string.
	 *
	 * The string is cleaned for each way it is read, and only where a reader would
	 * print a removable attribute (a `bdt-` name, or `attrs` / `sources` with a
	 * value). Every key is read one pair per line: Elementor Pro's Attributes take
	 * the first run of `[-_a-z0-9]` of the key and the text up to the next `|`,
	 * Element Pack's textareas (Price Table, Iframe, weForms) take the trimmed key if
	 * it is a valid name and the rest of the line, commas included. A link's
	 * `custom_attributes`, which Element Pack's textareas share, is also read the
	 * way Elementor's link control reads it: split on commas only, so a pair can run
	 * over a line break.
	 *
	 * Where a reader would print one, the comma-separated segment of the line that
	 * holds the name is dropped, and the readers run again, since dropping a segment
	 * changes what the next pair reads. A crafted string could expose one more pair
	 * each time, so after a few passes every segment whose own name is removable goes
	 * at once, which ends it: every name a reader prints is a segment's own name. A
	 * string no reader prints such an attribute from is returned unchanged.
	 *
	 * @param string $attributes Custom Attributes setting.
	 * @param string $delimiter  ',' when a comma reader may read it (a link's
	 *                           `custom_attributes`), "\n" when it is only read one
	 *                           pair per line (`_attributes`, Element Pack's Price
	 *                           Table `price_custom_attributes` and the like).
	 * @return string
	 */
	public static function strip_custom_attributes( $attributes, $delimiter = ',' ) {
		if ( ! is_string( $attributes ) || ! self::may_hold_removable_attribute( $attributes ) ) {
			return $attributes;
		}

		$lines = [];

		foreach ( explode( "\n", $attributes ) as $line ) {
			$lines[] = explode( ',', $line );
		}

		// Nothing to judge when no segment is even named like a removable attribute.
		if ( ! self::custom_attribute_segments_to_drop( $lines, false, true ) ) {
			return $attributes;
		}

		$comma   = "\n" !== $delimiter;
		$removed = false;

		for ( $pass = 1; $drop = self::custom_attribute_segments_to_drop( $lines, $comma, $pass > 8 ); $pass++ ) {
			$removed = true;

			foreach ( $drop as $place ) {
				unset( $lines[ $place[0] ][ $place[1] ] );
			}

			foreach ( $lines as $index => $segments ) {
				if ( $segments ) {
					$lines[ $index ] = array_values( $segments );
				} else {
					unset( $lines[ $index ] );
				}
			}

			$lines = array_values( $lines );
		}

		if ( ! $removed ) {
			return $attributes;
		}

		return implode( "\n", array_map( function ( $segments ) {
			return implode( ',', $segments );
		}, $lines ) );
	}

	/**
	 * The segments that hold a name some reader would print as a removable attribute.
	 *
	 * @param array $lines   Lines, each a list of its comma-separated segments.
	 * @param bool  $comma   Also apply the comma reading of a link's attributes.
	 * @param bool  $by_name Every segment whose own name (first run or trimmed key)
	 *                       is removable, value or not, instead of what readers print.
	 * @return array List of [ line index, segment index ].
	 */
	private static function custom_attribute_segments_to_drop( array $lines, $comma, $by_name ) {
		$drop = [];

		if ( $by_name ) {
			foreach ( $lines as $line => $segments ) {
				foreach ( $segments as $segment => $text ) {
					$key = self::custom_attribute_pair( $text, true )[0];

					if ( self::is_removable_attribute( self::first_name_run( $key ) ) || self::is_removable_attribute( strtolower( trim( $key ) ) ) ) {
						$drop[] = [ $line, $segment ];
					}
				}
			}

			return $drop;
		}

		if ( $comma ) {
			// A pair runs on from a line's last segment into the next line's first,
			// and ends at a comma. Each one is judged as soon as it is complete.
			$parts  = [];
			$places = [];

			foreach ( $lines as $line => $segments ) {
				foreach ( $segments as $segment => $text ) {
					if ( $segment > 0 && $places ) {
						self::judge_comma_pair( $parts, $places, $drop );
						$parts  = [];
						$places = [];
					}

					$parts[]  = $text;
					$places[] = [ $line, $segment ];
				}
			}

			if ( $places ) {
				self::judge_comma_pair( $parts, $places, $drop );
			}
		}

		foreach ( $lines as $line => $segments ) {
			$text = implode( ',', $segments );
			$pair = self::custom_attribute_pair( $text, false );

			if ( self::custom_attribute_prints_removable( self::first_name_run( $pair[0] ), $pair[1] ) ) {
				$at = self::segment_holding_name( $segments );

				if ( null !== $at ) {
					$drop[] = [ $line, $at ];
				}
			}

			$pair = self::custom_attribute_pair( $text, true );
			$key  = trim( $pair[0] );

			if ( preg_match( '/^[A-Za-z][A-Za-z0-9_:-]*$/', $key ) && self::custom_attribute_prints_removable( strtolower( $key ), $pair[1] ) ) {
				$drop[] = [ $line, 0 ];
			}
		}

		return array_values( array_unique( $drop, SORT_REGULAR ) );
	}

	/**
	 * Judge one pair of the comma reading: its segments joined by the line breaks
	 * between them, read like Utils::parse_custom_attributes().
	 *
	 * @param array $parts  The pair's segments, in order.
	 * @param array $places Their [ line index, segment index ].
	 * @param array $drop   Places to drop, added to.
	 * @return void
	 */
	private static function judge_comma_pair( array $parts, array $places, array &$drop ) {
		$pair = self::custom_attribute_pair( implode( "\n", $parts ), false );

		if ( self::custom_attribute_prints_removable( self::first_name_run( $pair[0] ), $pair[1] ) ) {
			$at = self::segment_holding_name( $parts );

			if ( null !== $at ) {
				$drop[] = $places[ $at ];
			}
		}
	}

	/**
	 * Split a `key|value` pair the way its reader does.
	 *
	 * @param string $text      The pair.
	 * @param bool   $rest      Take the value up to the end (explode( '|', $text, 2 ))
	 *                          rather than up to the next `|` (explode( '|', $text )).
	 * @return array [ key, value ].
	 */
	private static function custom_attribute_pair( $text, $rest ) {
		$first = strpos( $text, '|' );

		if ( false === $first ) {
			return [ $text, '' ];
		}

		$second = $rest ? false : strpos( $text, '|', $first + 1 );
		$value  = false === $second ? substr( $text, $first + 1 ) : substr( $text, $first + 1, $second - $first - 1 );

		return [ substr( $text, 0, $first ), (string) $value ];
	}

	/**
	 * Whether a printed Custom Attributes pair is a removable attribute: a `bdt-`
	 * name boots its component even without a value, while `attrs` and `sources`
	 * only matter with one.
	 *
	 * @param string|null $name  Printed name, lower-cased.
	 * @param string      $value Printed value.
	 * @return bool
	 */
	private static function custom_attribute_prints_removable( $name, $value ) {
		if ( null === $name || '' === $name ) {
			return false;
		}

		return self::is_component_attribute( $name ) || ( '' !== trim( $value ) && self::is_removable_attribute( $name ) );
	}

	/**
	 * The name Utils::parse_custom_attributes() prints for a key: the first run of
	 * `[-_a-z0-9]` once lower-cased.
	 *
	 * @param string $key Key.
	 * @return string|null
	 */
	private static function first_name_run( $key ) {
		$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $key ) : strtolower( $key );

		return preg_match( '/[-_a-z0-9]+/', (string) $key, $match ) ? $match[0] : null;
	}

	/**
	 * Which of a pair's parts holds the start of its name: the first part with a
	 * name character before the pair's first `|`.
	 *
	 * @param array $parts The pair's segments, in order.
	 * @return int|null
	 */
	private static function segment_holding_name( array $parts ) {
		foreach ( array_values( $parts ) as $index => $part ) {
			$key = self::custom_attribute_pair( $part, true );
			$low = function_exists( 'mb_strtolower' ) ? mb_strtolower( $key[0] ) : strtolower( $key[0] );

			if ( preg_match( '/[-_a-z0-9]/', (string) $low ) ) {
				return $index;
			}

			if ( false !== strpos( $part, '|' ) ) {
				return null;
			}
		}

		return null;
	}

	/**
	 * Drop component attributes from an atomic element's `attributes` prop:
	 * `{"$$type":"attributes","value":[{"$$type":"key-value","value":{"key":{"$$type":"string","value":"data-bdt-svg"},...}}]}`.
	 *
	 * Elementor Pro joins the items into `key="value"` text, which is printed in the
	 * tag as it is (before Elementor 4.4.1, and in the editor) or read back with
	 * /([a-zA-Z0-9_-]+)\s*=\s*(["'])(.*?)\2/ (4.4.1 and later). Both readings stay
	 * in step with the items only when each key is a plain name and no value holds a
	 * `"` that closes the attribute; Elementor 4.4.1 and later refuse anything else
	 * on save, but items stored earlier are still printed. So an item goes when its
	 * key is not such a name or is a removable one, or when its value holds a `"`.
	 * Every other item is then printed as exactly one attribute, its own, whatever
	 * its value says.
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
			$name  = is_array( $item ) && isset( $item['value']['key'] ) ? $item['value']['key'] : null;
			$name  = is_array( $name ) ? ( $name['value'] ?? null ) : $name;
			$value = is_array( $item ) && isset( $item['value']['value'] ) ? $item['value']['value'] : null;
			$value = is_array( $value ) ? ( $value['value'] ?? null ) : $value;

			if (
				( is_string( $name ) && ( ! preg_match( '/^[a-zA-Z0-9_-]+$/D', $name ) || self::is_removable_attribute( $name ) ) ) ||
				( is_string( $value ) && false !== strpos( $value, '"' ) )
			) {
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
	 * Remove `bdt-*`, `data-bdt-*`, `attrs`, `data-attrs`, `sources` and
	 * `data-sources` attributes from the tags in a piece of HTML.
	 *
	 * The markup is walked once with WordPress's HTML tag processor, which splits
	 * it into tags, text and comments exactly as a browser's tokenizer does, with
	 * one difference: a browser's tree builder tells its tokenizer which elements
	 * hold text instead of markup, while the tag processor decides that from the
	 * tag name alone. It reads the content of TEXTAREA, TITLE, STYLE, XMP, IFRAME,
	 * NOEMBED, NOFRAMES and SCRIPT as text, which a browser reads as markup inside
	 * <svg> or <math> and wherever the tag itself is ignored (in a <select>, in a
	 * <template>'s column group); it reads NOSCRIPT content as markup, which a
	 * browser running scripts skips as text; and it reads a CDATA section, which is
	 * text inside <svg> or <math>, as a comment. Nothing else the tree builder does
	 * changes how the markup is split. A walk that reaches the end of the input
	 * inside a tag, a comment or one of those elements also leaves that last part
	 * unread, while in a page a browser goes on into the markup that follows.
	 *
	 * So up to the first such start or end tag, CDATA section or unread part, the
	 * tag processor saw every tag a browser builds, and removing the attributes from
	 * those tags is complete. From that point on, a removable name still left where
	 * a browser could read an attribute name is renamed so it no longer matches: one
	 * letter is inserted before it (`data-bdt-svg` becomes `xdata-bdt-svg`). Adding
	 * a letter cannot open or close a quote or a tag, so no attribute boundary moves
	 * and inert text never becomes an attribute. After such an element, any text
	 * that holds one of these names in such a place changes by a letter, whether it
	 * is prose, an attribute value (a class, a URL) or a block's attributes in its
	 * comment; markup without those elements, which is nearly all of it, only ever
	 * loses the removed attributes.
	 *
	 * Without the HTML API (WordPress < 6.5, below this plugin's minimum) every
	 * such name is renamed.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function strip_component_attributes( $html ) {
		if ( ! is_string( $html ) || ! self::may_hold_removable_markup( $html ) ) {
			return $html;
		}

		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) || ! method_exists( '\WP_HTML_Tag_Processor', 'next_token' ) ) {
			return self::neutralize_removable_names( $html, 0 );
		}

		list( $html, $unsafe_from ) = self::remove_attributes( $html );

		$divergent = preg_match( self::DIVERGENT_MARKUP_PATTERN, $html, $match, PREG_OFFSET_CAPTURE );

		if ( false === $divergent ) {
			$unsafe_from = 0;
		} elseif ( $divergent ) {
			$unsafe_from = null === $unsafe_from ? $match[0][1] : min( $unsafe_from, $match[0][1] );
		}

		return null === $unsafe_from ? $html : self::neutralize_removable_names( $html, $unsafe_from );
	}

	/**
	 * Rename every removable name a browser could read as an attribute name from
	 * an offset on, by inserting an `x` before it (`data-bdt-svg` becomes
	 * `xdata-bdt-svg`, `attrs=` becomes `xattrs=`).
	 *
	 * @param string $html Markup.
	 * @param int    $from Byte offset; the markup before it is left as it is.
	 * @return string
	 */
	private static function neutralize_removable_names( $html, $from ) {
		$rest        = (string) substr( $html, $from );
		$neutralized = preg_replace( self::REMOVABLE_NAME_PATTERN, 'x$0', $rest );

		// Never expected; if the pattern cannot run, the part it would have checked
		// is turned into text rather than kept as it was.
		if ( ! is_string( $neutralized ) ) {
			$neutralized = str_replace( '<', '&lt;', $rest );
		}

		return substr( $html, 0, $from ) . $neutralized;
	}

	/**
	 * One tag processor walk removing every removable attribute from the start tags
	 * it reads.
	 *
	 * @param string $html Markup.
	 * @return array The markup, and the byte offset in it from which the walk read
	 *               nothing because the input ended inside a token (null if it read
	 *               everything).
	 */
	private static function remove_attributes( $html ) {
		$processor = new class( $html ) extends \WP_HTML_Tag_Processor {
			/**
			 * Bytes left unread at the end of the input: 0 when the walk read all of
			 * it, otherwise everything after the last token it read, which is located
			 * through a bookmark set on that token. Null when that cannot be told.
			 *
			 * @param string $bookmark Bookmark set on every token read.
			 * @return int|null
			 */
			public function unread_length( $bookmark ) {
				if ( ! $this->paused_at_incomplete_token() ) {
					return 0;
				}

				if ( ! isset( $this->html ) || ! is_string( $this->html ) ) {
					return null;
				}

				$last = isset( $this->bookmarks[ $bookmark ] ) ? $this->bookmarks[ $bookmark ] : null;

				if ( ! isset( $last->start, $last->length ) ) {
					return strlen( $this->html );
				}

				return max( 0, strlen( $this->html ) - ( $last->start + $last->length ) );
			}
		};

		while ( $processor->next_token() ) {
			$processor->set_bookmark( 'read' );

			if ( '#tag' !== $processor->get_token_type() || $processor->is_tag_closer() ) {
				continue;
			}

			foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
				if ( self::is_removable_attribute( $name ) ) {
					$processor->remove_attribute( $name );
				}
			}
		}

		// The unread part follows every change, so it ends the updated markup too.
		$unread  = $processor->unread_length( 'read' );
		$updated = $processor->get_updated_html();

		if ( null === $unread ) {
			return [ $updated, 0 ];
		}

		return [ $updated, $unread > 0 ? max( 0, strlen( $updated ) - $unread ) : null ];
	}

	/**
	 * Cheap pre-check: whether a string could name a removable attribute at all.
	 *
	 * @param string $text Any text.
	 * @return bool
	 */
	private static function may_hold_removable_attribute( $text ) {
		return false !== stripos( $text, 'bdt-' ) || false !== stripos( $text, 'attrs' ) || false !== stripos( $text, 'sources' );
	}

	/**
	 * Cheap pre-check for markup: whether it could hold a removable attribute that
	 * matters. In a tag, `attrs` and `sources` only matter with a value, so without
	 * a `bdt-` the markup needs one of them followed by `=`; the words alone, as in
	 * "resources", do not send a post through the tag processor.
	 *
	 * @param string $html Markup.
	 * @return bool
	 */
	private static function may_hold_removable_markup( $html ) {
		if ( false !== stripos( $html, 'bdt-' ) ) {
			return true;
		}

		return ( false !== stripos( $html, 'attrs' ) || false !== stripos( $html, 'sources' ) )
			&& 1 === preg_match( '~(?:attrs|sources)[\t\n\f\r ]*=~i', $html );
	}

	/**
	 * Whether an attribute should be removed from untrusted markup: a `bdt-*`
	 * component attribute, UIkit's generic `attrs` carrier, whose value the
	 * lightbox applies as arbitrary attributes, or the lightbox's `sources` item
	 * option, whose objects become <source> attributes. None of them is ever
	 * emitted by a widget into content that passes through these filters.
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

		return 'attrs' === $name || 'sources' === $name;
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
