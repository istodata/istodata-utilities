# Ivory hook review and administrator cache status — 2026-10-02

## Scope and evidence

Local candidate retains Kit 2.22.0. No release, commit, push, tag or production
write. Staging is wordpress-218158-6702910.cloudwaysapps.com (manqbfzxjy).
Exact Elementor / Pro acceptance remains in `includes/elementor-compatibility.php`;
this change does not approve another pair or alter updater policy.

The official WordPress downloads for Ivory Search 5.5.16 and 5.5.18 contain
identical `public/class-is-public.php` bytes (SHA256
`e7fa44873fac7abfeb85b7785dac01dad6fee5bce1f4e2867aa8a511b36b602f`).
Source inspection establishes that `pre_get_posts` returns before modification
for non-search queries; join/distinct callbacks return unchanged SQL without
search terms. This is source evidence, separate from origin acceptance below.

## Narrow runtime contract

Ivory callbacks must match their named hook, actual plugin source location and
reviewed normalized method token fingerprint. Plugin version alone neither
admits nor rejects them. A changed method or unrecognized callback fails closed.
Whitespace and comments do not change a method fingerprint. Fingerprints enter
the graph signature, invalidating keys from the former policy automatically.
Menu injection is rejected using the actual callback object's loaded options,
not merely a second read of the database. Accepted graph settings and request
checks exclude search, AJAX, relative queries and uncertain cookie contexts.
A further capture guard observes actual queries and refuses publication when
search terms, is_search or Ivory settings markers appear; it does not mutate a
query. Hits substitute before widget construction, retaining existing replay
checks and fallback.

## Administrator status

The Advanced Element Cache panel has an on-demand status button. Tools →
Advanced Elements Cache also accepts a document and element ID, including a
configured widget that has never produced an indexed cache entry. Capability,
edit permission and nonce checks protect both routes.

Status distinguishes saved ON/OFF, global ON/OFF, static graph review in the
current context, and historical administrator frontend observations. Static
review never claims a frontend hit. Unknown hooks show their hook and callback,
without filesystem paths. Preview/editor, unsupported graphs, incompatible
Elementor, native cache, private cookies and uncertain contexts have reasons.
No widgets or loops are rendered to calculate status. Anonymous requests never
write diagnostic telemetry. At most 50 observations are stored in one
non-autoloaded option, with unchanged states throttled for five minutes.
Concurrent diagnostic writers may overwrite one another's historical status;
this does not affect fragments or their writer fencing. No public frontend
status output, nonce, trace or diagnostics script is introduced.

## Other non-Elementor guards retained

| Integration | Retained boundary and reason |
|---|---|
| Search & Filter Pro / Elementor extension | 2.5.17 / 1.4.1; query integration and widget lifecycle effects, with graph rejection of custom/search-filter queries. Ivory source equivalence does not prove these callbacks safe in another release. |
| ACF | 6.8.8; posts_where affects selected posts and dynamic field output. No new ACF version or altered field behavior was reviewed. |
| WPML | 4.9.7; language-specific queries, IDs and links. Existing origin checks and constrained wrapper inspection remain; language consistency and distinct variants stay required. |
| CF7 Multilingual / SEO Multilingual | 1.3.3 / 2.2.5; existing source origin checks cover locale and primary-category translation. This review did not establish source equivalence for other releases. |
| WP Rocket | 3.23.2.2; lazyload callback must originate from the reviewed source; nested filters are reviewed and effective image/iframe flags enter keys. |
| EWWW | 8.8.0; existing no-PTE-action condition and reviewed callback boundary remain. Other image modes are not newly accepted. |
| Rank Math | 1.0.277.2; post-type links and term ordering can affect cached output. No review proves another version's behavior equivalent. |
| Remove CPT Base | 6.7; rewrite normalization must already be applied and selected types enter the signature. A hit must skip no mutation. |

These retained boundaries are conservative acceptance limits, not claims that
newer versions are broken. Relaxing one needs its own source/behavior review and
origin acceptance. Their rejection now has a named, administrator-visible hook
reason instead of silently treating saved ON as proof of usable cache.

## Safety limits

Caching a whole navigation root can preserve the wrong current-page/ancestor
state across pages. The UI warns about this. Staging tests use independent
Template dropdown widgets 417d1ae and c60daee in headers 30 and 33000 at 86400
seconds, with 26fa46c6 OFF. Production opt-ins are not changed by this work.
Native Elementor Element Cache stays disabled. Unknown context bypasses.
Content updates use the existing selected TTL plus manual purge; Elementor
source/template edits invalidate immediately. This task does not broaden that
freshness contract or add preload.

## Rollback and acceptance artifacts

Private staging rollback directory:
`/home/master/applications/manqbfzxjy/tmp/iu-fragment-hooks-20261002`.
Created before observer, identities, session or plugin installation; contains
`plugins-before.tar.gz` (Kit and Ivory) and `database-before.sql`. Tar SHA256:
`add3368f2a369dcfff208f742c32c3555dd226a472bfebe90189e23659fc0d90`.
Use the plugin snapshot for code rollback. Full DB restoration is available
for disaster recovery; do not overwrite subsequent staging edits unnecessarily.

