# Full Kit staging acceptance — passed, 2026-10-01

Authorized target only: Cloud 1 / 218158, app 6702910,
`https://wordpress-218158-6702910.cloudwaysapps.com`,
logical root `/home/master/applications/manqbfzxjy/public_html`, verified physical
root `/home/218158.cloudwaysapps.com/manqbfzxjy/public_html`.
No production or other-application writes are authorized/performed.

## Baseline and recovery

Read-only SSH preflight: WordPress 7.1.2, PHP CLI 8.2.33, active Elementor
4.2.3 / Pro 4.2.2 / Kit 2.21.2, Atomic OFF, native Element Cache `disable`,
single-site. No saved fragment opt-in in header 30. Old `iu-fragment-probe` exists
but is inactive. WordPress environment type reports the default `production`;
target classification was independently verified from the staging hostname/app
and exact paths, without changing that setting.

Private application backup (SSH, not a Cloudways-platform snapshot):
`/home/master/applications/manqbfzxjy/tmp/iu-kit-acceptance-20261001T112014Z/`.
Started 11:20:14 UTC, completed 11:25:24 UTC (14:25:24 Europe/Athens).
Directory is private (umask 077), outside the web root. Includes the full
`public_html` tree and exported database; gzip integrity and SHA-256 verified.

- `database.sql`: 338 MB, SHA-256
  `6c5bc2e0d676fa79f28cfb642e6fe2f3fd9e0187db553b7edc68edfcc265f6d2`.
- `public_html.tar.gz`: 3.6 GB, SHA-256
  `d719524a47d0e2a37a273a77cb51ac16a05407fdacc900ab83f92083005a481c`.

Recovery requires a separate explicit rollback decision: verify the same staging
root/URL, restore the archived files and import this SQL into this staging DB,
then clear only its relevant caches. No automatic rollback or production restore.
The backup contains private site data/configuration and stays outside the repo
and public web root. No credentials are recorded in this report.

## Test Kit package

Clean staged copy, 74 entries, exactly one `istodata-utilities/` root, main file
at `istodata-utilities/istodata-utilities.php`, forward slashes, no tests/docs/
vendor fixtures/.git/.github/.claude/nested ZIPs or temporary files. ZIP inspected
directly and CRC verified. Header/constant version remain 2.21.2.

Local upload artifact:
`H:\Το Drive μου\Development\ISTODATA PLUGINS\istodata-utilities.zip`.
Initial SHA-256 `381269d60475ea4a7e17c7200bdc552c57fe92a252a6c349c105b5a83f2c6fdf`. The final corrected package checksum is recorded below.

## Accepted candidate and licence recovery

The user restored the staging licence and explicitly requested continuation.
Before upgrading Pro, a second private snapshot was created under the backup:
`resume-20261001T132827Z/`.

- `database.sql`: SHA-256 `02406fbbf81f1dfa1960a3b99dfbbb1be43268ab11fd5679e463f3b118a1e4f2`.
- `current-core-pro-kit.tar.gz`: SHA-256 `ce69bcc569dc0ccab15fc0886aa4d57d91e9ae5428a78aa27249823ff7923a46`.

The official extracted core 4.3.3 package was 23,603,361 bytes,
SHA-256 `e3e1a4eb6bb458da5efcb67d3aa70e5b8a8eb8c5177c51b88876136e8a098871`.
Official Pro 4.3.1 was 3,504,945 bytes,
SHA-256 `85baf7fb21e03b849825dec30c9b099433c255d9898b93f51fc0a7cec10aabfe`.
Package headers were verified. The exact nonce/fingerprint/confirmation override
succeeded through WordPress Plugin_Upgrader with the AJAX skin. Pro was reactivated;
`wp elementor update db` reported the database already updated. No licence keys,
token-bearing URLs or proprietary vendor packages are distributed in the Kit ZIP.

## Defects corrected during actual acceptance

1. The first plugin-page AJAX core update bypassed the original updater gates.
   WordPress bulk_upgrade's per-plugin hook_extra omits type/action. Both gates
   now recognize canonical plugin identity when that metadata is absent, while
   excluding explicitly different operations. The subsequent actual AJAX Pro
   attempt visibly failed with `iu_elementor_incompatible` before downloading.
