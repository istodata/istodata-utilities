const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict'),{randomUUID}=require('node:crypto');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com',kind=process.argv[2];
(async()=>{const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
try{
const gen=randomUUID(),query='iu_kit_fragment_test=cross-page-20261001&iu_kit_generation='+gen;
if(kind==='candidate-hit'){const guest=await browser.newContext();const r=await guest.request.get(BASE+'/en/?'+query+'&iu_kit_request='+randomUUID(),{timeout:180000});const d=JSON.parse((await r.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(d.loop_any,156);assert.deepEqual(d.events.map(e=>e.result),['stored','stored']);await guest.close();}
const s=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
const ctx=await browser.newContext({viewport:{width:1440,height:1024},userAgent:'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'});
await ctx.addCookies(['','auth_','secure_'].map(p=>({name:s[p+'name'],value:s[p+'value'],url:BASE})));
const page=await ctx.newPage(),errors=[],failed=[];page.on('pageerror',e=>errors.push(e.message));page.on('response',r=>{if(r.status()>=400&&/\.(?:js|css)(?:\?|$)/.test(r.url()))failed.push({url:r.url(),status:r.status()});});
const r=await page.goto(BASE+'/en/the-company/?'+query+'&iu_kit_request='+randomUUID()+(kind==='candidate-hit'?'':'&iu_kit_mode=baseline'),{waitUntil:'load',timeout:180000});
console.log('Page loaded',kind);const marker=JSON.parse((await r.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(marker.target,1);assert.equal(marker.loop_any,kind==='candidate-hit'?0:156);
const prefs=page.locator('#cmplz-cookiebanner-container .cmplz-view-preferences');if(await prefs.isVisible()){await prefs.click();await page.locator('#cmplz-cookiebanner-container .cmplz-save-preferences').click();}
const widget=page.locator('[data-id="26fa46c6"]:visible');assert.equal(await widget.count(),1);
const fragment=widget.locator('[data-id="417d1ae"]');assert.equal(await fragment.count(),1);
const contentId=await fragment.evaluate(el=>el.closest('[id^="e-n-menu-content-"]').id);
const content=widget.locator('#'+contentId),button=widget.locator('[aria-controls="'+contentId+'"]:visible');assert.equal(await button.count(),1);
const titleId=await button.evaluate(el=>el.closest('.e-n-menu-title').id),title=widget.locator('#'+titleId);
await page.waitForFunction(id=>!!window.jQuery?._data(document.getElementById(id),'events')?.mouseover,titleId,{timeout:90000});
console.log('Visible unique dropdown and registered hover handler',kind);const settings=JSON.parse(await widget.getAttribute('data-settings'));
await page.mouse.move(5,1000);await page.waitForFunction(id=>document.getElementById(id)?.getAttribute('aria-expanded')==='false',await button.getAttribute('id'),{timeout:30000});
await title.evaluate(el=>{window.iuMenuTrace=[];for(const type of ['mouseover','mouseenter','mousemove','mousedown','click'])el.addEventListener(type,e=>{window.iuMenuTrace.push({type,time:performance.now(),expanded:el.querySelector('[aria-expanded]')?.getAttribute('aria-expanded'),target:e.target.id||e.target.className});},true);});
await button.click();await page.waitForTimeout(700);
console.log('Physical click done',kind);const clickState={expanded:await button.getAttribute('aria-expanded'),visible:await content.isVisible(),trace:await page.evaluate(()=>window.iuMenuTrace)};
await page.mouse.move(5,1000);await title.locator('.e-n-menu-title-container').hover();
await page.waitForFunction(id=>document.getElementById(id)?.getAttribute('aria-expanded')==='true',await button.getAttribute('id'),{timeout:30000});
assert(await content.isVisible());const bounds=await content.boundingBox();assert(bounds&&bounds.height>100);
const links=content.locator('a[href^="'+BASE+'/"]:visible');assert((await links.count())>0);
const resources=await page.locator('script[src],link[rel="stylesheet"]').evaluateAll(els=>els.map(el=>el.src||el.href).filter(u=>/elementor|istodata-utilities/.test(u)).sort());
console.log('Native hover opened real dropdown',kind);const openState={expanded:await button.getAttribute('aria-expanded'),visible:true,bounds,visible_links:await links.count()};
await page.screenshot({path:'docs/fragment-cache-en-'+kind+'.png'});
const dest=links.first(),href=await dest.getAttribute('href');const nav=page.waitForNavigation({waitUntil:'commit',timeout:90000});await dest.click({noWaitAfter:true});const next=await nav;assert.equal(next.status(),200);assert.deepEqual(errors,[]);assert.deepEqual(failed,[]);
const report={kind,viewport:{width:1440,height:1024},device:'desktop',page:'/en/the-company/',consent:'preferences saved before trigger',cookie_names:(await ctx.cookies()).map(c=>c.name).sort(),loop_any:marker.loop_any,original_template_renders:marker.template_renders,outer_render:marker.target,settings,unique_root:true,unique_template:true,unique_visible_button:true,handler_registered:true,clickState,openState,resources,href,navigation:200,errors,failed_assets:failed};
fs.writeFileSync('docs/fragment-cache-en-'+kind+'.json',JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify({kind,loops:marker.loop_any,settings,clickState,openState,navigation:200}));
}finally{await browser.close();}})().catch(e=>{console.error(e.stack);process.exit(1)});
