(function ($, elementor) {

    'use strict';

    var widgetFiestar = function ($scope, $) {

        var $fiestar = $scope.find('.bdt-fiestar-slider');
        if (!$fiestar.length) {
            return;
        }
        var $fiestarContainer = $fiestar.find('.bdt-center-slider'),
            $settings = $fiestar.data('settings');

        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();
        async function initSwiper() {
            var swiper = await new Swiper($fiestarContainer, $settings);

            if ($settings.pauseOnHover) {
                $($fiestarContainer).hover(function () {
                    (this).swiper.autoplay.stop();
                }, function () {
                    (this).swiper.autoplay.start();
                });
            }
        };
    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-fiestar.default', widgetFiestar);
    });

}(jQuery, window.elementorFrontend));