2. Enabling native Atomic Widgets adds the vendor interaction collector to
   builder_content_data. Static fragment graphs previously failed closed solely
   because this pass-through collector was present. Only its reviewed exact
   hook/class/method/vendor source path is recognized. Nodes with interactions
   and documents with native cached interactions remain excluded, preventing
   cached fragments from omitting native interaction collection/assets.
3. Motion scrollOn disposal queues a final render. It could overwrite restored
   styles after moving to an excluded breakpoint. Kit retains scroll-driven
   controls through disposal and restores the captured baseline after that
   queued frame, guarded by initializer generation and excluding recollected
   elements. This preserves newly initialized interactions and other consumers.

A staging-only candidate adapter was used to exercise the exact pair before
registry acceptance. Following acceptance, the exact 4.3.3/4.3.1 pair was declared
in the local unreleased registry. The normal full Kit ZIP was installed through
WordPress, the adapter was retired to the private backup, and canonical registry
support plus Greek/English cross-page tests were repeated successfully.
No wider range, independent production exception or new patch approval exists.
A Kit release remains a separate user-authorized step.

## Full Kit acceptance evidence

- Header #30 (Greek) and #33000 (English): real Template controls `417d1ae` and
  `c60daee` saved ON, editor reloaded, default 86400-second/24-hour TTL retained.
  The central navigation `26fa46c6` remains unopted. English Save & Close was
  completed through the native editor; display conditions remained unchanged.
  No content/title/translation repair was made.
- Canonical Greek A `/` builds 150 loops and stores both dropdowns. First B
  `/techniki-ypostirixi/` hits both, builds zero heavy loops/dropdown templates
  and renders central navigation once. Normal B header HTML, 93 external assets,
  displayed/query IDs and excerpt behavior match exactly. Current-menu-item and
  aria-current remain correct. See `kit-candidate-cross-page-el.json`.
- Canonical English A `/en/` builds 156 translated loops; B `/en/the-company/`
  builds zero, hits both dropdowns, retains normal HTML, 89 external assets and
  query/excerpt effects. See `kit-candidate-cross-page-en.json`. Counts are from
  translated loop widgets, not only the Greek loop template's ID.
- Actual per-element admin purge of Header #30 / 417d1ae: next B builds 72 loops
  with `stored,hit`; following B builds zero with `hit,hit`. Only the purged key
  changes, the other key remains unchanged and header HTML is identical. This
  passed again with the final canonical Kit. No concurrent editor/source writes
  were performed between the retained keys and this purge verification.
- Actual Firefox desktop UA shares the normalized desktop hits; iPad and iPhone
  UAs omit the heavy desktop header before construction, with zero fragment events
  and loops. See `kit-candidate-device.json`. Responsive browser viewport checks
  are separate; resizing does not claim to change browser UA.
- Anonymous cached Greek desktop menu opens with 20 visible links. At 1440x1024,
  bounds x=69, y=161.5, width=949, height=722, bottom=883.5 exactly match the
  pre-Pro-upgrade baseline. Actual cached menu navigation opened Leica Infinity.
  English cached menu opens (20 links), its native translated tabs remain present,
  and navigation opened Measurement Services. Mobile 390x844 English hamburger
  popup opens with the normal menu. Browser frontend error logs were empty in
  these checked views. Editor deprecation warnings and existing English newsletter
  missing-form content are unrelated; no all-site clean-console claim is made.
- Atomic setting was tested OFF and ON through native Elementor document save,
  native footer interaction JSON, shared/Pro bundles and Motion 11.13.5. OFF on
  mobile reproduces the vendor exclusion bug. ON suppresses excluded effects;
  desktop load/click/pointer-entry and real scroll triggers animate normally.
  After desktop to excluded-tablet transition all six nodes restore baseline
  opacity 0.8 and empty inline styles, including scrollOn's deferred render.
  Pointer entry used documented native drag input; ordinary hover semantics and
  all six triggers were additionally verified against the actual vendor bundles
  in the controlled VM. Custom breakpoint/asynchronous/missing-API checks are VM
  coverage, not claims of every custom configuration in the live browser.
