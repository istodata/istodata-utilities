# Atomic element fragment cache — staging acceptance, 2026-10-05

## Result and scope

PASS for the installed Elementor **4.3.3 / Pro 4.3.1** pair on metrica staging
(`wordpress-218158-6702910.cloudwaysapps.com`). The local compatibility registry
accepts this exact pair for Atomic cache/device visibility. No other version pair
is inferred. This work is Unreleased; no version bump, ZIP, commit or release was made.

## Implementation

The shared engine substitutes builder data with its fragment proxy before the
original element is instantiated/rendered. Eligible Atomic widgets use the
existing widget capture hooks; Atomic elements such as the new Loop use generic
element capture hooks. Hits bypass the original widget/Loop and its descendants.
No Elementor core changes are required.

Atomic controls use the existing saved control IDs with native typed schema:
opt-in defaults OFF; duration defaults to 604800 seconds (7 days). Saved Classic
values remain supported. Creation is lazy on a real request, with no preload.

Atomic keys include page/post/path context as well as device and WPML language.
Only reviewed public-post/literal contexts are admitted. Valid logged-in
frontend requests share the same fragment with anonymous requests; unrelated
URL parameters do not create new variants. Concrete private sessions, dynamic
user/URL resolvers, unsafe queries and markup still bypass. Dependencies
come from actual rendered instances and queue deltas on the miss. Source
post/term/thumbnail changes invalidate Atomic generations; existing document,
Kit and template invalidation and publication fences remain in use. Shared locks
prevent duplicate concurrent generation. Atomic public post IDs do not modify
the Classic avoid-duplicates list. Typed device visibility runs before reuse.

## Early bypass and assets

| Fixture/request | Original renders | Loop queries | Output items |
| --- | ---: | ---: | ---: |
| Atomic Loop native | 5 | 1 | 3 |
| Atomic Loop miss | 5 | 1 | 3 |
| Atomic Loop hit | **0** | **0** | 3 |
| Atomic Heading native | 1 | 0 | 1 |
| Atomic Heading miss | 1 | 0 | 1 |
| Atomic Heading hit | **0** | 0 | 1 |

Loop renders include the Loop, layout and repeated items. The fixture used real
post titles and URLs. Within each run, native/miss/hit HTML hashes, external
JS/CSS manifests and inline JS/CSS hashes matched exactly. Request IDs verified
fresh PHP requests rather than a full-page cache response.

Evidence: [latest Loop acceptance (4.3.4)](elementor-434-atomic-result.json),
[standalone Heading](atomic-fragment-widget-result.json).

The extra action-link script found in an earlier candidate was corrected by
collecting actual-instance dependencies instead of unbound prototypes. Final
Loop and Heading runs both passed asset equality. The earlier finding is retained
in [historical mismatch evidence](atomic-fragment-dependency-mismatch.json).

## Context, editor and browser checks

- Greek/English WPML and real phone-UA native/miss/hit variants passed, with
  matching assets and correct translated article URLs. English uses `/en/`.
- Another actual page produced its own miss then hit. The original acceptance
  rejected logged-in requests and unknown parameters; the 2026-10-06 correction
  aligns Atomic reuse with Classic shared opt-in semantics. The correction passed
  real authenticated and random-parameter requests, including a logged-in writer
  followed by an anonymous hit: [context correction](atomic-fragment-shared-context.md).
- Cache OFF repeatedly rendered natively. Hide-on-phone produced zero items,
  zero original executions and zero Loop queries on a phone request.
- Two simultaneous cold requests produced one miss/one hit, identical output,
  and only one Loop query. A disposable source-post change caused a new miss,
  then a hit. Fixture keys were selectively removed from Redis on cleanup.
- Native Elementor controls were edited OFF/ON and saved through Save Draft;
  typed settings persisted. The displayed duration was 7 days. Native saved
  inactive query fields with null values are treated as absent.
- Desktop and mobile viewport styling and a cached title's navigation to its
  actual article passed without new frontend JS errors. Desktop color was
  `rgb(18, 52, 86)`; the narrow viewport used `rgb(101, 67, 33)`.
- Editor/preview remain outside fragment reuse. The native editor remained
  editable and the preview observer reported no cache hit. Editor AJAX/REST
  render queries were not fully instrumented, so no execution-count claim is
  made for those calls.

Evidence: [acceptance requests](atomic-fragment-integrated-acceptance.json),
[language/device](atomic-fragment-language-device.json),
[native policy](atomic-fragment-policy-result.json),
[browser observations](atomic-fragment-integrated-browser.json).

## Limits

Initial Atomic reuse is page-specific. Pagination, custom/current queries,
random/relative-date queries, components, interactions, conditions, embedded
documents and unknown resolvers bypass the cache. Dynamic post fields require
their own reviewed public Loop. Mixed Classic roots containing Atomic descendants
also bypass. Registered Atomic elements are inspected structurally, without a
vendor/widget-name allowlist; this is not acceptance of every Atomic feature.

The fixture's native three-column layout is not a mobile layout recommendation;
its narrow-screen wrapping was preserved. Cached pagination/AJAX interactions
are not certified. Direct rendering outside the builder-data pipeline is not an
independently accepted early-bypass path.

## Verification and restoration

Compatibility, update guard, runtime drift, Atomic activation policy, shared
fragment context/graph/query/lock/assets/replay/diagnostics and device visibility
checks passed. PHP syntax checks use the portable PHP binary because PHP is not
in PATH.

Temporary staging runtime files were restored byte-for-byte. Disposable fixtures,
CSS, private probe files, MU loader and their fragment entries were removed.
Settings and real documents 30/33000 were unchanged. Staging retains installed
Kit 2.22.0, Atomic Widgets ON and native Elementor Element Cache disabled.
Production was not changed. Pre-existing changes from other conversations were
preserved.

## Next manual staging test

When a test ZIP is requested, install only on staging. Use the accepted exact
pair, enable the Kit cache module, and opt in a reviewed real Atomic Loop; confirm
7-day default and preserved explicit durations. With full-page caching bypassed,
compare first miss/second hit execution diagnostics, assets, title links and
responsive styling. Repeat on another page, Greek/English and a real phone UA;
verify hide-on-phone, OFF, editor/preview and custom-query bypass. Change a
source post and confirm a fresh miss. Keep native Elementor Element Cache OFF.

## Release status — 2026-10-06

The initially Unreleased work above is included in Kit 2.23.0. The exact Core 4.3.4 / Pro 4.3.1 pair was subsequently reviewed and accepted with separate evidence in elementor-434-acceptance.md. Full-package verification is recorded in release-2.23.0-staging.json.
