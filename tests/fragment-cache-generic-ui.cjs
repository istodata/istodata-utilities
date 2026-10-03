const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict'),{randomUUID}=require('node:crypto');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
const mobileUA='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1';
const session=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
const parse=async r=>JSON.parse((await r.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);
(async()=>{const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'}),report=[];
try {for(const language of ['el','en'])for(const mobile of [false,true]){
 const query=`iu_kit_fragment_test=cross-page-20261001&iu_kit_generation=${randomUUID()}`;
 const source=language==='en'?'/en/':'/',consumer=language==='en'?'/en/the-company/':'/techniki-ypostirixi/';
 const options=mobile?{viewport:{width:390,height:844},isMobile:true,hasTouch:true,userAgent:mobileUA}:{viewport:{width:1440,height:1024}};
 const guest=await browser.newContext(options);const warm=await guest.request.get(BASE+source+'?'+query+'&iu_kit_request='+randomUUID(),{timeout:180000});
 const miss=await parse(warm);assert(miss.events.every(e=>e.result==='stored'),JSON.stringify(miss.events));await guest.close();
 const ctx=await browser.newContext(options);await ctx.addCookies(['','auth_','secure_'].map(prefix=>({name:session[prefix+'name'],value:session[prefix+'value'],url:BASE})));
 const page=await ctx.newPage(),errors=[],failedAssets=[];page.on('pageerror',e=>errors.push(e.message));page.on('response',r=>{if(r.status()>=400&&['script','stylesheet'].includes(r.request().resourceType()))failedAssets.push(r.url());});
 async function load(){const r=await page.goto(BASE+consumer+'?'+query+'&iu_kit_request='+randomUUID(),{waitUntil:'load',timeout:180000});const d=await parse(r);
  assert(d.events.every(e=>e.result==='hit'),JSON.stringify(d.events));assert.equal(d.loop_any,0);assert.deepEqual(d.template_renders,[]);assert.deepEqual(d.proof_renders,[]);
  const prefs=page.locator('#cmplz-cookiebanner-container .cmplz-view-preferences');if(await prefs.isVisible()){await prefs.click();await page.locator('.cmplz-save-preferences').click();}return d;
 }
 const hit=await load();const proof=page.locator('[data-id="iu-custom-proof"] .iu-generic-proof-button');
 await page.waitForFunction(()=>document.querySelector('.iu-generic-proof-button')?.dataset.initialized==='yes');
 assert.equal(await proof.evaluate(el=>getComputedStyle(el).getPropertyValue('--iu-proof-ready').trim()),'1');await proof.click();assert.equal(await proof.getAttribute('aria-pressed'),'true');
 assert.equal(await page.locator('[data-id="iu-custom-proof"]').getAttribute('data-widget_type'),'iu-generic-proof.default');
 assert((await page.locator('[data-id="iu-custom-proof"]').getAttribute('data-settings')).includes('proof_enabled'));
 const interactions=[];
 if(mobile){
  assert.equal(hit.target,0);assert(!hit.events.some(e=>['417d1ae','c60daee','a1ff108','e62c115','c28398e'].includes(e.id)));
  const menu=page.locator('[data-id="iu-mobile-menu"]');assert.equal(await menu.count(),1,'Mobile WP Menu fixture missing');
  const toggle=menu.locator('.elementor-menu-toggle');await toggle.click();await page.waitForFunction(()=>document.querySelector('[data-id="iu-mobile-menu"] .elementor-menu-toggle')?.getAttribute('aria-expanded')==='true');
  const link=menu.locator('.elementor-nav-menu--dropdown a[href^="'+BASE+'/"]:visible').first();await link.waitFor({state:'visible',timeout:10000});
  const nav=page.waitForNavigation({waitUntil:'commit',timeout:90000});await link.click({noWaitAfter:true});assert.equal((await nav).status(),200);interactions.push({id:'iu-mobile-menu',toggle:true,navigation:200});
  await load();const open=page.locator('.elementor-location-header a[href^="#elementor-action"]:visible').nth(1);await open.click();
  const modal=page.locator('.elementor-popup-modal:visible');await modal.waitFor({state:'visible',timeout:90000});assert(await modal.locator('a[href^="'+BASE+'/"]:visible').first().isVisible());
  interactions.push({native_phone_popup_open:true});
 }else{
  assert.equal(hit.target,1);assert(!hit.nav_renders.some(id=>['d7ebc85','a1ff108','e62c115','c28398e'].includes(id)));
  for(const id of ['d7ebc85','a1ff108','e62c115','c28398e']){
   await load();const menu=page.locator('[data-id="'+id+'"]');
   if(language==='en'&&id==='d7ebc85'&&await menu.count()===0){interactions.push({id,native_english_menu_empty:true});continue;}
   assert.equal(await menu.count(),1);
   const contentID=await menu.evaluate(el=>el.closest('[id^="e-n-menu-content-"]')?.id||null);
   if(contentID){const button=page.locator('[aria-controls="'+contentID+'"]');const titleID=await button.evaluate(el=>el.closest('.e-n-menu-title').id);
    await page.waitForFunction(id=>!!window.jQuery?._data(document.getElementById(id),'events')?.mouseover,titleID,{timeout:90000});
    await page.locator('#'+titleID+' .e-n-menu-title-container').hover();await page.waitForFunction(id=>document.getElementById(id)?.getAttribute('aria-expanded')==='true',await button.getAttribute('id'));
   }
   const link=menu.locator('a[href^="'+BASE+'/"]:visible').first();assert(await link.isVisible(),'Cached menu link invisible '+id);
   const nav=page.waitForNavigation({waitUntil:'commit',timeout:90000});await link.click({noWaitAfter:true});assert.equal((await nav).status(),200);
   interactions.push({id,outer_dropdown_opened:!!contentID,navigation:200});
  }
 }
 assert.deepEqual(errors,[]);assert.deepEqual(failedAssets,[]);await page.screenshot({path:`docs/fragment-cache-generic-${language}-${mobile?'mobile':'desktop'}.png`});
 report.push({language,mobile,miss_events:miss.events,hit_events:hit.events,hit_original_nav_renders:hit.nav_renders,custom_css_js_frontend_settings_verified:true,interactions,errors,failedAssets});
 fs.writeFileSync('docs/fragment-cache-generic-ui.json',JSON.stringify(report,null,2)+'\n');await ctx.close();console.log('PASS',language,mobile?'mobile':'desktop');
}}finally{await browser.close();}})().catch(e=>{console.error(e.stack);process.exit(1)});
