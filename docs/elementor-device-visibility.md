# Elementor Device Visibility: early frontend filtering

The saved controls remain `iu_hide_on_phone` and `iu_hide_on_desktop_tablet`.
Only the literal value `yes` hides a node. The existing user-agent detector treats
phones as one group and tablets/desktops as the other. A hidden widget or
container is removed together with its descendants; sections/columns are
traversed but retain their previous visibility behavior.

## Verified Elementor 4.2.3 paths

- `Frontend::get_builder_content()` obtains document data, applies
  `elementor/frontend/builder_content_data`, then calls
  `Document::print_elements_with_wrapper()`. This covers the confirmed Pro
  header template ID 30 path and other documents rendered through this method.
- `Document::do_print_elements()` creates element instances from the filtered
  array. `Element_Base::print_element()` calls `print_content()` before
  `elementor/frontend/{type}/should_render`. Filtering the array therefore
  prevents widget construction, content generation and descendant traversal.
- `Local::get_data()` also invokes `builder_content_data` when retrieving
  template content. Admin, REST, editor and preview requests bypass early
  filtering so template-library editing/export data is unaffected. Frontend
  nested templates rendered through `get_builder_content()` are filtered on
  each invocation; frontend callers of `Local::get_data()` also receive a
  filtered in-memory copy.
- `Local::prepare_template_export()` uses `Document::get_export_data()` and
  its own export filter, not `builder_content_data`.
- Direct `print_element()` calls, or other render paths that do not invoke
  `builder_content_data`, still use the legacy `should_render` fallback. It
  removes final HTML only; PHP work has already happened. An in-memory marker
  on retained nodes avoids a second visibility decision in the usual builder
  path. Elementor 4.2.3 passes unknown data keys through element construction;
  the marker is never saved to the document.

The filter does not guarantee early removal in every Elementor or third-party
render path. It also cannot stop work done before builder data is filtered,
including document loading, migrations and earlier filters. Editor/preview
content remains visible. Admin and REST rendering use the late fallback where
Elementor calls `should_render`.

## PHP and asset impact

For a removed branch, PHP does not instantiate its Elementor elements, render
its widgets, run its loops/queries/shortcodes, or visit its children. The
filter itself traverses the document tree once. Retained ancestors still run.
Elementor may still generate and enqueue document CSS from the complete saved
document; global CSS, theme assets and assets queued independently of hidden
widgets can remain. This change does not enable Element Cache and does not
alter page-cache variation by device.

## Staging acceptance for metrica.gr header ID 30

1. Snapshot the staging plugin and header configuration. Confirm Elementor and
   Pro versions, Element Cache remains off, and the saved visibility switches.
2. Use a temporary counter or query trace scoped to the hidden mega-menu loop
   and header ID 30. Compare anonymous phone and desktop requests before and
   after upload, with full-page cache bypassed. Check that the hidden branch
   yields zero loop executions, not just absent HTML.
3. Repeat on iPhone, Android phone, iPad/Android tablet and desktop user agents.
   Verify the opposite branch still renders and that a hidden parent suppresses
   all descendants. Check both cached and bypassed responses for device cache
   mixing without enabling Element Cache.
4. Open editor and preview, confirm all elements remain editable and that
   template export/saved data are unchanged. Test a template embedded in the
   header and an AJAX/REST-rendered template, recording whether each enters
   `builder_content_data` or uses the late fallback.
5. Compare Elementor CSS, widget assets and PHP query counts. Restore the
   staging plugin snapshot if layout, editor or cache behavior regresses.
