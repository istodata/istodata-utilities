(function(){
  'use strict';

  var loader = {
    loading: false,
    loaded: false,
    queue: []
  };

  function loadGoogleMaps(apiKey, lang, region){
    return new Promise(function(resolve, reject){
      if (window.google && window.google.maps) { loader.loaded = true; return resolve(); }
      if (!apiKey) { return reject(new Error('Missing Google Maps API key')); }
      if (loader.loading) { loader.queue.push({resolve: resolve, reject: reject}); return; }
      loader.loading = true;
      var s = document.createElement('script');
      var src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey);
      if (lang && lang !== 'auto') { src += '&language=' + encodeURIComponent(lang); }
      if (region) { src += '&region=' + encodeURIComponent(region); }
      s.src = src;
      s.async = true;
      s.defer = true;
      // Avoid extra attributes/params that may conflict with consent/optimizers
      s.onload = function(){
        loader.loaded = true;
        resolve();
        loader.queue.splice(0).forEach(function(q){ q.resolve(); });
      };
      s.onerror = function(e){
        reject(e);
        loader.queue.splice(0).forEach(function(q){ q.reject(e); });
      };
      document.head.appendChild(s);
    });
  }

  function parseBool(v){ return v === '1' || v === 'true' || v === true; }

  // Generate styles; default hides POIs (hotels, restaurants, etc.)
  function makeStyles(mode){
    if (mode === 'monochrome') {
      return [
        { elementType: 'geometry', stylers: [{ saturation: -100 }, { lightness: 10 }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#5f5f5f' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
        { featureType: 'water', elementType: 'geometry.fill', stylers: [{ color: '#cfd8dc' }] },
        { featureType: 'water', elementType: 'geometry.stroke', stylers: [{ color: '#cfd8dc' }] },
        { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit', stylers: [{ saturation: -100 }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ saturation: -100 }, { lightness: 0 }] },
        { featureType: 'landscape', elementType: 'geometry', stylers: [{ saturation: -100 }, { lightness: 20 }] },
        { featureType: 'administrative', elementType: 'geometry', stylers: [{ lightness: 20 }] }
      ];
    }
    // Default: hide POIs
    return [
      { featureType: 'poi', stylers: [{ visibility: 'off' }] }
    ];
  }

  var _iuWarnedPoiWithMapId = false;

  function initMap(el){
    try {
      var lat = parseFloat(el.dataset.lat);
      var lng = parseFloat(el.dataset.lng);
      var hasCoords = isFinite(lat) && isFinite(lng);
      var address = el.dataset.address || '';
      var zoom = parseInt(el.dataset.zoom || '14', 10);
      var styleMode = el.dataset.style || 'default';
      var mapId = el.dataset.mapId || el.dataset.styleId || '';
      var color = el.dataset.color || '';
      var scrollwheel = parseBool(el.dataset.scrollwheel);
      var draggable = parseBool(el.dataset.draggable);
      var zoomControls = parseBool(el.dataset.zoomControls);
      var title = el.dataset.title || '';
      var icon = el.dataset.icon || '';
      var cssVar = '';
      try { cssVar = getComputedStyle(el).getPropertyValue('--iu-marker-size') || ''; } catch(e){}
      var iconSize = parseInt((cssVar || '').toString().trim(), 10);
      if (!isFinite(iconSize) || iconSize <= 0) {
        iconSize = parseInt(el.dataset.iconSize || '64', 10);
      }

      var canvas = el.querySelector('.iu-google-map__canvas');
      // Remove placeholder if present
      try {
        var ph = el.querySelector('.iu-google-map__placeholder');
        if (ph) { ph.remove(); }
        if (canvas && canvas.style && canvas.style.display === 'none') { canvas.style.display = 'block'; }
      } catch(e){}
      try {
        var ch = window.getComputedStyle(canvas).height;
        if (!ch || (parseInt(ch, 10) || 0) === 0) {
          canvas.style.height = (parseInt(el.dataset.fallbackHeight, 10) || 360) + 'px';
        }
      } catch(e){}
      var styles = mapId ? null : makeStyles(styleMode);
      var mapOptions = {
        center: hasCoords ? {lat: lat, lng: lng} : {lat: 37.9838, lng: 23.7275}, // default Athens
        zoom: zoom,
        styles: styles || undefined,
        scrollwheel: scrollwheel,
        draggable: draggable,
        zoomControl: zoomControls,
        fullscreenControl: true,
        mapTypeControl: false,
        streetViewControl: false
      };
      if (mapId) { mapOptions.mapId = mapId; }
      var map = new google.maps.Map(canvas, mapOptions);

      // Extra compatibility: apply via StyledMapType as well
      try {
        if (!mapId && styles) {
          // Apply styles only when no Cloud Map ID is used
          map.setOptions({ styles: styles });
          if (google.maps.StyledMapType) {
            var styledMapType = new google.maps.StyledMapType(styles, { name: 'Styled' });
            map.mapTypes.set('styled_map', styledMapType);
            map.setMapTypeId('styled_map');
          }
        }
      } catch(e){}

      function refresh(){
        try {
          var c = map.getCenter();
          google.maps.event.trigger(map, 'resize');
          if (c) map.setCenter(c);
        } catch(e){}
      }
      // Help editor cases where container resizes after init
      setTimeout(refresh, 100);
      setTimeout(refresh, 400);
      if (document.visibilityState === 'hidden') {
        document.addEventListener('visibilitychange', function onv(){
          if (document.visibilityState === 'visible') { refresh(); document.removeEventListener('visibilitychange', onv); }
        });
      }

      function ensurePngIcon(iconSrc, size){
        return new Promise(function(resolve){
          try {
            var isDataSvg = /^data:image\/svg\+xml/i.test(iconSrc || '');
            var isSvgUrl = /\.svg(\?|$)/i.test(iconSrc || '');
            if (!iconSrc || (!isDataSvg && !isSvgUrl)) return resolve(iconSrc);

            function rasterize(svgDataUrl){
              var img = new Image();
              img.crossOrigin = 'anonymous';
              img.onload = function(){
                try {
                  var c = document.createElement('canvas');
                  c.width = size; c.height = size;
                  var ctx = c.getContext('2d');
                  ctx.clearRect(0,0,size,size);
                  ctx.drawImage(img, 0, 0, size, size);
                  var png = c.toDataURL('image/png');
                  resolve(png);
                } catch(e){ resolve(iconSrc); }
              };
              img.onerror = function(){ resolve(iconSrc); };
              img.src = svgDataUrl;
            }

            if (isDataSvg) {
              rasterize(iconSrc);
            } else if (isSvgUrl) {
              // Fetch SVG then convert to data URL to avoid CORS tainting
              fetch(iconSrc, { credentials: 'same-origin' })
                .then(function(r){ return r.text(); })
                .then(function(txt){
                  var svgUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(txt);
                  rasterize(svgUrl);
                })
                .catch(function(){ resolve(iconSrc); });
            }
          } catch(e){ resolve(iconSrc); }
        });
      }

      function attachTooltip(marker){
        if (!title) return;
        try {
          var node = document.createElement('div');
          node.className = 'iu-gmap-tooltip';
          node.textContent = title;
          // Tooltip text styling
          try {
            node.style.fontSize = '15px';
            node.style.lineHeight = '1.4';
            var siteFont = window.getComputedStyle(document.body).fontFamily;
            if (siteFont) { node.style.fontFamily = siteFont; }
            node.style.fontWeight = '700';
            node.style.padding = '6px 8px';
            var bg = 'rgba(0,0,0,0.85)';
            node.style.background = bg;
            node.style.color = '#fff';
            node.style.borderRadius = '4px';
            node.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
          } catch(e){}

          var markerOffset = (function(){
            var sz = 0;
            try { sz = icon ? (parseInt(iconSize, 10) || 0) : 40; } catch(e) { sz = 40; }
            return sz + 10; // icon height + gap
          })();

          function TooltipOverlay(position, content){
            this.position = position;
            this.content = content;
            this.div = null;
            this.offset = markerOffset;
          }
          TooltipOverlay.prototype = new google.maps.OverlayView();
          TooltipOverlay.prototype.onAdd = function(){
            var div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.transform = 'translate(-50%, -100%)';
            div.style.pointerEvents = 'none';
            var wrap = document.createElement('div');
            wrap.style.position = 'relative';
            wrap.style.display = 'inline-block';
            wrap.appendChild(this.content);
            var arrow = document.createElement('div');
            arrow.style.position = 'absolute';
            arrow.style.left = '50%';
            arrow.style.top = '100%';
            arrow.style.transform = 'translateX(-50%)';
            arrow.style.width = '0';
            arrow.style.height = '0';
            arrow.style.borderLeft = '6px solid transparent';
            arrow.style.borderRight = '6px solid transparent';
            arrow.style.borderTop = '6px solid rgba(0,0,0,0.85)';
            wrap.appendChild(arrow);
            div.appendChild(wrap);
            this.div = div;
            this.getPanes().floatPane.appendChild(div);
          };
          TooltipOverlay.prototype.draw = function(){
            if (!this.div) return;
            var proj = this.getProjection();
            var pt = proj && this.position ? proj.fromLatLngToDivPixel(this.position) : null;
            if (!pt) return;
            this.div.style.left = pt.x + 'px';
            this.div.style.top = (pt.y - (this.offset || 0)) + 'px';
          };
          TooltipOverlay.prototype.onRemove = function(){
            if (this.div && this.div.parentNode) this.div.parentNode.removeChild(this.div);
            this.div = null;
          };

          var overlay = null;
          function open(){
            if (overlay) return;
            var pos = null;
            try {
              if (marker && typeof marker.getPosition === 'function') {
                pos = marker.getPosition();
              } else if (marker && marker.position) {
                // AdvancedMarkerElement stores LatLng or LatLngLiteral in position
                var p = marker.position;
                if (p && typeof p.lat === 'function') {
                  pos = p; // google.maps.LatLng
                } else if (p && (typeof p.lat === 'number') && (typeof p.lng === 'number')) {
                  pos = new google.maps.LatLng(p.lat, p.lng);
                }
              }
            } catch(e){}
            if (!pos) return;
            overlay = new TooltipOverlay(pos, node);
            overlay.setMap(map);
          }
          function close(){
            if (!overlay) return;
            overlay.setMap(null);
            overlay = null;
          }
          marker.addListener('mouseover', open);
          marker.addListener('mouseout', close);
          marker.addListener('click', function(){ if (overlay) { close(); } else { open(); } });
        } catch(e){}
      }

      function placeMarker(pos){
        // Keep tooltip rendering fully custom to avoid the browser's native title tooltip.
        var markerOpts = { position: pos, map: map };
        if (!icon) {
          var marker = new google.maps.Marker(markerOpts);
          attachTooltip(marker);
          return;
        }
        ensurePngIcon(icon, iconSize).then(function(pngIcon){
          markerOpts.icon = {
            url: pngIcon,
            scaledSize: new google.maps.Size(iconSize, iconSize),
            anchor: new google.maps.Point(Math.round(iconSize/2), iconSize)
          };
          markerOpts.optimized = false;
          var marker = new google.maps.Marker(markerOpts);
          attachTooltip(marker);
        });
      }

      if (hasCoords) {
        placeMarker({lat: lat, lng: lng});
      } else if (address) {
        var geocoder = new google.maps.Geocoder();
        var bias = {};
        try { if (el.dataset.region) { bias.region = el.dataset.region; } } catch(e){}
        geocoder.geocode(Object.assign({ address: address }, bias), function(results, status){
          if (status === 'OK' && results && results[0]) {
            var loc = results[0].geometry.location;
            map.setCenter(loc);
            placeMarker({ lat: loc.lat(), lng: loc.lng() });
            setTimeout(refresh, 50);
            setTimeout(refresh, 300);
          } else {
            if (window.console) console.warn('IU Google Maps: Geocoding failed: ' + status);
          }
        });
      }
    } catch(e) {
      // no-op
    }
  }

  function boot(){
    var nodes = document.querySelectorAll('.iu-google-map:not([data-ready="1"])');
    if (!nodes.length) return;

    nodes.forEach(function(el){
      if (el.getAttribute('data-initializing') === '1') return;
      // Detect Elementor editor to always render during editing
      var inEditor = false;
      try { inEditor = !!(window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode()); } catch(e){}

      // Complianz consent gate: require consent; skip in Elementor editor
      var category = el.dataset.cmplzCategory || 'marketing';
      try {
        var hasCmplz = (typeof window.cmplz_has_consent === 'function') || (window.cmplz && typeof window.cmplz.hasConsent === 'function');
        var granted = false; // default: blocked until explicit consent
        if (hasCmplz) {
          try {
            if (typeof window.cmplz_has_consent === 'function') granted = !!window.cmplz_has_consent(category);
            else if (window.cmplz && typeof window.cmplz.hasConsent === 'function') granted = !!window.cmplz.hasConsent(category);
          } catch(e) { granted = false; }
        }
        if (!inEditor && !granted) {
          // Show placeholder once
          if (!el.querySelector('.iu-google-map__placeholder')) {
            // Localized UI texts based on dataset lang or document language
            var uiLang = (function(){
              try {
                var l = (el.getAttribute('data-lang') || document.documentElement.lang || '').toString().trim().toLowerCase();
                if (l.length >= 2) return l.slice(0,2);
              } catch(e){}
              return 'en';
            })();
            function tr(key){
              var M = {
                el: { placeholder: 'Για να εμφανιστεί ο χάρτης, θα πρέπει να αποδεχτείτε τα cookies.', accept: 'Αποδοχή και εμφάνιση' },
                en: { placeholder: 'To display the map, you need to accept cookies.', accept: 'Accept and show' },
                fr: { placeholder: 'Pour afficher la carte, vous devez accepter les cookies.', accept: 'Accepter et afficher' },
                de: { placeholder: 'Um die Karte anzuzeigen, müssen Sie Cookies akzeptieren.', accept: 'Akzeptieren und anzeigen' },
                it: { placeholder: 'Per visualizzare la mappa, è necessario accettare i cookie.', accept: 'Accetta e mostra' },
                es: { placeholder: 'Para mostrar el mapa, debes aceptar las cookies.', accept: 'Aceptar y mostrar' }
              };
              var g = M[uiLang] || M.en;
              return g[key] || M.en[key] || '';
            }
            var ph = document.createElement('div');
            ph.className = 'iu-google-map__placeholder';
            // Height: match canvas computed height if available; else fallback
            (function(){
              try {
                var can = el.querySelector('.iu-google-map__canvas');
                var h = can ? window.getComputedStyle(can).height : '';
                var hv = parseInt(h, 10) || 0;
                if (hv > 0) { ph.style.height = h; }
                else { ph.style.minHeight = (parseInt(el.dataset.fallbackHeight, 10) || 360) + 'px'; }
              } catch(e){ ph.style.minHeight = (parseInt(el.dataset.fallbackHeight, 10) || 360) + 'px'; }
            })();
            // Background: placeholder image cover
            try {
              var img = el.getAttribute('data-placeholder-image');
              if (img) {
                ph.style.backgroundImage = 'url(' + img + ')';
              }
            } catch(e){}
            ph.style.color = '#444';

            var msg = document.createElement('div');
            msg.textContent = el.getAttribute('data-placeholder') || tr('placeholder');
            ph.appendChild(msg);

            var actions = document.createElement('div');
            actions.style.marginTop = '12px';
            ph.appendChild(actions);

            var btnAccept = document.createElement('a');
            btnAccept.setAttribute('role', 'button');
            btnAccept.textContent = el.getAttribute('data-placeholder-button') || tr('accept');
            // Prefer Elementor default button styles if available
            btnAccept.className = 'elementor-button elementor-size-sm';
            // Neutral fallback styles (only spacing/cursor), avoid hard colors
            btnAccept.style.margin = '0 6px';
            btnAccept.style.cursor = 'pointer';
            actions.appendChild(btnAccept);

            btnAccept.addEventListener('click', function(e){
              e.preventDefault();
              var did = false;
              try {
                if (typeof window.cmplz_accept_all === 'function') { window.cmplz_accept_all(); did = true; }
                else if (window.cmplz && typeof window.cmplz.acceptAll === 'function') { window.cmplz.acceptAll(); did = true; }
              } catch(ex){}
              // Re-check after a short delay in case banner updates state
              setTimeout(function(){ boot(); }, 250);
            });

            var canvas = el.querySelector('.iu-google-map__canvas');
            if (!canvas) {
              canvas = document.createElement('div');
              canvas.className = 'iu-google-map__canvas';
              el.appendChild(canvas);
            }
            try { canvas.style.display = 'none'; } catch(e){}
            el.appendChild(ph);
          }
          if (!el.getAttribute('data-cmplz-await')) {
            el.setAttribute('data-cmplz-await', '1');
            var reattempt = function(){ try { el.removeAttribute('data-cmplz-await'); } catch(e){} boot(); };
            // Listen for either category event or any status change (Compat: event can fire later even if added now)
            document.addEventListener('cmplz_event_' + category, reattempt, { once: true });
            document.addEventListener('cmplz_status_change', reattempt, { once: true });
          }
          return; // do not proceed until consent is granted
        }
      } catch(e){}

      el.setAttribute('data-initializing', '1');
      var apiKey = el.dataset.apiKey;
      var lang = el.dataset.lang || '';
      var region = el.dataset.region || '';
      if (!apiKey) {
        // If Elementor frontend config exposes the key, try to use it
        try {
          var k = (window.elementorFrontendConfig && window.elementorFrontendConfig.kits && window.elementorFrontendConfig.kits[0] && window.elementorFrontendConfig.kits[0].settings && window.elementorFrontendConfig.kits[0].settings.google_map_api_key) || '';
          if (k) apiKey = k;
        } catch(e) {}
      }
      if (!apiKey) { console.warn('IU Google Maps: Missing API key'); el.removeAttribute('data-initializing'); return; }
      loadGoogleMaps(apiKey, lang, region)
        .then(function(){ initMap(el); el.setAttribute('data-ready', '1'); el.removeAttribute('data-initializing'); })
        .catch(function(err){
          if (window.console && console.warn) console.warn('IU Google Maps: loader error', err && err.message ? err.message : err);
          // do not mark ready; allow retry after consent or later load
          el.removeAttribute('data-initializing');
        });
    });
  }

  if (document.readyState !== 'loading') boot(); else document.addEventListener('DOMContentLoaded', boot);

  // Observe DOM changes (helps in Elementor editor re-renders)
  try {
    var mo = new MutationObserver(function(muts){
      for (var i=0;i<muts.length;i++){
        var t = muts[i].target;
        if (!(t instanceof Element)) continue;
        if (t.querySelector && t.querySelector('.iu-google-map:not([data-ready="1"])')) { boot(); break; }
      }
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  } catch(e){}

  // Elementor editor support
  if (window.jQuery) {
    jQuery(window).on('elementor/frontend/init', function(){
      if (window.elementorFrontend && elementorFrontend.hooks) {
        var trigger = function($scope){
          var el = $scope && $scope[0] ? $scope[0].querySelector('.iu-google-map') : null;
          if (el) boot();
        };
        elementorFrontend.hooks.addAction('frontend/element_ready/global', trigger);
        elementorFrontend.hooks.addAction('frontend/element_ready/iu_google_maps.default', trigger);
      }
      // Also hook panel events as fallback
      if (window.elementor && window.elementor.hooks && window.elementor.hooks.addAction){
        var handler = function(){ boot(); };
        try {
          elementor.hooks.addAction('panel/open_editor/element', handler);
          elementor.hooks.addAction('panel/open_editor/widget', handler);
          elementor.hooks.addAction('panel/close_editor/element', handler);
        } catch(e){}
      }
    });
  }
})();
