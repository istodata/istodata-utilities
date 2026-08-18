# Changelog

All notable changes to this project will be documented in this file.

## 2.20.2 - 2026-08-18

- Version bump to 2.20.2.
- New: ACF Post Gallery field with ordered image selection, preview, removal, and drag-and-drop sorting, plus an Elementor Dynamic Tag with an automatic field selector and fallback to the default WPML language when needed.

## 2.20.1 - 2026-08-18

- Fix: ACF Simple Repeater fields can now be saved with no items, clearing all previously stored rows.

## 2.20.0 - 2026-08-18

- Tweak: Simple Repeater widget - split Title and Description styling into separate Elementor style sections for clearer typography and color controls.
- Tweak: Simple Repeater widget - hide image and link controls in layouts where those controls do not apply.
- New: Simple Repeater widget - added optional responsive horizontal or vertical dividers between items with thickness and length controls.
- Fix: Simple Repeater widget - hide vertical grid dividers before the first item of each row.
- Fix: Simple Repeater widget - apply responsive divider orientation correctly at each breakpoint.
- Fix: Simple Repeater widget - isolate divider rules to their matching responsive breakpoint and inherit orientation correctly when a breakpoint value is unset.
- New: ACF Simple Repeater field - added configurable admin labels for Title, Description, Image, and Link item fields.
- New: ACF Simple Repeater field - added an optional maximum item limit enforced in the editor UI and on save.
- Tweak: ACF Simple Repeater field - translated the maximum item limit note and row removal button in the admin UI.
- Improve: Simple Repeater widget - split the general item spacing into separate responsive row and column gap controls.
- Fix: Simple Repeater widget - preserved saved legacy Gap values after adding separate row and column gap controls.

## 2.19.8 - 2026-08-12

- New: Taxonomy Links widget - links/buttons mode now supports optional default and active icons with responsive size and text spacing controls.
- Fix: Taxonomy Links widget - icons now render at their configured size from the first paint, preventing a brief oversized flash.

## 2.19.7 - 2026-08-10

- Fix: Social Share widget - custom SVG icon IDs and their internal references are now isolated per widget instance, preventing rendering conflicts when multiple widgets use the same SVG on one page.

## 2.19.6 - 2026-08-10

- New: Social Share widget - optional network names can be shown beside their icons, with independent typography, color, and icon spacing controls. Disabled by default to preserve existing widget output.
- Improve: Social Share widget - added a responsive network spacing control under Layout. The Icons spacing control now sets the icon-to-network-name distance when names are shown, while saved legacy spacing remains intact for existing widgets.
- Fix: Social Share widget - network names now align to the start in vertical layouts and use the configured icon hover color.
- New: Social Share widget - the main label can now include an optional leading icon with size, color, and spacing controls.
- New: Social Share widget - added an accessible Popover layout that opens the vertically listed, named networks from the label on hover, focus, or tap, with configurable label hover color and popover background, padding, border, radius, and shadow.
- Fix: Social Share popover now opens with a single tap on touch devices by ignoring the initial click that follows touch focus and limiting hover listeners to mouse-capable pointers.
- Fix: WPML Language Switcher - applies the configured high z-index to the Elementor widget wrapper only while its dropdown is open, keeping it above mega menus without overlapping side carts and other overlays.

## 2.19.5 - 2026-07-31

- Improve: WPML Language Switcher - added a Label Source option that displays the first two uppercase characters of each language's native name, such as `ΕΛ`, `EN`, and `ES`.
- Version bump to 2.19.5.

## 2.19.4 - 2026-07-17

- New: Added a Dashboard option to remove Elementor's `elementor-manage-dashboard` widget, enabled by default for new and upgraded installations.
- Improve: GitHub update checks now cache successful release metadata for 24 hours and reuse the last known good response if GitHub is unavailable or rate-limited.
- Docs: Clarified release branch, title, asset, and no-hardcoded-token rules in `AGENTS.md`.
- Version bump to 2.19.4.

