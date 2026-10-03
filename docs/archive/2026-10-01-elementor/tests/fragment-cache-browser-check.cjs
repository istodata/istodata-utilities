// Temporary staging browser check. Uses a separate anonymous Chrome context.
const { chromium } = require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const sharp = require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/sharp');
const { randomUUID, createHash } = require('node:crypto');

const BASE = 'https://wordpress-218158-6702910.cloudwaysapps.com/';
const PROBE = process.env.IU_FRAGMENT_PROBE || 'iu_fragment_probe=26fa46c6-20260930-a7d4e2';
const REQUEST_KEY = process.env.IU_FRAGMENT_REQUEST_KEY || 'iu_probe_request';
const generation = process.env.IU_FRAGMENT_GENERATION || randomUUID();
const suffix = PROBE.startsWith('iu_kit_') ? `&iu_kit_generation=${generation}` : '';
const MISS = `${BASE}?${PROBE}&${REQUEST_KEY}=${process.env.IU_FRAGMENT_MISS || randomUUID()}${suffix}`;
const HIT = `${BASE}?${PROBE}&${REQUEST_KEY}=${process.env.IU_FRAGMENT_HIT || randomUUID()}${suffix}`;
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36';

async function inspect(page, label, url) {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 180000 });
  await page.waitForLoadState('load', { timeout: 90000 });
  const source = await response.text();
  const marker = source.match(/IU_(?:FRAGMENT_PROBE|KIT_FRAGMENT_TEST)[^>]*-->/)?.[0] || null;
  if (PROBE.startsWith('iu_kit_')) {
    const expected = label === 'miss' ? 'target=1 nested=718 loop=150' : 'target=0 nested=0 loop=0';
    if (!marker?.includes(expected) || response.headers()['x-cache'] !== 'MISS') {
      throw new Error(`${label}: expected origin ${expected}; got ${marker}`);
    }
  }
  const widget = page.locator('[data-id="26fa46c6"]');
  const title = widget.locator('#e-n-menu-title-6532');
  const button = widget.locator('#e-n-menu-dropdown-icon-6532');
  const content = widget.locator('#e-n-menu-content-6532');
  const sourceData = await page.evaluate(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return { html: doc.querySelector('[data-id="26fa46c6"]').outerHTML,
      assets: [...doc.querySelectorAll('script[src],link[rel="stylesheet"][href]')]
        .map(e => e.src || e.href).sort() };
  }, source);
  const fragmentHash = createHash('sha256').update(sourceData.html).digest('hex');
  const before = await button.getAttribute('aria-expanded');
  const js = await page.evaluate(() => ({
    jquery: typeof window.jQuery,
    elementor: typeof window.elementorFrontend,
    consent: document.querySelector('.cmplz-cookiebanner')?.className || null,
  }));
  await page.evaluate(() => document.fonts.ready);
  // load is earlier than asynchronous Elementor handler attachment. Activate
  // the normal user-interaction path and wait for the real handler, not URLs.
  await page.mouse.move(400, 400);
  await page.waitForFunction(() => !!window.jQuery?._data(
    document.querySelector('#e-n-menu-title-6532'), 'events')?.mouseover);
  const preferences = page.locator('#cmplz-cookiebanner-container .cmplz-view-preferences');
  await preferences.waitFor({ state: 'visible' });
  await preferences.click();
  await page.locator('#cmplz-cookiebanner-container .cmplz-save-preferences').click();
  await page.waitForFunction(() => !document.querySelector('#cmplz-cookiebanner-container.cmplz-show'));
  await title.locator('.e-n-menu-title-container').hover();
  await page.waitForTimeout(500);
  const hover = await button.getAttribute('aria-expanded');
  if (errors.length) {
    throw new Error(`${label}: JavaScript errors: ${errors.join('; ')}`);
  }
  const panel = await content.evaluate(el => ({
    display: getComputedStyle(el).display,
    visibility: getComputedStyle(el).visibility,
    height: el.getBoundingClientRect().height,
    links: el.querySelectorAll('a[href]').length,
  }));
  console.log(JSON.stringify({ label, stage: 'before-screenshot', before, hover, panel, js, marker, fragmentHash, assets: sourceData.assets.length, errors }));
  if (hover !== 'true' || panel.display === 'none' || panel.height < 100) {
    throw new Error(`${label}: menu failed to open`);
  }
  const bounds = await content.boundingBox();
  if (!bounds || bounds.height < 100) throw new Error(`${label}: visible panel has no bounds`);
  const shot = await page.screenshot({ clip: bounds, animations: 'disabled' });
  if (await button.getAttribute('aria-expanded') !== 'true') throw new Error(`${label}: screenshot closed panel`);
  const firstLink = content.locator('a[href^="https://wordpress-218158-6702910.cloudwaysapps.com/"]').first();
  const destination = await firstLink.getAttribute('href');
  const navigation = page.waitForNavigation({ waitUntil: 'commit', timeout: 90000 });
  await firstLink.click({ noWaitAfter: true });
  const next = await navigation;
  if (next?.status() !== 200) throw new Error(`${label}: navigation did not return 200`);
  console.log(JSON.stringify({ label, status: response.status(), xCache: response.headers()['x-cache'], marker, destination, finalUrl: page.url(), navigationStatus: next.status(), errors }));
  return { shot, fragmentHash, assets: sourceData.assets };
}

async function compareImages(left, right) {
  const a = await sharp(left).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const b = await sharp(right).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  if (a.info.width !== b.info.width || a.info.height !== b.info.height) {
    throw new Error(`Panel screenshot dimensions differ: ${a.info.width}x${a.info.height} vs ${b.info.width}x${b.info.height}`);
  }
  let differentPixels = 0;
  for (let i = 0; i < a.data.length; i += 4) {
    if (Math.max(...[0, 1, 2, 3].map(channel => Math.abs(a.data[i + channel] - b.data[i + channel]))) > 8) {
      differentPixels++;
    }
  }
  const pixels = a.info.width * a.info.height;
  console.log(JSON.stringify({ visual: 'menu-panel', width: a.info.width, height: a.info.height, differentPixels, pixels, differentPercent: (differentPixels / pixels * 100).toFixed(4) }));
}

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    const missContext = await browser.newContext({ viewport: { width: 1440, height: 900 }, userAgent: UA });
    const miss = await inspect(await missContext.newPage(), 'miss', MISS);
    const hitContext = await browser.newContext({ viewport: { width: 1440, height: 900 }, userAgent: UA });
    const hit = await inspect(await hitContext.newPage(), 'hit', HIT);
    if (miss.fragmentHash !== hit.fragmentHash || JSON.stringify(miss.assets) !== JSON.stringify(hit.assets)) {
      throw new Error('Fragment or external assets differ');
    }
    await compareImages(miss.shot, hit.shot);
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
