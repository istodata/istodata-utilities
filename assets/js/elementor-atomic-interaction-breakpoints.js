/* Temporary internal-API workaround for #35831. PHP gates versions through
 * includes/elementor-compatibility.php; this runtime retains API/shape preflight. */
(function () {
  'use strict';
  var api = window.elementorModules && window.elementorModules.interactions;
  var config = window.ElementorInteractionsConfig;
  var motion;
  var keys = ['skipInteraction', 'waitForAnimateFunction', 'getAnimateFunction',
    'getInViewFunction', 'getTransformBaselineFromComputedStyle'];
  if (typeof Proxy !== 'function' || typeof Reflect !== 'object' || typeof Reflect.get !== 'function'
      || typeof Reflect.set !== 'function' || typeof Map !== 'function' || typeof Set !== 'function'
      || typeof Promise !== 'function') return;
  // Validate everything before replacing anything. Also avoid a second copy of the Avra workaround.
  if (!api || api.iuAtomicBreakpointFix || api.avraBreakpointFix || !Object.isExtensible(api)
      || !config || !config.breakpoints || typeof config.breakpoints !== 'object'
      || Array.isArray(config.breakpoints)) return;
  function writable(object, key) {
    var descriptor = Object.getOwnPropertyDescriptor(object, key);
    return descriptor && descriptor.writable === true && typeof object[key] === 'function';
  }
  if (!keys.every(function (key) { return writable(api, key); })
      || typeof window.addEventListener !== 'function'
      || typeof window.setTimeout !== 'function' || typeof window.clearTimeout !== 'function') return;
  var entries = Object.entries(config.breakpoints);
  if (!entries.every(function (entry) {
    var item = entry[1];
    return item && Number.isFinite(item.value) && (item.direction === 'max' || item.direction === 'min');
  })) return;
  var max = entries.filter(function (entry) { return entry[1].direction === 'max'; })
    .sort(function (a, b) { return a[1].value - b[1].value; });
  var min = entries.filter(function (entry) { return entry[1].direction === 'min'; })
    .sort(function (a, b) { return b[1].value - a[1].value; });
  function breakpoint() {
    var width = window.innerWidth;
    for (var i = 0; i < max.length; i++) if (width <= max[i][1].value) return max[i][0];
    for (var j = 0; j < min.length; j++) if (width >= min[j][1].value) return min[j][0];
    return 'desktop';
  }
  var native = {};
  keys.forEach(function (key) { native[key] = api[key]; });
  var initialize = null, collecting = false, generation = 0, initialized = false, ready = false;
  var cleanups = new Set(), controls = new Set(), baselines = new Map();
  var activeBreakpoint = breakpoint(), resizeTimer;

  function rememberElement(element) {
    if (!collecting || !element || !element.style || baselines.has(element)) return;
    var saved = {};
    ['transition', 'transform', 'opacity'].forEach(function (property) {
      saved[property] = [element.style.getPropertyValue(property), element.style.getPropertyPriority(property)];
    });
    baselines.set(element, saved);
  }
  function trackedCleanup(dispose) {
    if (typeof dispose !== 'function') return dispose;
    var active = true;
    function once() {
      if (!active) return;
      active = false;
      cleanups.delete(once);
      dispose();
    }
    cleanups.add(once);
    return once;
  }
  // Guard both trigger callbacks and their returned leave/end callbacks.
  function guardedCallback(callback, token) {
    return function () {
      if (token !== generation) return;
      var result = callback.apply(this, arguments);
      return typeof result === 'function' ? guardedCallback(result, token) : result;
    };
  }
  function subscription(original, receiver, token) {
    return function () {
      if (token !== generation) return function () {};
      var args = Array.prototype.slice.call(arguments);
      if (typeof args[1] === 'function') args[1] = guardedCallback(args[1], token);
      return trackedCleanup(original.apply(receiver, args));
    };
  }
  // Preserve Motion controls (scroll receives these), but suppress stale completion style writes.
  function guardedControl(control, token) {
    if (!control || typeof control.then !== 'function') return control;
    return new Proxy(control, {
      get: function (target, key) {
        if (key === 'then') return function (done, failed) {
          return target.then(function (value) {
            if (token === generation && typeof done === 'function') return done(value);
          }, function (error) {
            if (token === generation && typeof failed === 'function') return failed(error);
          });
        };
        var value = Reflect.get(target, key, target);
        return typeof value === 'function' ? value.bind(target) : value;
      },
      set: function (target, key, value) { return Reflect.set(target, key, value, target); }
    });
  }
  function disposeInteractions() {
    generation++;
    Array.from(cleanups).forEach(function (dispose) { dispose(); });
    controls.forEach(function (control) {
      if (typeof control.cancel === 'function') control.cancel();
      else if (typeof control.stop === 'function') control.stop();
    });
    controls.clear();
    var disposed = new Map(baselines), token = generation;
    function restore(saved, element) {
      Object.keys(saved).forEach(function (property) {
        var value = saved[property];
        if (value[0]) element.style.setProperty(property, value[0], value[1]);
        else element.style.removeProperty(property);
      });
    }
    disposed.forEach(restore);
    baselines.clear();
    // Motion timeline teardown queues one final render. Restore excluded
    // elements after that frame; never overwrite a newly initialized element.
    if (typeof window.requestAnimationFrame === 'function') window.requestAnimationFrame(function () {
      if (token !== generation) return;
      disposed.forEach(function (saved, element) {
        if (!baselines.has(element)) restore(saved, element);
      });
    });
    initialized = false;
  }
  function run() {
    if (!initialize || initialized) return;
    motion = window.Motion;
    // Motion can arrive asynchronously. Until its complete API is ready all wrappers
    // delegate to native behavior, so a missing API never enables a partial workaround.
    ready = !!motion && ['hover', 'press', 'scroll'].every(function (key) { return writable(motion, key); })
      && typeof motion.animate === 'function' && typeof motion.inView === 'function';
    if (!ready) {
      initialize();
      initialize = null;
      return;
    }
    var originals = {};
    // Only the synchronous native initializer captures these wrappers. Other Motion users
    // retain their original functions and their subscriptions are never collected here.
    ['hover', 'press', 'scroll'].forEach(function (name) {
      originals[name] = motion[name];
      motion[name] = subscription(originals[name], motion, generation);
    });
    collecting = true;
    initialized = true;
    try { initialize(); } catch (error) {
      disposeInteractions();
      initialize = null;
      ready = false;
      throw error;
    } finally {
      collecting = false;
      Object.keys(originals).forEach(function (name) { motion[name] = originals[name]; });
    }
  }
  var replacements = {
    skipInteraction: function (interaction) {
      if (!ready) return native.skipInteraction.apply(this, arguments);
      var excluded = interaction && interaction.breakpoints && interaction.breakpoints.excluded;
      return Array.isArray(excluded) && excluded.indexOf(breakpoint()) !== -1;
    },
    getTransformBaselineFromComputedStyle: function (element) {
      rememberElement(element);
      return native.getTransformBaselineFromComputedStyle.apply(this, arguments);
    },
    getAnimateFunction: function () {
      var animate = native.getAnimateFunction.apply(this, arguments);
      if (!collecting || typeof animate !== 'function') return animate;
      var token = generation;
      return function () {
        if (token !== generation) return guardedControl(Promise.resolve(), token);
        var control = animate.apply(motion, arguments);
        if (control) {
          controls.add(control);
          // A scroll-driven control can report completion and later be driven
          // again by its timeline. Keep it until the subscription is disposed.
          if (!(arguments[2] && arguments[2].autoplay === false) && typeof control.then === 'function') {
            control.then(function () { controls.delete(control); }, function () { controls.delete(control); });
          }
        }
        return guardedControl(control, token);
      };
    },
    getInViewFunction: function () {
      var inView = native.getInViewFunction.apply(this, arguments);
      return collecting && typeof inView === 'function' ? subscription(inView, motion, generation) : inView;
    },
    waitForAnimateFunction: function (callback, retries) {
      // The verified Pro bundle has one initializer. Do not capture other consumers.
      if (typeof callback !== 'function' || (initialize && initialize !== callback)) {
        return native.waitForAnimateFunction.apply(this, arguments);
      }
      return native.waitForAnimateFunction(function () {
        if (initialize && initialize !== callback) return callback();
        initialize = callback;
        activeBreakpoint = breakpoint();
        run();
      }, retries);
    }
  };
  // Preflight above ensures ordinary writable properties; roll back if installation fails.
  try {
    keys.forEach(function (key) { api[key] = replacements[key]; });
    api.iuAtomicBreakpointFix = true;
  } catch (error) {
    keys.forEach(function (key) { api[key] = native[key]; });
    return;
  }
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      var next = breakpoint();
      if (next === activeBreakpoint || !initialize) return;
      activeBreakpoint = next;
      disposeInteractions();
      run();
    }, 120);
  });
}());
