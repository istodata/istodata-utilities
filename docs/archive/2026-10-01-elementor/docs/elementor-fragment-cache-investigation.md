# Current cross-page dropdown acceptance — 2026-10-01

The final administrator-selected policy reuses opted-in content across pages.
Page/path/current-post/queried-object keys and automatic scope inference are
removed. The administrator must select content that can remain the same; unknown
executable hooks and unsupported replay effects still cause technical bypass.
The new control explicitly explains reuse across pages and visitors.

## Correct metrica targets

| Element in header 30 | Referenced dropdown document | Included work |
|---|---:|---|
| Template widget 417d1ae | 7767 | 12 partner Loop Grids, template 1635 |
| Template widget c60daee | 7778 | 13 partner Loop Grids and 13 hidden-excerpt Classic Posts |

These two Template widgets contain the expensive dropdown contents. The central
mega-menu 26fa46c6, sibling navigation and active/current/ancestor classes render
normally. Template is now a generic supported root type; runtime code contains
no metrica document/element IDs, template fingerprints or content-eligibility
adapter. Containers remain unsupported roots; a Template widget is sufficient.

## First request on another page

Page A was `/`; page B was `/techniki-ypostirixi/`. Both were anonymous desktop
origin requests (`X-Cache: MISS`), using fresh private test generations. The first
request on B reused the two fragments created on A, with exactly the same keys.

| Check | A cold miss | First B origin | B normal baseline |
|---|---:|---:|---:|
| Central mega-menu renders | 1 | 1 | 1 |
| Dropdown Template renders | 2 | 0 | 2 |
| Loop template 1635 renders | 150 | 0 | 150 |
| Nested element renders (concurrency capture) | 718 | 16 | 718 |
| Required external assets on B | — | 87 | 87 |

B's full header HTML equals its normal baseline byte-for-byte (SHA-256
`5a6f3ee9a93a3ad8d0aaae460e011234ef8a55fb35b98a77222e8d7488065155`).
Its current-menu-item/aria-current states differ from A as required. Pro displayed
IDs and complete excerpt profiles also match B's normal rendering. The two cached
Template widgets are bypassed before original instance creation; all their
nested render/loop work is skipped. See fragment-cache-cross-page-staging.json.

A browser comparison on B confirmed hover-open, 224 dropdown links, 722px panel,
normal active navigation, navigation/redirect to the topography route with HTTP
200, identical parsed DOM/87 assets and zero JS errors. Clipped menu screenshots
had **0 different pixels out of 929,214**. Consent used the normal UI actions.

The existing relative company URL `/etairia/` does not receive Pro's server-side
e-current even in ordinary uncached rendering: Pro compares its empty URL host
with the absolute permalink host. The initial assertion expecting different
company-page classes was therefore unsuitable; no site content/core change was
made. The final B route exercises a real active WordPress navigation item and
proves that cache replay preserves its normal page-dependent classes.

## Languages and devices

English `/en/` -> `/en/the-company/` also passed cross-page reuse. Its translated
loop document rendered 156 times on A and zero on B; both dropdown Template
widgets were skipped. B's full header/assets/Pro IDs/excerpt profile matched its
normal baseline (83 external assets, versus 85 on the English homepage). Greek
1635 is not the English loop ID; the probe counts translated Loop documents too.
Language/document identities separate the English and Greek entries. See
fragment-cache-cross-page-en-staging.json.

The phone request retained zero desktop mega-menu/Template/Loop renders and no
fragment lookup keys, after the existing Device Visibility pruning. Earlier
actual mobile-popup interaction and normalized device-key regressions remain
valid; that path was not changed by this extension.

## Query and replay corrections

Reviewed queries disallow Avoid duplicates and do not read prior displayed IDs.
Those IDs therefore do not fragment keys. Capture records **all** query-result
IDs, including IDs already present in the building request; hits merge them at
its render position. A regression proves this distinction from storing only a
prior-dependent delta. Cached descendants also populate enclosing capture ledgers.

