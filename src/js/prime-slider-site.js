/**
 * Safe values from widget markup
 *
 * Slider handlers read their options from data-settings. That attribute is
 * only trustworthy when the widget printed it: markup that imitates a widget
 * (a comment or excerpt that keeps class and data-* through KSES) reaches the
 * same handler. Swiper resolves navigation/pagination selectors through a
 * helper that builds elements from any string holding "<", and writes some
 * string options into markup, so values that could carry markup are dropped.
 * Prime Slider and Prime Slider Pro both ship this; the first one loaded wins.
 */
(function (window) {

    'use strict';

    var UNSAFE_STRING = /[<>"'`]/;

    if (typeof window.primeSliderSafeSelector !== 'function') {
        /**
         * A selector read from widget markup, or one that matches nothing when the
         * string could be parsed as HTML. Elements and jQuery collections a handler
         * builds itself pass through: jQuery never parses those as HTML.
         */
        window.primeSliderSafeSelector = function (selector) {
            if (typeof selector === 'string') {
                return UNSAFE_STRING.test(selector) ? '#bdt-ps-invalid-selector' : selector;
            }
            return selector && typeof selector === 'object' ? selector : '#bdt-ps-invalid-selector';
        };
    }

    if (typeof window.primeSliderSafeSwiperSettings !== 'function') {
        /**
         * Swiper options read from widget markup: plain JSON values only, no string
         * that could carry markup, and none of the options Swiper turns into markup.
         */
        window.primeSliderSafeSwiperSettings = function (settings) {
            var DROPPED_KEYS = ['virtual', 'injectStyles', 'injectStylesUrls', 'on', 'modules', '__proto__', 'constructor', 'prototype'];

            var clean = function (value, depth) {
                if (depth > 6) {
                    return undefined;
                }
                if (value === null || typeof value === 'boolean') {
                    return value;
                }
                if (typeof value === 'number') {
                    return isFinite(value) ? value : undefined;
                }
                if (typeof value === 'string') {
                    return UNSAFE_STRING.test(value) ? undefined : value;
                }
                if (Array.isArray(value)) {
                    return value.map(function (item) {
                        return clean(item, depth + 1);
                    }).filter(function (item) {
                        return item !== undefined;
                    });
                }
                if (typeof value === 'object') {
                    var out = {};
                    Object.keys(value).forEach(function (key) {
                        if (DROPPED_KEYS.indexOf(key) !== -1) {
                            return;
                        }
                        var item = clean(value[key], depth + 1);
                        if (item !== undefined) {
                            out[key] = item;
                        }
                    });
                    return out;
                }
                return undefined;
            };

            var out = clean(settings, 0);
            if (!out || typeof out !== 'object' || Array.isArray(out)) {
                out = {};
            }

            // Swiper writes bulletElement into markup as a tag name.
            if (out.pagination && typeof out.pagination === 'object' && 'bulletElement' in out.pagination &&
                !/^[a-z][a-z0-9-]*$/i.test(String(out.pagination.bulletElement))) {
                delete out.pagination.bulletElement;
            }

            return out;
        };
    }

}(window));

(function ($, elementor) {

    'use strict';

    function bdtPsObserveTarget(target, callback) {
        var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
        // Set the rootMargin to trigger when the target is 10% past the viewport
        options.rootMargin = options.rootMargin || '10% 0px 0px 0px';
        var observer = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    callback(entry);

                    if (!options.loop)
                        observer.unobserve(entry.target); // Unobserve after the first intersection
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

        //console.log($scrollButton);

        if (!$scrollButton.length) {
            return;
        }

        $($scrollButton).on('click', function (event) {
            event.preventDefault();

            // The target comes from a free-text setting: ignore a selector that
            // is invalid or matches nothing instead of throwing on every click.
            var $target;
            try {
                $target = $selector ? $(document).find($selector) : $();
            } catch (e) {
                $target = $();
            }

            if (!$target.length) {
                return;
            }

            bdtUIkit.scroll($scrollButton, $settings).scrollTo($target);
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
            var runReveal = function () {
                revealFX.reveal();
                this.destroy();
            };

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
    }

    // Slides that are not active are display:none, so the browser does not
    // fetch their CSS background images until they are shown and the slide
    // fades in blank. Preload them once the slider comes near the viewport.
    var primeSliderPreloadSlideImages = function ($scope) {
        var $items = $scope.find('.bdt-slideshow-items');

        if (!$items.length) {
            return;
        }

        bdtPsObserveTarget($items[0], function () {
            $items.find('[style*="background-image"]').each(function () {
                var match = /url\((['"]?)(.*?)\1\)/.exec(this.style.backgroundImage);

                if (match && match[2]) {
                    new Image().src = match[2];
                }
            });
        }, {
            rootMargin: '200px 0px'
        });
    };

    jQuery(window).on('elementor/frontend/init', function () {
        // preload background images of the slides that are not shown yet
        elementorFrontend.hooks.addAction('frontend/element_ready/widget', primeSliderPreloadSlideImages);

        // initialize reveal effects
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-general.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-general.slide', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-general.crelly', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-general.meteor', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.coral', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.folio', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.zinest', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-isolate.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-isolate.locate', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-isolate.slice', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-dragon.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-flogia.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-mount.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-elysium.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-fiestar.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-sequester.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-mercury.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-pacific.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-paranoia.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-rubix.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-storker.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-tango.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-vertex.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-woocommerce.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-woolamp.default', RevealEffects);

        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-astoria.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-avatar.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-flexure.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-fluent.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-fortune.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-knily.default', RevealEffects);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-monster.default', RevealEffects);


        //scroll button
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-general.default', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-general.meteor', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.default', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.coral', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-isolate.default', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-isolate.locate', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-woocommerce.default', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-fluent.default', primeSliderScrollButton);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-astoria.default', primeSliderScrollButton);
    });

}(jQuery, window.elementorFrontend));