Scoped runtime diff: `fragment-cache-hook-review.patch` against the preceding
local 2.22.0 ZIP. Build manifest: `fragment-cache-hook-review-build.json`.
Origin evidence is recorded separately from inspection in hook-origin,
hook-search-before/after, hook-diagnostics-ui, hook-menu, hook-preview and
hook-final-state reports. Incomplete or failed checks must not be represented
as successful acceptance.

## Origin acceptance observed

Six desktop build/consume pairs passed: anonymous → manager, manager → anonymous,
and subscriber → anonymous, in both el and en, using different pages. Greek
misses rendered 150 loops; English misses rendered 156 translated loop documents.
Hits rendered zero loops and zero dropdown Template widgets; the central menu
rendered once. Fragment HTML, external asset tags, excerpt state and displayed
post IDs equal the ordinary consumer baseline. Header query observations fell
from 45–46 to 6–7 in Greek, and 48 to 7 in the measured English cases; these
counts describe observed WP_Query callbacks under the header, not SQL timings.
Phone origin requests have zero heavy keys, result events and loops. The real
mobile popup menu opens and navigates successfully in both languages.

Desktop menu opening and navigation passed in el and en on real hits. The
English dropdown preserves an existing native link to the Greek route
`/solution/topografia/`; equality with the uncached fragment confirms that the
cache did not translate or introduce this link. This content/translation issue
is outside this change and should be corrected separately if an English target
is required. No site content or WPML settings were edited.

Four ordinary origin Ivory searches (Leica and Metrica, el/en, form 6361) have
identical pre_get_posts characteristics and ordered result IDs before/after the
upgrade. The actual main-query Ivory marker is observed before template render,
not inferred from shutdown state. All searches bypass cache. AJAX search UI was
not separately exercised; this is explicitly ordinary GET-search evidence.

The real native page-2 editor iframe and its direct preview render 477 widgets
and 150 loops with zero cache operations. No document save was made. A named,
unreviewed no-op pre_get_posts callback provoked normal render with 150 loops and
unknown-hook rejection of both dropdowns, proving fail-closed origin behavior.

Early browser harness assumptions (standard phone nav toggle, untranslated loop
ID counter, generic first dropdown/link) were corrected to actual popup and
translated-loop/targeted-fragment structure. These adjustments changed test
selection, not plugin or site behavior.


The administrator UI can see admin-only hooks (for example Rank Math post-list
filters) that are absent from normal frontend requests. The static inspection
message explicitly labels that context and does not declare a frontend verdict.
Actual observations are separately labeled with state, language, device and time.
The native button successfully showed the deliberately unreviewed callback
`iu_unreviewed_acceptance_query`. Invalid nonce returns 403; anonymous AJAX does
not expose status. An editor browser's newly introduced private cookies produced
an actual `private-cookies` bypass, not an incorrect cache hit. The named-hook
negative control uses an isolated authenticated request to avoid cookie guards
preempting the deliberately unknown hook.


## Final build linkage

The full cross-user/assets/query/menu/preview acceptance used build
`6027dcc7788f50594e127d45a26c2ec1aacd4e29525e4e1ff4e48b0192015ce6`.
Subsequent runtime changes are limited to administrator diagnostic wording and
wrapping of long callback names. The final build's native button, Tools UI,
actual unknown-hook reason, nonce/anonymous controls and computed wrapping were
verified again. Cache eligibility, key, source contract, capture and replay
files remain identical to the full acceptance build. Final el/en origin hits
are separately recorded against the final ZIP hash in hook-final-origin.json.

Final ZIP SHA256: `a90536f72f548d2b176617db7a4091de85fffcfe2387627900fd70eefd35699f`.
The archive contains exactly 76 files under one istodata-utilities/ root, no
Windows backslashes, development directories, tests/docs, OS artifacts or
nested ZIPs. All installed files match the archive manifest. Portable PHP
8.5.11 was used because PHP is not in PATH; regression details are in
hook-local-tests.json. Pre-existing Atomic/Device Visibility, main-file,
AGENTS and ignore-file work was preserved. This scoped patch is based on the
preceding local 2.22.0 ZIP, not a claim that all dirty checkout changes belong
to this hook/status task.


## Final staging state and cleanup

Kit 2.22.0 and Ivory 5.5.18 remain active on staging, with Elementor 4.3.3 /
Pro 4.3.1. Existing Atomic and Fragment global activation flags and all six
header opt-in/TTL values match the before snapshot. Native Element Cache is
still disable. Both header roots 26fa46c6 are OFF/unset; both dropdowns remain
ON at 86400 seconds. All 76 installed files match the final ZIP. The observer
plugin and two temporary identities have been removed; our own manager session
was destroyed, without touching other sessions. Server and local temporary
credential files are removed. Four administrator observation rows remain.
Private rollback snapshots are retained. No production changes, release, tag,
commit, push, or compatibility-monitor modifications were made.
