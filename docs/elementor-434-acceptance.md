# Elementor 4.3.4 / Pro 4.3.1 acceptance — PASS, 2026-10-06

Current status: PASS after the native asset-order and loop stylesheet corrections. Earlier STOP findings and browser timeouts below are retained chronologically; the later successful repeat supersedes them. Exact-pair release approval is recorded at the end.

Authorized target: metrica staging only. The user confirmed backup readiness
before this continuation. The earlier source review remains in
`elementor-434-review.md` and the official distribution comparison manifest.

## Passed evidence

- Real Atomic Loop: native/miss 5 original renders and 1 query; hit 0 originals
  and 0 queries, with identical HTML and inline assets.
- `atomic-fragment-integrated-acceptance.json`: 23 real PHP requests PASS under
  4.3.4 / 4.3.1, including authenticated shared hits, authenticated cold writer,
  anonymous reuse, random URL parameters, page context, default OFF, device
  visibility, simultaneous cold misses and source invalidation.
- `atomic-fragment-language-device.json`: English and Greek phone variants
  native/miss/hit PASS, translated links and matching per-variant HTML/assets.
- `atomic-fragment-policy-result.json`: actual SDK schema and privacy/context
  rejection checks passed. Opt-in OFF / seven-day default preserved.
- Actual 4.3.4 shared interactions bundle and installed Pro 4.3.1 bundle
  checksums match the previously reviewed code; 17 vendor runtime tests passed.
- Browser: 4.3.4 asset URLs observed, desktop products mega-menu expanded with
  visible staging links; at 390px the mobile search and menu popups opened and
  closed. No JavaScript errors observed before the navigation attempt.
- Browser navigation to the company page could not be verified: CDP timed out.
  Viewport override was reset; no configuration was changed through the UI.

## Product decision update — 2026-10-06

Latest technical result: the inline loop CSS defect is now corrected locally.
Two repeats with Core 4.3.4 / Pro 4.3.1 passed Atomic and Classic asset/HTML/early
bypass checks. A browser repeat also verified the real miss/hit counters, menu
tabs/navigation and mobile menu/search. See `fragment-cache-loop-css-fix.md`.
The older STOP findings below are historical evidence; frozen menu highlighting
is accepted and the duplicate inline CSS finding is resolved. Registry approval,
version and release assets have not been changed by this correction.

The user has explicitly accepted identical shared cached HTML across pages,
including frozen menu active classes and `aria-current`, when opting an element
into caching. This supersedes the earlier requirement to preserve the current
menu indicator by caching only two dropdowns. The cross-page menu finding below
remains valid evidence of behavior, but is no longer a failure or release
blocker under this product decision. Do not implement the page-key/bypass
proposal below solely for menu highlighting. At that earlier decision point the inline loop CSS finding and
unfinished technical acceptance remained open; the later correction closed them. See the locked decisions in
`AGENTS.md`; product acceptance does not itself verify runtime compatibility.

## Asset-order correction repeat: historical cross-page and inline CSS STOP

The local runtime now records required assets in native enqueue order, with
format 16 preventing reuse of older manifests. The focused change is in
`fragment-cache-native-asset-order.patch`; CHANGELOG has an Unreleased fix.
No plugin version, registry approval, ZIP or release changed.

The repeated staging test passed ordered external asset URLs and captured
generated CSS bytes/hashes for Atomic and Classic native/miss/hit. Classic
still returned six hits, equal same-page fragment HTML, zero cached original
widget executions and zero header loops (150 on miss). Cross-page external
asset parity also passed when independently checked from the captured rows.

The live run stopped on cross-page HTML equality: elements `a1ff108` and
`e62c115` retain the previous page's `current-menu-item`, `current_page_item`,
`elementor-item-active` and `aria-current="page"` on `/etairia/`. Their keys
are shared across pages. These menu fragments must use a reliable page/query
context key or bypass shared caching; retain shared keys for demonstrably
page-independent heavy dropdowns. Do not accept stale current-page indicators.

Offline comparison of the same captured HTML also confirms an extra identical
`<style id="loop-1635">` on the hit (two occurrences versus one native/miss).
Its hash is `fd80f749fd55f5d5a58a7a666fc0502d37c28b5e76212c9156aee8aa8917ba6c`.
This needs coordinated per-request replay of Elementor's loop CSS emission
state, preserving native count and ordering. There is no observed visual-break
claim. The unrelated MailOptin inline config changes its AJAX URL to the actual
request URL; that is outside cached fragments and accounts for the other
inline script difference.

The inline CSS gate was added while the already loaded child test was running;
it was not executed by that process. Its traceback source line therefore
shows the updated inline assertion, while the executed assertion was the
original cross-page HTML check. Captured rows/HTML independently verify both
findings. The test now has explicit messages and an inline CSS gate for the
next run. No repeat or additional runtime correction followed these failures.
Mobile and final browser acceptance remain pending.

