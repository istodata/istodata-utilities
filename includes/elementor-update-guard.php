<?php
/** WordPress updater gate. Available updates remain visible. */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/elementor-compatibility.php';

final class IU_Elementor_Update_Guard {
    private static $override = null;

    public static function plugins() {
        return array('elementor/elementor.php' => 'core', 'elementor-pro/elementor-pro.php' => 'pro');
    }

    public static function boot() {
        add_filter('auto_update_plugin', array(__CLASS__, 'auto_update'), PHP_INT_MAX, 2);
        add_filter('upgrader_pre_download', array(__CLASS__, 'pre_download'), PHP_INT_MAX, 4);
        // Check the actual extracted version before old files are moved/deleted, including ZIP replacement.
        add_filter('upgrader_source_selection', array(__CLASS__, 'source_selection'), PHP_INT_MAX, 4);
        add_action('admin_notices', array(__CLASS__, 'notices'));
        add_action('network_admin_notices', array(__CLASS__, 'notices'));
        foreach (array_keys(self::plugins()) as $plugin) {
            add_action('in_plugin_update_message-' . $plugin, static function ($data, $response) use ($plugin) {
                self::update_message($plugin, $response);
            }, 10, 2);
        }
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('network_admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_iu_elementor_update_override', array(__CLASS__, 'override_update'));
    }

    public static function installed_pair() {
        $pair = array('core' => null, 'pro' => null);
        foreach (self::plugins() as $plugin => $key) {
            $file = WP_PLUGIN_DIR . '/' . $plugin;
            if (is_file($file)) {
                // Constants/get_plugins() can remain stale after the first plugin in a bulk update.
                $data = get_file_data($file, array('Version' => 'Version'), 'plugin');
                $pair[$key] = $data['Version'];
            }
        }
        return $pair;
    }

    public static function active_sites() {
        if (!is_multisite()) return array(get_current_blog_id() => iu_elementor_active_features());
        $active = array();
        $offset = 0;
        $kit = plugin_basename(IU_PLUGIN_PATH . 'istodata-utilities.php');
        do {
            // Shared plugin files affect every network, not only the current blog/network.
            $sites = get_sites(array('number' => 200, 'offset' => $offset, 'deleted' => 0));
            foreach ($sites as $site) {
                $network_plugins = get_network_option($site->site_id, 'active_sitewide_plugins', array());
                if (!isset($network_plugins[$kit]) && !in_array($kit, get_blog_option($site->blog_id, 'active_plugins', array()), true)) continue;
                switch_to_blog($site->blog_id);
                try { $active[$site->blog_id] = iu_elementor_active_features(); }
                finally { restore_current_blog(); }
            }
            $offset += 200;
        } while (count($sites) === 200);
        return $active;
    }

    public static function decision($plugin, $version) {
        if (!isset(self::plugins()[$plugin])) return array('blocked' => false, 'pair' => array(), 'failures' => array());
        $pair = self::installed_pair();
        // An unknown update target is not an absent Pro installation (the core-only rule).
        $pair[self::plugins()[$plugin]] = $version ?? '';
        $failures = array();
        foreach (self::active_sites() as $site => $active) {
            $failed = iu_elementor_pair_failures($pair, $active);
            if ($failed) $failures[$site] = $failed;
        }
        return array('blocked' => !empty($failures), 'pair' => $pair, 'failures' => $failures);
    }

    private static function reason($decision) {
        $parts = array('ISTODATA Kit: η ενημέρωση μπλοκάρεται για τον συνδυασμό Elementor ' .
            ($decision['pair']['core'] ?? 'απόν') . ' / Pro ' . ($decision['pair']['pro'] ?? 'απόν') . '.');
        foreach ($decision['failures'] as $site => $features) {
            $parts[] = 'Site #' . $site . ': ' . implode(' ', array_map('iu_elementor_compatibility_description', $features));
        }
        $parts[] = 'Απενεργοποιήστε την ασύμβατη λειτουργία ή χρησιμοποιήστε ρητή παράκαμψη για αυτή την ενημέρωση. Κάθε ενδιάμεσο core/Pro βήμα ελέγχεται χωριστά.';
        return implode(' ', $parts);
    }

