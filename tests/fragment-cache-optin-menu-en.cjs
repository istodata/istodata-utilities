const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict'),{randomUUID}=require('node:crypto');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
const query=`iu_kit_fragment_test=cross-page-20261001&iu_kit_generation=${randomUUID()}`;
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
 try{
 const guest=await browser.newContext();const warm=await guest.request.get(BASE+'/en/?'+query+'&iu_kit_request='+randomUUID(),{timeout:180000});
 const wm=JSON.parse((await warm.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(wm.loop_any,156);assert.equal(wm.target,1);await guest.close();
 const context=await browser.newContext({viewport:{width:1440,height:1024}});
 const s=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
 await context.addCookies(['','auth_','secure_'].map(prefix=>({name:s[prefix+'name'],value:s[prefix+'value'],url:BASE})));
 const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const response=await page.goto(BASE+'/en/the-company/?'+query+'&iu_kit_request='+randomUUID(),{waitUntil:'load',timeout:180000});
 const marker=JSON.parse((await response.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(marker.loop_any,0);assert.equal(marker.target,1);assert.deepEqual(marker.template_renders,[]);
 const preferences=page.locator('#cmplz-cookiebanner-container .cmplz-view-preferences');
 if(await preferences.isVisible()){await preferences.click();await page.locator('#cmplz-cookiebanner-container .cmplz-save-preferences').click();}
 const widget=page.locator('[data-id="26fa46c6"]');
 const contentId=await widget.locator('[data-id="417d1ae"]').evaluate(el=>el.closest('[id^="e-n-menu-content-"]').id);
 const content=widget.locator('#'+contentId),button=widget.locator('[aria-controls="'+contentId+'"]');
 console.log('Heavy dropdown control',contentId,await button.getAttribute('id'));
 const titleId=await button.evaluate(el=>el.closest('.e-n-menu-title').id);
 assert.equal(await widget.count(),1);assert.equal(await button.count(),1);
 await page.waitForFunction(id=>!!window.jQuery?._data(document.getElementById(id),'events')?.mouseover,titleId,{timeout:90000});
 await widget.locator('#'+titleId+' .e-n-menu-title-container').hover();
 await page.waitForFunction(id=>document.getElementById(id)?.getAttribute('aria-expanded')==='true',await button.getAttribute('id'),{timeout:30000});
 assert(await content.isVisible());
 const bounds=await content.boundingBox();assert(bounds&&bounds.height>100);await page.screenshot({path:'docs/fragment-cache-optin-menu-en.png'});
 const destination=content.locator('a[href^="'+BASE+'/"]:visible').first();
 console.log('Visible destination',await destination.getAttribute('href'),'target',await destination.getAttribute('target'));
 const navigation=page.waitForNavigation({waitUntil:'commit',timeout:90000});await destination.click({noWaitAfter:true});const next=await navigation;assert.equal(next.status(),200);assert.deepEqual(errors,[]);
 fs.writeFileSync('docs/fragment-cache-optin-menu-en.json',JSON.stringify({anonymous_warm_loops:156,manager_hit_loops:0,central_menu_renders:1,dropdown_renders:0,opened:true,bounds,navigation_status:200,frontend_errors:errors},null,2)+'\n');
 console.log('PASS actual logged-in menu: anonymous warm, zero loops on hit, open and navigation 200, no frontend JS errors');
 }finally{await browser.close();}
})().catch(e=>{console.error(e.message);process.exit(1)});
