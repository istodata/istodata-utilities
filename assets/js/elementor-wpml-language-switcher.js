(function() {
    var closeDelay = 120;

    function getButton(root) {
        return root ? root.querySelector('.iu-wpml-switcher__toggle') : null;
    }

    function getWidgetWrapper(root) {
        return root ? root.closest('.elementor-widget') : null;
    }

    function setExpanded(root, expanded) {
        var button = getButton(root);
        if (button) {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
    }

    function clearCloseTimer(root) {
        if (root && root.__iuWpmlCloseTimer) {
            window.clearTimeout(root.__iuWpmlCloseTimer);
            root.__iuWpmlCloseTimer = null;
        }
    }

    function openSwitcher(root) {
        if (!root) {
            return;
        }

        clearCloseTimer(root);
        root.classList.add('is-open');
        var widgetWrapper = getWidgetWrapper(root);
        if (widgetWrapper) {
            widgetWrapper.classList.add('iu-wpml-switcher-is-open');
        }
        setExpanded(root, true);
    }

    function closeSwitcher(root) {
        if (!root) {
            return;
        }

        clearCloseTimer(root);
        root.classList.remove('is-open');
        var widgetWrapper = getWidgetWrapper(root);
        if (widgetWrapper) {
            widgetWrapper.classList.remove('iu-wpml-switcher-is-open');
        }
        setExpanded(root, false);
    }

    function scheduleClose(root) {
        if (!root) {
            return;
        }

        clearCloseTimer(root);
        root.__iuWpmlCloseTimer = window.setTimeout(function() {
            closeSwitcher(root);
        }, closeDelay);
    }

    function initSwitcher(root) {
        if (!root || root.__iuWpmlInitialized) {
            return;
        }

        var button = getButton(root);
        if (!button || button.disabled) {
            return;
        }

        root.__iuWpmlInitialized = true;

        root.addEventListener('mouseenter', function() {
            openSwitcher(root);
        });

        root.addEventListener('mouseleave', function() {
            scheduleClose(root);
        });

        root.addEventListener('focusin', function() {
            openSwitcher(root);
        });

        root.addEventListener('focusout', function() {
            window.setTimeout(function() {
                if (!root.contains(document.activeElement)) {
                    scheduleClose(root);
                }
            }, 0);
        });
    }

    function initAll(context) {
        var scope = context || document;
        var switchers = scope.querySelectorAll('.iu-wpml-switcher');
        switchers.forEach(initSwitcher);
    }

    document.addEventListener('DOMContentLoaded', function() {
        initAll(document);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.iu-wpml-switcher.is-open').forEach(function(root) {
                closeSwitcher(root);
            });
        }
    });

    document.addEventListener('pointerdown', function(event) {
        document.querySelectorAll('.iu-wpml-switcher.is-open').forEach(function(root) {
            if (!root.contains(event.target)) {
                closeSwitcher(root);
            }
        });
    }, true);

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {
            if ($scope && $scope[0]) {
                initAll($scope[0]);
            }
        });
    } else {
        initAll(document);
    }
})();
