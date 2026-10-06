# Atomic shared frontend contexts — 2026-10-06

PASS on metrica staging, Elementor 4.3.3 / Pro 4.3.1. This correction removes
the Atomic adapter's blanket login and unknown-query-parameter rejection.
Eligible opted-in Atomic elements follow the existing Classic shared-output
contract. Unrelated URL parameters and validated frontend login do not create
additional cache variants. Page/path, device and WPML language variants remain.

The common request guard still excludes editor/preview, invalid authentication,
private cookies/sessions and unsupported request modes. Atomic resolver/query
inspection and shared public-post/markup checks still exclude concrete private
or unresolved dependencies. No per-user or per-random-parameter key is added.

## Real PHP acceptance

The private fixture used the installed native Atomic Loop, real public post
titles/URLs, a genuine temporary WordPress administrator login session and
individually echoed request IDs. Requests bypassed full-page caching at the
staging loopback origin. No existing user's password or session was changed.

| Scenario | Original Loop/layout/item executions | Queries | Result |
| --- | ---: | ---: | --- |
| Anonymous native baseline | 5 | 1 | native |
| Anonymous miss | 5 | 1 | stored |
| Anonymous hit | 0 | 0 | hit |
| Five different random query strings | 0 each | 0 each | same key, hit |
| Logged-in native baseline | 5 | 1 | native |
| Logged-in reader of anonymous fragment | 0 | 0 | same key, hit |
| Logged-in reader with random query string | 0 | 0 | same key, hit |
| Logged-in cold writer after selective invalidation | 5 | 1 | stored |
| Anonymous reader of logged-in fragment | 0 | 0 | same key, hit |

All fragments in this table had the same HTML hash. Anonymous native/miss/hit
external and inline CSS/JS equality also passed. The actual administrator native
fragment matched the shared fragment byte-for-byte. Other-page variants, OFF,
phone visibility, concurrent cold requests and source invalidation passed in
the same run (23 acceptance requests).

The native SDK policy test accepts shared login/query signatures and rejects
user-info/request-parameter dynamic resolvers. Common request regression tests
retain editor/preview, private session, bad auth and private-query guards.

Evidence: [engine](atomic-fragment-integrated-result.json),
[context requests](atomic-fragment-integrated-acceptance.json),
[native policy](atomic-fragment-policy-result.json).

An initial harness comparison sent duplicate candidate/baseline headers,
mistakenly making the native request a hit. The duplicate header was removed;
the successful rerun measured a genuine native logged-in render/query.

## Restoration and package

The temporary authenticated session was destroyed. Disposable fixture/CSS,
private files, MU loader and selectively recorded fragment entries were removed.
Installed runtime files were restored byte-for-byte; settings and documents
30/33000 were unchanged. Atomic Widgets stay ON and native Element Cache OFF.
No production changes or release. The local manual-test ZIP retains version
2.22.4 and includes current Unreleased changes from the shared checkout.