    private static function available($plugin) {
        $updates = get_site_transient('update_plugins');
        return isset($updates->response[$plugin]) ? $updates->response[$plugin] : null;
    }

    public static function auto_update($allow, $item) {
        if (!is_object($item) || !isset(self::plugins()[$item->plugin ?? ''])) return $allow;
        return self::decision($item->plugin, $item->new_version ?? null)['blocked'] ? false : $allow;
    }

    public static function pre_download($reply, $package, $upgrader, $extra) {
        // Plugin_Upgrader::bulk_upgrade() (also used by AJAX) supplies only
        // plugin/temp_backup here; type/action are added at process_complete.
        if (is_wp_error($reply) || (isset($extra['type']) && $extra['type'] !== 'plugin') ||
            (isset($extra['action']) && $extra['action'] !== 'update')) return $reply;
        $plugin = $extra['plugin'] ?? '';
        if (!isset(self::plugins()[$plugin])) return $reply;
        $item = self::available($plugin);
        if (self::$override !== null && self::$override['plugin'] === $plugin &&
            (!$item || !self::override_matches($plugin, $item->new_version ?? null, $package))) {
            return new WP_Error('iu_elementor_override_changed', 'ISTODATA Kit: το πακέτο ή ο συνδυασμός εκδόσεων άλλαξε μετά την έγκριση.');
        }
        // No trusted target metadata: the source gate checks the actual ZIP instead.
        if (!$item || ($item->package ?? '') !== $package) return $reply;
        $decision = self::decision($plugin, $item->new_version ?? null);
        if ($decision['blocked'] && !self::override_matches($plugin, $item->new_version ?? null, $package)) {
            return new WP_Error('iu_elementor_incompatible', self::reason($decision));
        }
        return $reply;
    }

    public static function source_selection($source, $remote_source, $upgrader, $extra) {
        if (is_wp_error($source) || (isset($extra['type']) && $extra['type'] !== 'plugin') ||
            (!isset($extra['type']) && !isset(self::plugins()[$extra['plugin'] ?? '']))) return $source;
        global $wp_filesystem;
        $plugin = $extra['plugin'] ?? '';
        $version = null;
        // WP_Filesystem also covers FTP/SSH transports; no direct local read assumption here.
        foreach (self::plugins() as $candidate => $key) {
            if ($plugin && $plugin !== $candidate) continue;
            $file = rtrim($source, '/\\') . '/' . basename($candidate);
            if (!$wp_filesystem->exists($file)) continue;
            $contents = $wp_filesystem->get_contents($file);
            if (is_string($contents) && preg_match('/^[ \t\/*#@]*Version:[ \t]*([^\r\n]+)/mi', substr($contents, 0, 8192), $matches)) {
                $version = trim($matches[1], " \t*/");
            }
            $plugin = $candidate;
            break;
        }
        if (!isset(self::plugins()[$plugin])) return $source;
        $decision = self::decision($plugin, $version);
        // Never authorize a different ZIP version, even when both versions are supported.
        if (self::$override !== null && (self::$override['plugin'] !== $plugin || self::$override['version'] !== $version)) {
            return new WP_Error('iu_elementor_override_changed', 'ISTODATA Kit: το πακέτο διαφέρει από τη συγκεκριμένη εγκεκριμένη ενημέρωση.');
        }
        if ($decision['blocked'] && !self::override_matches($plugin, $version, self::$override['package'] ?? '')) {
            return new WP_Error('iu_elementor_incompatible', self::reason($decision));
        }
        return $source;
    }

    public static function can_override() {
        return current_user_can('update_plugins') && current_user_can('manage_options') &&
            (!is_multisite() || (is_super_admin() && current_user_can('manage_network_plugins')));
    }

    public static function fingerprint($plugin, $item) {
        return hash('sha256', wp_json_encode(array($plugin, $item->new_version ?? null,
            $item->package ?? null, self::installed_pair())));
    }

