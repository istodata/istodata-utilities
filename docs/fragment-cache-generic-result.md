# Generic widget roots — staging acceptance, 2026-10-02

## Scope

This supersedes the stopped WP Menu candidate report. The administrator explicitly authorized fixing the failed excerpt check and completing generic widget-root support locally and on staging. Production was untouched. No version bump, commit, push, tag or release was made.

Any actually registered `Elementor\Widget_Base` implementation can now attempt the per-element cache. Admission uses its registered name and native dependency APIs, with no widget-type, native-class, vendor-source or third-party-version allowlist. Containers remain outside root caching. Known Template/Loop/Posts adapters still inspect their source/query semantics; these are replay/privacy checks, not a root admission list.

Default OFF, lazy first-request creation, no preload, 24-hour default and existing selectable TTLs, device/WPML variants, generation fencing, locking, purge and the central exact Elementor compatibility registry remain in place.

## Changes

- `elementor-fragment-cache-graph.php`: generic widget registration/dependency validation and dependency/class signature; recursive traversal of stored structures and known referenced templates.
- `elementor-fragment-cache.php`: cache format 15; no-effect fragments preserve live excerpt filters. Mutating fragments check their actual render-slot input, after preceding siblings, rather than the builder-time profile. Original widget creation/render is bypassed on a valid hit.
- Capture rejects token/private/query/session contexts, inline asset mutations, unreplayable late asset registration, deferred frontend output-handler changes, asset dequeue/reordering, changed post/media context and Atomic interactions discovered inside opaque nested document rendering. Empty native output is a valid cache result.
- Menu-item API updates and deletions invalidate the existing site generation. Menu/template changes and protected manual purges retain lazy rebuilding and old-writer fencing.
- Local regressions, changelog and maintainer documentation were updated. Existing unrelated working-tree changes were preserved.

## Actual staging coverage

Target verified by home URL and physical app path; private code/database backups were taken before writes. Pair: Kit 2.22.0 / Elementor 4.3.3 / Pro 4.3.1. Native Elementor Element Cache remains `disable`.

| Case | Observed result |
| --- | --- |
| Four WP Menu roots: d7ebc85, a1ff108, e62c115, c28398e | Stored then hit; no original target widget render or target `wp_nav_menu` construction on hit |
| Template roots 417d1ae, c60daee | Greek 150 → 0 loops; English 156 → 0 loops |
| Core Text Editor (previously excluded root) | Stored then hit; full native wrapper HTML retained |
| Custom widget with CSS/JS and frontend settings | Stored then hit; actual custom `render()` body 1 → 0; original widget type/settings preserved |
| GR/EN anonymous → site_manager and site_manager → anonymous, across two pages | Same keys; source HTML equals native source; consumer HTML equals native consumer except deliberately shared current-page attributes; required assets equal |
| Subsequent public query and excerpt processing | Actual result IDs, excerpt output, excerpt length, Pro displayed IDs and excerpt profile equal native consumer baseline |
| Desktop navigation | Cached menu links navigate with HTTP 200; enclosing mega-menu dropdowns open with native hover handlers |
| Phone | Hidden heavy subtree absent; native phone popup opens; a temporary native WP Menu fixture opens its mobile toggle and navigates with HTTP 200 |
| Custom native frontend initialization | Actual Elementor `element_ready/iu-generic-proof.default` runs on hit in desktop/phone GR/EN; no fallback initializer; CSS marker and button interaction pass |
| Editor/native preview | Real editor iframe and direct preview render normally with zero cache operations/diagnostic writes. Six menu/template opt-ins plus core/custom fixtures exercised in the preview render copy; saved documents/autosaves were not changed |
| Concurrent cold requests | One producer; waiting authenticated consumer hits all eight roots without duplicate original widget/loop rendering |
| Protected site purge | Subscriber/invalid nonce rejected with 403 and no effect; authorized purge changes GR/EN keys; lazy rebuild followed by zero-loop hits |
| Actual source menu-item edit | Real `wp_update_nav_menu_item()` changes generations in both languages; changed title appears on miss and hit; exact temporary title restored and clean content rebuilt |
| Privacy negative controls | Custom token output → `volatile-markup`; permission-sensitive custom query → `query-context`; neither is stored/hit. Private cookie, active session and global OFF bypass |

Browser cases reported no frontend JS errors or failed JS/CSS requests. English `d7ebc85` is empty in native rendering; its empty fragment is cached correctly. Its missing translation/content was not repaired.