Excerpt input structures remain checked while parent/length values do not enter
keys. Unchanged excerpt state is preserved, while changed Classic skin state
replays exact callbacks/control-parent semantics. Real-Pro future-removal tests
pass. No original Template render/get_query or nested loop runs on hits; one
control-only Posts instance can be created for excerpt-state replay.

Additional image/query hooks are now audited. Rocket 3.23.2.2's
maybe_disable_core_lazyload reads configuration/boolean filters only; its effective
image/iframe results are signed after independent nested-hook review. WordPress
media counters/priority/loop/filter/header state is keyed and must remain unchanged
during capture/replay. Unknown hooks are not blanket-approved by administrator opt-in.

## Contention, invalidation and cleanup

Three simultaneous cold requests with an injected **20s delay per dropdown** took
about 56s for the complete page. Each dropdown had one stored result and two hits;
only one request rendered the 150 loops, and the followers rendered 16 shell
nodes instead of 718. The 45s cold wait bound applies to each fragment.

An actual nonce/capability-protected purge after aging the first entry defeated
stale retention: the next B request rebuilt only 417d1ae (72 loops), retained a
hit for c60daee, and the following request hit both (zero loops). Header HTML
remained equal to the same B baseline. Template-source signatures/epochs and
24h ordinary-content TTL/manual purge remain in place, with no preload.

Standalone regressions cover default-off/real Template controls, unknown widget
implementations/hooks, query/dynamic/cycle rejection, cross-page keys, full-ID
replay, source signatures, context/device/WPML gates, assets, locks and polling.
An additional regression prunes opted-in descendants beneath native/Kit hidden
ancestors before eligibility/lookup, while keeping default-off branches unchanged.
PHP is absent from local PATH; syntax/regression tests use staging PHP.

Full Kit upload/save/reload/editor visual testing remains the documented staging
follow-up. The installed Kit has not been replaced. No production change, version
bump, commit/push/tag or release was made. Prior unrelated Device Visibility,
Atomic description, AGENTS and changelog cleanup changes remain preserved.

The final same-key stale-refresh run also passed: each dropdown had one stored
refresh and two stale hits, with only 150 loop renders across all three requests.
The temporary cross-page harness was deactivated/deleted and uploaded fixtures
removed. Own database indexes/epochs were removed; external-object-cache test
fragments expire with their TTL, without a global flush. Native Element Cache
still reports `disable`. Saved Elementor data was not edited.

---

## Historical per-page implementation evidence (superseded policy)

# Current generic Kit staging acceptance — 2026-10-01

The `builder_content_data` rejection was corrected after actual callback/order
and source review. The reusable Kit policy now caches `26fa46c6` without staging
eligibility, prototype or dependency overrides and without site IDs/fingerprints
in runtime code. This is a local unreleased implementation, not a Kit install
or production deployment. Earlier adapter-only results below are historical.

## Exact builder callbacks

Observed order (64-bit PHP), with the private opt-in injector immediately before
the final priority. Full absolute source paths are recorded in
`fragment-cache-builder-order-staging.json`.

| Order | Priority | Callback | Plugin/source |
|---|---:|---|---|
| 1 | 10 | WPML_Elementor_Translate_IDs::translate_global_widget_ids | SitePress addons/wpml-page-builders/.../class-wpml-elementor-translate-ids.php:91 |
| 2 | 10 | ...::translate_product_ids | same file:106 |
| 3 | 10 | ...::translate_ids_in_widget_fields | same file:140 |
| 4 | 10 | WPML\PB\Elementor\Hooks\DynamicElements::convert | SitePress .../Hooks/DynamicElements.php:16 |
| 5 | 10 | WPML Promise closure -> WPML\PB\Elementor\V4\Hooks::translateContentIds | SitePress vendor/wpml/wp/classes/Hooks.php:27; V4/Hooks.php:29 |
| 6 | 10 | WPML\PB\Elementor\Hooks\DisplayConditions::convertDisplayConditions | SitePress .../Hooks/DisplayConditions.php:45 |
| 7 | 10 | ElementorPro\Modules\LoopBuilder\Module::filter_content_data | Pro modules/loop-builder/module.php:214 |
| 8 (fixture) | 9223372036854775806 | private opt-in injector | staging harness only |
| 9 | 9223372036854775807 | iu_elementor_filter_builder_content_by_device | Kit includes/elementor-device-visibility.php:107 |
| 10 | 9223372036854775807 | IU_Elementor_Fragment_Cache::filter | generic Kit source |

