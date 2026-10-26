# Kit release metadata refresh

Routine GitHub release metadata is cached for24hours. The native WordPress Updates Check again action can refresh Kit metadata in the same request, provided it runs on the authenticated load-update-core.php screen with update_plugins capability. Multisite also requires the matching network-admin screen and manage_network_plugins capability. Public query parameters or a caller supplying the getter's optional argument alone cannot authorize refresh. Cron, AJAX, REST, CLI and upgrade submissions do not enable the manual bypass.

The priority1 callback precedes native update callbacks. It projects the Kit result into existing checked WordPress update data without replacing other plugins or its last_checked value, even if WordPress later skips its own recent check. Update-transient and plugins-info callbacks share one attempted fetch/result per request, including failure. Existing zero-argument callers and cache/fallback keys remain supported.

Before remote I/O, a network-wide transient reserves a minimum60second retry interval. HTTP403/429 can extend it according to Retry-After or rate-limit reset, bounded to24hours. This is a sequential budget, not an atomic distributed lock. Separate WordPress installations on one hosting IP do not share this state; routine24hour caching remains their principal traffic protection.

A network failure, malformed response or cooldown retains cached or last-known-good metadata with the existing six-hour fallback. Failed responses cannot overwrite the last valid metadata. The manual screen warns when a fresh check failed or must wait; it does not claim successful refresh in those cases.

## Acceptance limits

Local regressions exercise32isolated requests using the actual updater functions with controlled WordPress HTTP/cache boundaries. They cover stale-cache refresh, routine hits, unauthorized contexts, multisite scope, network errors,403/429 retry headers, malformed data, fallback, repeated callbacks and earlier admin calls.

On2026-10-26, a bounded WordPress7.1.3 staging test clicked the real native Check again link. One real GitHub latest-release request returned HTTP200/v2.23.2 and the same response rendered the available Kit update. A routine cached request used no remote I/O. Repeated getter/plugins-info and actual transient filters added no fetch or retry write. Old metadata and checked-update data were fixtures; an API URL query suffix isolated their cache namespace. The updater section matched the local candidate. No update installation was executed.

The native callback was observed at priority10 after the manual refresh, but its recent-check early-return branch was not claimed as exercised. The probe measured retry-write counts, not remote cache expiry. Real rate limits were not induced. These results do not diagnose any live site's cache. Original staging files and scoped state were restored; no production installation formed part of this acceptance.
