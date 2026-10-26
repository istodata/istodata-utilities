# Approved Elementor / Pro update flow

The normal WordPress updater checks each installed intermediate pair. Selecting both updates does not authorize an unsupported intermediate pair. ISTODATA Kit therefore provides a separate, explicitly confirmed sequential update form through its compatibility screen.

The form pins the installed pair, available target versions and package URLs. The destination must be accepted by every relevant adapter in the central exact-pair registry. A capability check, user/session nonce, confirmation checkbox and native request lock protect the flow. Request-local authorization is bound to the specific upgrader and step; it does not permit automatic or cron bypasses. Existing single-plugin override controls remain available under advanced details.

Core runs before Pro through native Plugin_Upgrader bulk calls, one plugin per call, preserving activation. Each step rechecks installed files, metadata and extracted package version. A failed or interrupted step stops the flow without automatic rollback. Runtime policy reads the actual installed files for the rest of the coordinating request, including when the loaded Elementor constants still describe the old pair. Fresh requests use the ordinary exact-pair guards. A fresh confirmation can complete a remaining Pro update toward the accepted destination.

The transition is not atomic and cannot lock out arbitrary third-party updaters or manual filesystem changes. Keep a backup and avoid parallel updates. The flow refuses an existing maintenance marker and removes only the exact uniquely identified marker it wrote; a replacement marker is preserved. Feature settings and accepted version pairs are unchanged.

## Acceptance and limits

On 2026-10-26, the staging native form successfully moved Core4.3.4/Pro4.3.1 to Core4.3.5/Pro4.3.2. A controlled second-step failure retained Core4.3.5/Pro4.3.1 with activation preserved and incompatible features bypassed, including on a fresh frontend request. A newly confirmed Pro-only recovery restored the accepted pair and frontend cache eligibility. Native direct filesystem installation, HTTP download, extraction and rendered success/failure messages were exercised. Available metadata was a request-scoped fixture pointing to checksum-verified vendor archives; live vendor update discovery and filesystem-credential transports were not exercised.

The internal restore report retains two differences: the native updater refreshed the WordPress Greek language pack outside the scoped snapshot, and one restored generated CSS file lost the fractional part of its modification time. These are not claims of a byte-identical whole-site restore. No production installation or hosting change formed part of this acceptance.

Local regression coverage includes standard bulk deadlock, authentication/nonce/metadata/source changes, partial failures, stale constants, recovery, and own/pre-existing/replaced maintenance markers. The central registry remains the sole source of accepted pairs.