## 2.19.3 - 2026-07-06

- Fix: Elementor AI/Notes cleanup on profile screens now hides only the matching Elementor settings table, preserving the WordPress "Update User" submit button.
- Fix: Removed a hardcoded GitHub authorization token from the plugin update check.
- Docs: Added `AGENTS.md` with the local test zip, changelog, versioning, and GitHub release workflow.
- Cleanup: Removed obsolete local `.claude` settings from the plugin workspace.
- Version bump to 2.19.3.

## 2.19.2 - 2026-05-21

- Fix: Greeklish Permalinks now correctly transliterates uppercase Greek `Ι` through `Π`, including `Κ -> K`.

- Tweak: Admin tabs and storage calculation progress indicators now use the WP7 color `#564be4` instead of the legacy `#0073aa`.
- Version bump to 2.19.2.

## 2.19.1 - 2026-04-29

- Tweak: Dashboard - added the ISTODATA favicon to the Support and Storage Space widget titles.
- Tweak: Dashboard - renamed the Support widget title to "ISTODATA Technical Support" / "ISTODATA Τεχνική Υποστήριξη".
- Version bump to 2.19.1.

## 2.19.0 - 2026-04-28

- New: ACF - added an "ISTODATA Simple Repeater" custom field type with configurable Title, Text, Image, and Link subfields, plus add/remove/reorder controls in the post editor.
- New: Elementor - added a "Simple Repeater" widget that renders the ACF repeater field as grid, accordion, logos, or buttons with basic layout and style controls.
- Version bump to 2.19.0.

## 2.18.0 - 2026-04-27

- New: Elementor - added a new "Query Posts" widget for rendering post/CPT lists with selectable post type, limit, offset, ordering, taxonomy term filtering, optional date display, and optional custom extra link as the first or last item.
- Improve: Query Posts widget includes style controls for item spacing, alignment, typography, padding, borders, backgrounds, shadows, and normal/hover/active states without rendering as a native `ul`.
- Fix: Heading Group (Elementor) - the Overline field now accepts safe inline HTML such as `span`, `strong`, `em`, and `br` instead of outputting raw tags as text.
- Fix: Post Gallery (Elementor/WPML) - translated posts now fall back to the default-language gallery when their local `_isto_gallery_ids` meta is empty, and the plugin declares the gallery meta to WPML as a copied custom field.
- Version bump to 2.18.0.

## 2.17.2 - 2026-04-01

- Change: Elementor settings - the "WPML Language Switcher" and "Multilingual Shortcode Widget" options are now disabled until WPML is active, while still remaining visible in the settings.
- Fix: Restored the Greek labels in the main plugin settings file after the previous encoding regression.
- Fix: Advanced Google Map widget - removed the marker's native browser `title` tooltip so only the custom hover popover is shown.

## 2.17.1 - 2026-03-31

- New: Dashboard -> Plugins - added a dedicated "Elementor Accessibility" option to remove Elementor's new Accessibility dashboard widget (`e-dashboard-ally`).
- Improve: Elementor dashboard widget removal now handles "Elementor Overview" and "Elementor Accessibility" independently.
- Tweak: Greeklish Permalinks transliteration updated so `ου/ού -> ou` and `ω/ώ -> o`.

## 2.17.0 - 2026-03-27

- New: Elementor - added a new "WPML Language Switcher" widget with WPML-powered language data, custom trigger/dropdown output, code/native/translated label options, optional flags, and unavailable language handling.
- New: Elementor - added a new "Multilingual Shortcode" widget that detects the current WPML language and renders a language-specific shortcode with optional fallback shortcode support.

## 2.16.0 - 2026-03-25

- New: Elementor - added a new "Taxonomy Links" widget with support for categories, tags, product/custom taxonomies, optional "All" link, links/buttons mode, native dropdown mode, and dropdown-style mode with real links.
- Improve: Added responsive styling controls for buttons, dropdown trigger/panel/items, active/current term states, cleaner conditional panels in the editor, and simplified background controls without image options.

