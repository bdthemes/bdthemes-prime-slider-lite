<?php
use PrimeSlider\Prime_Slider_Loader;
use Elementor\Plugin;

/**
 * You can easily add white label branding for for extended license or multi site license.
 * Don't try for regular license otherwise your license will be invalid.
 * return white label
 */
define( 'BDTPS_CORE_PNAME', basename( dirname( BDTPS_CORE__FILE__ ) ) );
define( 'BDTPS_CORE_PBNAME', plugin_basename( BDTPS_CORE__FILE__ ) );
define( 'BDTPS_CORE_PATH', plugin_dir_path( BDTPS_CORE__FILE__ ) );
define( 'BDTPS_CORE_URL', plugins_url( '/', BDTPS_CORE__FILE__ ) );
define( 'BDTPS_CORE_ADMIN_PATH', BDTPS_CORE_PATH . 'admin/' );
define( 'BDTPS_CORE_ADMIN_URL', BDTPS_CORE_URL . 'admin/' );
define( 'BDTPS_CORE_ADMIN_ASSETS_URL', BDTPS_CORE_URL . 'admin/assets/' );
define( 'BDTPS_CORE_MODULES_PATH', BDTPS_CORE_PATH . 'modules/' );
define( 'BDTPS_CORE_INC_PATH', BDTPS_CORE_PATH . 'includes/' );
define( 'BDTPS_CORE_ASSETS_URL', BDTPS_CORE_URL . 'assets/' );
define( 'BDTPS_CORE_ASSETS_PATH', BDTPS_CORE_PATH . 'assets/' );
define( 'BDTPS_CORE_MODULES_URL', BDTPS_CORE_URL . 'modules/' );

if ( ! defined( 'BDTPS' ) ) {
	define( 'BDTPS', '' );
} //Add prefix for all widgets <span class="bdt-widget-badge"></span>
if ( ! defined( 'BDTPS_CORE_CP' ) ) {
	define( 'BDTPS_CORE_CP', '<span class="bdt-ps-widget-badge"></span>' );
} //Add prefix for all widgets <span class="bdt-widget-badge"></span>
if ( ! defined( 'BDTPS_CORE_NC' ) ) {
	define( 'BDTPS_CORE_NC', '<span class="bdt-ps-new-control"></span>' );
} // if you have any custom style
if ( ! defined( 'BDTPS_CORE_SLUG' ) ) {
	define( 'BDTPS_CORE_SLUG', 'prime-slider' );
} // set your own alias
if ( ! defined( 'BDTPS_CORE_TITLE' ) ) {
	define( 'BDTPS_CORE_TITLE', 'Prime Slider' );
} // set your own alias
if ( true === _is_ps_pro_activated() ) {

	if ( ! defined( 'BDTPS_CORE_PC' ) ) {
		define( 'BDTPS_CORE_PC', '' );
	}
	define( 'BDTPS_CORE_IS_PC', '' );
} else {
	if ( ! defined( 'BDTPS_CORE_PC' ) ) {
		define( 'BDTPS_CORE_PC', '<span class="bdt-ps-pro-control"></span>' );
	}
	define( 'BDTPS_CORE_IS_PC', 'bdt-ps-disabled-control' );
}


function prime_slider_is_edit() {
	return Plugin::$instance->editor->is_edit_mode();
}

function prime_slider_is_preview() {
	return Plugin::$instance->preview->is_preview_mode();
}


/**
 * Show any alert by this function
 * @param  mixed  $message [description]
 * @param  class prefix  $type    [description]
 * @param  boolean $close   [description]
 * @return helper           [description]
 */
function prime_slider_alert( $message, $type = 'warning', $close = true ) {
	?>
	<div class="bdt-alert-<?php echo esc_attr( $type ); ?>" bdt-alert>
		<?php if ( $close ) : ?>
			<a class="bdt-alert-close" bdt-close></a>
		<?php endif; ?>
		<?php echo wp_kses_post( $message ); ?>
	</div>
	<?php
}

/**
 * all array css classes will output as proper space
 * @param array $classes shortcode css class as array
 * @return proper string
 */

