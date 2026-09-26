// Read-only integration fixture: uses installed WPML view/template code, never loads WordPress.
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const host = process.env.IU_WPML_SSH_HOST;
const key = process.env.IU_WPML_SSH_KEY;
const wpRoot = process.env.IU_WPML_WP_ROOT;
if (!host || !key || !wpRoot || !/^\/[\w/.-]+$/.test(wpRoot)) {
    throw new Error('Set IU_WPML_SSH_HOST, IU_WPML_SSH_KEY and IU_WPML_WP_ROOT for a read-only WPML test installation.');
}
const root = wpRoot.replace(/\/$/, '') + '/';
const wpml = root + 'wp-content/plugins/sitepress-multilingual-cms/';
function readRemote(file) {
    return execFileSync('ssh', ['-o', 'BatchMode=yes', '-i', key,
        host, 'cat ' + file], { encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 });
}
(async () => {
    const scripts = ['jquery/jquery.min.js', 'underscore.min.js', 'backbone.min.js'].map(file => readRemote(root + 'wp-includes/js/' + file));
    scripts.push(readRemote(wpml + 'res/js/templates-compiled.js'));
    const modal = readRemote(wpml + 'res/js/taxonomy-translation/views/term-popup-view.js');
    const css = readRemote(wpml + 'res/css/taxonomy-translation.css');
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1100, height: 1100 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<div id="321-popup-en"></div>');
        await page.addStyleTag({ content: 'body{font-family:Arial;margin:20px}.wpml-form-row{display:grid;grid-template-columns:140px 300px 40px 300px;gap:10px;margin:15px 0}textarea{min-height:80px}input,textarea{padding:8px;box-sizing:border-box}button{min-height:34px}' + css });
        for (const content of scripts) { await page.addScriptTag({ content }); }
        await page.evaluate(() => {
            window.ajaxurl = '/fixture-only';
            window.labels = new Proxy({ copyFromOriginal: 'Copy from original', termPopupDialogTitle: 'Term translation', original: 'Original:', translationTo: 'Translation to:', Name: 'Name', Slug: 'Slug', Description: 'Description', save: 'Save', cancel: 'Cancel' }, { get: (obj, key) => obj[key] || key });
            window.iuRepeaterWPML = { nonce: 'valid', loading: 'Loading...', error: 'Reopen the window and try again.', copied: 'Numbers, images and links are copied from the original.' };
            jQuery.fn.dialog = function () { return this; };
            window.requests = [];
            jQuery.post = function (url, data) {
                const request = jQuery.Deferred();
                request.abort = () => request.reject({}, 'abort');
                requests.push({ data, request });
                return request;
            };
            const names = { name: 'Social Events', slug: 'social-events', description: '' };
            const original = new Backbone.Model({ term_taxonomy_id: 999, language_code: 'el' });
            original.getNameSlugAndDescription = () => ({ ...names, name: 'Original event' });
            const Term = Backbone.Model.extend({
                getNameSlugAndDescription: () => names,
                getMetaData: () => ({ avra_stats: 'serialized-old', rank_math_title: 'Existing SEO title' }),
                save(name, slug, description, meta) { window.saved = meta; }
            });
            window.TaxonomyTranslation = {
                models: { Term }, views: {},
                data: { activeLanguages: { el: { flag: '', label: 'Greek' }, en: { flag: '', label: 'English' } } },
                classes: { taxonomy: {
                    get: () => 'event-type', getOriginalTerm: () => original,
                    getOriginalTermMeta: () => [ { meta_key: 'avra_stats', meta_value: ['a:4:{serialized}'] }, { meta_key: 'rank_math_title', meta_value: ['Original SEO'] } ]
                } }
            };
            window.fixture = {
                avra_stats: { label: 'Statistics', fingerprint: 'hash', units: {
                    '30-text': { label: '1 / Caption', source: 'Original caption', value: 'Existing translation', multiline: true },
                    '31-title': { label: '2 / Title', source: 'Title <script>bad</script>', value: '', multiline: false }
                } },
                constants: { label: 'Numbers', fingerprint: 'numbers', units: [] }
            };
        });
        await page.addScriptTag({ content: modal });
        await page.addScriptTag({ content: fs.readFileSync(path.join(__dirname, '../assets/js/iu-simple-repeater-wpml.js'), 'utf8') });
        await page.evaluate(() => {
            window.popup = new TaxonomyTranslation.views.TermPopUpView({ model: new TaxonomyTranslation.models.Term({ trid: '321', language_code: 'en' }) });
            popup.open('321', 'en');
        });
        assert(await page.locator('.term-save').isDisabled());
        await page.evaluate(() => requests[0].request.resolve({ success: true, data: fixture }));
        assert.equal(await page.locator('.term-meta[data-meta-key="avra_stats"]').count(), 0);
        assert.equal(await page.locator('.term-meta[data-meta-key="rank_math_title"]').inputValue(), 'Existing SEO title');
        assert.equal(await page.locator('[data-iu-unit="30-text"]').inputValue(), 'Existing translation');
        assert.equal(await page.locator('.iu-wpml-repeater').count(), 2);
        assert(!(await page.locator('.term-save').isDisabled()));
        await page.locator('.iu-wpml-repeater .js-button-copy').first().click();
        assert.equal(await page.locator('[data-iu-unit="30-text"]').inputValue(), 'Original caption');
        await page.locator('[data-iu-unit="30-text"]').fill('Translated \\ path');
        await page.locator('.term-save').click();
        const saved = await page.evaluate(() => window.saved);
        assert.equal(saved.rank_math_title, 'Existing SEO title');
        assert.equal(saved.avra_stats, undefined);
        const payload = JSON.parse(saved._iu_sr_translation);
        assert.equal(payload.fields.avra_stats.values['30-text'], 'Translated \\ path');
        assert.equal(payload.source, 999);
        assert.equal(Object.keys(payload.fields.constants.values).length, 0);
        await page.evaluate(() => popup.model.trigger('saveFailed'));
        assert.equal(await page.locator('[data-iu-unit="30-text"]').inputValue(), 'Translated \\ path');
        assert.equal(await page.locator('.errors').textContent(), 'Reopen the window and try again.');
        await page.screenshot({ path: path.join(os.tmpdir(), 'iu-wpml-term-modal.png'), fullPage: true });
        await page.evaluate(() => {
            popup.close();
            popup = new TaxonomyTranslation.views.TermPopUpView({ model: new TaxonomyTranslation.models.Term({ trid: '321', language_code: 'en' }) });
            popup.open('321', 'en');
            requests[1].request.reject({}, 'error');
        });
        assert(await page.locator('.term-save').isDisabled());
        assert.equal(await page.locator('.errors').textContent(), 'Reopen the window and try again.');
        assert.deepEqual(errors, []);
        console.log('PASS: actual WPML modal/template integration, copied/prefilled fields, isolated SEO meta, save payload, failure states; no page errors.');
        console.log('Screenshot: ' + path.join(os.tmpdir(), 'iu-wpml-term-modal.png'));
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
