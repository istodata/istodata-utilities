/* ISTODATA Utilities: Simple Repeater accordion transitions. */
(function () {
    'use strict';

    function closest(element, selector) {
        return element && element.closest ? element.closest(selector) : null;
    }

    function finishTransition(details, panel, open) {
        panel.style.height = '';
        details.dataset.iuAccordionAnimating = '';
        if (!open) {
            details.open = false;
        }
    }

    function animate(details, panel, open) {
        if (details.dataset.iuAccordionAnimating === '1') {
            return;
        }

        if (open) {
            closeSiblingItems(details);
        }

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            details.open = open;
            return;
        }

        details.dataset.iuAccordionAnimating = '1';
        if (open) {
            details.open = true;
            panel.style.height = '0px';
            requestAnimationFrame(function () {
                panel.style.height = panel.scrollHeight + 'px';
            });
        } else {
            panel.style.height = panel.getBoundingClientRect().height + 'px';
            panel.offsetHeight;
            requestAnimationFrame(function () {
                panel.style.height = '0px';
            });
        }

        panel.addEventListener('transitionend', function onTransitionEnd(event) {
            if (event.target !== panel || event.propertyName !== 'height') {
                return;
            }
            panel.removeEventListener('transitionend', onTransitionEnd);
            finishTransition(details, panel, open);
        });
    }

    function closeSiblingItems(details) {
        var accordion = closest(details, '.iu-simple-repeater--accordion-single');
        if (!accordion) {
            return;
        }

        accordion.querySelectorAll('.iu-simple-repeater__accordion-item[open]').forEach(function (item) {
            if (item === details || item.dataset.iuAccordionAnimating === '1') {
                return;
            }

            var panel = item.querySelector(':scope > .iu-simple-repeater__accordion-panel');
            if (panel) {
                animate(item, panel, false);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var summary = closest(event.target, '.iu-simple-repeater__accordion-title');
        if (!summary) {
            return;
        }

        var details = closest(summary, '.iu-simple-repeater__accordion-item');
        var panel = details ? details.querySelector(':scope > .iu-simple-repeater__accordion-panel') : null;
        if (!details || !panel) {
            return;
        }

        event.preventDefault();
        animate(details, panel, !details.open);
    });
}());
