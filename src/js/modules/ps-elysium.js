(function ($, elementor) {

    'use strict';

    var widgetElysium = function ($scope, $) {

        var $elysium = $scope.find('.bdt-prime-slider-elysium');

        if (!$elysium.length) {
            return;
        }

        var $elysiumContainer = $elysium.find('.bdt-slider-continer'),
            $settings = $elysium.data('settings');
        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();
        async function initSwiper() {
            var swiper = await new Swiper($elysiumContainer, $settings);

            if ($settings.pauseOnHover) {
                $($elysiumContainer).hover(function () {
                    (this).swiper.autoplay.stop();
                }, function () {
                    (this).swiper.autoplay.start();
                });
            }
        }
    };

    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-elysium.default', widgetElysium);
    });

}(jQuery, window.elementorFrontend));