There were no callbacks after substitution. Root callbacks above execute on
both requests; the hash of the final device-filtered node at filter entry equals
the keyed-node hash. An earlier diagnostic compared before Device Visibility
and incorrectly expected equality; that test was corrected without changing the
real callback order. A regression rejects a later callback and distinguishes a
root-only prior callback from an unknown skipped referenced-document callback.

Nested-document callbacks remain subject to review. Pro's builder filter changes
only empty loop data during AJAX, which the request gate excludes. WPML's wrapped
query-ID metadata filter and V4 translation callback are traced through their
reviewed Promise/FP closures, not approved merely by Hooks.php provenance.

## Generic evidence

| Check | Miss | Hit |
|---|---:|---:|
| Original mega-menu render | 1 | 0 |
| Nested element renders | 718 | 0 |
| Loop template 1635 renders | 150 | 0 |
| Greek raw fragment bytes | 473,940 | 473,940 |
| External CSS/JS URLs | 87 | 87 |
| Open dropdown links | 224 | 224 |
| Open panel height | 722 px | 722 px |
| Navigation / existing redirect | HTTP 200 | HTTP 200 |
| Browser JS errors | 0 | 0 |

Both origin requests reported `X-Cache: MISS`; the second was a Kit fragment hit,
not a full-page hit. Greek SHA-256 remains
`6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79`.
The final browser pair's clipped panel screenshots differed at 14/929,214 pixels
(0.0015%, 8/255 threshold), with identical parsed source DOM and asset sets.

The English pair used its own automatic graph/asset manifest: 494,952 bytes,
85 matching external assets, SHA-256
`318f7e264a2b33357ce48697a14cd097e8b98c99b1e666008d337c8d65ad7f28`.
Its hit rendered zero target/nested elements. Translated loops use different
reference IDs, so the Greek 1635 counter is zero even on its English miss.
`/etairia/` also passed a separate-key pair. Desktop Firefox reused a Chrome UA
entry; the native-hidden tablet and Greek/English phone roots were absent before
cache lookup. The real Greek phone popup opened with 34 links and navigated to
`/etairia/` with HTTP 200.

## Loop dependencies and scope

The Greek graph contains 25 public partner Loop Grid queries, all referencing
loop template 1635, plus 13 Classic Posts widgets, 3 nav menus, 2 referenced
templates (7767/7778) and static/nested-tab widgets. Template 1635 uses post URL
and featured-image tags. Partner posts, taxonomy relationships, featured-image
metadata/files and permalink configuration affect it. Brand/article/media/term
content freshness is the selected 24-hour TTL/manual purge; Elementor template
source changes invalidate immediately. Page context remains necessary for Pro
active-link attributes and nav-menu current classes. Logged-in/uncertain user
contexts bypass rather than share anonymous fragments.

The Posts widgets explicitly hide excerpts. Pro 4.2.2 nonetheless leaves shared
Skin_Classic excerpt filters registered. The initial HTML/assets proof did not
cover this effect. The implementation now stores/replays their exact semantic
profile and callback identity, using one control-only Posts parent without render
or get_query. A real-Pro test confirms matching excerpt values and removal by a
later Posts widget, avoiding the incorrect alternative of a permanent constant
callback. Visible excerpts/content/block/opt-in effects are unsupported.

The reviewed integration guards include WPML 4.9.7, Search & Filter Pro 2.5.17 /
Elementor extension 1.4.1, Ivory Search 5.5.16, Rank Math 1.0.277.2, Remove CPT
Base 6.7, ACF 6.8.8, EWWW 8.8.0, CF7 multilingual 1.3.3 and SEO multilingual
2.2.5. Unknown callbacks/implementations or unsupported settings bypass.

## Concurrency and bounded behavior