function prime_slider_get_post_types() {

	$cpts         = get_post_types( array( 'public' => true, 'show_in_nav_menus' => true ) );
	$exclude_cpts = array( 'elementor_library', 'attachment', 'product' );

	foreach ( $exclude_cpts as $exclude_cpt ) {
		unset( $cpts[ $exclude_cpt ] );
	}

	$post_types = array_merge( $cpts );
	return $post_types;
}

function prime_slider_allow_tags( $tag = null ) {
	$tag_allowed = wp_kses_allowed_html( 'post' );

	$tag_allowed['input']  = [ 
		'class'   => [],
		'id'      => [],
		'name'    => [],
		'value'   => [],
		'checked' => [],
		'type'    => [],
	];
	$tag_allowed['select'] = [ 
		'class'    => [],
		'id'       => [],
		'name'     => [],
		'value'    => [],
		'multiple' => [],
		'type'     => [],
	];
	$tag_allowed['option'] = [ 
		'value'    => [],
		'selected' => [],
	];

	$tag_allowed['title'] = [ 
		'a'      => [ 
			'href'  => [],
			'title' => [],
			'class' => [],
		],
		'br'     => [],
		'em'     => [],
		'strong' => [],
		'hr'     => [],
	];

	$tag_allowed['logo'] = [ 
		'br'     => [],
		'em'     => [],
		'strong' => [],
		'span'   => [],
	];

	$tag_allowed['text'] = [ 
		'a'      => [ 
			'href'  => [],
			'title' => [],
			'class' => [],
		],
		'br'     => [],
		'em'     => [],
		'strong' => [],
		'hr'     => [],
		'i'      => [ 
			'class' => [],
		],
		'span'   => [ 
			'class' => [],
		],
	];

	if ( $tag == null ) {
		return $tag_allowed;
	} elseif ( is_array( $tag ) ) {
		$new_tag_allow = [];

		foreach ( $tag as $_tag ) {
			$new_tag_allow[ $_tag ] = $tag_allowed[ $_tag ];
		}

		return $new_tag_allow;
	} else {
		return isset( $tag_allowed[ $tag ] ) ? $tag_allowed[ $tag ] : [];
	}
}

function prime_slider_dashboard_link( $suffix = '#welcome' ) {
	return add_query_arg( [ 'page' => 'prime_slider_options' . $suffix ], admin_url( 'admin.php' ) );
}

function prime_slider_get_category( $taxonomy = 'category' ) {

	$post_options = [];

	$post_categories = get_terms( [ 
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	] );

	if ( is_wp_error( $post_categories ) ) {
		return $post_options;
	}

	if ( false !== $post_categories and is_array( $post_categories ) ) {
		foreach ( $post_categories as $category ) {
			// Ensure $category is an object, not an array
			if ( is_object( $category ) && isset( $category->slug, $category->name ) ) {
				$post_options[ $category->slug ] = $category->name;
			}
		}
	}
	return $post_options;
}

function prime_slider_first_word( $string ) {

	$words = explode( ' ', $string );
	$html  = '<span class="frist-word">' . $words[0] . '</span> ' . implode( " ", array_slice( $words, 1 ) );
	return $html;
}

/**
 * default get_option() default value check
 * @param string $option settings field name
 * @param string $section the section name this field belongs to
 * @param string $default default text if it's not found
 * @return mixed
 */
function prime_slider_option( $option, $section, $default = '' ) {

	$options = get_option( $section );

	if ( isset( $options[ $option ] ) ) {
		return $options[ $option ];
	}

	return $default;
}

// Elementor Saved Template
function prime_slider_et_options() {

	$templates = Prime_Slider_Loader::elementor()->templates_manager->get_source( 'local' )->get_items();
	$types     = [];

	if ( empty( $templates ) ) {
		$template_options = [ '0' => __( 'Template Not Found!', 'bdthemes-prime-slider' ) ];
	} else {
		$template_options = [ '0' => __( 'Select Template', 'bdthemes-prime-slider' ) ];

		foreach ( $templates as $template ) {
			$template_options[ $template['template_id'] ] = $template['title'] . ' (' . $template['type'] . ')';
			$types[ $template['template_id'] ]            = $template['type'];
		}
	}

	return $template_options;
}

