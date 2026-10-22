# Elementor 4.3.5 / Pro 4.3.2 acceptance

Kit 2.23.1 accepts only the exact Core 4.3.5 / Pro 4.3.2 pair for Atomic Interactions, Atomic element caching/device visibility and Classic fragment caching, within their existing eligibility and protective bypasses. The mixed 4.3.5/4.3.1 and 4.3.4/4.3.2 pairs remain unsupported. Compatibility control approved publication on 2026-10-22 after reviewing the separate tested evidence and final-package checks.

## Source and runtime evidence

Official Core and licensed Pro archives were compared with the previously accepted versions. Relevant builder, element/widget, shared interactions and Motion implementations were unchanged. The reviewed changes included REST CSS suppression, Atomic custom-attribute rendering and Pro Loop term filters. The Kit request gate and diagnostics now also recognize wp_is_rest_endpoint() during internal REST dispatches without REST_REQUEST, restoring normal eligibility after dispatch.

Seventeen actual vendor-bundle VM cases passed. Real Motion/native-initializer ON/OFF behavior, six triggers and desktop/tablet/mobile lifecycle were separately exercised in a local browser; this is not a claim of a new full staging Motion matrix.

Actual staging Atomic Loop native/miss/hit checks retained HTML and ordered external/generated/inline assets, with zero original renders/queries on hits. Coverage included 23 context requests, six Greek/English phone responses and 20 native SDK policy checks within the existing conservative scope. Native Atomic and Classic edit/save/reload persistence was functionally verified with their original control IDs and typed values.

Actual WordPress/Pro excerpt replay passed, preserving external callbacks and future removal. An earlier dependency-free isolated excerpt invocation exited 255; it did not pass and is not used as WordPress/Pro evidence.

## Final package evidence, 2026-10-22

The actual final 2.23.1 package was temporarily installed on metrica staging with Core 4.3.5 / Pro 4.3.2 and the normal registry, without a version/policy override. Installed file bytes matched the upload ZIP; real FPM bootstrap showed the expected versions, readable registry/main files and all three normal feature guards active.

Cold rendering stored six Classic header fragments and executed 150 header loops. The subsequent request, including an ordinary unknown parameter, delivered six hits with identical fragment HTML/keys, zero header loops and zero original cached widgets. Earlier corrected browser checks also verified desktop dropdown/tab/cached-link navigation and cross-page shared reuse.

The final-package editor displayed Classic OFF with stored seven-day duration and Atomic ON with its saved 24-hour duration and phone visibility setting. This check read existing settings; it did not repeat the already-passed save/reload round trip. Scoped snapshots and original directories were restored, disposable fixtures/probes removed, native Elementor cache left disabled, and protected settings/documents/homepage/session state preserved.

## Evidence limits

Editor elapsed usability observations are not clean latency measurements: additional main GETs with unknown initiators and changed preview versions appeared beyond the single agent navigation. A third-party reCAPTCHA connection warning was visible; forms, challenges and mail delivery were not tested.

Corrected responsive browser phone checks used a narrow viewport and scoped server UA substitution. They prove desktop-fragment non-replay/visibility and mobile menu/search UI behavior, not physical-device testing or a phone-fragment HIT. Separate Atomic language/device HTTP evidence remains separate.

Inline fingerprints retained raw hashes and audited only the fresh WPML cookie expiry and the explicitly identified MailOptin current-request URL delta; no general normalization is claimed. Invalid unreadable-package and fake-generation harness attempts were excluded. Historical health-gated attempts do not establish a Kit incompatibility, infrastructure root cause or recovery. Final scoped checks recorded no new matching failures; no infrastructure resolution or production installation is claimed.

Detailed historical artifacts and private operational logs remain outside the release commit and distributed ZIP. The changelog date 2026-10-16 records preparation; publication approval and final package completion occurred on 2026-10-22.
