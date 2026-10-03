const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict'),{randomUUID}=require('node:crypto');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com',session=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
(async()=>{const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'}),out=[];
try{for(const language of ['el','en'])for(const mobile of [false,true]){
 const options=mobile?{viewport:{width:390,height:844},isMobile:true,hasTouch:true,userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'}:{viewport:{width:1440,height:1024}};
 const gen=randomUUID(),url=BASE+(language==='en'?'/en/':'/')+'?iu_kit_fragment_test=cross-page-20261001&iu_kit_generation='+gen+'&iu_kit_mode=custom-only&iu_kit_request=';
 const guest=await browser.newContext(options);const warm=await guest.request.get(url+randomUUID(),{timeout:180000});const miss=JSON.parse((await warm.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);
 assert.equal(miss.custom_render_body,1);assert.deepEqual(miss.events.map(e=>e.result),['stored','stored']);await guest.close();
 const ctx=await browser.newContext(options);await ctx.addCookies(['','auth_','secure_'].map(prefix=>({name:session[prefix+'name'],value:session[prefix+'value'],url:BASE})));
 const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));const response=await page.goto(url+randomUUID(),{waitUntil:'load',timeout:180000});
 const hit=JSON.parse((await response.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(hit.custom_render_body,0);assert.deepEqual(hit.events.map(e=>e.result),['hit','hit']);
 await page.waitForFunction(()=>document.querySelector('[data-id="iu-custom-proof"]')?.getAttribute('data-native-hook')==='yes',null,{timeout:30000});
 const button=page.locator('.iu-generic-proof-button');const prefs=page.locator('.cmplz-view-preferences');if(await prefs.isVisible()){await prefs.click();await page.locator('.cmplz-save-preferences').click();}
 assert.equal(await button.evaluate(el=>getComputedStyle(el).getPropertyValue('--iu-proof-ready').trim()),'1');await button.click();assert.equal(await button.getAttribute('aria-pressed'),'true');assert.deepEqual(errors,[]);
 out.push({language,mobile,anonymous_miss_body_renders:1,authenticated_hit_body_renders:0,native_element_ready_hook:true,no_fallback_initializer:true,css_and_click_verified:true,errors});fs.writeFileSync('docs/fragment-cache-generic-native-init.json',JSON.stringify(out,null,2)+'\n');await ctx.close();console.log('PASS native frontend init',language,mobile?'phone':'desktop');
}}finally{await browser.close();}})().catch(e=>{console.error(e.stack);process.exit(1)});