## 2.15.3 - 2026-03-22

- Improve: Social Share (Elementor) - προστέθηκε προαιρετικό label με responsive επιλογές για θέση (`top` / `left`), typography, alignment, color και απόσταση από τα εικονίδια.
- Improve: Προστέθηκε επιλογή `width` και στην κεφαλίδα του Heading Group.
- Fix: Post Gallery (admin metabox) - the media modal now preloads previously selected images so adding new ones does not replace the existing gallery selection.
- Version bump to 2.15.3.

## 2.15.1 - 2026-03-17

- Improve: Έγιναν βελτιώσεις στο widget Heading Group, προστέθηκε δυνατότητα ρύθμισης πλάτους για το κείμενο και άλλες μικροβελτιώσεις.
- Version bump to 2.15.1.

## 2.15.0 - 2026-03-13

- New: Προστέθηκαν νέα custom entrance και exit animations για τον Elementor.
- Version bump to 2.15.0.

## 2.14.4 — 2026-01-08

- Change: Dashboard → Support widget — Ανανεώθηκε το κείμενο (ΕΛ/EN) και αφαιρέθηκαν οι εναλλακτικοί τρόποι επικοινωνίας (email/τηλέφωνο). Διατηρήθηκε το CTA.
- Change: Ενημερώθηκε το URL υποστήριξης σε `https://www.istodata.com/support/` με προ-συμπλήρωση `website`, `email`, `firstname`, `lastname` από τον τρέχοντα χρήστη.
- Cleanup: Αφαιρέθηκαν αχρησιμοποίητες μεταβλητές στο template του widget.
- Version bump to 2.14.4.

## 2.14.3 — 2025-12-29

- Fix: Bugfixes και βελτιστοποιήσεις στο widget Typed (Elementor).
- Version bump to 2.14.3.

## 2.14.2 — 2025-12-24

- New: Typed (Elementor) — Προστέθηκαν ρυθμίσεις κίνησης εικονιδίου στο hover: ενεργοποίηση, μετατόπιση X/Y και διάρκεια (ms), χωρίς ανάγκη για custom CSS.
- Change: Αφαιρέθηκε η επιλογή «Καμπύλη κίνησης». Η προεπιλογή easing είναι πλέον `ease-in-out`.
- Tweak: Αναδιάταξη πεδίων εικονιδίου — «Κάθετη μετατόπιση (px)» πάνω από «Περιστροφή (°)» για πιο φυσική ροή.
- Styles: Ενημέρωση `assets/css/typed.css` για υποστήριξη hover κίνησης μέσω CSS variables.
- Version bump to 2.14.2.

## 2.14.1 — 2025-12-24

- Fix: Typed (Elementor) — Η επιλογή «Εξαίρεση από το Delay JS» τώρα εξαιρεί και το inline init script (με μοναδικό marker), ώστε σε συνδυασμό με το WP Rocket να φορτώνει άμεσα σε above‑the‑fold περιεχόμενο.
- Fix: Typed (Editor UX) — Σταθεροποιήθηκε η προεπισκόπηση στον Elementor editor: περιορίζεται στο πρώτο string και δεν κάνει loop, ώστε αλλαγές όπως η «Καθυστέρηση Διαγραφής (ms)» να μην επανέρχονται οπτικά κατά την εναλλαγή items.
- Version bump to 2.14.1.

## 2.14.0 — 2025-12-23

- New: Elementor → Προσθήκη widget "Typed" για κινούμενο κείμενο με υποστήριξη πολλαπλών strings, συνδέσμων και εικονιδίων ανά item.
 - Options: Ταχύτητα πληκτρολόγησης/διαγραφής, καθυστέρηση διαγραφής, loop, και επιλογή «Εμφάνιση δρομέα πληκτρολόγησης» (blinking caret) που ενεργοποιεί/απενεργοποιεί τον δρομέα στο τέλος του κειμένου.
