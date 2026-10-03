const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
 try {
  const context=await browser.newContext({viewport:{width:1440,height:1024},extraHTTPHeaders:{'X-IU-Preview-Probe':'native-preview-20261001'}});
  const s=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
  await context.addCookies(['','auth_','secure_'].map(p=>({name:s[p+'name'],value:s[p+'value'],url:BASE})));
  const page=await context.newPage(),captures=[];
  page.on('response',async r=>{
   if(!r.request().isNavigationRequest()||!r.url().startsWith(BASE))return;
   try{const html=await r.text(),match=html.match(/IU_CROSS_PAGE (.+?) -->/);
    if(match)captures.push({url:r.url(),status:r.status(),data:JSON.parse(match[1]),has_menu:html.includes('data-id="26fa46c6"')});
   }catch{}
  });
  await page.goto(BASE+'/wp-admin/post.php?post=2&action=elementor',{waitUntil:'domcontentloaded',timeout:180000});
  await page.waitForFunction(()=>!!window.elementor?.getContainer,{timeout:180000});
  await page.waitForTimeout(5000);
  let pagePreview=page.frames().find(f=>f.url().includes('elementor-preview=2'));
  if(!pagePreview){
   console.log('Waiting for native iframe',page.url(),page.frames().map(f=>f.url()));
   pagePreview=await page.waitForEvent('framenavigated',{predicate:f=>f.url().includes('elementor-preview=2'),timeout:180000});
  }
  assert(pagePreview,'Native page editor iframe absent');
  await pagePreview.locator('[data-id="26fa46c6"]').waitFor({state:'attached',timeout:120000});
  const rendered=await context.request.get(pagePreview.url(),{timeout:180000});
  const renderedHTML=await rendered.text(),renderedMatch=renderedHTML.match(/IU_CROSS_PAGE (.+?) -->/);assert(renderedMatch);
  captures.push({url:pagePreview.url(),status:rendered.status(),data:JSON.parse(renderedMatch[1]),has_menu:renderedHTML.includes('data-id="26fa46c6"')});
  console.log(JSON.stringify(captures.map(x=>({url:x.url,widgets:x.data.rendered_widgets,loops:x.data.loop_any,gates:x.data.gates.filter(g=>g.document===30),gate_count:x.data.gates.length,ops:x.data.operations})),null,2));
  const tested=captures.filter(x=>x.url.includes('elementor-preview=2'));
  assert(tested.length>=2,'Both native page iframe and direct preview required');
  for(const c of tested){
   assert(c.has_menu&&c.data.rendered_widgets>0,'Normal preview render absent');
   const gates=c.data.gates.filter(g=>g.preview_request&&g.document===30&&g.optins.includes('417d1ae')&&g.optins.includes('c60daee'));
   assert(gates.length,'Active preview and saved opted-in nodes must reach early gate');
   assert(gates.every(g=>g.query_allowed&&!g.request_context_ok),'Preview must reject request context even with permissive query filter');
   assert(c.data.gates.every(g=>!g.eligible),'Eligible cache gate in preview');
   assert(gates.every(g=>g.operations_before===0));
   assert.deepEqual(c.data.operations,[]);assert.deepEqual(c.data.keys,[]);assert.deepEqual(c.data.events,[]);
  }
  await page.screenshot({path:'docs/fragment-cache-hook-preview.png'});
  fs.writeFileSync('docs/fragment-cache-hook-preview.json',JSON.stringify({requests:captures,native_iframe_verified:true,no_document_save:true},null,2)+'\n');
  console.log('PASS actual native iframe and direct preview: active preview at early gate, normal render, zero cache operations, opt-ins preserved');
 } finally {await browser.close();}
})().catch(e=>{console.error(e.message);process.exit(1)});
