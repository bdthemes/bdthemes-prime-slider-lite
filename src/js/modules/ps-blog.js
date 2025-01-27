(function ($, elementor) {

    'use strict';

    var widgetBlog = function ($scope, $) {

        var $sliderBlog = $scope.find('.bdt-slideshow'),
            $settings = $sliderBlog.data('settings');
            
        if (!$sliderBlog.length || $settings.animation_status !== 'yes') return;


        // start animation
        var $slideItem = $($settings.id + ' ul > li');
        $($sliderBlog).find('.bdt-slideshow-item').each(function (i, e) {

            var self = $(this),
                $quote = self.find($settings.animation_of),
                mySplitText = new SplitText($quote, {
                    type: 'chars, words, lines'
                }),
                splitTextTimeline = gsap.timeline();

            gsap.set($quote, {
                perspective: 400
            });

            function kill() {
                splitTextTimeline.clear().time(0);
                mySplitText.revert();
            }

            function gsapAnimation() {
                kill();
                mySplitText.split();

                var stringType = '';

                if ('lines' == $settings.animation_on) {
                    stringType = mySplitText.lines;
                } else if ('chars' == $settings.animation_on) {
                    stringType = mySplitText.chars;
                } else {
                    stringType = mySplitText.words;
                }

                splitTextTimeline.staggerFrom(stringType, 0.5, {
                    opacity: 0,
                    scale: $settings.anim_scale, //0
                    y: $settings.anim_rotation_y, //80
                    rotationX: $settings.anim_rotation_x, //180
                    transformOrigin: $settings.anim_transform_origin, //0% 50% -50  
                }, 0.1).then(function () {
                    // $($imageExpand).find('.bdt-image-expand-button').removeClass('bdt-invisible');
                });

                splitTextTimeline.play();
            }

            $slideItem.on('itemshow', function () {
                gsapAnimation();
            });
            gsapAnimation();
        });
        // end animation 
    };

    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.default', widgetBlog);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.coral', widgetBlog);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.folio', widgetBlog);
        elementorFrontend.hooks.addAction('frontend/element_ready/prime-slider-blog.zinest', widgetBlog);
    });

}(jQuery, window.elementorFrontend));