- Performance: Εκκίνηση μέσω IntersectionObserver με ρυθμιζόμενο threshold ώστε να ξεκινά όταν γίνει ορατό.
- Compatibility: Προαιρετική εξαίρεση από WP Rocket Delay JS για above‑the‑fold περιεχόμενο.
- Styles: Νέο `assets/css/typed.css` και ομαλοποίηση SVG στα εικονίδια για σωστό scaling με CSS.
- Version bump to 2.14.0.

## 2.13.1 — 2025-12-22

- New: Elementor → “Αφαίρεση Elementor AI και Notes από τη σελίδα του προφίλ”. Προστέθηκε επιλογή κάτω από τα “Βελτιστοποιήσεις” που κρύβει τα σχετικά sections από τις σελίδες `profile.php` και `user-edit.php`.
- Implemented best‑effort inline admin JS: κρύβει headings “Elementor AI/Notes” και ρητά το row του checkbox `elementor_enable_ai` (αν υπάρχει στο markup της έκδοσης Elementor).
- Persistence: Προστέθηκε το κλειδί `elementor_remove_profile_ai_notes` στη whitelist `iu_elem_opt_keys()` ώστε να αποθηκεύεται σωστά και να διατηρείται κατά την αποθήκευση άλλων καρτελών.
- Version bump to 2.13.1.

## 2.13.0 — 2025-12-20

- Typed.js: Φόρτωση μόνο όταν υπάρχει `#typed` στη σελίδα μέσω inline loader (conditional). Προστέθηκε προαιρετική επιλογή “Εξαίρεση από WP Rocket Delay JS (above the fold)” που εμφανίζεται μόνο όταν είναι ενεργά τα Typed.js και WP Rocket.
- Accordion: νέα ρύθμιση “Accordion: Κύλιση στο ενεργό” (Elementor → Βελτιστοποιήσεις). Υλοποίηση σε εξωτερικό αρχείο `assets/js/accordion-scroll.js` με vanilla JS, υποστήριξη off‑canvas, smooth scroll, offsets και delay.
- Mobile animations: νέα επιλογή “Mobile Animations: Έλεγχος ανά στοιχείο (Elementor)” (εμφανίζεται μόνο με ενεργό WP Rocket). Προσθέτει διακόπτη σε όλα τα elements και containers (Advanced tab) για απενεργοποίηση entrance animation μόνο στο κινητό. Εφαρμόζει αυτόματα την κλάση `iu-no-mobile-anim` και inject CSS μόνο σε mobile breakpoint. Μετονομάστηκε η global επιλογή για σαφέστερη διάκριση: “Mobile Animations: Καθολική απενεργοποίηση (όλα τα elements)”.
- UI: Η επιλογή εξαίρεσης WP Rocket για Typed εμφανίζεται μόνο όταν είναι ενεργό το Typed.js και το WP Rocket. Το φίλτρο εξαιρέσεων εφαρμόζεται μόνο όταν είναι ενεργό το WP Rocket.
- Version bump to 2.13.0.

## 2.12.6 — 2025-12-18

- Social Share (Elementor): Μετατροπή των πεδίων εικονιδίων σε Elementor ICONS control (όπως το Social Icons). Κανονικοποίηση SVG για σωστό scaling και ενιαίο χρωματισμό μέσω CSS. Αφαιρέθηκαν τα legacy MEDIA/SVG πεδία.
- Google Map (Advanced): Παραμένει PNG‑only για custom marker. Αφαιρέθηκαν δοκιμαστικά tweaks προεπισκόπησης στον editor — αποδεχόμαστε την προεπιλεγμένη συμπεριφορά του Elementor.
- CSS: Βελτιώσεις σε `assets/css/social-share.css` για `width/height:100%` και ισχυρό `currentColor` στα SVG.
- Version bump to 2.12.6.

## 2.12.5 — 2025-12-16

