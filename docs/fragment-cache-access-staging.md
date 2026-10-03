# Fragment cache access and global controls — staging status

Date: 2026-10-01. Target: https://wordpress-218158-6702910.cloudwaysapps.com/ only. Kit 2.21.2, Elementor 4.3.3 / Pro 4.3.1; native Element Cache remains disabled. No production, release, version, commit or push changes.

## Implemented

- Global switch defaults OFF; OFF prevents fragment lookup/storage and hides the widget panel while preserving saved opt-ins and TTL.
- The global switch alone activates the compatibility updater constraint. No saved-element metadata scan remains.
- Fully audited independent fragments can be shared between anonymous and authenticated requests; invalid auth, unknown cookies, private or volatile contexts bypass.
- Admin-bar all-site purge requires manage_options and a nonce. Generation checks fence concurrent writers. No preload or other cache flush.
- Panel title: ISTODATA — Cache στοιχείου. Default TTL: 24 hours.

## Actual staging evidence

- Final tested package: SHA256 `1ae300c2a4d010da0f02a52725c7180493ee2fab17667b0f559a705afe81fba3`; clean package has 74 entries, one plugin root, correct main file, forward-slash paths and verified exclusions/CRC. All 74 installed staging files match the final ZIP byte for byte; see `fragment-cache-preview-build-manifest.json` and `fragment-cache-preview-build-verification.json`.
- Final Greek anonymous-to-manager reuse: 150 loops on miss, zero on hit. English manager-to-anonymous: 156 loops on miss, zero on hit. Keys, fragments and assets matched ordinary consumer rendering. See `fragment-cache-access-final-reuse.json`.
- Cached elements are dropdown Template widgets `417d1ae` and `c60daee`, in documents 30 and 33000, TTL 86400. The central `26fa46c6` stays OFF and executes once, preserving current-page navigation. This final design does not claim complete bypass of that central widget.
- Anonymous/admin/subscriber cross-context and language checks passed; no private fragment markup. Unknown cookies remain a bypass limitation.
- Global OFF caused zero lookups/storage. Native editor OFF/ON saves preserved choices and TTL. Unexpected central opt-in during initial test setup was caught, corrected through native editor and retested with strict before/after assertions.
- Authenticated hover opening and navigation passed without browser JS errors. Existing White Label CMS hides the frontend admin bar; the purge action was tested in the backend bar without changing that setting.
- Unauthorized and invalid-nonce purge returned 403; authorized purge invalidated both languages lazily. Two in-flight old-generation writers rejected publication after purge. See purge/race evidence JSON files.
- Final phone requests, anonymous and manager, had zero fragment keys, events and loops; hidden heavy menu remained pruned. See `fragment-cache-access-device.json`.
- Compatibility, update guard (68 scenarios) and drift (50 runtime files) suites passed. PHP is unavailable in PATH; tests used portable PHP 8.5.11, with staging CLI PHP 8.2.33 lint checks. Existing Atomic and device visibility work was preserved.

## Preview acceptance closed

The original shutdown snapshot was insufficient: Elementor's zero-argument preview API depends on the current post. Nested document rendering can change that post. A narrow runtime guard now rejects every request containing `elementor-preview` before the query filter, lookup, graph evaluation or proxy substitution. No unsafe preview hit was observed; the explicit guard prevents a permissive query filter from reopening the context. Regression cases cover an allowed query filter, an inactive preview API/current-post switch and an empty preview parameter, followed by an ordinary frontend request.

The native header editor iframe renders the edited header on the client; its initial server response does not send header 30 through the builder filter. This was identified rather than counted as a successful opt-in bypass test. The final test opens the real native Elementor editor for homepage 2 and records its actual iframe navigation, then requests that same native preview URL separately. Both server requests render header 30 and send its saved opted-in Template nodes through `iu_elementor_fragment_gate`.

At that exact early gate, document 30 records preview=true, preview_request=true, admin=false, query_allowed=true, request_context_ok=false and eligible=false. Both saved opt-ins (`417d1ae`, `c60daee`) are present. Every gate is ineligible. There are zero walk-node, fragment transient lookup/storage, substitution or result events. Normal rendering executes 477 widgets and 150 menu loops in each preview response. The native editor backend also records normal rendering and zero cache operations. No editor save is performed; final saved-state verification confirms both dropdown opt-ins and 24-hour TTL are preserved and central navigation remains OFF.

Observer positive control: the same operation observer detects actual cache lookups on ordinary authenticated frontend hits. Final Greek and English anonymous-build/manager-hit pairs retain shared keys, identical normal-consumer HTML/assets and zero hit loops. See `fragment-cache-preview-gate.json`, `fragment-cache-preview-gate.png` and `fragment-cache-preview-frontend.json`.

One native editor navigation timed out at 180 seconds during staging slowness; a later successful run waited for the actual iframe navigation instead of assuming it existed after a fixed delay. A separate initial test expected server-rendered header content in the header editor iframe; that false-negative assertion was replaced with the real page preview described above. Neither unsuccessful attempt is counted as a pass.

Temporary observer plugin was deactivated and deleted. Both temporary users and the newly created manager session were removed, and temporary credential files deleted. Global switch stays ON only on staging; central menu OFF, dropdown opt-ins ON at 24 hours. Native Elementor cache stays disabled.

Post-cleanup checks confirm temporary users, observer directory and private session file are absent (`fragment-cache-access-final-state.json`). Ordinary Greek/English public requests return 200, contain the menu and have no observer marker (`fragment-cache-preview-final-public.json`). The original private Kit archive remains unchanged (SHA256 `a8866535fdb18b2299cdda9fcaebd772cbe6c49869caa4dd9f4d68d63b7c95b8`), and the initial settings backup is preserved.

Scoped code diff: `fragment-cache-access.patch` (5 files, 142 insertions / 76 deletions), based on the pre-task package. Earlier broader investigation and acceptance documents remain historical evidence. Compatibility declarations, UI and activation policy remain unchanged by the preview correction. No version bump or release was performed.
