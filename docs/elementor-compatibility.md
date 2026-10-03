# Elementor compatibility and WordPress updater gate

ISTODATA Kit 2.22.1, 2026-10-03. Registry:
`includes/elementor-compatibility.php`.

## Accepted versus tested

| Feature | Accepted combinations | Evidence and limits |
|---|---|---|
| Atomic Interactions breakpoints | Exactly 4.3.2/4.3.0 or 4.3.3/4.3.1 | Old pair vendor VM; new pair full Kit native Motion OFF/ON/resize and vendor VM. See staging report for browser limits. |
| Fragment Cache, Pro graph/excerpt adapters | Exactly 4.2.3/4.2.2 or 4.3.3/4.3.1 | New pair full Kit Greek/English save/reload/cross-page HTML/assets/effects, selective purge, device exclusions and joint Atomic ON. Older evidence retained separately. |
| Fragment Cache, static core-only path | Exactly core 4.2.3 with Pro absent | Existing behavior preserved; isolated proxy/bootstrap regression only, no new browser claim. Pro adapters are excluded. |

The registry has one common accepted pair: **4.3.3/4.3.1**.
Actual acceptance is recorded separately in `archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md`.
Compatibility descriptions in Kit settings derive this from the same registry.
No wider range or new patch is inferred. The former fragment widening filter was
removed. API/class/source-path/hook/shape guards remain; JS checks APIs while PHP
uses the registry to decide attachment.

Final generic widget-root acceptance is recorded in
`fragment-cache-generic-result.md`; production acceptance for the six configured
Greek metrica header widgets is in `metrica-production-generic-20261003.md`.
These add tested evidence without widening the accepted version pairs. Generic
admission does not certify every vendor widget, and shared WP Menu active-page
attributes remain those of the source request. Older pairs retain only their
separately recorded evidence; no generic browser coverage is inferred for them.

Every new Elementor/Pro version, including a patch that leaves feature code
unchanged, needs review, an explicit registry declaration and a Kit release
before acceptance. The schema currently accepts exact versions only and rejects
open-ended ranges. Approved combinations and actually tested evidence remain
separate fields, even when they currently identify the same version pair.

The earlier open 4.3.x policy is superseded by finite exact declarations. The
4.3.3/4.3.1 addition follows actual vendor and full Kit staging acceptance, not
mocked version tests. Core 4.3.4, Pro 4.3.2 and later versions remain unapproved.

The daily monitor reports actionable evidence/decisions read-only;
it never edits these declarations or performs releases. This work creates no
automation and does not manage the existing monitor's migration.

## What counts as enabled

Atomic is configured ON only by
`istodata_utilities_settings.optimizations.elementor_atomic_interaction_breakpoints`.
Absent/false remains OFF. Fragment Cache is configured ON for updater protection
exclusively by `istodata_utilities_settings.optimizations.elementor_fragment_cache`.
Missing/false is OFF. Global ON constrains updates even when no widget is opted in,
no fragment exists, native Element Cache is enabled or today's graph/request guards
bypass runtime caching. Global OFF removes only the Fragment constraint and preserves
saved widget opt-ins. Runtime separately requires global ON, widget opt-in, native
Element Cache disabled and its safety guards. The per-widget panel is hidden with OFF;
compatibility text belongs below the global setting.

No saved `_elementor_data` scan or activation index exists. Activation reads only Kit
settings; frontend rendering does not invoke a site-wide metadata scan. The previous
scan policy and its intermediate request-local optimization are superseded.

| Atomic | Fragment | Update constraint |
|---|---|---|
| OFF | OFF | None from Kit |
| ON | OFF | Atomic accepted pair only |
| OFF | ON | Fragment accepted pair only |
| ON | ON | Both policies; exact shared pair 4.3.3/4.3.1 |

## Gates, update order and override

`auto_update_plugin` vetoes only incompatible active feature pairs; it preserves
the caller's choice for supported/unrelated updates. `upgrader_pre_download`
rejects advertised incompatible manual targets before download.
`upgrader_source_selection` inspects the actual extracted main-file version via
WP_Filesystem, before WordPress moves/deletes installed files. It also guards
uploaded canonical Elementor/Pro ZIP replacements. No update-transient entries,
packages, update badges or normal update choices are removed.

The counterpart version is read from installed main-file headers on disk, not
Elementor constants or cached `get_plugins()` data. Every step in a sequential or
bulk update is checked against the counterpart **already installed**, not a
projected final pair. For example, enabling Atomic on an old core/Pro pair does
not approve an unsupported intermediate pair merely because two upgrades would
eventually reach an explicitly accepted pair. Disable the affected feature for the
transition, or explicitly override each blocked step. With disjoint installed/target policies, selecting a feature or newly reviewed
compatibility is required; a supported final pair does not approve intermediate steps.