- New: Dashboard → “Documentation” section in settings (Πίνακας Ελέγχου) with URL field to the site’s management guide. When set, a CTA appears in the Support widget.
- New: Support widget CTAs — added primary button “Αίτημα Υποστήριξης” / “Support Request” and secondary “Προβολή Οδηγού Διαχείρισης” / “View Documentation” (if URL is set), displayed side-by-side.
- Tweak: Added a subtle divider above the CTA buttons for visual separation.
- Tweak: Ticket URL now uses the current site’s domain in the `website` query param instead of a static `www.istodata.com`.
- Version bump to 2.12.5.

## 2.12.4 — 2025-12-16

- New: WordPress → “Μεταφορά jQuery στο footer” — μεταφέρει τα `jquery`, `jquery-core` (και `jquery-migrate` αν υπάρχει) στο footer και μετακινεί επίσης όλα τα scripts που εξαρτώνται από jQuery, ώστε να μην την «τραβούν» πίσω στο head.
- Tweak: Επιπλέον εγγύηση μεταφοράς στο footer ακόμη κι αν έχει ήδη τρέξει το `wp_default_scripts` (αναγκαστική αλλαγή group στο enqueue phase).
- Compatibility: Συμβατό με την επιλογή “Απενεργοποίηση jQuery Migrate”.
- Version bump to 2.12.4.

## 2.12.3 — 2025-12-15

- New: WordPress → “Αφαίρεση WP Rocket Options” — κρύβει/αφαιρεί το metabox “WP Rocket Options” (`rocket_post_exclude`) από όλα τα public post types και κρύβει το toggle στα Screen Options. Η επιλογή εμφανίζεται μόνο όταν είναι ενεργό το WP Rocket.
- New: Elementor → “Απενεργοποίηση Animations στα κινητά” — εισάγει inline JS+CSS μόνο σε κινητά για να απενεργοποιεί τα entrance animations του Elementor. Εξαιρείται ρητά από WP Rocket (Delay/Minify inline) και το breakpoint είναι παραμετρικό μέσω φίλτρου `iu_elementor_mobile_breakpoint`.
- Tweak: Δηλώνουμε τα φίλτρα/εξαιρέσεις του WP Rocket μόνο όταν το WP Rocket είναι ενεργό.
- Version bump to 2.12.3.

## 2.12.1 — 2025-12-11

- Fix: Preserve “Scroll To Top” when saving the “Πρόσθετες Λειτουργίες” tab (was being reset). Added default for `additional[scroll_to_top]`.
- Refactor: Centralized Elementor keys into helpers (`iu_elem_opt_keys()`, `iu_elem_add_bool_keys()`, `iu_elem_add_array_keys()`) and updated save logic to use them across tabs, reducing risk of future omissions.

## 2.12.0 — 2025-12-09

- New: Additional → “Προστασία Περιεχομένου (Κείμενα & Εικόνες) από Αντιγραφή” (απενεργοποιεί selection, δεξί κλικ, drag εικόνων εκτός editor/inputs).
- Improve: Scroll To Top widget — smoother fade, wrapper‑level visibility, editor preview, robust SVG coloring/sizing.
- Fix: Scroll To Top fatal in SVG normalize (use preg_replace_callback; consistent with Social Share approach).

## 2.11.0 — 2025-12-09

- New: Elementor “Scroll To Top” – Dynamic Tag (URL) and widget with icon, size, color (normal/hover), Advanced styling via Elementor (background, border, radius, position), smooth fade-in after 400px scroll, editor-visible preview, and robust SVG handling.
- Improve: Heading Group stagger reliability (hero, cache, reduced-motion) and on‑demand JS loading.
- Improve: Husky (WOOF) assets removal now blocks late enqueues and inline extras, with setting visible only when plugin is active.
- Tweak: Advanced Google Map naming in settings and widget list.

## 2.10.1 — 2025-12-09

- Fix: Husky (WOOF) assets removal on non‑archive pages made robust (handles late enqueues and inline extras via loader tag filters).
- Tweak: Show Husky setting only when plugin is active; place as last option under WooCommerce optimizations.
- Tweak: Minor Heading Group stagger and editor visibility refinements.

