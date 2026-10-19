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
 *      the carousel accessibility check. Because the slides are <ul>/<li> markup
 *      (tags we must not change), we keep the native list semantics and add
 *      aria-roledescription="slide", drop the presentational role UIkit puts on the
 *      list, and turn the dot controls into plain buttons.
 *
 *   2. Swiper — used by Mercury, Pacific, Fiestar, Rubix, Tango, Vertex, etc.
 *      (Lite) and Avatar, Cloud, Expo, Panorama, Super Flow, … (Pro). These init
 *      via elementorFrontend.utils.swiper with no a11y config, so slides may lack
 *      the carousel roles. We add them only when missing, scoped to Prime Slider
 *      widgets so no other plugin's Swiper is touched.
 *
 *   3. Every Prime Slider widget — icon-only links and buttons (social icons,
 *      play buttons, arrows, hotspots, section and thumbnail navigation added
 *      by the multiscroll/pagepiling libraries) get an accessible name when they
 *      have none, worked out from their class, link target or position.
 *
 * All writes are idempotent and additive-only: an accessible name UIkit or Swiper
 * already provided is never overwritten. A scoped MutationObserver keeps the
 * corrections in place through each engine's reactive updates. Frontend only; a
 * no-op on pages without a Prime Slider slider.
 */
