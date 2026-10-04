# Advanced Elements Cache

Version 2.22.0. Generic widget-root acceptance was completed on staging and the
configured metrica production header. See `fragment-cache-generic-result.md` and
`metrica-production-generic-20261003.md` for the exact coverage and limitations.

## Administrator contract

Global switch defaults OFF. Saved per-element opt-ins and TTL survive OFF saves;
Native preview also performs no observation writes.
OFF hides the editor panel and performs no fragment lookup, capture or observation
writes. Global ON is the sole fragment criterion for Elementor update protection.
Each element defaults OFF; default TTL is 7 days. Creation is lazy on a real
request, with no preload or scheduled generation.

Opt-in declares that this element's output can be reused as-is across pages and
anonymous/authenticated visitors and ordinary URL query parameters. Query strings
do not by themselves bypass caching or create a distinct fragment. This includes
tracking parameters (utm/gclid), pagination, sorting and custom parameters.
Keep elements OFF when their output should follow the current page or these
parameters. With ON, the first eligible request's HTML is intentionally reused
as-is, including current-page menu attributes, even if that first request has
parameters. This is the administrator's shared-output tradeoff, not a widget or
vendor prohibition. Personalized, permission-dependent, cart, geolocation and
other private content cannot be made public by opting in. Static inspection does
not prove independence from arbitrary callbacks.
Misses run the normal widget and hooks; hits intentionally skip that widget,
its descendant loops and their render/query hooks. Integrations requiring those
side effects on every request must remain uncached. No wizard or callback whitelist.

Third-party plugin versions, unknown hooks and source fingerprints are not global
prerequisites. Elementor/Pro are different: the proxy, graph and replay adapters
consume the single exact-pair registry in includes/elementor-compatibility.php.
No new pair is inferred from these policy tests.

## Early bypass and variants

After Device Visibility prunes hidden branches, the last builder-data filter
substitutes an opted-in widget with a registered proxy on a hit. Its print_element
returns the stored fragment before the original widget's print_content/render.
No Elementor core edits. A later builder callback requiring the original shape
causes bypass. Registered widgets use their native dependency APIs, including
core, Pro and custom implementations; there is no widget/class/vendor allowlist.
Containers are traversed but remain outside root caching. The outer metrica
mega-menu 26fa46c6 remains live. Opted-in WP Menu children reuse their first
request's active-page classes and aria-current attributes under the as-is contract.

Keys include effective filtered nodes/source documents, generation, device,
resolved WPML language and technical media/excerpt context. No page or user key.
Phone/tablet/desktop use distinct variants with existing Kit device semantics.
WPML version does not block; unresolved or conflicting language context bypasses.
Source IDs come from WPML-filtered render data. Cache neither translates output
nor repairs native language links.

## Concrete exclusions and replay

Editor/native preview, admin, feed, non-GET and REST/AJAX/CLI/customizer
requests bypass. These are actual request modes, not generic query exclusions.
The explicit `elementor-preview` parameter also bypasses before initialization
and after nested templates change the current post. The former
`iu_elementor_fragment_allow_query` filter is no longer used; no allow filter is
needed. Site integrations using `iu_elementor_fragment_key_context` can still
add their own context or return a non-array to bypass; the Kit itself does not
derive that context from the URL. Validated WordPress login cookies can share fragments.

Search and 404 requests are not excluded at the request gate. Neither flag is
added to the key, but replay-relevant media/excerpt state can still produce a
different key or a concrete safety rejection. Reuse is therefore conditional,
not guaranteed merely by removing the request exclusion. Keep elements OFF when
their output must follow the current search or 404 state. A search request does not itself
make a public, independent header/footer fragment unsafe; concrete search-sensitive
queries executed inside a captured widget retain their publication guards.
On the reviewed metrica staging 404, different native image-loading context and
a Pro loop's postdata leak prevent shared ordinary-page hits. Existing technical
variants and concrete replay guards retain native output/status; no blanket 404
exclusion or special site rule is added. This is an accepted reuse limitation,
not a guarantee of zero widget/loop work on every 404.
Known session/private/cart cookies, mismatched language cookies, Authorization
headers and active PHP session data bypass. Other cookies are covered by the
administrator's shared-output declaration, not a blanket cookie whitelist.

Missing widget registrations, invalid dependency manifests, dynamic user/current-page tags, conditional content,
private/unpublished templates, relative/search/permission-sensitive queries,
Atomic interactions and unsupported excerpt/media replay retain ordinary rendering.
Public query result IDs are rechecked before publication and reuse. Token/nonce,
user/session markers and admin edit/login controls in captured HTML cannot publish.
These are concrete safeguards, not proof that every arbitrary plugin is independent.

Declared and newly enqueued CSS/JS are captured/replayed at the original slot.
Render-time inline data changes, newly registered dependencies that cannot be
resolved before a hit, deferred frontend output-handler changes and media/post
context changes cause explicit bypass. Native empty output can also be cached.
Pro displayed-ID effects and hidden-excerpt Classic skin callbacks are replayed
without widget rendering or queries. Existing arbitrary excerpt callbacks stay live
with their original objects/order; unsupported external registration changes during
capture bypass. Callback identities describe replay order, not source approval.
No-effect fragments preserve the current live excerpt profile. Mutating fragments
validate their input at their actual render slot, after earlier siblings have run.

Opaque internal globals, arbitrary direct database/network reads and mutations to
untracked hooks cannot be proven or generically replayed from HTML. Opt-in is an
administrator declaration of reusable public output, not a certification of every
widget/plugin. Widgets needing such effects every request must remain uncached;
validate miss/hit output and interactions before opting in on a site.

## Verified metrica dependencies

Header 30 dropdown 417d1ae references template 7767: 12 partner Loop Grids.
Dropdown c60daee references template 7778: 13 partner Loop Grids plus 13 hidden-
excerpt Classic Posts queries. All 25 grids reference loop 1635. Loop 1635 is a
container/image with post-featured-image and post-url dynamic fields; no user tag
or query is inside that card. Query IDs are empty. Partner media/URLs, article
results, configured terms/links and translated source documents contribute to
output. Inventory is in fragment-cache-optin-dependencies.json. Effective EN
references and output counts are separately proved by real requests.

## Invalidation and locking

Element/template/configuration metadata and known theme/site/permalink/kit/WPML
configuration changes invalidate. Site and selective element purge are protected
by capabilities and nonces. Generations fence an old in-flight producer after
purge. Same-key producers use an owner-checked database mutex. An already validated
fragment can have a bounded 180-second stale grace while another request rebuilds;
source/purge generation changes and private result IDs still reject stale reuse.
Without a qualifying stale fragment, timeout retains
native rendering rather than publishing an unlocked fragment.

Public posts/Brands/taxonomy/external content are not exhaustively dependency
tracked. Such edits may remain stale until TTL (7 days by default) or manual
purge. Administrator must purge after dependent content changes when immediate
freshness is required. No universal invalidation claim.

## Diagnostics and acceptance

Administrator-only status separates saved opt-in/global state, supported stored
structure, and bounded historical frontend observations. It never renders widgets
or runs loop queries. Anonymous responses expose no status UI or telemetry.

Historical evidence is retained in fragment-cache-access-staging.md and
fragment-cache-hook-review.md; their former blanket policy is superseded here.
New actual acceptance is recorded separately in fragment-cache-optin-* artifacts.
Never infer a new browser acceptance result from offline fixtures. Existing EN
native dropdown links to Greek routes require a separate content/WPML correction.
Production and GitHub release require separate approval.
