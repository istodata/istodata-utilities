# Elementor 4.3.4 / Pro 4.3.1 source review, 2026-10-06

Current release status: accepted in Kit 2.23.0 after the corrections and full-package staging test. The initial STOP notes below are historical. See elementor-434-acceptance.md and release-2.23.0-staging.json. The raw vendor comparison is a local generated artifact; official distribution hashes and compared-file hashes are retained in the committed source manifest.

The user confirmed the WP Rocket Atomic Interactions, Query Posts HTML/WPML,
and Elementor update-warning changes. Their acceptance is no longer pending.

## Source review

Official WordPress.org Core 4.3.3 and 4.3.4 distributions were compared byte by
byte (`elementor-434-source-manifest.json`, `elementor-434-vendor.diff`).
Frontend builder-data dispatch, widget/element print methods, Atomic base
classes and frontend interaction bundles are unchanged. Pro remains 4.3.1.
Core document asset caching now merges saved asset handles with render queues;
experiment dependency resolution also changed. Those paths require staging
acceptance rather than approval inferred from unchanged early-bypass hooks.

## Exact stop

The staging-only vendor test swapped the official Core directory, retaining
the exact original directory for rollback. Its first WP-CLI snapshot emitted:

```
2026-10-06 14:37:45 [info] Elementor data updater process has been queued.
plugin: Elementor; from: 4.3.3; to: 4.3.4
```

That log precedes JSON and caused `Extra data: line 1 column 5 (char 4)`.
No candidate Kit runtime was installed and no Atomic miss/hit test ran.
This is a harness failure; it neither proves nor disproves 4.3.4 compatibility.

Elementor's ordinary upgrade bootstrap also clears generated files and
post-cache metadata (`core/base/db-upgrades-manager.php` calls `clear_cache()`
before queuing its updater). Consequently, all-meta hashes for documents
30/33000 differed after restoring vendor files/options. This was not covered
by the original rollback snapshot, which saved hashes but not generated cache
metadata/files. Do not report a byte-identical database rollback.

## Confirmed restoration

- Exact original Core directory restored: 4.3.3 / Pro 4.3.1.
- Installed Kit remains 2.22.0; its files were never replaced by this run.
- Kit settings, active plugin list, native Element Cache setting match snapshot.
- Native Elementor Element Cache remains `disable`.
- Header `_elementor_data` hashes match the earlier preflight for both documents:
  30: `5bbe73a41ebb6b815f31cdc967a1aae45bd2ba3048043a3ac50fbd1c0b9d82cb`;
  33000: `6eaa1da47d995022bb122a73eeb57c397373914136a05bff9c7513bbfa7629a7`.
- Temporary queued `elementor_1_elementor_updater_cron` event removed.
- Private Atomic MU loader and its temporary directory were removed.
- Protected vendor archives and rollback diagnostics retained outside webroot.
- No production action, compatibility approval, release, version bump or new ZIP.

## Proposed correction before another attempt

Make probe JSON independent from CLI logging (a private result file, with
explicit error handling). Snapshot generated Elementor files/cache metadata
and the updater cron state before loading the new version, in addition to
saved documents/options. Always persist rollback diagnostics even if a
restoration assertion fails. Rerun the real 4.3.4/4.3.1 Classic and Atomic
miss/hit, assets, device/language/login/context checks only after that repair.

## Release acceptance — 2026-10-06

The release-owned registry now accepts exactly Core 4.3.4 / Pro 4.3.1 in Kit 2.23.0. Earlier STOP/unapproved notes above are historical; the asset-order and native loop CSS findings were resolved and staging acceptance passed. Motion source/checksum and actual vendor runtime validation supplements the separate prior 4.3.3 browser matrix; it is not a claim of a new full Motion browser matrix. Full-package release acceptance is recorded separately in release-2.23.0-staging.json.
