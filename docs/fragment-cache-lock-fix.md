# Fragment lock correction — 2026-10-04

Validation candidate based on v2.22.2. Validation changed no version, changelog,
compatibility declaration, release, package or production installation.
The subsequently authorized patch release is v2.22.3, covering this correction
only. Compatibility declarations remain unchanged; site installation is separate.

## Runtime changes

- Cold consumers share **1.0 second per PHP request**, rather than up to 45
  seconds per element. The existing `iu_elementor_fragment_cold_wait_seconds`
  filter can lower the budget to zero; values above one are capped.
- Polling reads the shared database owner and generation. Release, replacement,
  lease expiry, generation changes and unknown shared state end waiting. A final
  fresh-entry read covers the publish-then-unlock race.
- A request never waits while holding a fragment reservation, on its own lock,
  or from a reentrant polling callback. Repeated builder passes cannot inherit
  the writer metadata from an earlier reservation.
- Normal WordPress shutdown releases unrendered reservations using exact token
  comparisons. Cleanup and rejected/obsolete producers cannot remove a newer
  owner's reservation. Existing publication and generation fencing remain.
- Existing valid, replay-safe stale fragments are preferred. Otherwise the
  fallback is native rendering without publication by the waiting consumer.
  The early render proxy still bypasses original widgets and their loops.

## Operational limits

The budget bounds intentional polling, not total page time. Database calls and
scheduler delays cannot be interrupted by this timer; native widget rendering
can still be slow. Short waits can increase simultaneous native work when a
cold producer takes longer than the budget. Only the authorized owner publishes.
The previous 120-second logical lease is retained. Shutdown cleanup is best
effort and cannot run after an operating-system kill.

No widget/vendor exceptions or blanket query/search/404 exclusions were added.
Saved opt-ins, device/language variants, TTLs and native Element Cache state are
unchanged. Main-menu root caching remains off on staging; the six opted-in
descendants are the tested cache roots.

## Verification

Offline production-helper regressions passed: publication/rejection/replacement
wakeup, six sibling budget, zero budget, self/holder/reentrant avoidance,
expiry/purge/shared-state failure, unrendered cleanup, stale early bypass and
invalid stale native fallback. PHP lint passed using portable PHP 8.5.11; PHP
is unavailable in PATH.

## Staging results and rollback

All scripted gates passed across 34 fresh PHP HTTP observations. Baseline,
miss and hit fragment HTML and external CSS/JS match separately for EL and EN.
Hits skip all six original opted-in roots and all header/1635 loops. Native
misses execute 150 EL or 156 EN header loops. The uncached root mega-menu still
executes, preserving the selected staging cache layout.

- Six foreign-held roots: total waiting 1.00016 seconds for EL; one shared budget
  for EN too. The other siblings consume no additional sleep after exhaustion.
  Native HTML/assets match and no consumer fragment is published.
- Successful publication: consumer wakes in 0.2066 seconds and performs a
  zero-loop hit with equal HTML/assets.
- Rejected producer: owner release observed in 0.1555 seconds. Consumer executes
  72 native loops for the cold target with no publication; native HTML/assets
  remain equal. Rejection uses an isolated volatile-markup test fixture.
- Generation change: consumer wakes in 0.2079 seconds; both consumer and obsolete
  producer are fenced from publication. Original saved element epoch restored.
- Owner replacement survives old-producer finish/cleanup; old producer cannot
  publish. Five reserved but unrendered locks are removed on WordPress shutdown;
  shared database inspection finds no remaining target locks.
- Replay-safe stale target: zero original cached widgets/loops and zero waiting,
  with identical HTML/assets. Foreign fixture locks are token-safely removed.
- Phone/tablet UA comparisons preserve native HTML/assets and zero heavy header
  loops in both languages. Actual 404 status, HTML/assets and final WordPress
  state match baseline; concrete context guards still choose native rendering.
- Desktop browser EL/EN cached menus open and switch to POSITIONING. EN menu
  navigation reaches `/en/solution/positioning/`. Screenshots and observer proof
  are saved separately. No new physical-device or exhaustive interaction testing
  is claimed; menu closing was not certified by this browser check.

Two-request concurrent cases passed the shared-server load gate (1-minute loads
2.24 / 2.12 / 2.00 on four CPUs, threshold 3). This is a correctness check, not
a capacity benchmark. Self/holder/reentrant, DB failure, expiry and invalid stale
fencing were exercised offline through real production helpers; they were not
new live fault injections.

Cache runtime, wp-salt and advanced-cache files restored byte-for-byte. Active
plugins, settings, documents and opt-ins match the preflight snapshot; native
Element Cache remains disabled. MU loader, executable fixtures and private
credentials removed. Only three recovery backups remain outside the webroot,
with directory mode 700/files 600 and application ACL removed. Local private
credentials removed too. Public EL/EN pages return 200 with no probe output.

Evidence: `fragment-cache-lock-evidence.json`, `fragment-cache-lock-browser.json`,
and `fragment-cache-lock-fix.patch`. The staging candidate is no longer installed.
