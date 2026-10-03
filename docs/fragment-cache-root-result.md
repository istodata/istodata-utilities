# Root menu investigation — 2026-10-02

## Decision

The administrator selected correct current-page highlighting with cache on the two heavy dropdowns. Staging headers 30 (Greek) and 33000 (English) now have `26fa46c6` cache OFF and `417d1ae` / `c60daee` cache ON, with a 86400-second TTL. Elementor native Element Cache remains disabled.

## Root evidence and failed acceptance gate

An anonymous Greek homepage request stored the root fragment. A subsequent authenticated site_manager request to `/techniki-ypostirixi/` reused the same key. Root widget renders fell from 1 to 0 and loop renders from 150 to 0. The stored fragment was 473940 bytes; hit assets matched the normal consumer request.

The complete cached root HTML did not match normal rendering on the destination page. Its nested Nav Menu widgets `a1ff108` and `e62c115` generate page-dependent markup. The destination's normal output contains `current-menu-item`, `current_page_item`, `elementor-item-active` and `aria-current="page"`; the fragment built on the homepage lacks them. Four list items and four anchors differ across the desktop/mobile menu copies. Diagnostic comparison excluding those attributes matched, but that does not make the unchanged shared root HTML correct.

The root acceptance gate therefore failed. Remaining root language/device/preview/purge acceptance was stopped; no complete root acceptance is claimed. No runtime attribute stripping or frozen-highlight acceptance was implemented.

## Final staging state

Only the cache opt-in and TTL fields were restored from the pre-investigation snapshot, preserving other settings. Final verification confirms both language headers, feature flags and disabled native cache. The temporary observer plugin, two test users and private test sessions were removed. Local session credentials were deleted.

The installed Kit remains 2.22.0. All 76 distributed files match the previously accepted ZIP with SHA256 `abc8ddb09ffb43877e0787a3e2129da98e121a4a69d9be968d8c2b03e0bc7023`. No runtime changes or new package were needed for this scope decision. Production was untouched; no commit, push, tag or release was made.

## Evidence

- `fragment-cache-root-origin.json`: completed first root miss/hit comparison and page-state difference.
- `fragment-cache-root-page-state.patch`: exact HTML difference.
- `fragment-cache-root-final-state.json`: verified final saved controls and cleanup.
- `fragment-cache-root-installed-build.json`: installed file equality with the accepted ZIP.
- `fragment-cache-optin-final.md`: prior completed two-dropdown acceptance, including full current-page root HTML equality, menu interactions, language/device contexts, invalidation and concurrent requests.

Existing unrelated working-tree changes were preserved. This investigation added diagnostic tests and reports only; it did not alter Kit runtime, version or release metadata.
