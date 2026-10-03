const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
(async()=>{
 const s=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
 try{
 const context=await browser.newContext({viewport:{width:1440,height:1024}});
 await context.addCookies(['','auth_','secure_'].map(prefix=>({name:s[prefix+'name'],value:s[prefix+'value'],url:BASE})));
 const page=await context.newPage();await page.goto(BASE+'/wp-admin/post.php?post=30&action=elementor',{waitUntil:'domcontentloaded',timeout:180000});
 await page.waitForFunction(()=>{try{return !!window.elementor?.getContainer('26fa46c6');}catch{return false;}},{timeout:180000});
 await page.evaluate(async()=>{const c=elementor.getContainer('26fa46c6');await $e.run('panel/editor/open',{model:c.model,view:c.view});});
 await page.waitForFunction(()=>elementor.getPanelView().getCurrentPageView().model?.get('id')==='26fa46c6');
 await page.locator('.elementor-panel-navigation-tab[data-tab="advanced"]').click();
 await page.getByText('Advanced Element Cache',{exact:true}).click();
 const input=page.locator('input[data-setting="iu_fragment_cache"]');
 if(await input.isChecked())await page.locator('.elementor-control-iu_fragment_cache .elementor-switch').click();
 assert(!(await input.isChecked()));
 const state=()=>page.evaluate(()=>['26fa46c6','417d1ae','c60daee'].map(id=>({id,cache:elementor.getContainer(id).settings.get('iu_fragment_cache'),ttl:elementor.getContainer(id).settings.get('iu_fragment_cache_ttl')})));
 const before=await state();console.log({before_publish:before});assert(!before[0].cache);assert(before.slice(1).every(x=>x.cache==='yes'));
 const responsePromise=page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('admin-ajax.php')&&(r.request().postData()||'').includes('save_builder'),{timeout:120000});
 await page.getByRole('button',{name:'Publish',exact:true}).click();const response=await responsePromise;assert((await response.json()).success);
 await page.waitForTimeout(8000);await page.reload({waitUntil:'domcontentloaded',timeout:180000});
 await page.waitForFunction(()=>{try{return !!window.elementor?.getContainer('26fa46c6');}catch{return false;}},{timeout:180000});
 const after=await state();console.log({after_reload:after});assert(!after[0].cache);assert(after.slice(1).every(x=>x.cache==='yes'&&(x.ttl??'86400')==='86400'));
 fs.writeFileSync('docs/fragment-cache-access-restored-nav.json',JSON.stringify({before_publish:before,after_reload:after},null,2)+'\n');
 }finally{await browser.close();}
})().catch(e=>{console.error(e.message);process.exit(1)});
