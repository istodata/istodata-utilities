const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const manifest = require('./fixtures/atomic-interactions.json');
const sources = Object.fromEntries(manifest.files.map(file => {
  const data = fs.readFileSync(path.join(__dirname, 'fixtures', 'vendor', file.name));
  assert.equal(crypto.createHash('sha256').update(data).digest('hex'), file.sha256, 'Fixture checksum');
  return [file.name, data.toString()];
}));
const patch = fs.readFileSync(path.join(__dirname, '../assets/js/elementor-atomic-interaction-breakpoints.js'), 'utf8');
const wrap = value => ({ $$type: 'test', value });
const interaction = (trigger = 'scrollIn', excluded = ['mobile', 'tablet'], replay = true) => ({
  $$type: 'interaction-item', value: {
    trigger: wrap(trigger), breakpoints: wrap({ excluded: wrap(excluded.map(wrap)) }),
    animation: wrap({ effect: wrap('fade'), type: wrap('in'), config: wrap({ replay: wrap(replay) }),
      timing_config: wrap({ duration: wrap({ size: 600, unit: 'ms' }) }) })
  }
});
function element(empty = false) {
  const values = empty ? {} : { opacity: '0.8', transform: 'rotate(2deg)', transition: 'color 1s' };
  const priorities = empty ? {} : { opacity: 'important', transform: 'important' };
  const style = new Proxy({
    getPropertyValue: key => values[key] || '', getPropertyPriority: key => priorities[key] || '',
    setProperty: (key, value, priority) => { values[key] = value; priorities[key] = priority || ''; },
    removeProperty: key => { delete values[key]; delete priorities[key]; }
  }, {
    get: (target, key) => key in target ? target[key] : values[key] || '',
    set: (target, key, value) => { values[key] = value; priorities[key] = ''; return true; }
  });
  return { style };
}
function harness({ width = 390, interactions = [interaction()], elements = [element()], enabled = true,
  breakpoints = { mobile: { value: 767, direction: 'max' }, tablet: { value: 1024, direction: 'max' } },
  alter, deferred = false, loading = false, legacy = false } = {}) {
  const listeners = {}, timers = new Map(), subscriptions = new Set(), historical = [], controls = [];
  let timerId = 0, animated = 0, disposed = 0;
  const subscribe = (kind, el, callback) => {
    const record = { kind, el, callback };
    subscriptions.add(record); historical.push(record);
    return () => { if (subscriptions.delete(record)) disposed++; };
  };
  const context = {
    console, innerWidth: width,
    setTimeout: callback => { timers.set(++timerId, callback); return timerId; },
    clearTimeout: id => timers.delete(id),
    addEventListener: (type, callback) => (listeners[type] ||= []).push(callback),
    getComputedStyle: () => ({ transform: 'none' }),
    ElementorInteractionsConfig: { constants: { defaultDuration: 600, defaultDelay: 0, defaultEasing: 'easeIn', slideDistance: 100 }, breakpoints },
    document: {
      readyState: loading ? 'loading' : 'complete',
      addEventListener: (type, callback) => (listeners[type] ||= []).push(callback),
      getElementById: () => legacy ? null : ({ textContent: JSON.stringify([{ elementId: 'test', interactions }]) }),
      querySelectorAll: () => elements
    },
    Motion: {
      animate: (el, frames) => {
        animated++;
        el.style.opacity = String(Array.isArray(frames.opacity) ? frames.opacity.at(-1) : frames.opacity || 1);
        let finish;
        const promise = deferred ? new Promise(resolve => { finish = resolve; }) : Promise.resolve();
        const control = Object.assign(promise, { cancel() { control.cancelled = true; }, finish: finish || (() => {}) });
        controls.push(control); return control;
      },
      inView: (el, callback) => subscribe('inView', el, callback),
      hover: (el, callback) => subscribe('hover', el, callback),
      press: (el, callback) => subscribe('press', el, callback),
      scroll: (control, options) => subscribe('scroll', options.target || elements[0], control)
    }
  };
  elements.forEach(el => { el.getAttribute = () => JSON.stringify(interactions); });
  context.window = context;
  vm.createContext(context);
  vm.runInContext(sources['interactions-shared.js'], context);
  const originalAPI = { ...context.elementorModules.interactions }, originalMotion = { ...context.Motion };
  if (alter) alter(context);
  if (enabled) vm.runInContext(patch, context);
  function flush() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(callback => callback()); }
  function resize(width) { context.innerWidth = width; (listeners.resize || []).forEach(callback => callback()); flush(); }
  function start() { vm.runInContext(sources['interactions-pro.js'], context); }
  function trigger(record) { return record.callback(record.el); }
  return { context, elements, listeners, originalAPI, originalMotion, subscriptions, historical, controls,
    flush, resize, start, trigger, get animated() { return animated; }, get disposed() { return disposed; } };
}
function restored(el, empty = false) {
  assert.equal(el.style.opacity, empty ? '' : '0.8');
  assert.equal(el.style.transform, empty ? '' : 'rotate(2deg)');
  assert.equal(el.style.transition, empty ? '' : 'color 1s');
  assert.equal(el.style.getPropertyPriority('opacity'), empty ? '' : 'important');
  assert.equal(el.style.getPropertyPriority('transform'), empty ? '' : 'important');
}
let cases = 0;
async function test(name, callback) { await callback(); cases++; console.log('PASS: ' + name); }
(async () => {
  await test('OFF preserves native API and reproduces vendor breakpoint bug', () => {
    const h = harness({ enabled: false }); h.start();
    assert.equal(h.subscriptions.size, 1);
    assert.equal(h.context.elementorModules.interactions.skipInteraction, h.originalAPI.skipInteraction);
  });
  await test('excluded mobile/tablet, Desktop execution and repeated resize restore styles', async () => {
    const h = harness(); h.start(); assert.equal(h.subscriptions.size, 0);
    for (let i = 0; i < 15; i++) {
      h.resize(1400); assert.equal(h.subscriptions.size, 1);
      h.trigger([...h.subscriptions][0]); await Promise.resolve();
      h.resize(i % 2 ? 820 : 390); assert.equal(h.subscriptions.size, 0); restored(h.elements[0]);
    }
    assert.equal(h.animated, 15); assert.equal(h.disposed, 15);
    h.resize(1400); const count = h.historical.length; h.resize(1500); assert.equal(h.historical.length, count);
  });
  for (const trigger of ['hover', 'click', 'scrollOn', 'scrollOut', 'load']) {
    await test(trigger + ': native registration, execution, cleanup and restoration', async () => {
      const h = harness({ interactions: [interaction(trigger)] }); h.start(); assert.equal(h.subscriptions.size, 0);
      h.resize(1400);
      if (['hover', 'click', 'scrollOut'].includes(trigger)) h.trigger([...h.subscriptions][0]);
      assert.ok(h.animated > 0); await Promise.resolve(); h.resize(390);
      assert.equal(h.subscriptions.size, 0); restored(h.elements[0]);
      for (let i = 0; i < 8; i++) { h.resize(1400); assert.ok(h.subscriptions.size <= 1); h.resize(820); }
      assert.equal(h.subscriptions.size, 0); restored(h.elements[0]);
    });
  }
  await test('custom unordered max/min breakpoints, boundaries, independent exclusions and multiple elements', () => {
    const h = harness({ width: 500, elements: [element(), element(true)],
      interactions: [interaction('hover', ['phone', 'wide']), interaction('click', ['desktop', 'pad'])],
      breakpoints: { wide: { value: 1600, direction: 'min' }, pad: { value: 1100, direction: 'max' }, phone: { value: 600, direction: 'max' }, huge: { value: 2000, direction: 'min' } } });
    h.start(); assert.equal(h.subscriptions.size, 2); assert.equal([...h.subscriptions][0].kind, 'press');
    h.resize(600); assert.equal(h.subscriptions.size, 2);
    h.resize(601); assert.equal(h.subscriptions.size, 2); assert.equal([...h.subscriptions][0].kind, 'hover');
    h.resize(1100); assert.equal(h.subscriptions.size, 2);
    h.resize(1101); assert.equal(h.subscriptions.size, 2);
    h.resize(1600); assert.equal(h.subscriptions.size, 2); assert.equal([...h.subscriptions][0].kind, 'press');
    h.resize(2000); assert.equal(h.subscriptions.size, 4);
    h.resize(500); assert.equal(h.subscriptions.size, 2);
  });
  await test('missing/invalid/read-only APIs and duplicate workaround install are no-ops', () => {
    for (const alter of [c => { delete c.elementorModules; },
      c => { c.ElementorInteractionsConfig.breakpoints.mobile.value = '767'; },
      ...['skipInteraction', 'waitForAnimateFunction', 'getAnimateFunction', 'getInViewFunction', 'getTransformBaselineFromComputedStyle'].map(key => c => { c.elementorModules.interactions[key] = null; }),
      c => { Object.defineProperty(c.elementorModules.interactions, 'skipInteraction', { writable: false }); },
      c => { c.elementorModules.interactions.avraBreakpointFix = true; }]) {
      const h = harness({ enabled: false, alter });
      const api = h.context.elementorModules && h.context.elementorModules.interactions;
      const before = api && { ...api };
      vm.runInContext(patch, h.context);
      if (api) Object.keys(before).forEach(key => assert.equal(api[key], before[key]));
      assert.equal((h.listeners.resize || []).length, 0);
    }
    const h = harness(); const api = { ...h.context.elementorModules.interactions };
    vm.runInContext(patch, h.context); Object.keys(api).forEach(key => assert.equal(h.context.elementorModules.interactions[key], api[key]));
    assert.equal(h.listeners.resize.length, 1);
    h.start(); assert.equal(h.listeners.resize.length, 2, 'one Kit and one native resize listener');
    for (let i = 0; i < 8; i++) { h.resize(1400); h.resize(390); }
    assert.equal(h.listeners.resize.length, 2);
  });
  await test('stale trigger, exit and animation completion callbacks cannot write styles', async () => {
    const h = harness({ width: 1400, deferred: true }); h.start();
    const old = [...h.subscriptions][0], exit = h.trigger(old);
    h.resize(390); restored(h.elements[0]); assert.equal(h.controls[0].cancelled, true);
    const count = h.animated; h.trigger(old); exit(); assert.equal(h.animated, count);
    h.controls.forEach(control => control.finish()); await Promise.resolve(); await Promise.resolve();
    restored(h.elements[0]);
  });
  await test('empty initial styles are removed and once-only cleanup survives native disposal', async () => {
    const h = harness({ width: 1400, elements: [element(true)], interactions: [interaction('scrollIn', ['mobile', 'tablet'], false)] });
    h.start(); h.trigger([...h.subscriptions][0]); assert.equal(h.subscriptions.size, 0);
    await Promise.resolve(); h.resize(390); assert.equal(h.disposed, 1); restored(h.elements[0], true);
  });
  await test('other Motion consumers keep original API, subscriptions and controls', () => {
    const h = harness(); const el = element();
    const api = h.context.elementorModules.interactions;
    assert.equal(api.getAnimateFunction(), h.originalMotion.animate); assert.equal(api.getInViewFunction(), h.originalMotion.inView);
    const stop = h.context.Motion.hover(el, () => {}); const control = h.context.Motion.animate(el, {});
    h.start(); h.resize(1400); h.resize(390);
    assert.equal(h.subscriptions.size, 1); assert.equal(control.cancelled, undefined);
    ['hover', 'press', 'scroll'].forEach(key => assert.equal(h.context.Motion[key], h.originalMotion[key])); stop();
  });
  await test('DOMContentLoaded and legacy data-interactions use the native initializer', () => {
    const h = harness({ loading: true, legacy: true }); h.start();
    assert.equal(h.subscriptions.size, 0); h.listeners.DOMContentLoaded.forEach(cb => cb());
    h.resize(1400); assert.equal(h.subscriptions.size, 1); h.resize(390); restored(h.elements[0]);
  });
  await test('asynchronous Motion arrival uses the native wait and complete API preflight', () => {
    const h = harness({ alter: c => { c.Motion = undefined; } }); h.start();
    assert.equal(h.subscriptions.size, 0);
    h.context.Motion = { ...h.originalMotion }; h.flush(); assert.equal(h.subscriptions.size, 0);
    h.resize(1400); assert.equal(h.subscriptions.size, 1); h.resize(390); restored(h.elements[0]);
  });
  await test('incomplete Motion API uses entirely native behavior without subscription wrappers', () => {
    for (const key of ['inView', 'hover', 'press', 'scroll']) {
      const h = harness({ alter: c => { c.Motion[key] = null; } }); h.start();
      assert.equal(h.subscriptions.size, 0);
      h.resize(1400); assert.equal(h.subscriptions.size, 0);
      ['hover', 'press', 'scroll'].filter(name => name !== key).forEach(name => assert.equal(h.context.Motion[name], h.originalMotion[name]));
    }
    const h = harness({ alter: c => { Object.defineProperty(c.Motion, 'hover', { writable: false }); } });
    h.start(); assert.equal(h.subscriptions.size, 1, 'unpatchable Motion keeps the native breakpoint bug');
    h.resize(1400); assert.equal(h.subscriptions.size, 1);
  });
  await test('initializer failure restores styles and the original Motion subscription APIs', () => {
    const h = harness({ width: 1400, interactions: [interaction('hover'), interaction('scrollIn')],
      alter: c => { c.Motion.inView = () => { throw new Error('fixture initializer failure'); }; } });
    assert.throws(() => h.start(), /fixture initializer failure/);
    assert.equal(h.subscriptions.size, 0); restored(h.elements[0]);
    ['hover', 'press', 'scroll'].forEach(key => assert.equal(h.context.Motion[key], h.originalMotion[key]));
    h.resize(390); assert.equal(h.subscriptions.size, 0);
  });
  console.log(`PASS: ${cases} runtime cases with checksum-verified shared + Pro bundles.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
