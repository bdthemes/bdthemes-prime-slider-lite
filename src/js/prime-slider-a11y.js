/**
 * Prime Slider — carousel accessibility normalizer.
 *
 * The bundled UIkit slideshow (`bdt-slideshow`) drives its dot navigation with
 * the WAI-ARIA Tabs pattern: it stamps role="tablist"/"tab" on the nav and
 * role="tabpanel" on every slide at runtime. Automated accessibility audits
 * (e.g. Lighthouse / PageSpeed "Agentic Browsing") expect the carousel pattern
 * instead — role="group" + aria-roledescription="slide" on each slide, with the
 * controls exposed as plain buttons.
 *
 * This module rewrites those runtime-generated roles to the carousel pattern and
 * gives the otherwise-nameless nav controls (prev/next arrows and dots) an
 * accessible name. It never overrides a name UIkit already provided. Because
 * UIkit re-applies its attributes reactively, a scoped MutationObserver keeps the
 * corrections in place. Frontend only; a no-op on pages without a slideshow.
 */
(function () {

    'use strict';

    var ROOT_SEL    = '[bdt-slideshow], [data-bdt-slideshow]';
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

    function normalize(root) {
        // Slides: role="tabpanel" -> role="group" + aria-roledescription="slide".
        var slides = root.querySelectorAll(SLIDE_SEL);
        Array.prototype.forEach.call(slides, function (slide, index) {
            setAttr(slide, 'role', 'group');
            setAttr(slide, 'aria-roledescription', 'slide');
            removeAttr(slide, 'aria-labelledby');
            labelIfEmpty(slide, (index + 1) + ' of ' + slides.length);
        });

        // Dot navigation: role="tablist"/"tab" -> a labelled group of buttons.
        Array.prototype.forEach.call(root.querySelectorAll(NAV_SEL), function (nav) {
            setAttr(nav, 'role', 'group');
            if (!nav.getAttribute('aria-label')) {
                setAttr(nav, 'aria-label', 'Choose slide to display');
            }
            Array.prototype.forEach.call(nav.querySelectorAll('[role="presentation"]'), function (li) {
                removeAttr(li, 'role');
            });
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

    function watch(root) {
        var observer = new MutationObserver(function () {
            // Suspend while we write so our own changes don't re-trigger the observer.
            observer.disconnect();
            normalize(root);
            observer.observe(root, { attributes: true, subtree: true, attributeFilter: WATCH_ATTRS });
        });

        normalize(root);
        observer.observe(root, { attributes: true, subtree: true, attributeFilter: WATCH_ATTRS });
    }

    function init() {
        var roots = document.querySelectorAll(ROOT_SEL);
        if (!roots.length) {
            return;
        }
        Array.prototype.forEach.call(roots, watch);
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
