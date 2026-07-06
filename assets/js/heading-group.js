(function(){
  function isEditMode(){
    try { return !!(window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode()); } catch(e){ return false; }
  }
  function schedulePlay(el){
    try {
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          // force reflow to ensure initial styles are applied before toggling
          try { void el.offsetWidth; } catch(e){}
          el.classList.add('iu-hg-play');
        });
      });
    } catch(e){
      setTimeout(function(){ try { el.classList.add('iu-hg-play'); } catch(e){} }, 50);
    }
  }

  function isInViewport(el, ratio){
    try {
      var r = el.getBoundingClientRect();
      var h = window.innerHeight || document.documentElement.clientHeight;
      var w = window.innerWidth || document.documentElement.clientWidth;
      var vRatio = typeof ratio === 'number' ? ratio : 0.01;
      if (r.width === 0 || r.height === 0) return false;
      var vertVisible = (r.top <= h * (1 - vRatio)) && (r.bottom >= h * vRatio);
      var horVisible = (r.left <= w) && (r.right >= 0);
      return vertVisible && horVisible;
    } catch(e){ return false; }
  }

  function reset(el){
    try { el.classList.remove('iu-hg-play'); } catch(e){}
    try { el.removeAttribute('data-iu-hg-observed'); } catch(e){}
  }

  function initOnce(el){
    if (!el || el.getAttribute('data-iu-hg-observed') === '1') return;
    el.setAttribute('data-iu-hg-observed', '1');
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting) {
            schedulePlay(el);
            io.unobserve(el);
          }
        });
      }, { root: null, threshold: 0.01 });
      io.observe(el);
      // Immediate check for hero/in-view on load
      if (isInViewport(el, 0.01)) {
        schedulePlay(el);
      }
    } else {
      // Fallback: trigger with slight delay so transitions apply
      schedulePlay(el);
    }
  }

  function scan(root){
    var nodes = (root || document).querySelectorAll('.iu-heading-group--stagger');
    if (!nodes || !nodes.length) return;
    for (var i=0;i<nodes.length;i++) {
      // reset state on each scan to allow re-trigger on reload or re-render
      reset(nodes[i]);
      if (isEditMode()) {
        schedulePlay(nodes[i]);
      } else {
        initOnce(nodes[i]);
      }
    }
  }

  if (document.readyState !== 'loading') scan(); else document.addEventListener('DOMContentLoaded', function(){ scan(); });
  // Ensure CSS fully applied and images loaded for hero: scan again on window load
  try { window.addEventListener('load', function(){ scan(); }); } catch(e){}

  // Observe DOM changes (Elementor/editor re-renders)
  try {
    var mo = new MutationObserver(function(muts){
      for (var i=0;i<muts.length;i++){
        var t = muts[i].target;
        if (t && t.querySelectorAll) {
          if (t.querySelector('.iu-heading-group--stagger')) { scan(t); break; }
        }
      }
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  } catch(e){}

  // Elementor editor hook
  if (window.jQuery) {
    jQuery(window).on('elementor/frontend/init', function(){
      if (window.elementorFrontend && elementorFrontend.hooks) {
        elementorFrontend.hooks.addAction('frontend/element_ready/iu_heading_group.default', function($scope){
          var el = $scope && $scope[0] ? $scope[0].querySelector('.iu-heading-group--stagger') : null;
          if (el) scan($scope[0]);
        });
      }
    });
  }
})();
