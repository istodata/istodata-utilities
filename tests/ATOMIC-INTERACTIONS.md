# Atomic Interactions breakpoint workaround (#35831)

The settings key is `optimizations.elementor_atomic_interaction_breakpoints`.
It is OFF when absent and OFF in activation defaults. The existing Elementor
settings whitelist sanitizes it to a boolean and preserves it when saving other tabs.
The PHP loader only attaches the plugin's JS before an enqueued, not-yet-printed
`elementor-interactions-pro`, only for the explicitly approved Elementor 4.3.2 /
Pro 4.3.0 and 4.3.3 / 4.3.1 pairs in `includes/elementor-compatibility.php`. The 2026-10-01 policy
supersedes the earlier open-ended 4.3.x patch acceptance. New patches, earlier
versions and prereleases are rejected until review and a Kit compatibility release.
The enqueue priority is 1000; footer priority 1 handles late enqueues. No vendor
runtime is shipped or loaded by the plugin. API guards remain in place for every
allowed version. Every new core or Pro version requires review, an updated
registry declaration and a Kit release, even if feature code does not change.

## Reproducible local runtime checks

The source references supplied for this task were downloaded vendor shared and
Pro bundles used with Elementor 4.3.2 / Pro 4.3.0. The Pro bundle includes the
4.3.0 build header dated 22-09-2026. The manifest pins their exact SHA-256 bytes;
the shared bundle has no version banner, so its association with 4.3.2 comes from
the supplied source provenance, not an independent banner verification.
Use licensed copies of these exact reference files in a directory of your choice:

- `avra-interactions-pro-source.js`
- `avra-interactions-shared-source.js`

```powershell
node tests/prepare-atomic-fixtures.cjs 'D:\elementor-reference-fixtures'
node tests/atomic-interaction-breakpoints.cjs
```

Preparation validates both files before copying them to the ignored
`tests/fixtures/vendor/` directory. The test resolves fixtures relative to itself,
never to a personal Temp directory. Missing or changed fixtures fail explicitly.
Keep the two original reference downloads to reproduce the exact test suite.
No authenticated vendor source or proprietary Pro bundle is committed.

The suite executes the actual shared and Pro bundles in an isolated Node VM.
DOM/style, timers and Motion are controlled test doubles. It checks the native
OFF regression, device exclusions, Desktop execution, repeated changes without
subscription accumulation, custom unordered max/min boundaries, independent
exclusions, multiple elements, hover/click (Motion press), scrollIn/scrollOut,
scrollOn and load, CSS value/priority restoration, empty inline properties,
stale trigger/leave/completion callbacks, once-only disposal, API/duplicate
guards, unaffected external Motion consumers, DOMContentLoaded and the legacy
data-interactions path. It counts registrations and callbacks; it does not prove
real browser Motion observer behavior or the visual correctness of every trigger.

## PHP checks

```powershell
php -l istodata-utilities.php
php -l includes/elementor-atomic-interaction-breakpoints.php
php -l tests/atomic-interaction-breakpoints.php
php tests/atomic-interaction-breakpoints.php
```

The isolated PHP checks cover OFF, missing plugins, rejection of unapproved patch
updates to either or both plugins, the 4.4 boundary, older versions, malformed/prerelease versions, missing
handle, already-printed handle, admin context, supported versions, late enqueue,
failed attachment retry, hook priorities, payload order and duplicate guards.
They require PHP; they are not a WordPress settings-page/browser acceptance test.

## Scope and acceptance still required

This is an internal-API workaround, not a public Elementor extension contract.
It retains the native animation engine and normal per-interaction Trigger on.
The JS preflights shared APIs and breakpoint data before installing wrappers.
It uses the native wait for asynchronous Motion loading, then validates the full
Motion API before enabling the workaround or replacing Motion subscription APIs.
If Motion is incomplete/non-writable, the wrappers delegate entirely to native
behavior and no breakpoint reinitialization is performed. A legacy Avra workaround
marker is recognized only to avoid a second patch; do not enable both copies.
Other consumers calling shared APIs during Pro's synchronous initializer have
not been tested. Dynamically inserted interactions/editor reinitialization are
outside the verified single native initializer lifecycle.

The local suite does not establish editor preview support, real Motion hover/press/
scroll behavior, script-optimizer compatibility,
or combined browser behavior with existing device visibility/legacy animation
options. Those implementations were left unchanged. Browser checks on a
coordinated staging install are required before broader support is claimed.
The only existing live evidence supplied with the task covers Avra frontend
scroll-into-view. This implementation performs no live installation or tests.

## Local validation on 2026-09-28

- All 16 runtime cases passed with both checksum-verified vendor bundles.
- JavaScript syntax checks and `git diff --check` passed.
- PHP was unavailable in PATH; PHP lint and the isolated PHP loader suite were
  prepared but not executed. Settings saving and admin rendering still require
  actual WordPress acceptance, alongside the browser checks above.
- Initial checkout: clean `main`, 2.21.0. Version remains 2.21.0. No unrelated
  pre-existing changes, commit, push, tag, release or live site mutation.

## Packaging and next step

Never include `tests/` (especially `tests/fixtures/vendor/`) in a manually staged
plugin ZIP. `.gitattributes` also excludes tests from `git archive`; filesystem ZIP
builders must explicitly exclude them. The plugin runtime uses only its new
`includes/` PHP and `assets/js/` file. No test ZIP is requested at this stage.

After local acceptance, coordinate installation, switch OFF WPCode 11756 while
switching ON this option, clean caches and verify the real frontend. Rollback is
OFF in Kit and re-enable the existing snippet. Until then leave it unchanged.

## Full Kit acceptance update, 2026-10-01

The old historical acceptance notes above describe the initial development run.
Current version remains 2.21.2; no release was created. Exact 4.3.3/4.3.1 was
accepted in the local unreleased registry after the full Kit staging checks in
`../docs/archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md`. Native Motion OFF reproduces the
exclusion bug; ON retains desktop effects and restores excluded mobile/tablet
baselines, including a queued scroll timeline final render. Restoration after
that frame excludes nodes recollected by the new initializer.

Both actual vendor-bundle VM suites pass 17 cases; PHP loader passes 24 cases.
Candidate fixtures are private official extracted files, checksum-pinned by a
manifest; set `IU_ATOMIC_CANDIDATE_FIXTURES` to that directory or use the temporary
`iu-candidate-433-431` directory. Run `node tests/atomic-interaction-candidate.cjs`.
No vendor packages/fixtures/tests are included in the 74-entry manual test ZIP.
Browser custom-breakpoint/cross-browser exhaustiveness is not claimed.