The final package adds a conservative asset queue guard after the broader acceptance run. Final actual GR/EN miss/hit checks pass for all eight roots, still produce zero original bodies/menus/loops, and match the native-parity HTML hashes from the broader run. Native custom initialization was also tested on that final build.

## Contract and limitations

Opt-in declares reusable public HTML **as-is across pages/users**. Cached WP Menu `current-menu-item`, `elementor-item-active` and `aria-current` retain the first request's values. There are no page/user variants or attribute rewriting. Root `26fa46c6` stays OFF; this alone does not make its opted-in child menus' attributes fresh.

The implementation attempts any registered widget; it does not certify every plugin or widget. Opaque internal globals, arbitrary direct DB/network reads and mutations to untracked hooks cannot be proven or generically replayed from HTML. A widget requiring such per-request effects must remain uncached. Detected unsafe contexts receive concrete bypass reasons; turning ON does not override privacy, preview, asset or replay guards. Test each new widget's public miss/hit output and interactions before site activation.

Preview can select an older Elementor autosave than the current header document. The test enables additional opt-ins in the temporary render copy to prove bypass with all roots, without saving an autosave. Mobile WP Menu toggle coverage uses a temporary fixture because the existing heavy desktop subtree is intentionally hidden on phones; the site's ordinary phone popup was also exercised.

Public content dependencies without a known invalidation hook retain the selected TTL/manual purge policy. No preload or background warming was added. Source-menu API invalidation and template source changes are covered; arbitrary external data changes need an explicit integration or manual purge.

## Final delivery

ZIP: `H:\Το Drive μου\Development\ISTODATA PLUGINS\istodata-utilities.zip`.

SHA256: `0edbc0289d047cdd14d9dd7210d0703f40231eea333488e5fb4e7558cf3336c2`.

The clean staged archive was inspected: one `istodata-utilities/` root, main plugin file inside it, 76 files, forward-slash entry paths, no `.git`, `.github`, `.claude`, tests/docs, metadata or nested archives. Version remains 2.22.0 for testing. This is a staging candidate; no production installation or release is authorized.

Final staging: six real opt-ins in each header (30/33000), TTL 604800; root OFF; native cache disabled. Temporary fixture widgets are render-copy additions, never saved elements. Observer plugin, temporary users/sessions and own fixture indexes/diagnostic rows are removed at completion. Unreachable fixture fragments expire by their normal TTL. The final installed-file/cleanup evidence and ordinary frontend observations are recorded separately below.

Final verification confirms all 76 installed files match the ZIP and current local source. Two ordinary authenticated requests per language, with no probe parameters or observer, leave twelve real frontend HIT observations (all six opt-ins in each header). No fixture assets or temporary menu title appear. The observer, temporary identities and private session files are absent; own menu edit cleanup is complete. Atomic and Fragment feature flags remain ON. No candidate/settings rollback was performed.

Unrelated pre-existing tracked changes were preserved in `.gitignore`, `AGENTS.md`, Atomic breakpoint JS/PHP, Device Visibility PHP, the main plugin/version file, Atomic documentation and Atomic tests. Existing earlier untracked modules/reports were also preserved. The current runtime changes are confined to the two fragment cache implementation files plus changelog; the other changes are scoped tests/docs.

## Evidence

- `fragment-cache-generic-scoped.patch`: full runtime/changelog changes against the previously accepted ZIP, plus scoped local regression changes.
- `fragment-cache-generic-origin.json`, `fragment-cache-generic-final-origin.json`: cross-context/native parity and final-build bypass.
- `fragment-cache-generic-ui.json`, `fragment-cache-generic-native-init.json`: browser interactions and native custom initialization.
- `fragment-cache-generic-preview.json`: native iframe/direct preview.
- `fragment-cache-generic-concurrent.json`, `fragment-cache-generic-purge.json`, `fragment-cache-generic-menu-invalidation.json`: concurrency and invalidation.
- `fragment-cache-generic-private.json`: concrete negative controls.
- `fragment-cache-generic-ordinary.json`, `fragment-cache-generic-final-state.json`: normal frontend requests, final configuration, build equality and cleanup.
- `fragment-cache-generic-zip.json`: actual archive verification.

Local graph, context, assets, replay-order, diagnostics, bootstrap, polling, compatibility, updater and drift suites pass. Syntax checks pass for the main plugin and modified runtime files. Portable PHP was used; PHP is not in PATH. No compatibility declaration was broadened.