(function () {

    'use strict';

    // Translated labels, printed by PHP as PrimeSliderA11y (wp_localize_script).
    // English is the fallback when the object is missing (an add-on that loads
    // this file on its own) or a key is empty.
    var I18N = window.PrimeSliderA11y || {};

    function t(key, fallback) {
        return typeof I18N[key] === 'string' && I18N[key] !== '' ? I18N[key] : fallback;
    }

    // UIkit and Swiper name their own controls in English ("Next slide",
    // "Slide 3", "Go to slide 3", "2 / 5"). Those names are mapped to the
    // translated labels; on an English site they are left exactly as they are.
    var ENGLISH = {
        previousSlide: 'Previous slide',
        nextSlide: 'Next slide',
        goToSlide: 'Go to slide %d',
        slideOf: '%1$d of %2$d'
    };

    // The label in the site's language, or '' when it is not translated.
    function translatedOnly(key) {
        var value = t(key, '');
        return value !== ENGLISH[key] ? value : '';
    }

    var LIBRARY_LABELS = [
        [/^Previous slide$/, function () { return translatedOnly('previousSlide'); }],
        [/^Next slide$/, function () { return translatedOnly('nextSlide'); }],
        [/^(?:Go to slide|Slide) (\d+)$/, function (m) { return translatedOnly('goToSlide') && fmt(translatedOnly('goToSlide'), m[1]); }],
        [/^(\d+) \/ (\d+)$/, function (m) { return translatedOnly('slideOf') && fmt(translatedOnly('slideOf'), m[1], m[2]); }]
    ];

    function translateLibraryLabels(root) {
        if (!window.PrimeSliderA11y) {
            return;
        }
        Array.prototype.forEach.call(root.querySelectorAll('[aria-label]'), function (el) {
            var label = el.getAttribute('aria-label');
            for (var i = 0; i < LIBRARY_LABELS.length; i++) {
                var match = LIBRARY_LABELS[i][0].exec(label);
                if (match) {
                    var translated = LIBRARY_LABELS[i][1](match);
                    if (translated && translated !== label && translated.indexOf('%') === -1) {
                        setAttr(el, 'aria-label', translated);
                    }
                    return;
                }
            }
        });
    }

    // printf-style: %d / %s in order, or numbered %1$d / %2$s.
    function fmt(format) {
        var args = Array.prototype.slice.call(arguments, 1);
        var next = 0;
        return String(format).replace(/%(?:(\d+)\$)?[ds]/g, function (match, position) {
            var value = position ? args[parseInt(position, 10) - 1] : args[next++];
            return value === undefined ? match : String(value);
        });
    }

    // UIkit slideshow, and UIkit slider (Flexure), which has the same dot
    // navigation.
    var UIKIT_SEL   = '[bdt-slideshow], [data-bdt-slideshow], [bdt-slider], [data-bdt-slider]';
    // Swiper containers, restricted to Prime Slider widgets (data-widget_type^="prime-slider-").
    var SWIPER_SEL  = '[data-widget_type^="prime-slider-"] .swiper, [data-widget_type^="prime-slider-"].swiper';
    var ITEMS_SEL   = '.bdt-slideshow-items';
    var SLIDE_SEL   = '.bdt-slideshow-item';
    var NAV_SEL     = '.bdt-slideshow-nav';
    // Prime Slider's own dot / thumbnail navigation: any item UIkit drives by
    // number, whatever class the widget gives its list (.bdt-ps-dotnav,
    // .bdt-ps-thumbnav, ...).
    var NAV_ITEM_SEL = '[bdt-slideshow-item], [data-bdt-slideshow-item], [bdt-slider-item], [data-bdt-slider-item]';
    var WIDGET_SEL  = '[data-widget_type^="prime-slider-"]';
    // Click-only <div>/<span> controls some widgets build: slide arrows, share
    // buttons, scroll-down. They become focusable buttons.
    var CLICKABLE_SEL = '.ps-turnar-nav-btn, .ps-motion-navigation-button, ' +
        '.bdt-ps-btn[class*="bdt-ps-"], .bdt-scroll-down-wrapper, .bdt-alert-close, ' +
        '[role="button"]:not(a[href]):not(button)';
    var SHARE_RE = /(facebook|twitter|linkedin|pinterest|whatsapp|reddit|telegram|tumblr|vk|email)/i;
    // aria-label too: UIkit and Swiper re-apply their English names on updates.
    var WATCH_ATTRS = ['role', 'aria-roledescription', 'aria-labelledby', 'aria-controls', 'aria-selected', 'aria-current', 'aria-label'];

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

    // --- UIkit slideshow: Tabs pattern -> valid list-based carousel ----------
    // The slides are <li> inside <ul class="bdt-slideshow-items">, so the tags are
    // fixed. UIkit stamps role="tabpanel"/"group" on each <li> (invalid on an <li>)
    // and role="presentation" on the <ul> (which also carries aria-live, so it can't
    // be consistently ignored) — both fail the agentic-browsing accessibility-tree
    // audit. Rather than change tags, we keep the native list semantics (<ul> list of
    // <li> listitems) and only add aria-roledescription="slide" so each item is still
    // announced as a slide.
    function normalizeUikit(root) {
        // Items container: drop UIkit's role="presentation" so the <ul> stays a
        // consistently exposed list (no presentation vs. aria-live conflict).
        Array.prototype.forEach.call(root.querySelectorAll(ITEMS_SEL), function (list) {
            removeAttr(list, 'role');
        });

        // Slides: strip the tab/tabpanel/group role UIkit stamps (none are valid on
        // an <li>), leaving the native listitem role, and describe each as a slide.
        var slides = root.querySelectorAll(SLIDE_SEL);
        Array.prototype.forEach.call(slides, function (slide, index) {
            removeAttr(slide, 'role');
            setAttr(slide, 'aria-roledescription', 'slide');
            removeAttr(slide, 'aria-labelledby');
            labelIfEmpty(slide, fmt(t('slideOf', '%1$d of %2$d'), index + 1, slides.length));
        });

        // Dot navigation: role="tablist"/"tab" -> a labelled group of buttons.
        // UIkit stamps role="presentation" on the wrapping <li> elements; that is
        // left intact on purpose. Stripping it would leave bare <li> children under
        // a non-list <ul role="group">, which axe flags as orphaned list items.
        Array.prototype.forEach.call(root.querySelectorAll(NAV_SEL), function (nav) {
            setAttr(nav, 'role', 'group');
            if (!nav.getAttribute('aria-label')) {
                setAttr(nav, 'aria-label', t('chooseSlide', 'Choose slide to display'));
            }
            Array.prototype.forEach.call(nav.querySelectorAll('a'), function (dot, index) {
                toButton(dot);
                labelIfEmpty(dot, fmt(t('goToSlide', 'Go to slide %d'), index + 1));
            });
        });

        // The same for navigation lists without .bdt-slideshow-nav. UIkit puts
        // role="tab" on their controls but no role="tablist" on the list, so
        // each tab is orphaned; and the list may hold non-<li> decoration.
        Array.prototype.forEach.call(root.querySelectorAll(NAV_ITEM_SEL), function (item) {
            var value = item.getAttribute('bdt-slideshow-item') || item.getAttribute('data-bdt-slideshow-item') ||
                item.getAttribute('bdt-slider-item') || item.getAttribute('data-bdt-slider-item');

            if (!/^\d+$/.test(String(value).trim())) {
                return; // "next" / "previous" controls are handled below.
            }

            var control = /^(A|BUTTON)$/.test(item.tagName) ? item : item.querySelector('a, button');
            var list    = item.parentElement;

            if (control) {
                toButton(control);
                labelIfEmpty(control, fmt(t('goToSlide', 'Go to slide %d'), parseInt(value, 10) + 1));
            }

            if (control !== item && item.tagName === 'LI') {
                setAttr(item, 'role', 'presentation');
            }

            if (list && /^(UL|OL)$/.test(list.tagName) && !list.closest(NAV_SEL)) {
                setAttr(list, 'role', 'group');
                if (!list.getAttribute('aria-label')) {
                    setAttr(list, 'aria-label', t('chooseSlide', 'Choose slide to display'));
                }
            }
        });

        // Prev/next arrows are empty anchors — expose an accessible name.
        Array.prototype.forEach.call(root.querySelectorAll('[bdt-slidenav-previous], [data-bdt-slidenav-previous]'), function (el) {
            labelIfEmpty(el, t('previousSlide', 'Previous slide'));
        });
        Array.prototype.forEach.call(root.querySelectorAll('[bdt-slidenav-next], [data-bdt-slidenav-next]'), function (el) {
            labelIfEmpty(el, t('nextSlide', 'Next slide'));
        });

        translateLibraryLabels(root);
    }

    // A UIkit tab control becomes a plain button. The selected state UIkit keeps
    // in aria-selected moves to aria-current, so the current slide is still
    // announced.
    function toButton(control) {
        if (control.getAttribute('role') === 'tab') {
            setAttr(control, 'role', 'button');
        }
        if (control.hasAttribute('aria-selected')) {
            if (control.getAttribute('aria-selected') === 'true') {
                setAttr(control, 'aria-current', 'true');
            } else {
                removeAttr(control, 'aria-current');
            }
            removeAttr(control, 'aria-selected');
        }
        removeAttr(control, 'aria-controls');
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
                labelIfEmpty(slide, fmt(t('slideOf', '%1$d of %2$d'), index + 1, slides.length));
            }
        });

        Array.prototype.forEach.call(root.querySelectorAll('.swiper-button-prev'), function (el) {
            labelIfEmpty(el, t('previousSlide', 'Previous slide'));
        });
        Array.prototype.forEach.call(root.querySelectorAll('.swiper-button-next'), function (el) {
            labelIfEmpty(el, t('nextSlide', 'Next slide'));
        });
        Array.prototype.forEach.call(root.querySelectorAll('.swiper-pagination-bullet'), function (el, index) {
            labelIfEmpty(el, fmt(t('goToSlide', 'Go to slide %d'), index + 1));
        });

        translateLibraryLabels(root);
    }

    // --- Any Prime Slider widget: name icon-only controls ---------------------
    function hasAccessibleName(el) {
        if ((el.textContent || '').trim() || el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || el.getAttribute('title')) {
            return true;
        }
        var img = el.querySelector('img[alt]');
        if (img && img.getAttribute('alt').trim()) {
            return true;
        }
        return !!el.querySelector('svg title');
    }

    function siteName(href) {
        var match = /^https?:\/\/(?:www\.)?([^/:?#]+)/i.exec(href || '');
        if (!match) {
            return '';
        }
        var parts = match[1].split('.');
        var name  = parts.length > 1 ? parts[parts.length - 2] : parts[0];
        return name.charAt(0).toUpperCase() + name.slice(1);
    }

    function guessLabel(el) {
        var cls  = (typeof el.className === 'string' ? el.className : '') + ' ' +
            (el.parentElement && typeof el.parentElement.className === 'string' ? el.parentElement.className : '');
        var href = el.getAttribute('href') || '';

        if (/play/i.test(cls)) {
            return t('playVideo', 'Play video');
        }
        if (/(^|[\s_-])(next|arrow-right)/i.test(cls)) {
            return t('nextSlide', 'Next slide');
        }
        if (/(^|[\s_-])(prev|previous|arrow-left)/i.test(cls)) {
            return t('previousSlide', 'Previous slide');
        }
        if (/hotspot/i.test(cls)) {
            return t('showProductDetails', 'Show product details');
        }
        if (/scroll-down/i.test(cls)) {
            return t('scrollDown', 'Scroll down');
        }
        if (/alert-close/i.test(cls)) {
            return t('close', 'Close');
        }
        if (/share/i.test(cls) && SHARE_RE.test(cls)) {
            var network = SHARE_RE.exec(cls)[1];
            return fmt(t('shareOn', 'Share on %s'), network.charAt(0).toUpperCase() + network.slice(1));
        }
        if (/social|share/i.test(cls) && siteName(href)) {
            return siteName(href);
        }
        // Section / slide navigation lists (multiscroll, pagepiling, thumbnails).
        var li = el.closest('li');
        if (li && li.parentElement) {
            return fmt(t('goToSlide', 'Go to slide %d'), Array.prototype.indexOf.call(li.parentElement.children, li) + 1);
        }
        if (el.querySelector('img') && el.parentElement) {
            return fmt(t('goToSlide', 'Go to slide %d'), Array.prototype.indexOf.call(el.parentElement.children, el) + 1);
        }
        return siteName(href);
    }

    function activateOnKey(event) {
        if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
            event.preventDefault();
            event.currentTarget.click();
        }
    }

    function makeOperable(el) {
        if (el.closest('[aria-hidden="true"]') || /\b(swiper-button-disabled)\b/.test(el.className) ||
            el.getAttribute('aria-disabled') === 'true') {
            return;
        }
        if (/^(A|BUTTON)$/.test(el.tagName) && (el.tagName === 'BUTTON' || el.hasAttribute('href'))) {
            return;
        }
        // Already focusable: its library handles the keys (Swiper's a11y module
        // does), and a second handler would move the slider twice. A wrapper
        // around a real link or button is left alone too: the inner control is
        // the one to reach, and a button around it would nest controls.
        if (el.hasAttribute('tabindex') || el.querySelector('a[href], button, input, select, textarea, [tabindex]')) {
            return;
        }
        setAttr(el, 'role', 'button');
        setAttr(el, 'tabindex', '0');
        if (!el.psKeyBound) {
            el.psKeyBound = true;
            el.addEventListener('keydown', activateOnKey);
        }
    }

    function normalizeControls(widget) {
        Array.prototype.forEach.call(widget.querySelectorAll(CLICKABLE_SEL), makeOperable);

        // Sliders whose Swiper container has no .swiper class are only reached
        // here, so their library labels are translated here as well.
        translateLibraryLabels(widget);

        Array.prototype.forEach.call(widget.querySelectorAll('a[href], button, [role="button"]'), function (el) {
            if (hasAccessibleName(el)) {
                return;
            }
            var label = guessLabel(el);
            if (label) {
                setAttr(el, 'aria-label', label);
            }
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

    // Controls some libraries add after start-up (multiscroll / pagepiling
    // navigation) arrive as new nodes, and Swiper names its controls once it
    // starts, so this one watches the child list and aria-label.
    var CONTROL_WATCH = { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-label'] };

    function watchControls(widget) {
        var timer = null;
        var observer = new MutationObserver(function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                observer.disconnect();
                normalizeControls(widget);
                observer.observe(widget, CONTROL_WATCH);
            }, 100);
        });

        normalizeControls(widget);
        observer.observe(widget, CONTROL_WATCH);
    }

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll(UIKIT_SEL), function (root) {
            watch(root, normalizeUikit);
        });
        Array.prototype.forEach.call(document.querySelectorAll(SWIPER_SEL), function (root) {
            watch(root, normalizeSwiper);
        });
        Array.prototype.forEach.call(document.querySelectorAll(WIDGET_SEL), watchControls);
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
