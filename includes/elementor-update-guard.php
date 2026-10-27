<?php
/** Compatibility information only. Native WordPress/vendor update decisions are untouched. */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/elementor-compatibility.php';

// Retain the existing class, screen slug and URLs for saved links/integrations.
final class IU_Elementor_Update_Guard {
    private static $notice_printed = false;

    public static function plugins() {
        return array('elementor/elementor.php' => 'core', 'elementor-pro/elementor-pro.php' => 'pro');
    }

    public static function boot() {
        // No automatic, download or extracted-source updater gates.
        add_filter('upgrader_post_install', array(__CLASS__, 'after_install'), PHP_INT_MAX, 2);
        foreach (array_keys(self::plugins()) as $plugin) {
            add_action('in_plugin_update_message-' . $plugin, static function ($data, $response) use ($plugin) {
                self::update_message($plugin, $response);
            }, 10, 2);
        }
        add_filter('site_transient_update_plugins', array(__CLASS__, 'updates_screen_advice'));
        add_action('admin_notices', array(__CLASS__, 'notices'));
        add_action('network_admin_notices', array(__CLASS__, 'notices'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('network_admin_menu', array(__CLASS__, 'menu'));
        // Explicit tombstones for old bookmarked/submitted forms; never load an upgrader.
        add_action('admin_post_iu_elementor_update_override', array(__CLASS__, 'retired_update'));
        add_action('admin_post_iu_elementor_update_pair', array(__CLASS__, 'retired_update'));
    }

    public static function installed_pair() { return iu_elementor_installed_pair(); }
    public static function uses_installed_pair() { return true; }
    public static function after_install($reply, $extra) {
        if (isset(self::plugins()[$extra['plugin'] ?? ''])) iu_elementor_runtime_pair(true);
        return $reply; // Observation only: never replace a native/vendor result.
    }

    // Harmless compatibility shims for callers of the previously shipped gates.
    public static function auto_update($allow, $item) { return $allow; }
    public static function pre_download($reply, $package, $upgrader, $extra) { return $reply; }
    public static function source_selection($source, $remote_source, $upgrader, $extra) { return $source; }
    public static function authorize_override($plugin, $fingerprint, $nonce, $confirmed) { return false; }
    public static function run_pair_update($upgrader, $fingerprint, $nonce, $confirmed) {
        return new WP_Error('iu_elementor_flow_retired', 'Η ειδική διαδικασία ενημέρωσης έχει αποσυρθεί. Χρησιμοποιήστε τις ενημερώσεις του WordPress.');
    }
    public static function override_update() { self::retired_update(); }
    public static function pair_update() { self::retired_update(); }
    public static function retired_update() {
        wp_die('ISTODATA Kit: η ειδική διαδικασία ενημέρωσης έχει αποσυρθεί. Χρησιμοποιήστε τις ενημερώσεις του WordPress.', '', array('response' => 410));
    }

    public static function pair_label($pair) {
        $labels = array();
        foreach (array('core', 'pro') as $key) {
            $version = $pair[$key] ?? null;
            $labels[$key] = $version === null ? 'απόν' :
                (is_string($version) && strlen($version) < 40 && iu_elementor_version_matches($version, array('exact' => $version)) ? $version : 'άγνωστη');
        }
        return 'Elementor ' . $labels['core'] . ' / Pro ' . $labels['pro'];
    }

    private static function labels($features) {
        $registry = iu_elementor_compatibility_registry();
        return implode(', ', array_map(static function ($feature) use ($registry) { return $registry[$feature]['label']; }, $features));
    }

    private static function available($plugin) {
        $updates = get_site_transient('update_plugins');
        return $updates->response[$plugin] ?? null;
    }

    /** Current site's loaded Elementor context only, never an inferred network total. */
    private static function advisory_features() {
        if (iu_elementor_runtime_pair()['core'] === null) return array();
        return array_keys(array_filter(iu_elementor_active_features()));
    }

    private static function can_view_advice() {
        return current_user_can('update_plugins') && (!is_multisite() ||
            (is_network_admin() && current_user_can('manage_network_plugins')));
    }

    /**
     * update-core.php has no per-plugin row action. Use its native upgrade_notice
     * display field, only on the get_plugin_updates() read by list_plugin_updates().
     * Other reads (including wp_update_plugins read/write cycles) stay untouched.
     * Clone both levels: never decorate cached objects or change updater inputs.
     */
    public static function updates_screen_advice($updates) {
        static $rendering = false;
        if ($rendering || !self::can_view_advice() || !function_exists('get_current_screen')) return $updates;
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'update-core' || !is_object($updates) ||
            !isset($updates->response) || !is_array($updates->response)) return $updates;
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        $native_list = false;
        foreach ($frames as $i => $frame) {
            if (($frame['function'] ?? '') === 'get_plugin_updates' && empty($frame['class']) &&
                ($frames[$i + 1]['function'] ?? '') === 'list_plugin_updates' && empty($frames[$i + 1]['class'])) {
                $native_list = true;
                break;
            }
        }
        if (!$native_list) return $updates;
        $rendering = true;
        try {
            $display = clone $updates;
            foreach (array_keys(self::plugins()) as $plugin) {
                $response = $updates->response[$plugin] ?? null;
                $advice = self::advisory($plugin, $response);
                if (!is_object($response) || !$advice) continue;
                $display->response[$plugin] = clone $response;
                $vendor = is_string($response->upgrade_notice ?? null) ? $response->upgrade_notice : '';
                // Core strips tags from this field; escape our plain text before
                // output, retaining any existing vendor notice verbatim.
                $display->response[$plugin]->upgrade_notice = ($vendor === '' ? '' : $vendor . ' — ') .
                    esc_html(self::advisory_text($advice));
            }
            return $display;
        } finally {
            $rendering = false;
        }
    }

    /** Accepted release declarations only; tested evidence cannot authorize a target. */
    public static function advisory($plugin, $response) {
        if (!isset(self::plugins()[$plugin])) return null;
        $features = self::advisory_features();
        if (!$features) return null;
        $installed = self::installed_pair();
        $pair = $installed;
        $version = is_object($response) ? ($response->new_version ?? '') : '';
        $pair[self::plugins()[$plugin]] = $version;
        $failed = iu_elementor_pair_failures($pair, array_fill_keys($features, true));
        $supported = array_values(array_diff($features, $failed));
        $state = 'unverified'; $destination = null;
        $known = iu_elementor_version_matches($version, array('exact' => $version));
        if (!$failed) {
            $state = 'verified';
        } elseif ($known) {
            foreach (self::plugins() as $other => $key) {
                if ($other === $plugin || $installed[$key] === null) continue;
                $item = self::available($other);
                $target = is_object($item) ? ($item->new_version ?? '') : '';
                if (!iu_elementor_version_matches($target, array('exact' => $target)) ||
                    !version_compare($target, $installed[$key], '>') ||
                    !is_string($item->package ?? null) || !preg_match('#^https://[^\s]+$#iD', $item->package)) continue;
                $joint = $pair; $joint[$key] = $target;
                if (!iu_elementor_pair_failures($joint, array_fill_keys($features, true))) {
                    $state = 'needs_both'; $destination = $joint;
                }
            }
        }
        return array('state' => $state, 'pair' => $pair, 'destination' => $destination,
            'supported' => $supported, 'affected' => $failed, 'metadata_known' => $known,
            'site_id' => is_multisite() ? get_current_blog_id() : null);
    }

    public static function advisory_text($advice) {
        $pair = self::pair_label($advice['pair']);
        $prefix = 'ISTODATA Kit: ' . ($advice['site_id'] === null ? '' :
            'ιστότοπος #' . (int) $advice['site_id'] . ' (όχι συνολικός έλεγχος δικτύου) — ');
        if ($advice['state'] === 'verified') {
            return $prefix . $pair . ' — επαληθευμένη συμβατότητα για τις ενεργές λειτουργίες: ' .
                self::labels($advice['supported']) . ', εντός των δηλωμένων ορίων τους.';
        }
        $text = $advice['state'] === 'needs_both' ?
            $prefix . 'για τις ενεργές λειτουργίες απαιτείται και η διαθέσιμη ενημέρωση του άλλου plugin: ' . self::pair_label($advice['destination']) . '. Το ενδιάμεσο ' . $pair . ' δεν είναι επαληθευμένο.' :
            $prefix . $pair . ' — μη επαληθευμένη συμβατότητα' . ($advice['metadata_known'] ? '.' : ' (λείπουν έγκυρα στοιχεία στόχου).');
        $text .= ($advice['state'] === 'needs_both' ? ' Στο ενδιάμεσο παρακάμπτονται: ' :
            ' Μετά την ενημέρωση παρακάμπτονται: ') . self::labels($advice['affected']) .
            ($advice['state'] === 'needs_both' ? '. Επανέρχονται στον επαληθευμένο τελικό συνδυασμό.' : ', μέχρι συμβατή έκδοση Kit.');
        if ($advice['supported']) $text .= ' Επαληθευμένο για: ' . self::labels($advice['supported']) . '.';
        return $text;
    }

    public static function update_message($plugin, $response) {
        if (!self::can_view_advice()) return;
        $advice = self::advisory($plugin, $response);
        // WordPress invokes this action inside its existing <p>; do not nest
        // paragraphs (that also repeats the native update icon on empty lines).
        if ($advice) echo '<br><span class="iu-elementor-compatibility-advice">' . esc_html(self::advisory_text($advice)) . ' ' . self::action_link() . '</span>';
    }

    public static function page_url() {
        return is_multisite() ? network_admin_url('settings.php?page=iu-elementor-compatibility') :
            admin_url('tools.php?page=iu-elementor-compatibility');
    }
    private static function action_link() {
        return '<a href="' . esc_url(self::page_url()) . '">Συμβατότητα Elementor / Pro</a>';
    }
    public static function menu() {
        if (is_network_admin()) {
            add_submenu_page('settings.php', 'Συμβατότητα Elementor / Pro', 'Συμβατότητα Elementor / Pro',
                'manage_network_plugins', 'iu-elementor-compatibility', array(__CLASS__, 'page'));
            remove_submenu_page('settings.php', 'iu-elementor-compatibility');
        } elseif (!is_multisite()) {
            add_management_page('Συμβατότητα Elementor / Pro', 'Συμβατότητα Elementor / Pro',
                'manage_options', 'iu-elementor-compatibility', array(__CLASS__, 'page'));
            remove_submenu_page('tools.php', 'iu-elementor-compatibility');
        }
    }

    public static function notices() {
        if (self::$notice_printed || (!current_user_can('manage_options') && !current_user_can('manage_network_plugins'))) return;
        $pair = iu_elementor_runtime_pair();
        $failed = iu_elementor_pair_failures($pair, iu_elementor_active_features());
        if (!$failed) return;
        self::$notice_printed = true;
        echo '<div class="notice notice-warning"><p>' . esc_html('ISTODATA Kit: στο ' . self::pair_label($pair) .
            ' παρακάμπτονται οι ενεργές λειτουργίες: ' . self::labels($failed) . '. Οι ρυθμίσεις διατηρούνται και οι λειτουργίες επανέρχονται όταν υποστηρίζονται οι εκδόσεις.') .
            ' ' . self::action_link() . '</p></div>';
    }

    public static function page() {
        if (!current_user_can(is_multisite() ? 'manage_network_plugins' : 'manage_options')) return;
        echo '<div class="wrap"><h1>Συμβατότητα Elementor / Pro</h1><p>' . esc_html(self::pair_label(self::installed_pair())) . '</p>';
        echo '<p>Οι ενημερώσεις γίνονται κανονικά από το WordPress. Η παρακάτω κατάσταση αφορά μόνο τις ευαίσθητες λειτουργίες του Kit· δεν αποτελεί γενική εγγύηση για τον ιστότοπο ή άλλα plugins. Οι αποθηκευμένες ρυθμίσεις διατηρούνται.</p>';
        $pair = iu_elementor_runtime_pair();
        if (self::installed_pair()['pro'] !== null && $pair['pro'] === null) {
            echo '<p>Το Elementor Pro είναι ανενεργό· εφαρμόζονται μόνο οι τεκμηριωμένες λειτουργίες χωρίς Pro.</p>';
        }
        foreach (iu_elementor_compatibility_registry() as $feature => $entry) {
            echo '<p>' . esc_html($entry['label'] . ': ' . (iu_elementor_feature_supported($feature, $pair) ?
                'επαληθευμένη συμβατότητα εντός των δηλωμένων ορίων.' : 'παράκαμψη εφόσον ενεργοποιηθεί, μέχρι συμβατή έκδοση Kit.')) . '</p>';
        }
        echo '<details><summary>Δηλώσεις και στοιχεία συμβατότητας</summary>';
        foreach (array_keys(iu_elementor_compatibility_registry()) as $feature) echo '<p>' . esc_html(iu_elementor_compatibility_description($feature)) . '</p>';
        echo '</details></div>';
    }
}
