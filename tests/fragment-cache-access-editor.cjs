const { chromium } = require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const assert=require('node:assert/strict');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
const sessions=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'),'utf8'));
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
 const context=await browser.newContext({viewport:{width:1440,height:1024}});
 const s=sessions.site_manager;
 await context.addCookies([{name:s.name,value:s.value,url:BASE},{name:s.auth_name,value:s.auth_value,url:BASE},{name:s.secure_name,value:s.secure_value,url:BASE}]);
 const page=await context.newPage();
 await page.goto(BASE+'/wp-admin/options-general.php?page=istodata-utilities&tab=elementor',{waitUntil:'load',timeout:180000});
 console.log({path:new URL(page.url()).pathname,title:await page.title(),headings:await page.locator('h1').allTextContents()});
 await page.screenshot({path:'docs/fragment-cache-access-settings-inspect.png'});
 const toggle=page.locator('input[name="istodata_utilities_settings[optimizations][elementor_fragment_cache]"]');
 const enabled=process.argv.includes('--on')||process.argv.includes('--seed');
 if(!enabled)assert(await toggle.isChecked());
 await page.screenshot({path:'docs/fragment-cache-access-global-on.png'});
 if(process.argv.includes('--inspect')){
  await page.goto(BASE+'/wp-admin/post.php?post=30&action=elementor',{waitUntil:'domcontentloaded',timeout:180000});
  await page.waitForFunction(()=>{try{return !!window.elementor?.getContainer('417d1ae')&&!!window.$e;}catch{return false;}},{timeout:180000});
  console.log(await page.evaluate(()=>({container:!!elementor.getContainer('417d1ae'),widgets:Object.keys(elementor.config.widgets.template||{}),saver:Object.keys(elementor.saver||{}),buttons:[...document.querySelectorAll('button')].map(x=>({text:x.textContent,aria:x.getAttribute('aria-label')})).filter(x=>/publish|update|save|פרסם|δημοσ|ενημ/i.test(x.text+' '+x.aria))})));
  await page.screenshot({path:'docs/fragment-cache-access-editor-inspect.png'});
 }else{
  await toggle.setChecked(enabled);
  await Promise.all([page.waitForNavigation({waitUntil:'load'}),page.locator('input[type=submit][name=submit]').click()]);
  const on=enabled;assert.equal(await toggle.isChecked(),on);
  console.log(`global ${on?'ON':'OFF'} saved via Kit settings UI`);
  await page.goto(BASE+'/wp-admin/post.php?post=30&action=elementor',{waitUntil:'load',timeout:180000});
  await page.waitForFunction(()=>window.elementor?.getContainer && window.$e,{timeout:180000});
  await page.waitForTimeout(15000);
  const controls=await page.evaluate(async()=>{
    const c=elementor.getContainer('417d1ae');
    await $e.run('panel/editor/open',{model:c.model,view:c.view});
    const common=elementor.config.widgets.common?.controls||{};
    const own=elementor.config.widgets.template?.controls||{};
    return {optin:c.settings.get('iu_fragment_cache'),ttl:c.settings.get('iu_fragment_cache_ttl'),cacheControl:!!(common.iu_fragment_cache||own.iu_fragment_cache),compatControl:!!(common.iu_fragment_cache_compatibility||own.iu_fragment_cache_compatibility)};
  });
  console.log({on,controls});assert.equal(controls.optin,'yes');assert.equal(controls.ttl??'86400',process.argv.includes('--repeat')?'86400':on&&process.argv.includes('--seed')?'86400':'21600');assert.equal(controls.cacheControl,on);assert.equal(controls.compatControl,false);
  await page.waitForFunction(()=>elementor.getPanelView().getCurrentPageView().model?.get('id')==='417d1ae');
  assert(!(await page.evaluate(()=>elementor.getContainer('26fa46c6').settings.get('iu_fragment_cache'))),'Navigation opt-in must remain OFF');
  await page.locator('.elementor-panel-navigation-tab[data-tab="advanced"]').click();
  const panel=page.getByText('Advanced Element Cache',{exact:true});
  if(on){await panel.waitFor({state:'visible'});await panel.click();}
  else assert.equal(await panel.count(),0);
  await page.screenshot({path:`docs/fragment-cache-access-editor-${on?'on':'off'}.png`});
  if(on){
   await page.locator('select[data-setting="iu_fragment_cache_ttl"]').selectOption(process.argv.includes('--seed')?'21600':'86400');
  }
  {
   // Native editor command marks an unchanged document dirty; the visible Publish button performs the actual save.
   await page.evaluate(()=>$e.internal('document/save/set-is-modified',{status:true}));
   const savedResponse=page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('admin-ajax.php')&&(r.request().postData()||'').includes('save_builder'),{timeout:120000});
   await page.getByRole('button',{name:'Publish',exact:true}).click();
   const response=await savedResponse;assert.equal(response.status(),200);const payload=await response.json();assert(payload.success);console.log('Native save_builder response succeeded');
   await page.waitForFunction(()=>!elementor.saver.isSaving?.(),{timeout:120000});
   await page.waitForTimeout(8000);
   await page.reload({waitUntil:'domcontentloaded',timeout:180000});
   await page.waitForFunction(()=>{try{return !!window.elementor?.getContainer('417d1ae');}catch{return false;}},{timeout:180000});
   const preserved=await page.evaluate(()=>['417d1ae','c60daee'].map(id=>({id,optin:elementor.getContainer(id).settings.get('iu_fragment_cache'),ttl:elementor.getContainer(id).settings.get('iu_fragment_cache_ttl')})));
   assert(preserved.every(x=>x.optin==='yes'&&(x.ttl??'86400')===(x.id==='417d1ae'&&(!on||process.argv.includes('--seed'))&&!process.argv.includes('--repeat')?'21600':'86400')));console.log({save_reload_preserved:preserved});
   assert(!(await page.evaluate(()=>elementor.getContainer('26fa46c6').settings.get('iu_fragment_cache'))),'Navigation opt-in changed during save');
   const file='docs/fragment-cache-access-editor.json';const r=fs.existsSync(file)?JSON.parse(fs.readFileSync(file)):{};
   if(process.argv.includes('--seed'))r.explicit_ttl_seeded=true;
   else if(!on){r.explicit_ttl_seeded=true;r.off_panel_hidden=true;if(process.argv.includes('--repeat'))r.off_repeat_preserved=preserved;else r.off_save_reload_preserved=preserved;}
   else{r.on_panel_restored=true;r.on_compatibility_text_absent=true;r.original_24h_restored=preserved;}
   r.navigation_optin_off_before_after_save=true;
   fs.writeFileSync(file,JSON.stringify(r,null,2)+'\n');
  }
 }
 await browser.close();
})().catch(e=>{console.error(e.message);process.exit(1)});
