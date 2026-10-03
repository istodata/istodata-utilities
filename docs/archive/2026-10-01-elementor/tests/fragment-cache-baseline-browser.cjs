const { chromium } = require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36';
(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const context = await browser.newContext({viewport:{width:1440,height:900},userAgent:UA});
  const page = await context.newPage();
  const errors=[];
  page.on('pageerror', e=>errors.push(e.message));
  const response=await page.goto('https://wordpress-218158-6702910.cloudwaysapps.com/', {waitUntil:'load',timeout:180000});
  console.log(JSON.stringify({stage:'loaded',status:response.status(),environment:await page.evaluate(()=>({ua:navigator.userAgent,width:innerWidth,height:innerHeight,widgets:[...document.querySelectorAll('[data-widget_type="mega-menu.default"]')].map(e=>({id:e.dataset.id,rect:e.getBoundingClientRect().toJSON(),titles:[...e.querySelectorAll('.e-n-menu-title')].map(t=>({tag:t.tagName,id:t.id,text:t.textContent.trim().slice(0,80),rect:t.getBoundingClientRect().toJSON()}))}))})),errors}));
  const widget=page.locator('[data-id="26fa46c6"]');
  const title=widget.locator('#e-n-menu-title-6532');
  console.log(JSON.stringify({stage:'trigger-markup',html:await title.evaluate(e=>e.outerHTML.slice(0,5000))}));
  const button=widget.locator('#e-n-menu-dropdown-icon-6532');
  const content=widget.locator('#e-n-menu-content-6532');
  async function state(stage){console.log(JSON.stringify({stage,expanded:await button.getAttribute('aria-expanded'),panel:await content.evaluate(e=>({display:getComputedStyle(e).display,rect:e.getBoundingClientRect().toJSON(),links:e.querySelectorAll('a[href]').length})),initialization:await page.evaluate(()=>({lazyScripts:document.querySelectorAll('script[type="rocketlazyloadscript"]').length,widgetData:Object.keys(window.jQuery?.('[data-id="26fa46c6"]').data()||{}),buttonEvents:Object.keys(window.jQuery?._data(document.querySelector('#e-n-menu-dropdown-icon-6532'),'events')||{}),titleEvents:Object.keys(window.jQuery?._data(document.querySelector('#e-n-menu-title-6532'),'events')||{})})),errors}));}
  await state('before');
  const trigger=title.locator('.e-n-menu-title-container');
  await trigger.hover();
  await page.waitForTimeout(600);
  await state('hover');
  const box=await trigger.boundingBox();
  console.log(JSON.stringify({stage:'hit-target',box,hit:await page.evaluate(({x,y})=>{const e=document.elementFromPoint(x,y);return e?.outerHTML.slice(0,1000)}, {x:box.x+box.width/2,y:box.y+box.height/2})}));
  if(await button.getAttribute('aria-expanded')!=='true'){
    await button.click({noWaitAfter:true});
    await page.waitForTimeout(600);
  await state('button-click');
  }
  if(await button.getAttribute('aria-expanded')!=='true'){
    await page.mouse.move(400,400);
    await page.waitForTimeout(5000);
    await state('after-initial-interaction-wait');
    console.log(JSON.stringify({stage:'consent-buttons',buttons:await page.locator('#cmplz-cookiebanner-container button').evaluateAll(es=>es.map(e=>({text:e.textContent.trim(),classes:e.className,display:getComputedStyle(e).display,rect:e.getBoundingClientRect().toJSON()})))}));
    await page.screenshot({path:'tests/fragment-consent-baseline.png'});
    const deny=page.locator('#cmplz-cookiebanner-container .cmplz-deny');
    if(await deny.isVisible()) await deny.click();
    else {
      await page.locator('#cmplz-cookiebanner-container .cmplz-view-preferences').click();
      await page.locator('#cmplz-cookiebanner-container .cmplz-save-preferences').click();
    }
    await trigger.hover();
    await page.waitForTimeout(600);
    await state('second-hover');
  }
  if(await button.getAttribute('aria-expanded')!=='true') throw new Error('Baseline dropdown remained closed');
  const bounds=await content.boundingBox();
  await page.screenshot({path:'tests/fragment-baseline.png',clip:bounds});
  await state('after-screenshot');
  const link=content.locator('a[href^="https://wordpress-218158-6702910.cloudwaysapps.com/"]').first();
  const destination=await link.getAttribute('href');
  const target=await link.getAttribute('target');
  console.log(JSON.stringify({stage:'navigation-link',destination,target}));
  const navigation=page.waitForNavigation({waitUntil:'commit',timeout:90000});
  await link.click({noWaitAfter:true});
  const next=await navigation;
  console.log(JSON.stringify({stage:'navigation',destination,url:page.url(),status:next?.status(),errors}));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
