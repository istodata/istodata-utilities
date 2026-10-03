// Staging only: Greek phone branch must omit the desktop fragment and keep the popup menu usable.
const { chromium } = require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const BASE = 'https://wordpress-218158-6702910.cloudwaysapps.com/';
const UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 }, userAgent: UA });
    const url = `${BASE}?iu_kit_fragment_test=26fa46c6-20260930-a7d4e2&iu_kit_request=${Date.now()}`;
    const response = await page.goto(url, { waitUntil: 'load', timeout: 180000 });
    const html = await response.text();
    if (html.includes('data-id="26fa46c6"')) throw new Error('desktop target leaked onto Greek phone');
    const trigger = page.locator('[data-id="da0fc94"] a[href*="popup%3Aopen"]').first();
    await page.mouse.move(100, 400);
    const preferences = page.locator('#cmplz-cookiebanner-container .cmplz-view-preferences');
    await preferences.waitFor({state:'visible'});
    await preferences.click();
    await page.locator('#cmplz-cookiebanner-container .cmplz-save-preferences').click();
    await trigger.click();
    const popup = page.locator('#elementor-popup-modal-6461');
    await popup.waitFor({ state: 'visible', timeout: 30000 });
    const links = popup.locator('a[href^="https://wordpress-218158-6702910.cloudwaysapps.com/"]');
    const count = await links.count();
    if (!count) throw new Error('mobile menu popup has no internal links');
    const href = await links.first().getAttribute('href');
    const navigation = page.waitForNavigation({ waitUntil: 'commit', timeout: 90000 });
    await links.first().click({noWaitAfter:true});
    const next = await navigation;
    if(next?.status() !== 200) throw new Error('Mobile menu navigation failed');
    console.log(JSON.stringify({ status: response.status(), xCache: response.headers()['x-cache'], marker: html.match(/IU_KIT_FRAGMENT_TEST[^>]*-->/)?.[0], popupVisible: true, links: count, href, navigationStatus: next.status(), finalUrl: page.url() }));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
