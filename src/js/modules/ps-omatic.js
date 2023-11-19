
(function ($, elementor) {

    'use strict';

    var widgetOmatic = function ($scope, $) {

        var $omatic = $scope.find('.bdt-omatic-slider');
        if (!$omatic.length) {
            return;
        }
        var $omaticContainer = $omatic.find('.swiper-carousel'),
            $settings = $omatic.data('settings');
        
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
            var swiper = await new Swiper($omaticContainer, $settings);
            if ($settings.pauseOnHover) {
                $($omaticContainer).hover(function () {
                    (this).swiper.autoplay.stop();
                }, function () {
                    (this).swiper.autoplay.start();
                });
            }

            var $thumbs = $scope.find('.bdt-omatic-thumbs-slide');

            var sliderThumbs = await new Swiper($thumbs, {
                loop: ($settings.loop) ? $settings.loop : false,
                speed: ($settings.speed) ? $settings.speed : 500,
				rewind: ($settings.rewind) ? $settings.rewind : false,
				mousewheel: ($settings.mousewheel) ? $settings.mousewheel : false,
                effect: "fade",
                fadeEffect: {
                    crossFade: true
                  },
                grabCursor: true,
                touchRatio: 0.2,
                slideToClickedSlide: true,
                loopedSlides: 4,

            });

            swiper.controller.control = sliderThumbs;
            sliderThumbs.controller.control = swiper;
            // sliderThumbs.controller.control = swiper;
        }

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-omatic.default', widgetOmatic);
    });

}(jQuery, window.elementorFrontend));