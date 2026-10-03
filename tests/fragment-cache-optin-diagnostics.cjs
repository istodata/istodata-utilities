const {chromium}=require('C:/Users/pe/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict');
const BASE='https://wordpress-218158-6702910.cloudwaysapps.com';
(async()=>{
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
try {
const ctx=await browser.newContext({viewport:{width:1440,height:1024}});
const s=JSON.parse(fs.readFileSync(path.join(os.tmpdir(),'iu-fragment-access-sessions.json'))).site_manager;
await ctx.addCookies(['','auth_','secure_'].map(p=>({name:s[p+'name'],value:s[p+'value'],url:BASE})));
const page=await ctx.newPage();
await page.goto(BASE+'/wp-admin/post.php?post=30&action=elementor',{waitUntil:'domcontentloaded',timeout:180000});
await page.waitForFunction(()=>{try{return !!elementor.getContainer('417d1ae')&&!!window.$e;}catch{return false;}},null,{timeout:180000});
await page.evaluate(async()=>{const c=elementor.getContainer('417d1ae');await $e.run('panel/editor/open',{model:c.model,view:c.view});});
await page.locator('.elementor-panel-navigation-tab[data-tab="advanced"]').click();
await page.getByText('Advanced Element Cache',{exact:true}).click();
const probe=await browser.newContext();await probe.addCookies(['','auth_','secure_'].map(p=>({name:s[p+'name'],value:s[p+'value'],url:BASE})));
const unknown=await probe.request.get(BASE+'/?iu_kit_fragment_test=cross-page-20261001&iu_kit_mode=unknown&iu_kit_generation=diagnostic-unknown&iu_kit_request='+Date.now(),{timeout:180000});
const marker=JSON.parse((await unknown.text()).match(/IU_CROSS_PAGE (.+?) -->/)[1]);assert.equal(marker.loop_any,150);assert.deepEqual(marker.events.map(e=>e.result),['stored','stored']);assert.equal(Object.keys(marker.reject).length,0);await probe.close();
await page.locator('.iu-fragment-diagnostic').click();
await page.waitForFunction(()=>document.querySelector('.iu-fragment-diagnostic-result')?.textContent.includes('Αποθηκευμένη επιλογή'),null,{timeout:120000});
assert.equal(await page.locator('.iu-fragment-diagnostic-result').evaluate(el=>getComputedStyle(el).overflowWrap),'anywhere');
const message=await page.locator('.iu-fragment-diagnostic-result').innerText();console.log(message);
assert(message.includes('ON')&&message.includes('Τελευταία παρατήρηση διαχειριστή')&&message.includes('δομή υποστηρίζεται'));assert(!/\/home\/|Stack trace|wp-content\/plugins/.test(message));
const report=await page.evaluate(async()=>{
const post=async(data)=>{let r=await fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data)});return {status:r.status,json:await r.json()};};
let data={action:'iu_fragment_diagnostics',nonce:iuFragmentDiagnostics.nonce,document_id:'30',element_id:'417d1ae'};
return {valid:await post(data),invalid:await post({...data,nonce:'invalid'})};
});
assert(report.valid.json.success);assert.equal(report.invalid.status,403);
await page.screenshot({path:'docs/fragment-cache-optin-diagnostics.png'});
fs.writeFileSync('docs/fragment-cache-optin-diagnostics-ui.json',JSON.stringify({message,report:report.valid.json.data,invalid_nonce_status:403,native_button:true,no_save:true},null,2)+'\n');
const anon=await browser.newContext();let denied=await anon.request.post(BASE+'/wp-admin/admin-ajax.php',{form:{action:'iu_fragment_diagnostics',document_id:'30',element_id:'417d1ae'}});assert(!((await denied.text()).includes('Αποθηκευμένη επιλογή')));await anon.close();
await page.goto(BASE+'/wp-admin/tools.php?page=iu-elementor-fragments',{waitUntil:'domcontentloaded',timeout:90000});
await page.locator('input[type=number][name=document_id]').fill('30');await page.locator('input[type=text][name=element_id]').fill('417d1ae');
await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.getByRole('button',{name:'Έλεγχος κατάστασης',exact:true}).click()]);
assert((await page.locator('p[role=status]').innerText()).includes('Αποθηκευμένη επιλογή'));
console.log('PASS native Elementor diagnostic button, Tools status, nonce rejection, anonymous denial; no save');
}finally{await browser.close();}
})().catch(e=>{console.error(e.stack);process.exit(1)});