Cold builders with injected 20-second delays produced one build and two waiting
hits with identical HTML/assets and excerpt profiles. Cold waiting is capped at
45 seconds. A shortened one-second wait fixture confirmed ordinary duplicate
render after timeout, with only the owner publishing and the following request
hitting. This is bounded contention protection, not unlimited single-flight.

Same-key TTL expiry retains a maximum 180-second grace. One refresher and two
stale hits bypassed render; the relevant timing is cache selection time, not
whole-page HTTP time. A concurrent browser/variant run overloaded staging and
made total HTTP time exceed an earlier 15-second assertion despite correct
stale-hit/zero-loop markers. The final test measured lookup selection separately:
both stale followers selected the entry in 1 ms. Cold followers waited 23.7 and
24.8 seconds, while only one request rendered the widget and its 150 loops.
The one-second timeout fixture also passed; only the elected owner published.
An expired-grace regression prevents replay. An actual nonce/capability-protected
purge advanced the element epoch, rebuilt the next same-generation request and
prevented old retained stale HTML from crossing the new key.

Standalone tests cover unsupported prototypes/queries/dynamic tags, cycles,
source signatures, prior/later callback ordering, filtered-node and normalized
key separation, context/device/WPML gates, source-invalidation exclusions,
root assets, stale/lock ownership and request-local negative-cache polling.
PHP is not available in local PATH; tests/lint use staging PHP. The existing
unrelated Device Visibility, Atomic description, AGENTS and changelog cleanup
working-tree changes are preserved and excluded from the focused cache patch.

Final cleanup deactivated and deleted the temporary Kit harness and removed its
uploaded PHP fixtures and database indexes/epochs. Native Element Cache still
reports `disable`. Staging uses an external object cache; private experimental
fragments expire with their own TTL, without a global cache flush. The older
probe remains inactive. Saved Elementor documents and production were untouched.

Full Kit upload/save/reload/editor visual testing is a separate staging follow-up;
no production installation or release has been made. Runtime support is limited
to the reviewed graph/renderer/integrations, with conservative bypass elsewhere.
Per-page variants duplicate the large fragment and require storage planning.

---

# Historical prototype and adapter-only evidence (superseded status)

# Historical failed generic trial (2026-09-30; superseded above)

The latest generic graph-policy trial removes the staging-specific eligibility
and dependency adapters. It **does not pass** the required second-request bypass
gate on staging. Two consecutive anonymous desktop origin requests each executed
`26fa46c6` once, 718 nested elements and loop template 1635 150 times. Both
returned the same 473,940-byte HTML (SHA-256
`6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79`)
and 87 external assets. Both reported `eligible=no`, `result=none` and
`graph=unknown-hook:elementor/frontend/builder_content_data`.

The conservative policy bypasses caching because the installed builder-data
callbacks are not fully reviewed. Earlier successful miss/hit and browser
results below used a staging-specific eligibility adapter; they establish the
early bypass mechanism, not acceptance of the current reusable graph policy.
The normal Kit cannot currently cache this menu through its built-in graph
policy. The local feature remains an unreleased draft, not a completed delivery.
Documentation and the previously generated patch below predate this trial.

Work stopped at this failed gate as requested. The temporary staging harness
was deactivated after the trial. Elementor Element Cache remains `disable`.
Production was untouched. Proposed next correction: inspect the exact WPML and
Elementor Pro builder-data callbacks, including wrapped closures, and approve
only reviewed deterministic callbacks under explicit compatibility guards;
then rerun the pair and the full assets, interactions, variants and concurrency
gates. Do not replace this rejection with a broad hook allowlist.

---

# Elementor fragment cache: staging investigation

Status (2026-09-30): the baseline automation failure was identified and the
corrected desktop miss/hit interaction test passed. The local opt-in runtime
is integrated and remains unreleased. Early bypass, assets, WPML/page/device
variants, concurrent misses and purge-key behavior were demonstrated on
staging through a temporary harness. Complex widgets require a reviewed
eligibility adapter; unsupported/uncertain contexts bypass. No production
change or release has been made. Remaining limits appear below.

## Target and bypass