function prime_slider_template_edit_link( $template_id ) {
	if ( Prime_Slider_Loader::elementor()->editor->is_edit_mode() ) {

		$final_url = add_query_arg( [ 'elementor' => '' ], get_permalink( $template_id ) );

		$output = sprintf( '<a class="bdt-elementor-template-edit-link" href="%s" title="%s" target="_blank"><i class="eicon-edit"></i></a>', esc_url( $final_url ), esc_html__( 'Edit Template', 'bdthemes-prime-slider' ) );

		return $output;
	}
}

// BDT Transition
function prime_slider_transition_options() {
	$transition_options = [ 
		''                    => esc_html__( 'None', 'bdthemes-prime-slider' ),
		'fade'                => esc_html__( 'Fade', 'bdthemes-prime-slider' ),
		'scale-up'            => esc_html__( 'Scale Up', 'bdthemes-prime-slider' ),
		'scale-down'          => esc_html__( 'Scale Down', 'bdthemes-prime-slider' ),
		'slide-top'           => esc_html__( 'Slide Top', 'bdthemes-prime-slider' ),
		'slide-bottom'        => esc_html__( 'Slide Bottom', 'bdthemes-prime-slider' ),
		'slide-left'          => esc_html__( 'Slide Left', 'bdthemes-prime-slider' ),
		'slide-right'         => esc_html__( 'Slide Right', 'bdthemes-prime-slider' ),
		'slide-top-small'     => esc_html__( 'Slide Top Small', 'bdthemes-prime-slider' ),
		'slide-bottom-small'  => esc_html__( 'Slide Bottom Small', 'bdthemes-prime-slider' ),
		'slide-left-small'    => esc_html__( 'Slide Left Small', 'bdthemes-prime-slider' ),
		'slide-right-small'   => esc_html__( 'Slide Right Small', 'bdthemes-prime-slider' ),
		'slide-top-medium'    => esc_html__( 'Slide Top Medium', 'bdthemes-prime-slider' ),
		'slide-bottom-medium' => esc_html__( 'Slide Bottom Medium', 'bdthemes-prime-slider' ),
		'slide-left-medium'   => esc_html__( 'Slide Left Medium', 'bdthemes-prime-slider' ),
		'slide-right-medium'  => esc_html__( 'Slide Right Medium', 'bdthemes-prime-slider' ),
	];

	return $transition_options;
}

// BDT Blend Type
function prime_slider_blend_options() {
	$blend_options = [ 
		'multiply'    => esc_html__( 'Multiply', 'bdthemes-prime-slider' ),
		'screen'      => esc_html__( 'Screen', 'bdthemes-prime-slider' ),
		'overlay'     => esc_html__( 'Overlay', 'bdthemes-prime-slider' ),
		'darken'      => esc_html__( 'Darken', 'bdthemes-prime-slider' ),
		'lighten'     => esc_html__( 'Lighten', 'bdthemes-prime-slider' ),
		'color-dodge' => esc_html__( 'Color-Dodge', 'bdthemes-prime-slider' ),
		'color-burn'  => esc_html__( 'Color-Burn', 'bdthemes-prime-slider' ),
		'hard-light'  => esc_html__( 'Hard-Light', 'bdthemes-prime-slider' ),
		'soft-light'  => esc_html__( 'Soft-Light', 'bdthemes-prime-slider' ),
		'difference'  => esc_html__( 'Difference', 'bdthemes-prime-slider' ),
		'exclusion'   => esc_html__( 'Exclusion', 'bdthemes-prime-slider' ),
		'hue'         => esc_html__( 'Hue', 'bdthemes-prime-slider' ),
		'saturation'  => esc_html__( 'Saturation', 'bdthemes-prime-slider' ),
		'color'       => esc_html__( 'Color', 'bdthemes-prime-slider' ),
		'luminosity'  => esc_html__( 'Luminosity', 'bdthemes-prime-slider' ),
	];

	return $blend_options;
}

