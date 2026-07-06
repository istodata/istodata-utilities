(function($){
  var IUDevVis = {
    _observer: null,
    _scheduled: false,
    getSetting: function(model, key){
      try {
        if (!model) return undefined;
        if (typeof model.getSetting === 'function') {
          return model.getSetting(key);
        }
        var settings = (typeof model.get === 'function') ? model.get('settings') : null;
        if (settings && typeof settings.get === 'function') {
          return settings.get(key);
        }
        if (settings && settings.attributes && key in settings.attributes){
          return settings.attributes[key];
        }
        if (model.attributes && model.attributes.settings && key in model.attributes.settings){
          return model.attributes.settings[key];
        }
      } catch(e) {}
      return undefined;
    },
    hasDeviceVisibility: function(model){
      try {
        if (!model) return false;
        var hidePhone = IUDevVis.getSetting(model, 'iu_hide_on_phone');
        var hideDT = IUDevVis.getSetting(model, 'iu_hide_on_desktop_tablet');
        return hidePhone === 'yes' || hideDT === 'yes';
      } catch(e){ return false; }
    },
    updatePanelBadge: function(model){
      var needs = IUDevVis.hasDeviceVisibility(model);
      // Prefer the global panel header title if present
      var $globalTitle = $('#elementor-panel-header-title');
      if ($globalTitle.length){
        var has = $globalTitle.find('.iu-devvis-badge').length > 0;
        if (needs && !has){
          $('<span class="iu-devvis-badge" title="Ορατότητα Συσκευών ενεργή" aria-hidden="true"></span>').appendTo($globalTitle);
        } else if (!needs && has){
          $globalTitle.find('.iu-devvis-badge').remove();
        }
        return;
      }
      // Fallback: Try multiple header selectors across Elementor versions
      var $header = $('.elementor-panel .elementor-controls-stack-header, .elementor-panel .e-controls__header, .e-controls__header');
      if (!$header.length) return;
      var $container = $header.first();
      var has2 = $container.find('.iu-devvis-badge').length > 0;
      if (needs && !has2){
        var $titleWrap = $container.find('.elementor-controls-stack-header__title, .e-controls__header__title, .elementor-panel-heading-title').first();
        var $badge = $('<span class="iu-devvis-badge" title="Ορατότητα Συσκευών ενεργή" aria-hidden="true"></span>');
        if ($titleWrap.length){
          $badge.insertAfter($titleWrap);
        } else {
          $badge.appendTo($container);
        }
      } else if (!needs && has2){
        $container.find('.iu-devvis-badge').remove();
      }
    },
    observeHeader: function(model){
      try {
        // Disconnect any existing observer
        if (IUDevVis._observer && typeof IUDevVis._observer.disconnect === 'function'){
          IUDevVis._observer.disconnect();
        }
        // Observe a narrower header container to avoid heavy DOM churn
        var root = document.querySelector('#elementor-panel-header') ||
                   document.querySelector('#elementor-panel-header-title') ||
                   document.querySelector('.elementor-panel .elementor-controls-stack-header') ||
                   document.querySelector('.elementor-panel .e-controls__header') ||
                   document.querySelector('.e-controls__header');
        if (!root) return;
        IUDevVis._observer = new MutationObserver(function(){
          if (IUDevVis._scheduled) return;
          IUDevVis._scheduled = true;
          setTimeout(function(){
            IUDevVis._scheduled = false;
            IUDevVis.updatePanelBadge(model);
          }, 80);
        });
        IUDevVis._observer.observe(root, { childList: true, subtree: true });
      } catch(e) {}
    },
    unobserveHeader: function(){
      try {
        if (IUDevVis._observer && typeof IUDevVis._observer.disconnect === 'function'){
          IUDevVis._observer.disconnect();
        }
      } catch(e) {}
      IUDevVis._observer = null;
      IUDevVis._scheduled = false;
    },
    bind: function(){
      // When opening any element editor, add/remove the badge
      if (window.elementor && elementor.hooks && elementor.hooks.addAction){
        var refresh = function(model){
          // Multiple passes to catch async DOM changes
          setTimeout(function(){ IUDevVis.updatePanelBadge(model); }, 0);
          setTimeout(function(){ IUDevVis.updatePanelBadge(model); }, 100);
          setTimeout(function(){ IUDevVis.updatePanelBadge(model); }, 250);
        };
        var handler = function(panel, model){
          refresh(model);
          IUDevVis.observeHeader(model);
          if (model && typeof model.on === 'function'){
            model.off('change:iu_hide_on_phone');
            model.off('change:iu_hide_on_desktop_tablet');
            model.on('change:iu_hide_on_phone change:iu_hide_on_desktop_tablet', function(){
              IUDevVis.updatePanelBadge(model);
            });
          }
        };
        var closeHandler = function(){
          IUDevVis.unobserveHeader();
        };
        elementor.hooks.addAction('panel/open_editor/element', handler);
        elementor.hooks.addAction('panel/open_editor/widget', handler);
        elementor.hooks.addAction('panel/open_editor/container', handler);
        elementor.hooks.addAction('panel/open_editor/column', handler);
        elementor.hooks.addAction('panel/open_editor/section', handler);
        // Close handlers to free observers
        elementor.hooks.addAction('panel/close_editor', closeHandler);
        elementor.hooks.addAction('panel/close_editor/element', closeHandler);
        elementor.hooks.addAction('panel/close_editor/widget', closeHandler);
        elementor.hooks.addAction('panel/close_editor/container', closeHandler);
        elementor.hooks.addAction('panel/close_editor/column', closeHandler);
        elementor.hooks.addAction('panel/close_editor/section', closeHandler);
      }
    }
  };

  // Init when Elementor editor is ready
  var init = function(){ IUDevVis.bind(); };
  // Try both init events
  $(window).on('elementor:init', init);
  $(window).on('elementor/editor/init', init);
})(jQuery);