The compatibility screen exposes one POST form per blocked available update.
It has no permanent menu entry in Tools or network settings. Blocked-update
notices and plugin update rows link to the existing protected screen and URLs;
removing the submenu leaves the registered callback and permission checks intact.
The administrator must explicitly acknowledge the specific update. Authorization
requires `update_plugins` AND `manage_options`, a WordPress nonce tied to a
fingerprint of plugin, target version, package URL and installed core/Pro pair;
multisite additionally requires super-admin and `manage_network_plugins`.
Changed targets/packages/installed pairs invalidate the form. The actual ZIP
version must match the grant. The grant exists only in that PHP request and is
cleared after the one upgrade; it is never stored in options/transients or used
for auto updates/cron. If filesystem credentials are needed, the credentials form
preserves the exact POST fields and the next request reauthorizes them; this path
has an isolated round-trip regression, with real transport/UI acceptance pending.
A failed attempt can be explicitly retried; WordPress
nonces themselves are time-limited, not single-use tokens. Upload replacements
have the source gate but no separate uploaded-package override UI; disable the
affected feature or use the specific available-update form.

Unsupported versions installed through override or direct replacement bypass
sensitive runtime paths using the same registry and display administrator notices.
The updater protection requires Kit to be loaded and the normal WordPress hooks
to run. SSH/SFTP/direct replacements and updaters bypassing those hooks cannot be
prevented. Other plugins can override WordPress hooks; this is not a security
boundary against arbitrary PHP code.

## Multisite and performance limits

When Kit is loaded, the shared plugin-file update checks global feature settings on all
non-deleted sites across networks where Kit is site- or network-active, restoring
the original blog after each inspection. It ignores sites with inactive Kit.
Network activation is required for dependable protection from network admin/cron
requests where the initiating blog would otherwise not load Kit. There is no
MU-plugin bootstrap or remote network deployment in this change.

Network site enumeration is synchronous and batched (200 sites), without a saved
Elementor metadata scan or persistent activation index. Large multisite installations need timing acceptance;
database availability and ordinary readable installed plugin headers are required.
Isolated tests validate the branching/switching logic, not real network admin UI,
filesystem-credentials prompts, every database failure mode or concurrent updates.

## Executed local checks

PHP is **not available in PATH**. A temporary portable PHP 8.5.11 NTS CLI from
the official PHP Windows download was checksum-verified against its published
SHA-256 and used with `-n`; PATH/system/server configuration was not changed.
Subsequent explicitly authorized staging work is recorded in `archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md`. It exposed and corrected missing bulk/AJAX hook metadata handling.

```powershell
$phpExe = Join-Path $env:TEMP 'iu-compatibility-php-8.5.11\php.exe'
& $phpExe -n -l istodata-utilities.php
& $phpExe -n tests/elementor-compatibility.php
& $phpExe -n tests/elementor-update-guard.php
& $phpExe -n tests/atomic-interaction-breakpoints.php
& $phpExe -n tests/elementor-fragment-bootstrap.php
& $phpExe -n tests/elementor-fragment-context.php
& $phpExe -n tests/elementor-fragment-graph.php
& $phpExe -n tests/elementor-fragment-assets.php
& $phpExe -n tests/elementor-fragment-poll.php
& $phpExe -n tests/elementor-fragment-replay-order.php
node tests/elementor-compatibility-drift.cjs
node tests/atomic-interaction-breakpoints.cjs
node --check assets/js/elementor-atomic-interaction-breakpoints.js
git diff --check
```

The new policy suite checks 23 core/Pro targets × four activation states in eight
isolated runtime environments, including absence, prereleases, malformed versions,
Pro adapter boundaries, unsupported-runtime fallback and restricted admin notices. The updater suite checks
68 activation/target scenarios through auto/manual/extracted/upload callbacks,
permissions/nonce/confirmation, changed package/target/base, cron refusal, native
cache/opt-out/multi-batch discovery, sequential update state, multi-network
activation and admin text/forms. The drift suite scans all runtime PHP/JS and
checks both rejection controls and registry consumers. Existing Atomic suites
cover 24 PHP loader cases and 17 checksum-verified vendor JS runtime cases per old/candidate suite.
Changed/new PHP files also passed syntax checks. These are isolated regressions;
they do not establish full WordPress browser/updater acceptance.

## Staging acceptance and remaining platform limits

The authorized full Kit staging acceptance passed after licence recovery on exact
4.3.3/4.3.1. Greek/English saved controls, anonymous cross-page hits, exact normal
HTML/assets/effects, selective purge, UA exclusions, native Atomic OFF/ON/resize
and joint operation were checked. Normal Kit was reinstalled after registry
approval and the temporary adapter removed. The temporary fixture was trashed,
experiment restored and observation harness deactivated. See the staging report
for backups, checksums, screenshots, final state and the observed updater defect.

No complete auto-update cron run, real multisite network UI, filesystem credentials
prompt or every browser/custom-breakpoint combination is claimed. The release
remains pending a separate user request; version 2.21.2 and production are unchanged.
