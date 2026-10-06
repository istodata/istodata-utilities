# Fragment cache loop CSS replay — 2026-10-06

## Finding and correction

Elementor Pro 4.3.1's `Loop_Css_Trait::print_all_css()` maintains a private,
request-local `printed_with_css` list. Previously, cached HTML emitted the
captured `<style id="loop-1635">` without registering that native side effect.
A later uncached page loop printed the same style again.

The Kit now captures the native loop CSS state before/after each fragment.
It verifies that new printed handles correspond exactly to unique captured
static loop style blocks. At a hit, prerequisite already-printed styles must
exist; otherwise the original element renders normally. For captured style
slots, the public Pro `Loop::create(id)->print_all_css(id)` printer supplies the
CSS and updates its own state. Previously printed styles produce no duplicate
output. No private state is written, no Elementor core/vendor file is patched,
and the cached widget/loops never render on a successful hit.

The read-only reflection adapter validates the native static property shape
and available API. Unknown state/contracts bypass storage or replay. Dynamic
loop styles are not rewritten by this adapter. Format 17 separates older
fragments; rebuilding remains lazy, with no preload.

## Verification

- Actual metrica staging Core 4.3.4 / Pro 4.3.1, native Element Cache disabled.
- Two isolated native/miss/hit runs: Classic header 150 loops on native/miss,
  zero on hit, six hits and zero cached original widgets. Same-page fragment
  HTML, ordered external assets, generated CSS bytes and ordered inline CSS
  hashes match. `loop-1635` CSS appears once in every response, rather than twice
  on hits. Atomic Loop also preserves HTML/assets with zero renders/queries.
- Cross-page hit has zero header loops and matching assets/inline CSS. Only the
  two known WP menu fragments' active classes/`aria-current` are normalized for
  the comparison, reflecting the locked shared-content policy. No blanket HTML
  equality exemption was introduced.
- Actual phone User-Agent request executes zero hidden desktop header loops.
- Browser with the candidate installed: desktop miss has 150 header loops;
  the second desktop request, with an unrelated URL parameter, has six hits,
  zero cached original widgets and zero header loops. Both contain one loop
  CSS block. Mega-menu opens and its Positioning tab changes on both responses.
  Clicking the cached menu's Positioning link reaches `/solution/positioning/`.
  At 390px, mobile menu and search open/close; viewport is reset. No captured
  JavaScript errors. Responsive viewport testing is distinct from the actual
  phone User-Agent origin check.
- Unit regression covers cached/native/repeated siblings, prerequisite-state
  fallback, missing/duplicate/unreadable capture state and dynamic-style
  preservation. Existing asset/replay, exact compatibility, 68 updater
  scenarios and 53-file drift tests pass. Portable PHP lint and diff checks pass;
  PHP is not in PATH.

## Evidence and limits

- `fragment-cache-loop-css-fix.patch`: focused runtime diff against the previous
  asset-order candidate; `tests/elementor-fragment-loop-css.php`: new regression.
- `fragment-cache-loop-css-result.json`: counts, CSS hashes and browser results.
- `elementor-434-classic-result.json`, `elementor-434-atomic-result.json`,
  `elementor-434-staging-result.json`: actual acceptance and rollback records.

The printer may read template CSS/settings; the zero-work claim applies to
widget and loop rendering, not to every metadata or CSS operation. Unknown
native CSS state deliberately falls back. This repeat does not claim a new
WPML/concurrency/editor matrix; those prior checks are separately documented.
The exact 4.3.4 / 4.3.1 registry acceptance has not been changed here.

After testing, Core 4.3.3 / Pro 4.3.1, byte-identical original Kit runtime,
saved headers/settings, native CSS metadata/files and updater state were
restored. Independent generated-directory `tar -df` returned no differences.
Temporary observers, fixture and tracked fragments were removed. Native
Element Cache remains disabled; Atomic Widgets remain enabled. Existing
unrelated working tree edits were preserved. The fix is Unreleased; no plugin
version, ZIP, GitHub or production change was made.

## Release acceptance — 2026-10-06

The release-owned registry now accepts exactly Core 4.3.4 / Pro 4.3.1 in Kit 2.23.0. Earlier STOP/unapproved notes above are historical; the asset-order and native loop CSS findings were resolved and staging acceptance passed. Motion source/checksum and actual vendor runtime validation supplements the separate prior 4.3.3 browser matrix; it is not a claim of a new full Motion browser matrix. Full-package release acceptance is recorded separately in release-2.23.0-staging.json.
