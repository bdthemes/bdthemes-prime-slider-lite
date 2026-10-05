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
 * attribute through, so a Contributor could place `data-bdt-svg="src: ..."` or
 * `data-bdt-lightbox-panel="..."` in a post, submit it for review, and have the
 * component run when an administrator opens the post on a page that loads the
 * bundle (one Prime Slider widget in the same document is enough).
 *
 * This guard removes those attributes from markup on its way into the database
 * whenever the saving user lacks `unfiltered_html`: Elementor document saves (the
 * editor's widgets, including the Text Editor), classic and block editor content,
 * and comments. Users with `unfiltered_html` are trusted by WordPress to write any
 * HTML and are left alone. The bundle itself also refuses non-http(s) SVG sources
 * and drops scripts, event handlers and script URLs from every fragment it parses,
 * so content stored before this guard existed cannot run script either.
 */
final class Content_Guard {

	/**
	 * A UIkit component attribute, with or without the `data-` prefix, inside a tag.
	 * Matches the attribute name and its value in any of the three HTML quoting forms.
	 */
	const ATTRIBUTE_PATTERN = '/\s+(?:data-)?bdt-[a-z0-9_:.-]*(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i';

	public static function init() {
		// Runs before Elementor's own wp_kses_post() pass in Document::save().
		add_filter( 'elementor/document/save/data', [ __CLASS__, 'filter_elementor_save_data' ], 5 );

		// Classic and block editor content, before kses (priority 10).
		add_filter( 'content_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 5 );
		add_filter( 'excerpt_save_pre', [ __CLASS__, 'filter_untrusted_content' ], 5 );

		// Comments, before kses (priority 10).
		add_filter( 'pre_comment_content', [ __CLASS__, 'filter_untrusted_content' ], 5 );
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
	 * `elementor/document/save/data`: strip component attributes from every string
	 * in the document data (elements, their settings, page settings) for users
	 * without `unfiltered_html`.
	 *
	 * @param mixed $data Document data.
	 * @return mixed
	 */
	public static function filter_elementor_save_data( $data ) {
		if ( self::current_user_is_trusted() ) {
			return $data;
		}

		return map_deep( $data, [ __CLASS__, 'strip_from_value' ] );
	}

	/**
	 * `content_save_pre`, `excerpt_save_pre`, `pre_comment_content`.
	 *
	 * @param mixed $content Content being saved.
	 * @return mixed
	 */
	public static function filter_untrusted_content( $content ) {
		if ( self::current_user_is_trusted() || ! is_string( $content ) ) {
			return $content;
		}

		// These filters carry slashed data, like wp_filter_post_kses() they run next to.
		return addslashes( self::strip_from_value( stripslashes( $content ) ) );
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
	 * Only text inside a tag is touched: the words of a paragraph are never changed.
	 * A tag runs to its closing `>`, skipping any `>` inside a quoted attribute value,
	 * so a component option such as `template: <img ...>` is removed whole.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function strip_component_attributes( $html ) {
		if ( false === stripos( $html, 'bdt-' ) ) {
			return $html;
		}

		$stripped = preg_replace_callback(
			'/<[a-zA-Z][^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*>/',
			function ( $match ) {
				$tag = preg_replace( self::ATTRIBUTE_PATTERN, '', $match[0] );

				return null === $tag ? $match[0] : $tag;
			},
			$html
		);

		return null === $stripped ? $html : $stripped;
	}
}
