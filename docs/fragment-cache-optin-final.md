# Advanced Elements Cache — final opt-in policy acceptance

2026-10-02, Kit 2.22.0, authorized metrica staging only.
Final ZIP SHA256: abc8ddb09ffb43877e0787a3e2129da98e121a4a69d9be968d8c2b03e0bc7023.
All 76 installed staging files match that ZIP after cleanup. Production, GitHub
release/tags, commits/pushes and automations were untouched.

## Policy and scoped changes

Administrator opt-in declares output reused as-is across pages and visitors.
Removed blanket third-party version/source contracts and unknown-hook rejection.
Retained the sole exact Elementor/Pro registry (4.3.3/4.3.1), concrete private/
session/query/token exclusions, device/WPML variants, supported structures and
asset/excerpt/query-result replay. No page or user key. Existing arbitrary excerpt
callbacks remain live with their actual objects/order; changed external registration
within capture bypasses. Identity hashes express order, not source approval.
Cache format 14 fences entries from the former policy. Preview and global OFF do
not write observations. Public terminology and saved control IDs remain intact.
Default OFF, lazy 24-hour TTL, no preload, protected purge and owner/generation
fencing remain unchanged. Former source/hook helpers are archived with manifests.

Scoped code/test diff: fragment-cache-optin-scoped.patch. Main current design and
administrator responsibilities: elementor-fragment-cache.md. Unreleased changelog
entries describe this policy and administrator status without changing version.

## EN dropdown investigation

The first uninstrumented hit click left aria-expanded false. It was not counted
as a pass. The prior stable build, candidate uncached and candidate HIT were then
compared at the same 1440x1024 desktop viewport/UA/page, fresh authentication and
normal consent UI state. The actual translated heavy dropdown was located from
417d1ae; unique visible root/template/button counts ruled out hidden clones.
The comparison explicitly waited for its title's real jQuery mouseover handler.
The configured trigger is open_on: hover. Event traces prove mouseover/mouseenter
opened the same content before physical click, and the content/22 links were
visible; click and native hover both passed on all three builds. Geometry,
settings and destinations matched. Candidate uncached/HIT resource URLs match
exactly. Stable CSS ver timestamps changed during normal Elementor regeneration
on plugin installation; resource paths match and both version URLs were fetched
and returned identical CSS bytes (checked after installation).

The original instant was not traced, so its exact transient cause is not proved.
No plugin JS fix was justified. The harness now verifies handler readiness,
unique visible controls and actual dropdown/link visibility rather than asserting
only aria-expanded after an arbitrary delay. The comparisons passed again on the
final candidate. Native editor test synchronization also used Playwright's actual
third options argument instead of accidentally falling back to a 30s timeout.

A separate measured preview observation write was corrected locally. The final
real native iframe and direct preview each render 477 widgets/150 menu loops,
with zero fragment operations and zero observation writes; no editor save.

## Actual final results

| Check | Observed result |
|---|---|
| GR anonymous/manager/subscriber cross-page pairs | 150 miss loops, 0 hit loops |
| EN same three pairs | 156 miss loops, 0 hit loops |
| Original cached Template widgets | 0 renders on hit |
| Outer 26fa46c6 | 1 native render; full HTML equals current-page baseline |
| Fragments/assets/excerpt/displayed-ID effects | Equal normal consumer output |
| Unknown no-op pre_get_posts callback | Stored then HIT; no blanket rejection |
| Token or readable-permission query fixtures | No publication/HIT on repeated requests |
| Session/private cookie/user dynamic tag | Ordinary render, no cache publication |
| Global OFF | No fragment operations or observation writes |
| GR/EN desktop and phone menus | Real opening and navigation 200; no JS errors |
| Administrator status UI | Native editor button and Tools, nonce403/anonymous denial; no save |
| Site purge | Capability/nonce403; GR/EN lazy rebuild then 0-loop HIT |
| Selective purge | Only 417d1ae rebuilt (72 loops); sibling and EN remained HIT |
| Concurrent cold requests | One producer; waiting consumer 0 loops/Template renders |
| Purge while old producer runs | Both old writers generation-changed; new miss then HIT |
| Ordinary Ivory searches | Four GET queries/results match prior verified 5.5.18 baseline |
| Ordinary non-probe manager frontend | Equal fragments on paired requests; all four final observations HIT |

Machine evidence is in fragment-cache-optin-final-acceptance.json and the
origin/context/preview/menu/mobile/diagnostics/purge/selective/concurrent/race/
search/ordinary/installed-build/final-state JSONs, plus EN comparison event traces
and screenshots. Compatibility/update/context/graph/assets/diagnostics/bootstrap/
poll/replay-order/drift commands passed, with five PHP lint checks. Portable PHP
8.5.11 was used; PHP is not in PATH. git diff --check passed. These are actual
request and interaction results, not only HTTP success or mocked loop counters.

## Dependencies, boundaries and final state

Loop 1635 contains one image with post URL/featured image tags. Template 7767 has
12 partner Loop Grids; 7778 has 13 plus 13 hidden-excerpt Classic Posts queries.
All grids reference 1635 with fixed term criteria and empty custom query IDs.
Current inventory is fragment-cache-optin-dependencies.json. Elementor template/
config metadata invalidates; partner/article/media/term/external content changes
need manual purge for immediate freshness, otherwise TTL applies. An existing
validated entry can have bounded 180-second stale grace during concurrent rebuild.

The administrator contract does not prove independence of arbitrary custom hooks.
Hits skip widget/loop hooks; opt out if their per-request effects are required.
Unsupported/Atomic roots or unhandled replay structures retain normal rendering.
The deliberate 999.0.0 third-party constants are policy fixtures, not live vendor
compatibility certifications. No WPML version wall remains. Existing native EN
links pointing to Greek routes are unchanged; cache neither translates nor fixes
those links. Ivory AJAX UI was not separately exercised.

Final staging: global Atomic/Fragment ON, native Element Cache disable. Root
26fa46c6 OFF and Template 417d1ae/c60daee ON/86400 in both headers 30 and 33000,
exactly matching the before snapshot. Temporary observer, test users, own manager
session and remote/local credential files were removed. Prior inactive probes
and private recovery backups were preserved. The candidate remains installed;
there was no further automatic rollback. Parent upload ZIP contains this final
candidate, not the prior stable a905 package. Archive verification proves one
istodata-utilities root, correct main file, forward slashes, both versions 2.22.0,
no git/dev/test/docs/metadata/nested ZIP entries and exact installed file hashes.

Pre-existing Atomic/Device Visibility changes, AGENTS.md, .gitignore and their
related tests were preserved. No unrelated working-tree cleanup or Git action.
Production installation or public release needs separate approval.
