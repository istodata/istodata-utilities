/* ISTODATA Utilities: Accordion Scroll to Active (vanilla JS) */
(function () {
  function matchesSelector(el, selector) {
    return (el && el.matches) ? el.matches(selector) : false;
  }

  function closest(el, selector) {
    while (el && el.nodeType === 1) {
      if (matchesSelector(el, selector)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function smoothScrollToWindow(top) {
    try {
      window.scrollTo({ top: top, behavior: 'smooth' });
    } catch (e) {
      window.scrollTo(0, top);
    }
  }

  function smoothScrollElement(el, top) {
    try {
      el.scrollTo({ top: top, behavior: 'smooth' });
    } catch (e) {
      el.scrollTop = top;
    }
  }

  document.addEventListener('click', function (e) {
    var title = closest(e.target, '.e-n-accordion-item-title, .elementor-widget-accordion .elementor-tab-title');
    if (!title) return;

    var offCanvas = closest(title, '.e-off-canvas__content');
    var inOffCanvas = !!offCanvas;
    var scrollOffset = inOffCanvas ? 80 : 95;

    setTimeout(function () {
      var target = closest(title, '.e-n-accordion-item, .elementor-accordion-item');
      if (!target) return;

      var rect = target.getBoundingClientRect();
      if (inOffCanvas) {
        var curr = offCanvas.scrollTop || 0;
        var offset = rect.top + curr - scrollOffset;
        smoothScrollElement(offCanvas, offset);
      } else {
        var currWin = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        var offsetWin = rect.top + currWin - scrollOffset;
        smoothScrollToWindow(offsetWin);
      }
    }, 500);
  });
})();