// BDT Position
function prime_slider_position() {
	$position_options = [ 
		''              => esc_html__( 'Default', 'bdthemes-prime-slider' ),
		'top-left'      => esc_html__( 'Top Left', 'bdthemes-prime-slider' ),
		'top-center'    => esc_html__( 'Top Center', 'bdthemes-prime-slider' ),
		'top-right'     => esc_html__( 'Top Right', 'bdthemes-prime-slider' ),
		'center'        => esc_html__( 'Center', 'bdthemes-prime-slider' ),
		'center-left'   => esc_html__( 'Center Left', 'bdthemes-prime-slider' ),
		'center-right'  => esc_html__( 'Center Right', 'bdthemes-prime-slider' ),
		'bottom-left'   => esc_html__( 'Bottom Left', 'bdthemes-prime-slider' ),
		'bottom-center' => esc_html__( 'Bottom Center', 'bdthemes-prime-slider' ),
		'bottom-right'  => esc_html__( 'Bottom Right', 'bdthemes-prime-slider' ),
	];

	return $position_options;
}

// BDT Thumbnavs Position
function prime_slider_thumbnavs_position() {
	$position_options = [ 
		'top-left'      => esc_html__( 'Top Left', 'bdthemes-prime-slider' ),
		'top-center'    => esc_html__( 'Top Center', 'bdthemes-prime-slider' ),
		'top-right'     => esc_html__( 'Top Right', 'bdthemes-prime-slider' ),
		'center-left'   => esc_html__( 'Center Left', 'bdthemes-prime-slider' ),
		'center-right'  => esc_html__( 'Center Right', 'bdthemes-prime-slider' ),
		'bottom-left'   => esc_html__( 'Bottom Left', 'bdthemes-prime-slider' ),
		'bottom-center' => esc_html__( 'Bottom Center', 'bdthemes-prime-slider' ),
		'bottom-right'  => esc_html__( 'Bottom Right', 'bdthemes-prime-slider' ),
	];

	return $position_options;
}

function prime_slider_navigation_position() {
	$position_options = [ 
		'top-left'      => esc_html__( 'Top Left', 'bdthemes-prime-slider' ),
		'top-center'    => esc_html__( 'Top Center', 'bdthemes-prime-slider' ),
		'top-right'     => esc_html__( 'Top Right', 'bdthemes-prime-slider' ),
		'center'        => esc_html__( 'Center', 'bdthemes-prime-slider' ),
		'bottom-left'   => esc_html__( 'Bottom Left', 'bdthemes-prime-slider' ),
		'bottom-center' => esc_html__( 'Bottom Center', 'bdthemes-prime-slider' ),
		'bottom-right'  => esc_html__( 'Bottom Right', 'bdthemes-prime-slider' ),
	];

	return $position_options;
}


function prime_slider_pagination_position() {
	$position_options = [ 
		'top-left'      => esc_html__( 'Top Left', 'bdthemes-prime-slider' ),
		'top-center'    => esc_html__( 'Top Center', 'bdthemes-prime-slider' ),
		'top-right'     => esc_html__( 'Top Right', 'bdthemes-prime-slider' ),
		'bottom-left'   => esc_html__( 'Bottom Left', 'bdthemes-prime-slider' ),
		'bottom-center' => esc_html__( 'Bottom Center', 'bdthemes-prime-slider' ),
		'bottom-right'  => esc_html__( 'Bottom Right', 'bdthemes-prime-slider' ),
	];

	return $position_options;
}

// Title Tags
function prime_slider_title_tags() {
	$title_tags = [ 
		'h1'   => esc_html__( 'H1', 'bdthemes-prime-slider' ),
		'h2'   => esc_html__( 'H2', 'bdthemes-prime-slider' ),
		'h3'   => esc_html__( 'H3', 'bdthemes-prime-slider' ),
		'h4'   => esc_html__( 'H4', 'bdthemes-prime-slider' ),
		'h5'   => esc_html__( 'H5', 'bdthemes-prime-slider' ),
		'h6'   => esc_html__( 'H6', 'bdthemes-prime-slider' ),
		'div'  => esc_html__( 'div', 'bdthemes-prime-slider' ),
		'span' => esc_html__( 'span', 'bdthemes-prime-slider' ),
		'p'    => esc_html__( 'p', 'bdthemes-prime-slider' ),
	];

	return $title_tags;
}

function prime_slider_time_diff( $from, $to = '' ) {
	$diff    = human_time_diff( $from, $to );
	$replace = array(
		' hour'    => 'h',
		' hours'   => 'h',
		' day'     => 'd',
		' days'    => 'd',
		' minute'  => 'm',
		' minutes' => 'm',
		' second'  => 's',
		' seconds' => 's',
	);

	return strtr( $diff, $replace );
}

