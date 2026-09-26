# Simple Repeater and WPML

The adapter loads with the ISTODATA Simple Repeater ACF field. It uses the
existing field name, ACF reference and array stored in post/term meta. No data
migration, frontend replacement or bulk rewrite is performed.

## Translation workflow

- Keep the ACF field translation preference set to Translate. New Simple
  Repeater fields default to Translate; explicitly saved Ignore, Copy and
  Copy Once preferences are left to WPML.
- Send the post or taxonomy term for translation again after installing the
  adapter. Existing jobs do not gain the new units retrospectively.
- Each nonempty title and description containing words is a separate unit.
  Numeric statistics (including `45+`, `4.500`, `10%`, `80x80`) are copied.
- Row keys/order, images, links and any additional row properties are copied.
  Images and links retain their source values; the adapter does not remap them.
- Completed translations are written back as the same array, allowing WordPress
  to serialize it normally. ACF references and value caches are maintained.
- Empty repeaters clear the corresponding translated value on delivery.

Post translation jobs include repeaters on the post and on its attached,
translatable terms. Independent taxonomy jobs use WPML 5's
`wpml_translatable_term_meta_values` and `wpml_apply_translated_term_meta` hooks.
Those paths were inspected in WPML 5.0.2. Older WPML versions without these hooks
cannot use the independent taxonomy-job integration.

The manual **WPML > Taxonomy Translation > Term translation** dialog has a
separate, admin-only bridge (verified against the WPML 5.0.2 view contract).
It replaces the repeater's serialized input with individual text controls using
the ACF field/subfield labels, while leaving unrelated term meta untouched.
Numeric-only titles, images and links are copied when saving. Existing translated
text is prefilled. A nonce, taxonomy edit capability, translation-group identity
and source fingerprint are checked before WPML writes anything. If loading fails
or the source changes, reopen the dialog; the bridge will not submit a partial
repeater. This integration extends WPML's modal view, so changes to that view in
future WPML releases require regression testing.

## Pending jobs

The post job contains a non-translatable snapshot of each repeater. Standalone
term units include a fingerprint of the source array. If source rows have
changed since export, that field's stale delivery is ignored: send a new job
to translate the current rows. Unchanged fields in the job can still apply.
Incomplete field deliveries also leave existing translated values intact.

## Verification

Run `php tests/simple-repeater-wpml.php` for isolated adapter contract checks.
They cover extraction, reconstruction, preserved types/escaping, empty values,
partial/stale jobs, term IDs versus taxonomy IDs, preferences and unrelated
fields. They use WordPress/WPML stubs and do not replace an end-to-end ATE test.

The optional `node tests/simple-repeater-wpml-modal.cjs` fixture requires
Playwright, Chrome and read-only SSH access to a WPML installation. Configure
`IU_WPML_SSH_HOST`, `IU_WPML_SSH_KEY` and `IU_WPML_WP_ROOT` in the environment.
It reads the installed modal assets, mocks requests in an isolated browser and
does not load WordPress or write site data.

Before release, use a disposable post and term in the installed WPML setup:

1. Add two rows with distinct titles/descriptions, a numeric-only title, an image
   and a link. Send new translation jobs and confirm separate editor units.
2. Complete translations and check the destination editor and frontend.
3. Repeat via the independent taxonomy translation dashboard.
   Also open the manual Term translation dialog, save, reopen, and confirm
   individual fields, existing translations and unrelated Rank Math meta.
4. Remove/add/reorder rows, send new jobs, and verify destination structure.
5. Clear all rows and verify the destination clears after translation.
6. Edit source rows while a job is pending and verify the old delivery cannot
   overwrite the corresponding translated field; send a fresh job afterwards.

No automatic translation jobs or paid translation services are triggered by
the adapter itself.