- Native Atomic Widgets experiment was ON during joint Atomic/Fragment testing.
  Both features operated together in the full Kit without a separate runtime
  fragment loader. Final canonical pair support was verified after adapter removal.

Screenshots: `kit-candidate-canonical-menu.png`, `kit-candidate-english-menu.png`,
`kit-candidate-mobile-menu.png`, `kit-candidate-english-editor.png`,
`kit-candidate-atomic-off-mobile.png`, `kit-candidate-atomic-on-mobile.png`.

## Actual updater checks and limits

On the formerly unsupported 4.3.3/4.2.2 installation, actual WordPress single/bulk
upgrader calls, extracted official source replacement and the auto_update_plugin
filter were exercised for OFF/OFF, Atomic-only, Fragment-only and both ON.
OFF/OFF passes the gate (the earlier download then failed at the licence endpoint);
the three active states reject before replacement. Installed plugin hashes stayed
unchanged during these corrected-path tests. The exact override later installed
Pro successfully after licence recovery. Evidence: `kit-acceptance-live-updater.json`.
No complete cron update, real multisite network UI or filesystem credential prompt
is claimed. Permissions/stale nonce/fingerprint/upload/ordering/multisite branches
have isolated regressions. SSH/direct file replacements cannot be gated by Kit.

## Final staging state and temporary setup cleanup

- Elementor 4.3.3 / Pro 4.3.1 / normal full Kit 2.21.2, active.
- Atomic setting ON; Greek/English saved dropdown opt-ins ON, 24h default.
- Native Element Cache remains `disable`; canonical registry supports both.
- Temporary native test page #40121 was moved to Trash (recoverable).
- Native Atomic Widgets experiment was restored to its original absent option
  and inactive state. This restores temporary test setup, not plugin versions.
- Observation harness deactivated; candidate MU adapter absent. Private backup
  retains the retired adapter. Older inactive probes remain untouched.
- Supported staging WP Rocket domain purge performed after setup cleanup.
- Final anonymous Greek/English desktop and phone requests return 200, contain no
  observation comment/admin bar/critical-error page. Greek phone excludes the heavy
  header through its saved `iu_hide_on_phone=yes`. English has no such saved opt-in
  and retains its existing native CSS hide_mobile/hide_tablet behavior; mobile
  popup browser acceptance passed. No new device setting was copied between
  languages. See `kit-candidate-final-public.json`.
- No production changes, automatic rollback, version bump, commit, push, tag or
  release. Existing user tabs and unrelated site content were not repaired.

Final verified ZIP SHA-256:
`f219e0744b3794c0cd6a363ce2f22250603405d14b7575f380c512a7a31ed6d0`.
74 entries, exact plugin root/main file, no root main/nested root/backslashes/
version folders/development metadata/nested ZIPs; CRC verified. The final ZIP
installed on staging matches this SHA. See `kit-candidate-final-zip.json`.

## Local checks and diff boundaries

PHP is not in local PATH. Official checksum-verified portable PHP 8.5.11 `-n`
passes main-file lint, 23 pairs x four activation states x eight environments,
68 updater target/activation scenarios and 24 Atomic loader cases. Node drift
checks 50 runtime files. Both old and candidate checksum-verified vendor JS suites
pass 17 cases, including the deferred-render and reinitialized-element regression.
Fragment graph (including native cached/source interactions), bootstrap, context,
asset, replay/invalidation checks pass; excerpt replay also passes in actual
staging WordPress PHP 8.2.33. No production PHP configuration change was made.

Pre-existing `includes/elementor-device-visibility.php` was byte-compared against
start-of-task snapshot and unchanged: SHA-256
`4a49d9b5956aa935153a4f8d0fd98726585f2fed606152976a5dc52e61c8323c`.
Existing fragment/device/docs/test changes and scratch files were retained.
Task-only textual diff: `elementor-compatibility.patch`; JSON and screenshots
remain local development evidence and are excluded from the upload ZIP.
