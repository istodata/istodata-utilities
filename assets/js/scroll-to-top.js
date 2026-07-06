(function(){
  function isEditMode(){
    try { return !!(window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode()); } catch(e){ return false; }
  }
  function onClick(e){
    try {
      var href = (this.getAttribute('href')||'').trim();
      if (href === '#iu-scroll-top') {
        e.preventDefault();
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); }
        catch(err){ window.scrollTo(0,0); }
      }
    } catch(err){}
  }
  function updateVisibility(){
    var y = (window.pageYOffset || document.documentElement.scrollTop || 0);
    var nodes = document.querySelectorAll('.elementor-widget-iu_scroll_to_top');
    var show = isEditMode() ? true : (y > 400);
    for (var i=0;i<nodes.length;i++){
      if (show) nodes[i].classList.add('iu-stt--visible');
      else nodes[i].classList.remove('iu-stt--visible');
    }
  }
  function bind(root){
    var scope = root || document;
    var links = scope.querySelectorAll('a[href="#iu-scroll-top"]');
    for (var i=0;i<links.length;i++){
      links[i].addEventListener('click', onClick, false);
    }
    updateVisibility();
  }
  function init(){ bind(); updateVisibility(); }
  if (document.readyState !== 'loading') init(); else document.addEventListener('DOMContentLoaded', init);
  window.addEventListener('scroll', updateVisibility, { passive: true });
  window.addEventListener('resize', updateVisibility);
  // Elementor editor hot‑binds
  if (window.jQuery){
    jQuery(window).on('elementor/frontend/init', function(){
      if (window.elementorFrontend && elementorFrontend.hooks) {
        elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope){ bind($scope && $scope[0]); });
      }
    });
  }
})();
