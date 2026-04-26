(function ($, elementor) {

    'use strict';

    var widgetPagepiling = function ($scope, $) {

        var $pagepiling = $scope.find('.bdt-pagepiling-slider');

        if (!$pagepiling.length) {
            return;
        }

        var $social = $scope.find('.bdt-prime-slider-social-icon');
        var socialFxEnabled = false;
        var triggerSocialTransition = function () {
            if (!$social.length || !socialFxEnabled) {
                return;
            }
            $social.removeClass('bdt-ps-social-active');
            // Force reflow to restart transition reliably.
            void $social[0].offsetWidth;
            $social.addClass('bdt-ps-social-active');
        };

        if ($.fn.pagepiling && typeof $.fn.pagepiling.destroy !== 'undefined') {
            try {
                $.fn.pagepiling.destroy('all');
            } catch(e) {
                console.log('Pagepiling destroy error (safe to ignore):', e);
            }
        }

        var $settings = $pagepiling.data('settings');

        if (!$settings) {
            return;
        }

        let interval;
        let autoPlayDuration = parseInt($settings.autoplay_duration) || 1000;
        let scrollingSpeed = parseInt($settings.scrollingSpeed) || 700;
        let isAutoplay = $settings.autoplay === true;

        $($pagepiling).pagepiling({
            menu: null,
            direction: 'vertical',
            verticalCentered: true,
            scrollingSpeed: scrollingSpeed,
            easing: 'swing',
            navigation: {
                'position': 'left',
            },
            loopBottom: $settings.loopBottom,
            loopTop: $settings.loopTop,
            css3: true,
            normalScrollElements: null,
            normalScrollElementTouchThreshold: 5,
            touchSensitivity: 5,
            keyboardScrolling: true,
            sectionSelector: '.section',

            afterRender: function () {
                if (!isAutoplay) {
                    return;
                }

                // Start autoplay after initial render
                interval = setInterval(function () {
                    $.fn.pagepiling.moveSectionDown();
                }, autoPlayDuration);
            },

            afterLoad: function () {
                triggerSocialTransition();
            },
            
            onLeave: function(index, nextIndex, direction) {
                if ($social.length) {
                    if (!socialFxEnabled) {
                        socialFxEnabled = true;
                        $pagepiling.addClass('bdt-ps-social-fx');
                    }
                    $social.removeClass('bdt-ps-social-active');
                }

                if (!isAutoplay) {
                    return;
                }

                // Clear and restart interval on any navigation to keep timing consistent
                clearInterval(interval);
                interval = setInterval(function () {
                    $.fn.pagepiling.moveSectionDown();
                }, autoPlayDuration);
            }
        });
    };

    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-pagepiling.default', widgetPagepiling);
    });

}(jQuery, window.elementorFrontend));