    /** Request-local authorization only; no option/transient/query-string bypass. */
    public static function authorize_override($plugin, $fingerprint, $nonce, $confirmed) {
        self::$override = null;
        if (!self::can_override() || !$confirmed) return false;
        $item = self::available($plugin);
        if (!$item || !isset(self::plugins()[$plugin]) || empty($item->package) ||
            !isset($item->new_version) || !is_string($item->new_version) || $item->new_version === '' ||
            !is_string($fingerprint) || !hash_equals(self::fingerprint($plugin, $item), $fingerprint) ||
            !wp_verify_nonce($nonce, 'iu_elementor_override_' . $fingerprint)) return false;
        self::$override = array('plugin' => $plugin, 'version' => $item->new_version,
            'package' => $item->package, 'installed' => self::installed_pair());
        return true;
    }

    private static function override_matches($plugin, $version, $package) {
        return self::$override !== null && self::can_override() &&
            !(function_exists('wp_doing_cron') && wp_doing_cron()) &&
            self::$override['plugin'] === $plugin && self::$override['version'] === $version &&
            self::$override['package'] === $package && self::$override['installed'] === self::installed_pair();
    }

    public static function override_update() {
        $plugin = isset($_POST['plugin']) && is_string($_POST['plugin']) ? wp_unslash($_POST['plugin']) : '';
        $fingerprint = isset($_POST['fingerprint']) && is_string($_POST['fingerprint']) ? wp_unslash($_POST['fingerprint']) : '';
        $nonce = isset($_POST['_wpnonce']) && is_string($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !self::authorize_override($plugin, $fingerprint, $nonce, ($_POST['confirm'] ?? '') === 'yes')) {
            wp_die('ISTODATA Kit: μη εξουσιοδοτημένη, ληγμένη ή αλλαγμένη ενημέρωση.', '', array('response' => 403));
        }
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $skin = self::override_skin($plugin, $fingerprint);
        $upgrader = new Plugin_Upgrader($skin);
        iframe_header('ISTODATA Kit: ρητή παράκαμψη ενημέρωσης');
        try { $upgrader->upgrade($plugin); }
        finally { self::$override = null; }
        iframe_footer();
        exit;
    }

    public static function override_skin($plugin, $fingerprint) {
        return new class(array('title' => 'ISTODATA Kit: ρητή παράκαμψη ενημέρωσης',
            'url' => admin_url('admin-post.php'), 'nonce' => 'iu_elementor_override_' . $fingerprint,
            'plugin' => $plugin)) extends Plugin_Upgrader_Skin {
            public function request_filesystem_credentials($error = false, $context = '', $allow_relaxed_file_ownership = false) {
                // Preserve the exact POST authorization if WordPress needs an FTP/SSH credentials round-trip.
                // The next request rechecks capabilities, nonce, package and installed-pair fingerprint.
                return request_filesystem_credentials($this->options['url'], '', $error,
                    $context ?: $this->options['context'],
                    array('action', 'plugin', 'fingerprint', 'confirm', '_wpnonce'), $allow_relaxed_file_ownership);
            }
        };
    }

    private static function page_url() {
        return is_multisite() ? network_admin_url('settings.php?page=iu-elementor-compatibility') :
            admin_url('tools.php?page=iu-elementor-compatibility');
    }

    public static function menu() {
        if (is_network_admin()) {
            add_submenu_page('settings.php', 'ISTODATA Elementor Compatibility', 'ISTODATA Elementor Compatibility',
                'manage_network_plugins', 'iu-elementor-compatibility', array(__CLASS__, 'page'));
        } elseif (!is_multisite()) {
            add_management_page('ISTODATA Elementor Compatibility', 'ISTODATA Elementor Compatibility',
                'manage_options', 'iu-elementor-compatibility', array(__CLASS__, 'page'));
        }
    }

    public static function update_message($plugin, $response) {
        if (!isset(self::plugins()[$plugin])) return;
        $decision = self::decision($plugin, $response->new_version ?? null);
        if ($decision['blocked']) echo '<p>' . esc_html(self::reason($decision)) . ' <a href="' . esc_url(self::page_url()) . '">Συμβατότητα / ρητή παράκαμψη</a></p>';
    }

