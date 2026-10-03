'use strict';
// A new rule belongs in the registry. Tests/fixtures/docs may retain historical versions.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const registry = 'includes/elementor-compatibility.php';
function scan(relative) {
  const dir = path.join(root, relative);
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    const file = path.posix.join(relative, entry.name);
    return entry.isDirectory() ? scan(file) : /\.(php|js)$/.test(file) ? [file] : [];
  });
}
const files = ['istodata-utilities.php', ...scan('includes'), ...scan('assets')];
function violations(file, code) {
  const errors = [];
  if (file !== registry) {
    // Comparisons, regexes and local allowlists must not reference Elementor version constants.
    if (/ELEMENTOR_(?:PRO_)?VERSION\s*(?:===?|!==?|[<>])|(?:version_compare|preg_match)\([^;]*ELEMENTOR_(?:PRO_)?VERSION/s.test(code)) errors.push('independent version guard');
    if (/iu_elementor_fragment_versions_supported/.test(code)) errors.push('legacy widening filter');
    if (/elementor-(?:atomic-interaction|fragment-cache|update-guard)/.test(file)) {
      // Retained non-Elementor dependency guards are a different API contract.
      // Strip only their comparisons, then reject version/range literals anywhere in these consumers.
      const otherDependencies = '(?:Search_Filter_Elementor_Extension::VERSION|SEARCH_FILTER_VERSION|IS_VERSION|ACF_VERSION|WP_ROCKET_VERSION|EWWW_IMAGE_OPTIMIZER_VERSION|RANK_MATH_VERSION|ICL_SITEPRESS_VERSION)';
      const policyCode = code.replace(new RegExp(`${otherDependencies}\\s*(?:===?|!==?)\\s*["'][0-9]+(?:\\.[0-9]+)+["']`, 'g'), '');
      const literals = [...policyCode.matchAll(/["'][0-9]+\.[0-9]+(?:\.[0-9x]+)*(?:[-+][^"']+)?["']/g)];
      const duplicates = literals.filter(match => {
        const context = policyCode.slice(Math.max(0, match.index - 450), match.index);
        // These header reads belong to reviewed remove-CPT-base and WPML companion APIs.
        return !(/\$header\['Version'\]/.test(context.slice(-70)) &&
          /remove_cpt_base::remove_slug|contact-form-7-multilingual\/classes\/Locale|wp-seo-multilingual\/classes\/Shared/.test(context));
      });
      if (duplicates.length) errors.push('duplicate compatibility literal');
    }
  }
  return errors;
}
const failures = files.flatMap(file => violations(file, fs.readFileSync(path.join(root, file), 'utf8')).map(reason => `${file}: ${reason}`));
assert.deepEqual(failures, [], failures.join('\n'));
// Prove the detector rejects realistic reintroductions; fixture exceptions are directory-scoped.
assert.ok(violations('includes/elementor-fragment-cache.php', "ELEMENTOR_VERSION === '4.4.0'").length);
assert.ok(violations('includes/elementor-atomic-interaction-breakpoints.php', "version_compare(ELEMENTOR_PRO_VERSION, '4.5.0', '>=')").length);
assert.ok(violations('includes/elementor-fragment-cache.php', "apply_filters('iu_elementor_fragment_versions_supported', true)").length);
assert.ok(violations('includes/elementor-update-guard.php', "version_compare($pair['core'], '4.5.0', '>=')").length);
assert.ok(violations('assets/js/elementor-atomic-interaction-breakpoints.js', "const minVersion = '4.5.0';").length);
const consumers = {
  'includes/elementor-atomic-interaction-breakpoints.php': /iu_elementor_feature_supported\('atomic'\)/,
  'includes/elementor-fragment-cache.php': /iu_elementor_feature_supported\('fragment'\)/,
  'includes/elementor-fragment-cache-graph.php': /iu_elementor_feature_supported\('fragment'\)/,
  'includes/elementor-fragment-cache-excerpt.php': /iu_elementor_feature_supported\('fragment', null, true\)/,
  'includes/elementor-update-guard.php': /iu_elementor_pair_failures/,
};
for (const [file, pattern] of Object.entries(consumers)) assert.match(fs.readFileSync(path.join(root, file), 'utf8'), pattern, `${file} must use the registry`);
console.log(`PASS compatibility drift: ${files.length} runtime files; registry consumers and detector negative controls`);
