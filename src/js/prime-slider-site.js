(function ($, elementor) {

    'use strict';

    function bdtPsObserveTarget(target, callback) {
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
        options.rootMargin = options.rootMargin || '10% 0px 0px 0px';
        var observer = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    callback(entry);

                    if (!options.loop)
                        observer.unobserve(entry.target);
                }
            });
        }, options);
        observer.observe(target);
    }

    var primeSliderScrollButton = function ($scope, $) {

        var $primeSlider = $scope.find('.bdt-prime-slider'),
            $scrollButton = $primeSlider.find('.bdt-scroll-down'),
            $selector = $scrollButton.data('selector'),
            $settings = $scrollButton.data('settings');

        if (!$scrollButton.length) {
            return;
        }

        $($scrollButton).on('click', function (event) {
            event.preventDefault();
            bdtUIkit.scroll($scrollButton, $settings).scrollTo($($selector));
        });

    };

    var RevealEffects = function ($scope, $) {
        var widgetID = $scope.data("id"),
            $revealEnable = $scope.find(`[data-reveal-enable]`).data('reveal-enable');
        if (($revealEnable === undefined) || ($revealEnable !== 'yes')) {
            return;
        }

        const revealID = $('.reveal-active-' + widgetID).find(`[data-reveal="reveal-active"]`);
        $(revealID).css({ 'opacity': '1' });
        const revealOptions = $scope.find(`[data-reveal-settings]`).data(`reveal-settings`);
        let counter = 0;
        $(revealID).each(function (index, revealWrapper) {
            counter += 80;
            const revealFX = new RevealFx(revealWrapper, {
                revealSettings: {
                    bgColors: [revealOptions.bgColors],
                    direction: String(revealOptions.direction),
                    duration: Number(revealOptions.duration + counter),
                    easing: String(revealOptions.easing),
                    onHalfway: function (contentEl, ngsrevealerEl) {
                        contentEl.style.opacity = 1;
                    },
                },
            });

            bdtPsObserveTarget(revealWrapper, function () {
                revealFX.reveal();
            }, {
                root: null,
                rootMargin: '0px',
                threshold: 0.8
            });

        });

        setTimeout(() => {
            const revealWrap = $('.reveal-active-' + widgetID);
            var mutedClass = $(revealWrap).find('.reveal-muted');
            $(mutedClass).each(function (index, muted) {
                $(muted).addClass('reveal-loaded');
                $(muted).removeClass('reveal-muted');
            });
        }, (revealOptions.duration + counter) * 1.3);
    };

    function registerSiteHooks(hooks, callback) {
        (hooks || []).forEach(function (hook) {
            elementorFrontend.hooks.addAction('frontend/element_ready/' + hook, callback);
        });
    }

    jQuery(window).on('elementor/frontend/init', function () {
        var config = window.PrimeSliderSiteConfig || { reveal: [], scroll: [] };

        registerSiteHooks(config.reveal, RevealEffects);
        registerSiteHooks(config.scroll, primeSliderScrollButton);
    });

}(jQuery, window.elementorFrontend));
