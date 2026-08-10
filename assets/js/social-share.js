(function() {
    var closeDelay = 120;

    function getToggle(root) {
        return root ? root.querySelector('.iu-social-share__toggle') : null;
    }

    function getPanel(root) {
        return root ? root.querySelector('.iu-social-share__icons') : null;
    }

    function clearCloseTimer(root) {
        if (root && root.__iuSocialShareCloseTimer) {
            window.clearTimeout(root.__iuSocialShareCloseTimer);
            root.__iuSocialShareCloseTimer = null;
        }
    }

    function setOpen(root, open) {
        var toggle = getToggle(root);
        var panel = getPanel(root);

        clearCloseTimer(root);
        root.classList.toggle('is-open', open);

        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (panel) {
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
    }

    function scheduleClose(root) {
        clearCloseTimer(root);
        root.__iuSocialShareCloseTimer = window.setTimeout(function() {
            setOpen(root, false);
        }, closeDelay);
    }

    function initPopover(root) {
        if (!root || root.__iuSocialShareInitialized) {
            return;
        }

        var toggle = getToggle(root);
        var panel = getPanel(root);
        if (!toggle || !panel) {
            return;
        }

        root.__iuSocialShareInitialized = true;

        toggle.addEventListener('click', function() {
            if (root.__iuSocialShareOpenedByFocus) {
                root.__iuSocialShareOpenedByFocus = false;
                setOpen(root, true);
                return;
            }

            setOpen(root, !root.classList.contains('is-open'));
        });

        if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            root.addEventListener('mouseenter', function() {
                setOpen(root, true);
            });

            root.addEventListener('mouseleave', function() {
                scheduleClose(root);
            });
        }

        root.addEventListener('focusin', function() {
            if (root.__iuSocialShareSkipFocusOpen) {
                root.__iuSocialShareSkipFocusOpen = false;
                return;
            }

            root.__iuSocialShareOpenedByFocus = !root.classList.contains('is-open');
            setOpen(root, true);
        });

        root.addEventListener('focusout', function() {
            window.setTimeout(function() {
                if (!root.contains(document.activeElement)) {
                    root.__iuSocialShareOpenedByFocus = false;
                    scheduleClose(root);
                }
            }, 0);
        });
    }

    function initAll(context) {
        var scope = context || document;
        scope.querySelectorAll('.iu-social-share--popover').forEach(initPopover);
    }

    document.addEventListener('DOMContentLoaded', function() {
        initAll(document);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('.iu-social-share--popover.is-open').forEach(function(root) {
            root.__iuSocialShareSkipFocusOpen = true;
            setOpen(root, false);
            var toggle = getToggle(root);
            if (toggle) {
                toggle.focus();
            }
            window.setTimeout(function() {
                root.__iuSocialShareSkipFocusOpen = false;
            }, 0);
        });
    });

    document.addEventListener('pointerdown', function(event) {
        document.querySelectorAll('.iu-social-share--popover.is-open').forEach(function(root) {
            if (!root.contains(event.target)) {
                setOpen(root, false);
            }
        });
    }, true);

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/iu_social_share.default', function($scope) {
            if ($scope && $scope[0]) {
                initAll($scope[0]);
            }
        });
    }
})();
