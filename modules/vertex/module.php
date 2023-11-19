<?php
namespace PrimeSliderPro\Modules\Vertex;

use PrimeSliderPro\Base\Prime_Slider_Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Module extends Prime_Slider_Module_Base {

	public function get_name() {
		return 'vertex';
	}

	public function get_widgets() {
		$widgets = [
			'Vertex',
		];

		return $widgets;
	}
}
