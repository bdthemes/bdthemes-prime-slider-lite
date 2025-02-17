(function ($, elementor) {

    'use strict';

    var widgetFlogia = function ($scope, $) {

        var $flogiaSlider = $scope.find('.bdt-prime-slider-flogia'),
            $thumbNav = $($flogiaSlider).find('.bdt-thumb-wrapper > .bdt-thumbnav-scroller'),
            $settings = $($flogiaSlider).find('.bdt-slideshow').data('settings');

        if ( !$flogiaSlider.length ) return;

        $($thumbNav).mThumbnailScroller({
            axis: 'x',
            type: 'hover-precise'
        });
    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-flogia.default', widgetFlogia);
    });

}(jQuery, window.elementorFrontend));