(function(){
  function isEditMode(){
    try { return !!(window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode()); } catch(e){ return false; }
  }
  function allowNode(t){
    try {
      if (!t) return false;
      if (t.closest) {
        if (t.closest('input, textarea, [contenteditable="true"], [contenteditable=""]')) return true;
      }
    } catch(e){}
    return false;
  }
  function prevent(e){ if (!allowNode(e.target)) { e.preventDefault(); return false; } }
  function init(){
    if (isEditMode()) return;
    try { document.body.classList.add('iu-no-copy'); } catch(e){}
    // Block context menu
    document.addEventListener('contextmenu', prevent);
    // Block text selection
    document.addEventListener('selectstart', prevent);
    // Block image drag
    document.addEventListener('dragstart', function(e){
      try { if (e.target && e.target.tagName === 'IMG') { e.preventDefault(); return false; } } catch(err){}
    });
  }
  if (document.readyState !== 'loading') init(); else document.addEventListener('DOMContentLoaded', init);
})();

