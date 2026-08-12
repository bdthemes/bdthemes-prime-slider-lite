/**
 * Prime Slider — carousel accessibility normalizer (global, engine-aware).
 *
 * Prime Slider ships two slider engines, and this module normalizes both so the
 * carousel pattern is consistent for ANY widget or skin an end user builds:
 *
 *   1. UIkit slideshow (`bdt-slideshow`) — used by Blog (all skins), Dragon,
 *      Flogia, General, Isolate, Mount, Sequester, WooCommerce, WooLamp (Lite)
 *      and Custom, Fluent, WooStand (Pro). UIkit drives its dot navigation with
 *      the WAI-ARIA Tabs pattern (role="tablist"/"tab"/"tabpanel"), which fails
 *      the carousel accessibility check. We rewrite it to role="group" +
 *      aria-roledescription="slide" with plain button controls.
 *
 *   2. Swiper — used by Mercury, Pacific, Fiestar, Rubix, Tango, Vertex, etc.
 *      (Lite) and Avatar, Cloud, Expo, Panorama, Super Flow, … (Pro). These init
 *      via elementorFrontend.utils.swiper with no a11y config, so slides may lack
 *      the carousel roles. We add them only when missing, scoped to Prime Slider
 *      widgets so no other plugin's Swiper is touched.
 *
 * All writes are idempotent and additive-only: an accessible name UIkit or Swiper
 * already provided is never overwritten. A scoped MutationObserver keeps the
 * corrections in place through each engine's reactive updates. Frontend only; a
 * no-op on pages without a Prime Slider slider.
 */
(function () {

    'use strict';

    var UIKIT_SEL   = '[bdt-slideshow], [data-bdt-slideshow]';
    // Swiper containers, restricted to Prime Slider widgets (data-widget_type^="prime-slider-").
    var SWIPER_SEL  = '[data-widget_type^="prime-slider-"] .swiper, [data-widget_type^="prime-slider-"].swiper';
    var SLIDE_SEL   = '.bdt-slideshow-item';
    var NAV_SEL     = '.bdt-slideshow-nav';
    var WATCH_ATTRS = ['role', 'aria-roledescription', 'aria-labelledby', 'aria-controls', 'aria-selected'];

    function setAttr(el, name, value) {
        if (el && el.getAttribute(name) !== value) {
            el.setAttribute(name, value);
        }
    }

    function removeAttr(el, name) {
        if (el && el.hasAttribute(name)) {
            el.removeAttribute(name);
        }
    }

    // Only add a label when the control has no text and no existing accessible name.
    function labelIfEmpty(el, label) {
        if (!el) {
            return;
        }
        var hasText = (el.textContent || '').trim().length > 0;
        if (!hasText && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby')) {
            setAttr(el, 'aria-label', label);
        }
    }

    // --- UIkit slideshow: Tabs pattern -> carousel pattern -------------------
    function normalizeUikit(root) {
        // Slides: role="tabpanel" -> role="group" + aria-roledescription="slide".
        var slides = root.querySelectorAll(SLIDE_SEL);
        Array.prototype.forEach.call(slides, function (slide, index) {
            setAttr(slide, 'role', 'group');
            setAttr(slide, 'aria-roledescription', 'slide');
            removeAttr(slide, 'aria-labelledby');
            labelIfEmpty(slide, (index + 1) + ' of ' + slides.length);
        });

        // Dot navigation: role="tablist"/"tab" -> a labelled group of buttons.
        // UIkit stamps role="presentation" on the wrapping <li> elements; that is
        // left intact on purpose. Stripping it would leave bare <li> children under
        // a non-list <ul role="group">, which axe flags as orphaned list items.
        Array.prototype.forEach.call(root.querySelectorAll(NAV_SEL), function (nav) {
            setAttr(nav, 'role', 'group');
            if (!nav.getAttribute('aria-label')) {
                setAttr(nav, 'aria-label', 'Choose slide to display');
            }
            Array.prototype.forEach.call(nav.querySelectorAll('a'), function (dot, index) {
                if (dot.getAttribute('role') === 'tab') {
                    setAttr(dot, 'role', 'button');
                }
                removeAttr(dot, 'aria-selected');
                removeAttr(dot, 'aria-controls');
                labelIfEmpty(dot, 'Go to slide ' + (index + 1));
            });
        });

        // Prev/next arrows are empty anchors — expose an accessible name.
        Array.prototype.forEach.call(root.querySelectorAll('[bdt-slidenav-previous], [data-bdt-slidenav-previous]'), function (el) {
            labelIfEmpty(el, 'Previous slide');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[bdt-slidenav-next], [data-bdt-slidenav-next]'), function (el) {
            labelIfEmpty(el, 'Next slide');
        });
    }

    // --- Swiper: add the carousel pattern only where it is missing ------------
    function normalizeSwiper(root) {
        // Real slides only (skip Swiper's visual loop clones).
        var slides = root.querySelectorAll('.swiper-slide:not(.swiper-slide-duplicate)');
        Array.prototype.forEach.call(slides, function (slide, index) {
            // If Swiper's own a11y already described this slide, leave it untouched.
            if (!slide.getAttribute('aria-roledescription')) {
                setAttr(slide, 'role', 'group');
                setAttr(slide, 'aria-roledescription', 'slide');
                labelIfEmpty(slide, (index + 1) + ' of ' + slides.length);
            }
        });

        Array.prototype.forEach.call(root.querySelectorAll('.swiper-button-prev'), function (el) {
            labelIfEmpty(el, 'Previous slide');
        });
        Array.prototype.forEach.call(root.querySelectorAll('.swiper-button-next'), function (el) {
            labelIfEmpty(el, 'Next slide');
        });
    }

    function watch(root, normalizeFn) {
        var observer = new MutationObserver(function () {
            // Suspend while we write so our own changes don't re-trigger the observer.
            observer.disconnect();
            normalizeFn(root);
            observer.observe(root, { attributes: true, subtree: true, attributeFilter: WATCH_ATTRS });
        });

        normalizeFn(root);
        observer.observe(root, { attributes: true, subtree: true, attributeFilter: WATCH_ATTRS });
    }

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll(UIKIT_SEL), function (root) {
            watch(root, normalizeUikit);
        });
        Array.prototype.forEach.call(document.querySelectorAll(SWIPER_SEL), function (root) {
            watch(root, normalizeSwiper);
        });
    }

    if (typeof MutationObserver === 'undefined') {
        return;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
