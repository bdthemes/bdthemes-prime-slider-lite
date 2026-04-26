(function ($, elementor) {

    'use strict';

    var widgetSniper = function ($scope, $) {

        var $sniper = $scope.find('.bdt-sniper-slider');
        if (!$sniper.length) {
            return;
        }
        var $sniperContainer = $sniper.find('.bdt-main-slider'),
            $settings = $sniper.data('settings');

        // Scope controls to this widget instance (important for multiple widgets / Elementor editor).
        var $paginationEl = $sniper.find('.bdt-pagination');
        var $scrollbarEl = $sniper.find('.swiper-scrollbar');

        if ($settings && $settings.pagination && $paginationEl.length) {
            $settings.pagination.el = $paginationEl[0];
            if ($settings.pagination.type === 'fraction') {
                $settings.pagination.formatFractionCurrent = function (number) {
                    return (number < 10) ? ('0' + number) : String(number);
                };
                $settings.pagination.formatFractionTotal = function (number) {
                    return (number < 10) ? ('0' + number) : String(number);
                };
            }
        }

        if ($settings && $settings.scrollbar && $scrollbarEl.length) {
            $settings.scrollbar.el = $scrollbarEl[0];
        }
        
        //swiper effects start
		if ($settings.effect === 'shutters') {
			$settings.modules = [EffectShutters];
		}
		if ($settings.effect === 'slicer') {
			$settings.modules = [EffectSlicer];
		}
		if ($settings.effect === 'tinder') {
			$settings.modules = [EffectTinder];
		}
		if ($settings.effect === 'gl') {
			$settings.modules = [SwiperGL];
		}
		if ($settings.effect === 'creative') {
			if ($settings.creativeEffect === 'creative-1') {
				$settings.creativeEffect = {
					next: { shadow: true, translate: ["100%", 0, 0] },
					prev: { shadow: true, translate: ["0%", "0%", -400] },
					limitProgress: 5,
				}
			} else if ($settings.creativeEffect === 'creative-2') {
				$settings.creativeEffect = {
					next: { shadow: true, translate: ["120%", 0, -500] },
					prev: { shadow: true, translate: ["-120%", 0, -500] },
					limitProgress: 5,
				}
			} else if ($settings.creativeEffect === 'creative-3') {
				$settings.creativeEffect = {
					next: { shadow: true, translate: ["100%", 0, 0] },
					prev: { shadow: true, translate: ["-20%", 0, -1] },
					limitProgress: 5,
				}
			} else if ($settings.creativeEffect === 'creative-4') {
				$settings.creativeEffect = {
					next: {
						rotate: [0, 0, 90],
						shadow: true,
						translate: ["120%", "0%", -800],
					},
					prev: {
						rotate: [0, 0, -90],
						shadow: true,
						translate: ["-120%", "0%", -800],
					},
					limitProgress: 5,
				}
			} else if ($settings.creativeEffect === 'creative-5') {
				$settings.creativeEffect = {
					next: {
						rotate: [0, 100, 0],
						shadow: true,
						translate: ["70%", "0%", -400],
					},
					prev: {
						rotate: [0, -100, 0],
						shadow: true,
						translate: ["-70%", "0%", -400],
					},
					limitProgress: 5,
				}
			}
		}
		//swiper effects end

        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();
        async function initSwiper() {
            // Elementor can re-render widgets; prevent duplicate Swipers.
            if ($sniperContainer && $sniperContainer[0] && $sniperContainer[0].swiper) {
                try { $sniperContainer[0].swiper.destroy(true, true); } catch (e) {}
            }
            var swiper = await new Swiper($sniperContainer, $settings);
            if ($settings.pauseOnHover) {
                $($sniperContainer).hover(function () {
                    (this).swiper.autoplay.stop();
                }, function () {
                    (this).swiper.autoplay.start();
                });
            }

            var $thumbs = $scope.find('.bdt-thumbs-slider');
            if ($thumbs && $thumbs[0] && $thumbs[0].swiper) {
                try { $thumbs[0].swiper.destroy(true, true); } catch (e) {}
            }

            var sliderThumbs = await new Swiper($thumbs, {
                loop: ($settings.loop) ? $settings.loop : false,
                rewind: ($settings.rewind) ? $settings.rewind : false,
                speed: ($settings.speed) ? $settings.speed : 500,
                freeMode: true,
                parallax: true,
                spaceBetween: 10,
                slideToClickedSlide: true,
                loopedSlides: 4,
                centeredSlides: true,
                slidesPerView: 2,
                initialSlide: 0,
                keyboardControl: true,
                mousewheel: ($settings.mousewheel) ? $settings.mousewheel : false,
                lazyLoading: true,
                preventClicks: false,
                preventClicksPropagation: false,
                lazyLoadingInPrevNext: true,

                breakpoints: {
                    768: {
                        slidesPerView: 2.5,
                    },
                    1024: {
                        slidesPerView: 3,
                    },
                    1440: {
                        slidesPerView: 3.5,
                    },
                }
            });

            swiper.controller.control = sliderThumbs;
            sliderThumbs.controller.control = swiper;

            function getTotalSlides(mainSwiper) {
                if (!mainSwiper || !mainSwiper.slides) return 0;
                // When loop is enabled, Swiper creates duplicates. Count only originals.
                var count = 0;
                for (var i = 0; i < mainSwiper.slides.length; i++) {
                    var slide = mainSwiper.slides[i];
                    if (slide && slide.classList && !slide.classList.contains('swiper-slide-duplicate')) {
                        count++;
                    }
                }
                return count || mainSwiper.slides.length || 0;
            }

            function updateActiveBar(mainSwiper) {
                if (!$scrollbarEl || !$scrollbarEl.length) return;
                var total = getTotalSlides(mainSwiper);
                if (!total) return;

                var index = (typeof mainSwiper.realIndex === 'number') ? mainSwiper.realIndex : (mainSwiper.activeIndex || 0);
                if (index < 0) index = 0;
                if (index > total - 1) index = total - 1;

                // Progressive fill from start: width grows and hits 100% on last item.
                var widthPct = ((index + 1) / total) * 100;
                $scrollbarEl[0].style.setProperty('--ps-sniper-progress', widthPct + '%');
            }

            updateActiveBar(swiper);
            // Keep progress in sync across interactions/responsive changes.
            swiper.on('realIndexChange', function () { updateActiveBar(swiper); });
            swiper.on('resize', function () { updateActiveBar(swiper); });

            $sniper.addClass('bdt-sniper-ready');
        }

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-sniper.default', widgetSniper);
    });

}(jQuery, window.elementorFrontend));