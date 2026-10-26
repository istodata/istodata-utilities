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

    /** Accepted release declarations only; tested evidence cannot authorize a target. */
    public static function advisory($plugin, $response) {
        if (!isset(self::plugins()[$plugin])) return null;
        $installed = self::installed_pair();
        $pair = $installed;
        $version = is_object($response) ? ($response->new_version ?? '') : '';
        $pair[self::plugins()[$plugin]] = $version;
        $features = array_keys(iu_elementor_compatibility_registry());
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
            'supported' => $supported, 'affected' => $failed, 'metadata_known' => $known);
    }

    public static function advisory_text($advice) {
        $pair = self::pair_label($advice['pair']);
        if ($advice['state'] === 'verified') {
            return 'ISTODATA Kit: ' . $pair . ' — επαληθευμένη συμβατότητα με τις ευαίσθητες λειτουργίες του Kit, εντός των δηλωμένων ορίων τους.';
        }
        $text = $advice['state'] === 'needs_both' ?
            'ISTODATA Kit: για επαληθευμένη συμβατότητα απαιτείται και η διαθέσιμη ενημέρωση του άλλου plugin: ' . self::pair_label($advice['destination']) . '. Το ενδιάμεσο ' . $pair . ' δεν είναι επαληθευμένο.' :
            'ISTODATA Kit: ' . $pair . ' — μη επαληθευμένη συμβατότητα' . ($advice['metadata_known'] ? '.' : ' (λείπουν έγκυρα στοιχεία στόχου).');
        $text .= ($advice['state'] === 'needs_both' ? ' Στο ενδιάμεσο παρακάμπτονται, εφόσον είναι ενεργές: ' :
            ' Μετά την ενημέρωση παρακάμπτονται, εφόσον είναι ενεργές: ') . self::labels($advice['affected']) .
            ($advice['state'] === 'needs_both' ? '. Επανέρχονται στον επαληθευμένο τελικό συνδυασμό.' : ', μέχρι συμβατή έκδοση Kit.');
        if ($advice['supported']) $text .= ' Επαληθευμένο για: ' . self::labels($advice['supported']) . '.';
        return $text;
    }

    public static function update_message($plugin, $response) {
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
