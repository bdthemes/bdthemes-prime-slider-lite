(function ($, elementor) {

    'use strict';

    var widgetMultiscroll = function ($scope, $) {

        var $multiscroll = $scope.find('.bdt-mltiscroll-slider'),
            editMode = Boolean(elementorFrontend.isEditMode());

        if (!$multiscroll.length) {
            return;
        }

        if (typeof $.fn.multiscroll !== 'function') {
            console.warn('Prime Slider: multiscroll.js is not loaded, skipping Multiscroll widget.');
            return;
        }

        // data-settings is read from the page, so it may not be the widget's own:
        // multiscroll.js writes navigationPosition into its tooltip markup, so only
        // the two positions the widget offers are passed on, and only plain values.
        var $raw = $multiscroll.data('settings'),
            $settings = {},
            speed,
            autoplaySpeed;

        if ($raw && typeof $raw === 'object') {
            speed = Number($raw.scrollingSpeed);
            autoplaySpeed = Number($raw.autoplay_speed);

            $settings = {
                scrollingSpeed: isFinite(speed) && speed > 0 ? speed : undefined,
                navigation: $raw.navigation === true,
                navigationPosition: $raw.navigationPosition === 'left' ? 'left' : 'right',
                loopBottom: $raw.loopBottom === true,
                loopTop: $raw.loopTop === true,
                autoplay: $raw.autoplay === true,
                autoplay_speed: isFinite(autoplaySpeed) && autoplaySpeed > 0 ? autoplaySpeed : undefined,
                css3: $raw.css3 === true
            };
        }

        if ($settings.autoplay === true && !editMode) {
            $settings.loopBottom = true;
        }

        $($multiscroll).multiscroll({
            verticalCentered: true,
            scrollingSpeed: $settings.scrollingSpeed,
            easing: 'easeInQuart',
            menu: false,
            navigation: $settings.navigation,
            navigationPosition: $settings.navigationPosition,
            loopBottom: $settings.loopBottom,
            loopTop: $settings.loopTop,
            css3: $settings.css3,
            paddingTop: 0,
            paddingBottom: 0,
            normalScrollElements: null,
            scrollOverflowOptions: null,
            keyboardScrolling: true,
            touchSensitivity: 5
        });

        var autoPlaySpeed = ($settings.autoplay === true) ? ($settings.autoplay_speed || 5000) : 5000;

        function scrollMe() {
            $.fn.multiscroll.moveSectionDown();
        }
        if ($settings.autoplay === true && !editMode) {
            setInterval(scrollMe, autoPlaySpeed);
        }

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-multiscroll.default', widgetMultiscroll);
    });

}(jQuery, window.elementorFrontend));