function prime_slider_post_time_diff( $format = '' ) {
	$displayAgo = esc_html__( 'ago', 'bdthemes-prime-slider' );

	if ( $format == 'short' ) {
		$output = prime_slider_time_diff( strtotime( get_the_date() ), current_time( 'timestamp' ) );
	} else {
		$output = human_time_diff( strtotime( get_the_date() ), current_time( 'timestamp' ) );
	}

	$output = $output . ' ' . $displayAgo;

	return $output;
}

function prime_slider_custom_excerpt( $limit = 25, $strip_shortcode = false, $trail = '' ) {

	$output = get_the_content();

	if ( $limit ) {
		$output = wp_trim_words( $output, $limit, $trail );
	}

	if ( $strip_shortcode ) {
		$output = strip_shortcodes( $output );
	}

	return wpautop( $output );
}

/**
 * License Validation
 */
if (!function_exists('ps_license_validation')) {
	function ps_license_validation() {

		if (!class_exists('PrimeSliderPro\Base\Prime_Slider_Base')) {
			return false;
		}

		$license_key = get_option(\PrimeSliderPro\Base\Prime_Slider_Base::get_lic_key_param('prime_slider_license_key'));

		if (isset($license_key) && !empty($license_key)) {
			return true;
		}

		return false;
	}
}

// Start: Custom CSS/JS Frontend Injection Functions
 if ( ! function_exists( 'ps_inject_header_custom_code' ) ) {
	function ps_inject_header_custom_code() {
		if ( ps_is_page_excluded() ) {
			return;
		}

		$custom_css = get_option( 'ps_custom_css', '' );
		$custom_js = get_option( 'ps_custom_js', '' );

		if ( ! empty( $custom_css ) ) {
			echo "\n<!-- Prime Slider Custom Header CSS -->\n";
			echo '<style type="text/css">' . "\n";
			echo $custom_css . "\n";
			echo '</style>' . "\n";
		}

		if ( ! empty( $custom_js ) ) {
			echo "\n<!-- Prime Slider Custom Header JS -->\n";
			echo '<script type="text/javascript">' . "\n";
			echo $custom_js . "\n";
			echo '</script>' . "\n";
		}
	}
}

if ( ! function_exists( 'ps_inject_footer_custom_code' ) ) {
	function ps_inject_footer_custom_code() {
		if ( ps_is_page_excluded() ) {
			return;
		}

		$custom_css_2 = get_option( 'ps_custom_css_2', '' );
		$custom_js_2 = get_option( 'ps_custom_js_2', '' );

		if ( ! empty( $custom_css_2 ) ) {
			echo "\n<!-- Prime Slider Custom Footer CSS -->\n";
			echo '<style type="text/css">' . "\n";
			echo $custom_css_2 . "\n";
			echo '</style>' . "\n";
		}

		if ( ! empty( $custom_js_2 ) ) {
			echo "\n<!-- Prime Slider Custom Footer JS -->\n";
			echo '<script type="text/javascript">' . "\n";
			echo $custom_js_2 . "\n";
			echo '</script>' . "\n";
		}
	}
}

if ( ! function_exists( 'ps_is_page_excluded' ) ) {
	function ps_is_page_excluded() {
		$excluded_pages = get_option( 'ps_excluded_pages', array() );
		
		if ( empty( $excluded_pages ) || ! is_array( $excluded_pages ) ) {
			return false;
		}

		$current_id = 0;
		
		if ( is_home() && ! is_front_page() ) {
			$current_id = get_option( 'page_for_posts' );
		} elseif ( is_front_page() ) {
			$current_id = get_option( 'page_on_front' );
		} elseif ( is_singular() ) {
			$current_id = get_queried_object_id();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			return false;
		} elseif ( is_author() ) {
			return false;
		} elseif ( is_archive() ) {
			return false;
		} else {
			$current_id = get_queried_object_id();
		}
		
		$current_id = (int) $current_id;		
		$excluded_pages = array_map( 'intval', $excluded_pages );

		return $current_id > 0 && in_array( $current_id, $excluded_pages );
	}
}