## 2.10.0 — 2025-12-09

- New: Elementor “Heading Group” widget (Overline/Heading/Text) with minimal DOM, text shadow controls, responsive spacing, and tokens-based styling.
- New: Staggered entrance animations (Fade In / Up / Down / Left / Right), per-element delays in ms, distance control, device toggles (default off on mobile/tablet), reduced-motion and noscript fallbacks.
- Improve: Best-practice CSS assets for widgets (Heading Group, Social Share, Google Map) and on-demand JS loading only when needed.
- New: Settings → WooCommerce → “Load Husky (WOOF) assets only on Product Archives”, shown only when Husky is active, and de-queues assets off non-archive pages.
- Change: Rename “Google Maps (Advanced)” to “Advanced Google Map” (settings label: “Advanced Google Map Widget”).

## 2.9.1 — 2025-12-09

- Fix: Preserve Elementor settings when saving other tabs (WordPress/Πρόσθετες Λειτουργίες) and guard against missing Elementor classes.
- Tweak: Sync legacy `optimizations[elementor_social_share_widget]` with new `additional[...]` key to avoid mismatches.
- Tweak: Use IU_PLUGIN_VERSION for asset versioning and update logs.
- Version bump to 2.9.1.

## 2.9.0 — 2025-12-09

- New: Post Gallery for Elementor Pro via Dynamic Tag. Add images per post/page/CPT and use Elementor’s native Image Gallery widget with the dynamic source.
- Settings: Added toggle under Πρόσθετες Λειτουργίες → Elementor → “Post Gallery”, with checkboxes for all public post types. Excludes `attachment`, `elementor_library`, and `e-floating-buttons` from the list.
- Metabox: “Post Gallery” metabox on selected post types with multi-select via media frame, thumbnail preview, drag-and-drop reorder, remove/clear. Saves to `_isto_gallery_ids` (array of attachment IDs).
- Elementor: Registered Dynamic Tag “Post Gallery” that exposes the saved gallery to the Image Gallery widget (Pro required).
- Assets: Added `assets/js/iu-gallery-metabox.js` and enqueue only on editor screens for selected post types.
- Version bump to 2.9.0.

## 2.8.0 — 2025-12-08

- Google Maps (Advanced): Added GDPR/Complianz consent gate with placeholder (uses widget height and `assets/images/map-placeholder.jpg` as cover) and an Elementor-styled accept button.
- Google Maps (Advanced): Tooltip now uses site font with bold weight, appears above marker, without close “X”, and with consistent padding + arrow.
- Google Maps (Advanced): Removed Zoom note from panel, removed API Key placeholder, and updated placeholder text copy.
- Google Maps (Advanced): Improved loader stability (retry on consent, avoid double init) and removed internal console warnings.
- Settings UI: Removed Elementor API Key note from Additional → Elementor section as requested.
- Version bump to 2.8.0.

## 2.7.0 — 2025-12-03

- Moved “Social Share Widget” from the “Βελτιστοποιήσεις” tab to the “Πρόσθετες Λειτουργίες” tab.
- Added a new “Elementor” section at the end of the “Πρόσθετες Λειτουργίες” tab that displays only when Elementor is installed/active.
- Moved and renamed “Χρόνος Ανάγνωσης για Elementor” to “Χρόνος Ανάγνωσης” inside the new Elementor section (still requires Elementor Pro).
- Kept backward compatibility for Social Share enablement: reads both the old `optimizations[elementor_social_share_widget]` and the new `additional[elementor_social_share_widget]` settings.
- Added `additional[elementor_social_share_widget]` default key to plugin defaults.
- Version bump to 2.7.0.
- Social Share Widget: Added configurable tooltips and title attributes per network with defaults (e.g., "Κοινοποίηση στο Facebook"). Accessible aria-label now mirrors the tooltip text. Visual tooltip can be toggled via the new "Εμφάνιση Tooltip" option.

