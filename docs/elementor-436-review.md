# Elementor 4.3.6 / Pro 4.3.2 compatibility review

Reviewed and accepted by Compatibility Control on 2026-11-03 for Kit 2.23.7 preparation. Publication remains separately authorized. This is an exact-pair, declaration-only update for Atomic Interactions breakpoints, Elementor Fragment Cache, and Atomic Elements Cache / Device Visibility. Every prior accepted pair, the 4.2.3 core-only static path, adapters, saved controls/TTL/markup, eligibility and privacy/editor bypasses remain unchanged. Native WordPress/vendor updates remain unrestricted. This is not whole-site safety approval or an upstream #35831 fix.

## Sources and tested candidate

Official Core source: [WordPress.org 4.3.6 ZIP](https://downloads.wordpress.org/plugin/elementor.4.3.6.zip), matched against its official per-file checksum map (3,437 files), and [pinned upstream commit](https://github.com/elementor/elementor/tree/561b44289f6d953c6ce950c851579dc085e93a4f). The annotated upstream tag is `4.3.6`, without `v`. Pro source is the retained licensed 4.3.2 package, not a newly inferred Pro range.

| Input | SHA-256 |
| --- | --- |
| Official Core 4.3.6 ZIP | `f9f04c40e559ecc1c737eec6519e7223ff9a1ab04abcc2e004b44c9f6d1247a3` |
| Core web-permission repack used in staging | `0415bbc61e25c65f54ef5e57f9e8a1f602168a6e420358cedd8664ce4abe0b29` |
| Retained licensed Pro 4.3.2 staging ZIP | `1ef8c7332b84b6aabe5b2a05d2553b288b10664064ff804f53dbdc7b6bf9d8d2` |
| Private Kit 2.23.6 test candidate | `a0b7a8117a01459f466b7015c1468fa2eeee27c7dca322e3402aa0de021c9b79` |

Actual QA used WordPress 7.1.3 / PHP 8.3.36, Core 4.3.6 / Pro 4.3.2 and that private Kit candidate. Only its copied central registry temporarily added the exact pair; no widening filter or adapter exception was used. Restored staging baseline was Core 4.3.4 / Pro 4.3.1 / Kit 2.23.0 with native Elementor Element Cache OFF. Kit 2.23.7 receives local contract/package verification and runtime parity against this candidate; it is not described as a newly installed remote candidate.

## Source review and focused QA

- Reviewed changes include Atomic post-ID CSS batching/deduplication, request-local decoded cache-validity trees, Kit repeater stylesheet updates, empty design-system stylesheets, Twig JS identifiers and native SVG URL normalization. No reviewed Kit-facing method/hook signature was removed. No adapter change was needed.
- Thirty-two relevant Core interaction/Atomic serialization assets and PHP files were byte-identical between 4.3.5 and 4.3.6. Retained Pro 4.3.2 normal/minified initializer hashes match the earlier 4.3.5 review manifest. Current Kit interaction PHP/JS match the 2.23.5 release manifest and published 2.23.6 ZIP. This narrows repeat testing; it is not a claim of an earlier 4.3.5 optimized-Rocket PASS.
- A: 17 real shared/Pro bundle VM cases passed, using Motion doubles. A separate standalone real Motion/native-initializer browser load/fade check covered ON mobile, native OFF mobile, ON desktop and resize cleanup. These are separate forms of evidence, not full browser coverage for all triggers.
- B: one mixed Classic Template/Pro-loop and Atomic Loop fixture passed native/cold/hit HTML and ordered external/generated/inline CSS/JS comparisons in external and internal CSS modes. Both cached roots stored, then hit without original renders or queries. Repeated post IDs added zero CSS batches; one late new document added one. Pro query avoid-list/excerpt states were retained in primary evidence. No larger context matrix or old-version browser rerun is claimed.
- C: native SVG save canonicalized a valid attachment URL and emptied an invalid-ID value. Correctly published/frontend save emitted `styles/clear` for the local document and regenerated desktop CSS from `#123456` to `#224466`, with new fragment keys. The earlier draft-save/frontend-file mismatch is not classified as a Core/Kit invalidation bug.
- Language/device: the real `/en/` route resolved WPML `en`, its translated page/template IDs and English cached output, with keys distinct from the Greek translated document. A mobile-UA request used separate device keys and did not execute the hidden heading. This is bounded translated-page/template and device evidence, not a full same-fragment cross-language HIT matrix or physical-device claim.
- Rocket integration: one owned published canvas was saved through native `Document::save` with nonempty interaction cache and actual Pro license feature availability. Anonymous/no-query HTML used the existing Delay JS setting; 40 other script tags, including the canary, were delayed. The protected order was Motion -> shared -> config -> Kit inline-before -> Pro, with no Rocket delay/async/defer on that chain. In one actual top-level anonymous Chromium load at width 390, one button click released the canary while the excluded element acquired no inline animation style. Browser errors/warnings were empty and Motion/shared/Pro resources were observed.

## Fixture corrections and evidence limits

- Empty PHP counter maps serialize as JSON lists; the local assertion was corrected without rerunning completed external requests. WPML one-day cookie expiry and the exact test-owned MailOptin URL marker were audited separately; no unrelated inline difference was ignored.
- The C fixture's dynamic heading title failed the actual registered schema; a static title alone made all four Atomic nodes valid. This did not change product dynamic-tag support.
- Draft saves invalidate preview CSS. The corrected published fixture matched frontend consumption and recorded native clear arguments; no manual/global CSS clear was used.
- Native interactions persist in the element's top-level `interactions` envelope (`version`/`items`), with both `timing_config.duration` and `delay`. The individual PropType check alone did not enforce the full save validator's required delay. Corrected serialization was proven offline with actual vendor classes, then in native save/cache/footer evidence. In-memory WordPress adapters in the offline proof are not real DB/browser proof.
- The final Rocket fixture's optional opacity 0.8 style did not persist. Its actual default opacity was 1 before and after the click, with no inline style; no 0.8-baseline PASS is claimed. A supplementary read-only custom-global probe did not establish main-world runtime-marker visibility, so installation-marker PASS is explicitly excluded. Rocket evidence is ordered excluded assets, observed resources, canary release and no inline effect, not a full trigger/custom-breakpoint/cross-browser matrix.
- No upstream #35831 resolution, clean whole-site console, site-wide safety, editor latency or all older-version browser coverage is claimed. Existing conservative unsupported-content bypasses remain in force.

## Primary references and restoration

References below identify private retained QA runs and file hashes. Raw logs, proprietary bundles, screenshots, authorization/session hashes and private machine paths are intentionally not committed or shipped.

| Evidence | Private run/file reference | SHA-256 |
| --- | --- | --- |
| Source review | `iu-436-source-review-pq2a6cce/read-only-review.md` | `1d387149e7dc31e88c094499829aab581c506e044d6952f2271b1277180ebb03` |
| External CSS native/cold/hit | `iu-436-qa-cs9324hz/external-results.json` | `8566b7e9f96bb741250252a5c4932c29cc46dc2093ee82791741681788d65e3b` |
| Internal CSS native/cold/hit | `iu-436-qa-cs9324hz/internal-results.json` | `e715b7dc0171b89f4a878ce01cd9d161b273f6ff6fb54a59d4f26cacfa18959b` |
| Published save/clear arguments | `iu-436-published-0vkr9i2y/published-save-result.json` | `afac66283da75f01da06b83246d5b2902e55d4f285ec2ab5e93dec77662f90e1` |
| Actual WPML English/template | `iu-436-closure-9tea1zi0/native-results.json` | `43ea7198e7239ef5c5531961038614767c9bb84a748fa69a513e582e31f5090f` |
| Native Rocket order/footer | `iu-436-rocket-final-nbetrzac/native-order-proof.json` | `085d52ad1ec2384d807298afa2b40085ac9af3aa100b37e3ce256e0baae2d990` |
| Mobile before interaction | `iu-436-rocket-final-nbetrzac/browser-before.json` | `44b87852d76ce03725419f9cc843e8099ad9774a1d2debc0ee96e2189a468d25` |
| Mobile after interaction | `iu-436-rocket-final-nbetrzac/browser-after.json` | `1a762d5485049d91a25eb88768c789c8ac1e06eab1a63291d209c6a00175c7be` |

Supplementary retained artifacts include `external-offline-audit.json`, `preflight-result.json`, `save-result.json`, `published-after-results.json`, `published-mobile-result.json`, `motion-browser/browser-evidence.json`, `offline-interactions/serialization-proof-result.json`, `435-436-parity.json`, `Pro-and-Kit-historical-parity.json`, and the final `saved-payload.json`, `saved-interactions-cache.json`, `license-proof.json`, `browser-critical-assets.json` and `final-integration-report.json`.

Scoped fixtures/plugins/generated files/options/transients were restored after each phase. The final fixture and its WPML row/canary/owned Rocket cache path were absent; generated originals and ACL/xattrs matched the snapshot. Homepage and authentication hashes matched their baselines; native cache remained OFF. The final short health window observed no new OOM/service restart and the restored origin returned HTTP 200. This is not an overall hosting stability guarantee. No production or hosting configuration change occurred.

## Release preparation contract

The new pair is appended to each of the three central accepted declarations; separate tested entries record the limits above. Existing declarations/evidence are preserved. Focused local regressions pin all 15 declarations across seven distinct pairs, including core-only support, exercise the new runtime/advisory destination and keep 4.3.6/4.3.1, 4.3.7/4.3.2 and 4.3.6/4.3.3 unsupported. Required compatibility/updater/drift/lint checks, clean archive inspection and runtime parity are recorded in the separate 2.23.7 preparation report. No remote retest, publication or production installation is implied.