- Staging only: `wordpress-218158-6702910.cloudwaysapps.com`.
- Elementor 4.2.3, Pro 4.2.2, header document 30, mega-menu widget `26fa46c6`.
- Elementor Element Cache remained disabled. Production was not changed.
- `elementor/frontend/builder_content_data` runs before element instantiation
  and `print_element()`. The probe substitutes a widget that prints cached HTML
  on a hit. `elementor/frontend/widget/should_render` is too late because it
  runs after `print_content()`.

Elementor 4.2.3 source: [frontend.php](https://github.com/elementor/elementor/blob/4.2.3/includes/frontend.php),
[document.php](https://github.com/elementor/elementor/blob/4.2.3/core/base/document.php),
[element-base.php](https://github.com/elementor/elementor/blob/4.2.3/includes/base/element-base.php).

## Staging evidence

The temporary probes v0.2.0 and v0.3.0 were enabled only for a private test query on the
staging host, anonymous Windows desktop requests, and disabled Elementor
Element Cache. Two distinct URLs reached WordPress (`X-Cache: MISS`):

| Response | Probe state | Target renders | Nested renders | Template 1635 renders | Fragment bytes | Fragment SHA-256 | Styles |
| --- | --- | ---: | ---: | ---: | ---: | --- | --- |
| First request | miss | 1 | 718 | 150 | 473,940 | `6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79` | 5 captured |
| Second request | hit | 0 | 0 | 0 | 473,940 | `6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79` | 5 replayed |

The captured style handles were `widget-posts`, `widget-loop-grid`,
`widget-nested-tabs`, `elementor-post-7767`, and `elementor-post-7778`. The
probe re-enqueued ordinary handles via WordPress and Elementor post CSS via
`Elementor\Core\Files\CSS\Post::create($id)->enqueue()`. External CSS and JS
URL sets then matched on miss and hit. The `loop-1635` inline style resides
inside the cached fragment and matched byte for byte. Differing `loop-436`,
`loop-1279`, and `loop-dynamic-1279` styles were outside the target fragment.

An isolated anonymous Chrome desktop test replayed the two responses. Both
loaded jQuery and Elementor frontend, opened the products dropdown on hover
(`aria-expanded=true`, 224 links, 722 px high), and navigated from its first
link to the same destination with HTTP 200. Menu-panel screenshots differed
at only 6 of 931,224 pixels above an 8/255 channel threshold (0.0006%). The
hit response marker still recorded zero target, nested, and template renders.

Read-only variants with fresh origin responses:

| Context | Target | Fragment SHA-256 | Template 1635 copies |
| --- | --- | --- | ---: |
| Greek desktop home | present | `6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79` | 150 |
| Greek desktop `/etairia/` | present | same | 150 |
| Greek phone home | absent after device visibility pruning | — | 0 |
| English desktop home | present | `318f7e264a2b33357ce48697a14cd097e8b98c99b1e666008d337c8d65ad7f28` | 0 |
| English phone home before the Kit native-hide guard | present but CSS-hidden | same English hash | 0 |

These observations require language and device separation and preservation of
the Kit's visibility pruning. They do not prove all page, session, or user
contexts. The probe deliberately bypassed logged-in visitors and phones.

## Content dependencies

The Greek target contains Elementor templates 7767 and 7778 and 25 distinct
Loop Grid widgets. The 150 copies of template 1635 use a dynamic Featured
Image and dynamic Post URL for each queried post. Six Brand post IDs appeared
25 times each in the rendered fragment; two blog post IDs appeared 13 times
each through Posts widgets. The target contains 146 unique links.

Read-only WP-CLI access to the staging site's saved `_elementor_data`
resolved the editor loading problem. Template 7767 has 12 Loop Grids;
template 7778 has 13. All 25 query the `partner` (Brands) post type through
template 1635. One grid in each nested template includes taxonomy term
taxonomy ID 48; the other 23 have no saved term inclusion. Template 7778
also has 13 Posts widgets with two posts per classic view. Their query
source is not explicitly saved, so Elementor Pro's defaults and filters
must be considered. The saved data contains no enabled
`post_query_avoid_duplicates` setting. Its Elementor Pro 4.2.2 default is
off; the editor's hidden switch input had misleadingly appeared as `yes`.

Even with that setting off, Elementor Pro's
`ElementorPro\Modules\QueryControl\Module::add_to_avoid_list()` records
the results of each query in a global displayed-ID list. A hit that skips
all 38 queries also skips this side effect. A later widget with Avoid
Duplicates enabled can therefore produce different output on the same page.
The public `get_avoid_list_ids()` and `add_to_avoid_list()` methods offer
a capture/replay mechanism. Probe v0.3.0 captured eight unique IDs on a miss
and replayed eight on a hit; the same target fragment had 718 nested element
renders and 150 template-1635 renders on the miss, versus zero of both on the
hit. The post IDs after the target still differed: 15 appeared only in each
response. Seven came from Loop Carousel `6f5dec7a` in document 2307
(partner posts, template 1635); eight came from Loop Carousel `8ece9dc`
in home document 2 (product posts, template 1279). Both have saved
`post_query_orderby=rand`. They are outside the target and vary between
independent requests. With those two carousels excluded, all downstream post
IDs and Elementor template IDs matched between miss and hit. This is a
targeted parity result, not proof that every downstream side effect matches.
The saved data also contains static menu items and links; their output can
depend on current page and WPML language.

## Kit runtime staging trial

The user selected a 24-hour initial TTL with manual purge as the proposed
freshness policy. This accepts that Brand/article changes may remain stale
until expiry or purge; Elementor document/template saves can have separate
automatic invalidation. It does not waive the early-bypass or context checks.

A first local draft hit HTTP 500 because its proxy class was declared before
`Elementor\\Widget_Base` existed. The harness was deactivated and removed;
a fresh origin request returned HTTP 200. The repaired source registers an
anonymous proxy at `elementor/widgets/register`. A PHP bootstrap regression
case loads the include before `Widget_Base` exists and then registers the
proxy after it becomes available; it passes. The repaired source was tested
through a private-query staging harness without changing the installed Kit
or saved Elementor data.

On two fresh Greek desktop origin requests (`X-Cache: MISS`), the first
rendered the target once, 718 nested elements, and 150 template-1635 copies.
The second returned the identical 473,940-byte fragment (SHA-256
`6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79`)
with zero target, nested, or loop renders. All 91 external CSS/JS URLs matched.
An isolated desktop Chrome check opened the dropdown on hover, found 224 links
in a 722px panel, navigated through the same internal link with HTTP 200, and
found 4 differing screenshot pixels of 931,224 above an 8/255 threshold.

The staging site sets `wp-wpml_current_language` even for anonymous visitors.
The cache accepts only that cookie when it equals `ICL_LANGUAGE_CODE`; unknown
or mismatched cookies, logged-in users, preview/editor, search, and unknown
query contexts bypass. PHP regression cases pass for these request gates.
English desktop received a separate fragment; Greek and English phone requests
do not render or retrieve the CSS-hidden desktop widget. The English target
had native Elementor `hide_mobile=hidden-mobile` but no Kit phone control;
the Kit cache therefore removes opted-in widgets hidden by either control.
The Greek phone popup menu opened, displayed 34 internal links, and navigated
successfully. Greek company and English desktop also reached cache hits on
second origin requests.

Three concurrent fresh-key requests produced one fragment build and two hits,
both without widget/loop execution. Changing the per-element epoch for
`30:26fa46c6` caused the next request to miss and rebuild; the following
request hit. The control regression checks default-off, 24-hour default TTL,
no control on unsupported containers, and an admin purge form per built widget.

The final runtime invalidates on post/template saves, post/ACF/media metadata,
taxonomy/relationship changes and relevant site configuration events using a
global content epoch. Known Elementor generated-cache metadata is excluded;
otherwise frontend cache bookkeeping can invalidate fragments as they are
built. External/custom data without a known event remains bounded by the
chosen TTL or adapter/manual invalidation. Tools offers per-element purge;
full-page/CDN cache still needs separate clearing for immediate freshness.
There is no preload. Containers, dynamic root tags, unknown cookies and query
contexts bypass. Complex widgets, including mega-menu, require an explicitly
reviewed adapter; default static eligibility is denied with unknown render
callbacks. The runtime itself is guarded to Elementor 4.2.3 / Pro 4.2.2.

## Baseline diagnosis and corrected final interaction gate

Without the harness, a clean anonymous desktop session confirmed all three
header mega-menus and the actual `26fa46c6` trigger at 1440x900 with desktop UA.
At `load`, the title had no jQuery events. After the initial real pointer/click
interaction, its `click/mouseover/mouseout/keyup` handlers attached. The
Complianz `cmplz-soft-cookiewall` then covered the page and intercepted pointer
events. The former forced-hover test bypassed actionability and neither waited
for these handlers nor reliably dismissed this late overlay. No JS exception
was observed. Real clicks on View Preferences then Save Preferences dismissed
the wall; a normal hover on `.e-n-menu-title-container` immediately opened the
722 px panel. A page screenshot with a clip preserved the open menu. Its first
link redirects from `/solution/topografia/` to `/solution/topografikos-exoplismos/`
with HTTP 200; exact-URL waiting in the former test incorrectly timed out.

With the corrected flow in separate fresh anonymous contexts, both Kit origin
responses had `X-Cache: MISS`. The fragment miss rendered the widget once,
718 nested elements and 150 loop-1635 copies; the hit rendered none. Raw HTML
remains 473,940 bytes with SHA-256 `6da07a58bd784165612e922c26432ccfa0f4d65eac3556d9a0a2e84b36b88f79`.
The parsed source DOM hashes and all external asset URLs also matched
(91 in the first corrected run, 87 in the final manifest run).
Both dropdowns opened with 224 links, both navigated through the redirect with
HTTP 200. The final manifest run's screenshots differed at 13/929,214 pixels
(0.0014%, 8/255 threshold); the first corrected run differed at 35 pixels.
No browser JS errors were recorded.

Concurrent origin requests during the runtime trial produced one build and two hits with
identical raw fragments. The phone popup opened with 34 links and navigated
to `/etairia/` with HTTP 200, while the desktop target was absent. Separate
company-page and English desktop pairs reached hits with identical per-pair
HTML/assets. English output is different (494,952 bytes, raw SHA-256
`318f7e264a2b33357ce48697a14cd097e8b98c99b1e666008d337c8d65ad7f28`).
WPML filters `_elementor_data` for nested templates 7767/7778 differently in
English; a Greek-only saved-data fingerprint correctly denied the English
context until separate English audit fingerprints were supplied. Greek and
English phone requests both omit the desktop target before cache lookup.
The explicit shared CSS manifest initially added Greek template CSS in the
English hit. The staging adapter was corrected to declare those two post CSS
handles only in Greek; the final English pair had identical HTML and all 85
external assets. The engine's default manifest includes the root widget's
declared dependencies, and adapters supply reviewed language-specific shared
descendant handles.
An initial company pair run alongside mobile navigation produced two safe
misses; the isolated repeat passed. No stale HTML was returned; the specific
concurrent invalidation was not captured in that initial run.

The source now defers asset/displayed-ID replay until the proxy's original
render position. A regression case proves builder traversal does not publish
IDs early, hits execute no original widget, and intervening query context
falls back to the original render. Additional cases cover bootstrap order,
request/eligibility gates, content-invalidation exclusions, default-off
controls and the capability-protected admin purge form. A dedicated case
confirms root assets already queued before capture are explicitly retained;
reviewed compound adapters can declare shared descendant dependencies in the
manifest filter. Remote PHP lint and
`git diff --check` passed; PHP is not available in local PATH.

The actual admin purge handler passed with an administrator and a valid nonce:
the element epoch advanced, the same request generation rebuilt on its next
origin request, and the following request hit with the same raw HTML/assets.

The local runtime is integrated for review. The temporary Kit harness was
deactivated and deleted after final checks; temporary remote test files were
removed. Elementor Element Cache remains disabled. No release, installation,
or change on production was made. No saved Elementor element data was changed.
