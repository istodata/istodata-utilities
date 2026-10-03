# Widget root expansion: stopped acceptance — 2026-10-02

## Scope and status

The requested scope was expanded during this run from WP Menu roots to all widget roots that expose the opt-in. That general expansion is **not implemented or accepted**. The first actual WP Menu acceptance gate failed with a specific replay-context blocker, so further acceptance stopped in accordance with the instruction to stop on a failed check.

The candidate contains the smaller WP Menu root admission and menu-item update/deletion invalidation changes. No Elementor core changes, version bump, commit, push, tag or release were made. Existing unrelated working-tree changes were preserved.

## Actual staging failure

Staging was verified by home URL and physical app path before writes. A private code archive and non-empty database backup were created under `../tmp/iu-fragment-nav-20261002` with restrictive permissions.

Header 30 and its existing English counterpart 33000 were configured with the four WP Menu opt-ins (`d7ebc85`, `a1ff108`, `e62c115`, `c28398e`) and two Template opt-ins (`417d1ae`, `c60daee`), all TTL 604800. Root `26fa46c6` remains OFF. No production settings were changed.

An anonymous Greek homepage miss followed by a site_manager request to `/techniki-ypostirixi/` produced:

| Element | Miss | Next request |
| --- | --- | --- |
| d7ebc85 | stored | hit |
| 417d1ae | stored | hit |
| a1ff108 | stored | hit |
| c60daee | stored | hit |
| e62c115 | excerpt-side-effect | excerpt-side-effect |
| c28398e | excerpt-side-effect | excerpt-side-effect |

The hit did not execute the first two original WP Menu widgets or their `wp_nav_menu` construction. The two later WP Menus rendered normally. The two heavy Template fragments retained their early bypass: loops fell from 150 to 0.

The rejected menu build metadata contains only the native `excerpt_more` callback, captured when the builder data is traversed. Immediately before those menus render, the live profile contains Classic Posts callbacks for `excerpt_length` and `excerpt_more` as well. The preceding dropdown's Classic Posts rendering/replay establishes those callbacks. `finish()` rejects the difference between builder-time and render-time excerpt structure as `excerpt-side-effect`. Instrumentation confirms this difference; it is not a menu-type or third-party-version rejection.

No complete HTML/assets/interaction acceptance is claimed. The strict six-root test failed before those assertions. English miss/hit, WPML menu resolution, mobile, editor/preview, actual purge/menu-edit invalidation and representative custom widget acceptance were not completed in this run.

## Proposed correction

Review the excerpt context contract in both publication and proxy replay. A fragment whose render leaves the live excerpt profile unchanged should preserve that live profile on a hit; it must not require an unrelated earlier sibling's builder-time profile to equal its render-time profile. Fragments that change excerpt registration still need the existing validated replay contract, external callback identity checks and ordering tests. Do not simply remove the excerpt safety checks.

After that correction, implement generic admission through actual registered widget instances and native dependency APIs, preserving private/token/session/query/editor guards and explicit capture side-effect exclusions. Validate a previously excluded core widget, WP Menu, Template/loops and a custom widget with its own CSS/JS. The administrator's opt-in declares shared output; it does not guarantee replay of opaque globals, arbitrary callbacks, runtime registrations or private content. Those need specific technical bypass reasons. No assertion that every vendor widget has been tested is justified.

Shared menu current-page attributes remain those of the first request under the requested as-is contract. This run did not introduce per-page keys or modify those attributes.

## Local checks and final state

- Graph regressions: WP Menu root dependencies; unknown implementation, private dynamic setting and Atomic interaction rejection.
- Bootstrap regressions: menu-item API invalidation hooks; default-OFF sites acquire no epoch; unrelated post deletion does not purge.
- Existing asset, context and replay-order suites passed.
- Central compatibility policy, updater guard and runtime drift suites passed; registry was not changed.
- Main plugin and modified cache file syntax checks passed with portable PHP. PHP is not available in PATH.
- Final `git diff --check` passed.

The installed candidate remains Kit 2.22.0 / Elementor 4.3.3 / Pro 4.3.1. Native Elementor Element Cache stays disabled. The observer plugin, two temporary users and private test sessions were removed; local credentials were deleted. No automatic candidate/settings rollback was performed after failure. Both language headers retain the six test opt-ins and 7-day TTL.

The **unaccepted test candidate** ZIP is `H:\Το Drive μου\Development\ISTODATA PLUGINS\istodata-utilities.zip`, SHA256 `744b551044b5209dcc3f02b87ad86e34d95b72b3960d11214f93bc9e76ac1501`. It was built from a clean staging copy and the actual archive was inspected: one `istodata-utilities/` root, main plugin file inside it, 76 files, forward-slash paths, no development directories/metadata/nested archives. All 76 installed files match that ZIP. It supersedes the previously accepted local ZIP file but is not a production or release candidate approved by acceptance.

## Evidence

- `fragment-cache-nav-blocker.json`: actual miss/hit events, original menu construction and excerpt profiles.
- `fragment-cache-nav-scoped.patch`: runtime/changelog diff against the previously accepted ZIP.
- `fragment-cache-nav-final-state.json`: installed-build equality, final opt-ins and cleanup.
- `fragment-cache-nav-zip.json`: inspected archive details.
- `tests/fragment-cache-nav-origin.py`: failed six-root acceptance test, retained for the next correction.

Production was untouched.
