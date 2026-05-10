(function ($, elementor) {

    'use strict';

    var widgetElysium = function ($scope, $) {

        var $elysium = $scope.find('.bdt-prime-slider-elysium');

        if (!$elysium.length) {
            return;
        }

        var $elysiumContainer = $elysium.find('.bdt-slider-continer'),
            $settings = $elysium.data('settings');
        var $reveal = $elysium.data('elysiumReveal') || {};
        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();

        function splitToChars(el) {
            if (!el || el.dataset.psSplit === 'yes') return;
            var text = (el.textContent || '').trim();
            if (!text) return;

            el.dataset.psSplit = 'yes';
            el.setAttribute('aria-label', text);

            var frag = document.createDocumentFragment();
            var i = 0;

            for (var c = 0; c < text.length; c++) {
                var ch = text[c];
                if (ch === ' ') {
                    var space = document.createElement('span');
                    space.className = 'ps-space';
                    space.setAttribute('aria-hidden', 'true');
                    space.innerHTML = '&nbsp;';
                    frag.appendChild(space);
                    continue;
                }
                var span = document.createElement('span');
                span.className = 'ps-char';
                span.style.setProperty('--ps-i', String(i));
                span.setAttribute('aria-hidden', 'true');
                span.textContent = ch;
                frag.appendChild(span);
                i++;
            }

            el.textContent = '';
            el.appendChild(frag);
        }

        function prepareSlide(slideEl) {
            if (!slideEl) return;
            slideEl.classList.add('ps-reveal-ready');

            var title = slideEl.querySelector('.bdt-title');
            var desc = slideEl.querySelector('.bdt-text');

            splitToChars(title);
            if (desc && desc.dataset.psSplit !== 'yes') {
                desc.dataset.psSplit = 'yes';
            }
        }

        function setActiveReveal(rootEl) {
            if (!rootEl) return;

            var slides = rootEl.querySelectorAll('.bdt-item');
            slides.forEach(function (slide) {
                prepareSlide(slide);
                slide.classList.remove('ps-reveal-in');
            });

            var active = rootEl.querySelector('.bdt-item.swiper-slide-active');
            if (active) {
                prepareSlide(active);
                // force reflow so transitions restart cleanly
                void active.offsetWidth;
                active.classList.add('ps-reveal-in');
            }
        }

        async function initSwiper() {
            var swiper = await new Swiper($elysiumContainer, $settings);

            if (!$reveal || $reveal.enabled === false) {
                return;
            }

            var root = (swiper && swiper.el) ? swiper.el : $elysiumContainer.get(0);
            if (root) {
                if (typeof $reveal.staggerMs === 'number') root.style.setProperty('--ps-stagger', ($reveal.staggerMs / 1000) + 's');
                if (typeof $reveal.textDelayMs === 'number') root.style.setProperty('--ps-text-delay', ($reveal.textDelayMs / 1000) + 's');
                if (typeof $reveal.translateY === 'number') root.style.setProperty('--ps-reveal-y', $reveal.translateY + 'px');
                if (typeof $reveal.blur === 'number') root.style.setProperty('--ps-reveal-blur', $reveal.blur + 'px');
            }

            setActiveReveal(root);
            swiper.on('slideChangeTransitionStart', function () {
                setActiveReveal(root);
            });

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