    public static function notices() {
        if (!current_user_can('manage_options') && !current_user_can('manage_network_plugins')) return;
        $active = iu_elementor_active_features();
        $failed = iu_elementor_pair_failures(iu_elementor_runtime_pair(), $active);
        foreach ($failed as $feature) {
            // Atomic owns its existing local notice; network admin needs this notice too.
            if ($feature === 'atomic' && !is_network_admin()) continue;
            echo '<div class="notice notice-warning"><p>' . esc_html('ISTODATA Kit: η ενεργή λειτουργία παρακάμπτεται στις εγκατεστημένες εκδόσεις. ' .
                iu_elementor_compatibility_description($feature)) . '</p></div>';
        }
        foreach (self::plugins() as $plugin => $key) {
            $item = self::available($plugin);
            if (!$item) continue;
            $decision = self::decision($plugin, $item->new_version ?? null);
            if ($decision['blocked']) echo '<div class="notice notice-warning"><p>' . esc_html(self::reason($decision)) .
                ' <a href="' . esc_url(self::page_url()) . '">Συμβατότητα / ρητή παράκαμψη</a></p></div>';
        }
    }

    public static function page() {
        if (!current_user_can(is_multisite() ? 'manage_network_plugins' : 'manage_options')) return;
        echo '<div class="wrap"><h1>ISTODATA Elementor Compatibility</h1>';
        foreach (array_keys(iu_elementor_compatibility_registry()) as $feature) {
            echo '<p>' . esc_html(iu_elementor_compatibility_description($feature)) . '</p>';
        }
        echo '<p>' . esc_html(iu_elementor_common_pair(array('atomic', 'fragment')) !== null ? 'Υπάρχει κοινός αποδεκτός συνδυασμός.' :
            'Δεν υπάρχει κοινός αποδεκτός συνδυασμός για ταυτόχρονη χρήση Atomic και Fragment Cache.') . '</p>';
        foreach (self::active_sites() as $site => $active) {
            echo '<p>' . esc_html('Site #' . $site . ': Atomic ' . ($active['atomic'] ? 'ON' : 'OFF') .
                ', Fragment Cache ' . ($active['fragment'] ? 'ON' : 'OFF')) . '</p>';
        }
        echo '<p>Κάθε core/Pro ενημέρωση ελέγχεται με την τρέχουσα έκδοση του άλλου plugin. Αν δεν επιτρέπεται ενδιάμεσο βήμα, απενεργοποιήστε τη λειτουργία ή εγκρίνετε χωριστά την παράκαμψη. Η παράκαμψη αφορά μία ενημέρωση σε αυτή την αίτηση· η ασύμβατη λειτουργία παρακάμπτεται μετά την εγκατάσταση. Δεν προστατεύεται αντικατάσταση μέσω SSH ή updater που δεν εκτελεί τα WordPress hooks.</p>';
        foreach (self::plugins() as $plugin => $key) {
            $item = self::available($plugin);
            if (!$item) continue;
            $decision = self::decision($plugin, $item->new_version ?? null);
            echo '<p>' . esc_html($plugin . ' → ' . ($item->new_version ?? '?') . ': ' .
                ($decision['blocked'] ? self::reason($decision) : 'Δεν περιορίζεται από το Kit.')) . '</p>';
            if (!$decision['blocked'] || !self::can_override() || empty($item->package)) continue;
            $fingerprint = self::fingerprint($plugin, $item);
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="iu_elementor_update_override">';
            echo '<input type="hidden" name="plugin" value="' . esc_attr($plugin) . '">';
            echo '<input type="hidden" name="fingerprint" value="' . esc_attr($fingerprint) . '">';
            wp_nonce_field('iu_elementor_override_' . $fingerprint);
            echo '<label><input type="checkbox" name="confirm" value="yes" required> Εγκρίνω αυτή τη συγκεκριμένη ενημέρωση και την παράκαμψη της ασύμβατης λειτουργίας.</label>';
            submit_button('Παράκαμψη και ενημέρωση ' . $plugin, 'secondary');
            echo '</form>';
        }
        echo '</div>';
    }
}