Rollback restored Core 4.3.3 / Pro 4.3.1 and byte-identical installed Kit files,
raw header/settings rows, generated CSS, metadata and updater state. Independent
`tar -df` returned no differences; temporary loaders are absent. Native Element
Cache remains disabled; production was untouched. Existing unrelated working
tree changes were preserved. Portable PHP (not PATH) lint, asset regression,
replay-order tests, compatibility policy, 68 updater scenarios, 53-file drift
and `git diff --check` passed.

## Previous isolated repeat: Classic CSS ordering STOP (corrected locally)

The isolated repeat captured generated CSS bytes and SHA-256 at each response.
Atomic native/miss/hit passed exact asset URLs, ordering and CSS bytes; the
earlier CSS-version reference instability below did not recur.

Classic header native and miss each executed 150 copies of loop template 1635.
The hit executed zero header loops, returned six fragment hits, and did not
execute the cached original widgets. All six fragment HTML hashes matched.
The ten remaining page loops are outside the measured header.

However, the hit changes external CSS order: `custom-widget-nested-tabs.min.css`
moves before `post-7767.css`, and `widget-posts.min.css` moves before
`post-7778.css`. Native and miss have identical ordered URLs. All 97 external
asset references remain present; none are missing or added. This is an actual
asset-order acceptance failure, with a possible cascade effect, not proof of
an observed visual regression. Inline hash lists also differ and remain
unresolved. Cross-page and mobile Classic steps stopped at the asset gate.
Evidence: `elementor-434-classic-assets-finding.json` and
`elementor-434-classic-result.json`.

Proposed correction: preserve the actual miss enqueue order when recording and
replaying assets, including template styles and dependencies; invalidate old
fragment entries and repeat ordered external/inline asset and browser checks.
The current dependency-first merge in `includes/elementor-fragment-cache.php`
is a candidate cause, not yet proven. No runtime correction was made after this
failure, in accordance with the requested stop condition.

Final rollback restored Core 4.3.3 / Pro 4.3.1, original Kit runtime, saved
header rows/settings, cache metadata, updater state and generated files.
An independent `tar -df` comparison of the generated-directory backup returned
zero differences; temporary probe loaders are absent. Native Element Cache
remains disabled. Core 4.3.4 is unapproved; no release or production changes.

## Earlier stop and remaining acceptance (superseded reference instability)

During the last repeat before the Classic header observer, strict external
asset equality against the native reference failed. All three responses have
85 assets in the same path order; seven generated CSS URLs changed only their
`ver=` query values between native and miss. Miss and hit URLs are exactly
identical. HTML and inline assets are also identical; the hit still does no
original rendering or query work. See `elementor-434-assets-finding.json`.

This does not show a missing dependency, but the native reference was not
stable. CSS content was not captured independently at each response, so no
claim of byte-identical external CSS can be made. Concurrent public browser
navigation and Elementor regeneration are possible confounders; their causal
role has not been proven.

The Classic real-header acceptance did not run to completion. Its first
observer attempt stopped on PHP's empty-array JSON representation before any
miss/hit comparison. That observer handling and child-process UTF-8 encoding
were corrected. The next attempt stopped on the Atomic reference asset check
above before invoking the Classic observer.

Required next correction: run the native/miss/hit sequence without simultaneous
browser traffic, capture generated CSS bytes/hash at each response (not only
URLs), and distinguish file regeneration from missing/incorrect assets. Do not
drop CSS query versions silently or weaken the asset gate. Then complete the
Classic header miss/hit, cross-page/current-menu, device and browser navigation
acceptance before approving this exact pair.

## Restoration

The last run restored the exact original vendor directory to Core 4.3.3 / Pro
4.3.1, byte-identical installed Kit files, the raw SQL rows of headers 30/33000,
the backed cache metadata and generated Elementor directory, and original
updater cron/options. Native Elementor Element Cache remains `disable`.
The temporary fixture, tracked fragment entries, authentication session and
private MU loaders were removed. Protected rollback copies remain outside
webroot; no production action was performed.

The first resumed run's all-metadata hash assertion still differed for header
30 despite unchanged saved builder data. Subsequent restoration snapshots now
include complete raw header post/meta rows and verify those rows directly;
that stronger rollback check passed. Do not describe the earlier failed
all-metadata assertion as a passing test.

Registry acceptance remains unchanged (4.3.4 unapproved); version, GitHub and
ZIP are unchanged. Required compatibility policy/updater/drift tests passed
against the existing registry. PHP is absent from PATH; portable PHP was used.
The user's confirmed unrelated release changes were preserved.

## Release acceptance — 2026-10-06

The release-owned registry now accepts exactly Core 4.3.4 / Pro 4.3.1 in Kit 2.23.0. Earlier STOP/unapproved notes above are historical; the asset-order and native loop CSS findings were resolved and staging acceptance passed. Motion source/checksum and actual vendor runtime validation supplements the separate prior 4.3.3 browser matrix; it is not a claim of a new full Motion browser matrix. Full-package release acceptance is recorded separately in release-2.23.0-staging.json.
