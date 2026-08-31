(function ($, elementor) {

    'use strict';

    var widgetMercury = function ($scope, $) {

        var $mercury = $scope.find('.bdt-mercury-slider');
        if (!$mercury.length) {
            return;
        }
        var $mercuryContainer = $mercury.find('.bdt-mercury-image-slider'),
            $settings = $mercury.data('settings');

        //swiper effects start
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
            var swiper = await new Swiper($mercuryContainer, $settings);
            if ($settings.pauseOnHover) {
                $($mercuryContainer).hover(function () {
                    (this).swiper.autoplay.stop();
                }, function () {
                    (this).swiper.autoplay.start();
                });
            }

            var $mainWrapper = $scope.find('.bdt-mercury-slider'),
                $thumbs = $mainWrapper.find('.bdt-mercury-content-slider');

            var sliderThumbs = await new Swiper($thumbs, {
                loop: ($settings.loop) ? $settings.loop : false,
                speed: ($settings.speed) ? $settings.speed : 500,
                rewind: ($settings.rewind) ? $settings.rewind : false,
                mousewheel: ($settings.mousewheel) ? $settings.mousewheel : false,
                parallax: true,
                loopedSlides: 4,
            }); 

            swiper.controller.control = sliderThumbs;
            sliderThumbs.controller.control = swiper;
        }

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-mercury.default', widgetMercury);
    });

}(jQuery, window.elementorFrontend));