const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),assert=require('node:assert/strict'),{randomUUID}=require('node:crypto');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
(async()=>{const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});const out=[];
try{for(const lang of ['el','en']){
 const ctx=await browser.newContext({viewport:{width:390,height:844},isMobile:true,hasTouch:true,userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'});
 const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
 const r=await page.goto(BASE+(lang==='en'?'/en/':'/')+'?iu_kit_fragment_test=cross-page-20261001&iu_kit_generation='+randomUUID()+'&iu_kit_request='+randomUUID(),{waitUntil:'load',timeout:180000});
 const d=JSON.parse((await r.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(d.loop_any,0);assert.equal(d.target,0);assert.deepEqual(d.events,[]);
 const prefs=page.locator('.cmplz-view-preferences');if(await prefs.isVisible()){await prefs.click();await page.locator('.cmplz-save-preferences').click();}
 const open=page.locator('.elementor-location-header a[href^="#elementor-action"]:visible').nth(1);assert(await open.isVisible(),'Phone popup menu launcher absent');await open.click();
 const modal=page.locator('.elementor-popup-modal:visible');await modal.waitFor({state:'visible',timeout:90000});
 const link=modal.locator('a[href^="'+BASE+'/"]:visible').first();assert(await link.isVisible());const nav=page.waitForNavigation({waitUntil:'commit',timeout:90000});await link.click({noWaitAfter:true});assert.equal((await nav).status(),200);assert.deepEqual(errors,[]);
 out.push({language:lang,phone_menu_open:true,navigation:200,heavy_widget_loops_cache_operations:0,errors});await ctx.close();
}fs.writeFileSync('docs/fragment-cache-optin-mobile-ui.json',JSON.stringify(out,null,2)+'\n');console.log('PASS actual phone menu opening/navigation el/en; heavy desktop subtree absent');
}finally{await browser.close();}})().catch(e=>{console.error(e.stack);process.exit(1)});
