<?php
/** Optional, early Elementor widget fragment cache. */
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/elementor-compatibility.php';
require_once __DIR__ . '/elementor-fragment-cache-graph.php';
require_once __DIR__ . '/elementor-fragment-cache-atomic.php';
require_once __DIR__ . '/elementor-fragment-cache-excerpt.php';
require_once __DIR__ . '/elementor-fragment-cache-diagnostics.php';

final class IU_Elementor_Fragment_Cache {
    const FORMAT = 17;
    const STALE_GRACE = 180;
    const MAX_BYTES = 2097152;
    const INDEX_PREFIX = 'iu_fragment_entry_';
    const EPOCH = 'iu_elementor_fragment_epoch';
    const COLD_WAIT_BUDGET = 1.0;
    private static $capture = array();
    private static $policies = array();
    private static $request_failure = '';
    private static $owned_locks = array();
    private static $wait_spent = 0.0;
    private static $wait_budget = null;
    private static $waiting = false;

    public static function boot() {
        if (did_action('elementor/init')) {
            self::controls();
        } else {
            add_action('elementor/init', array(__CLASS__, 'controls'));
        }
        add_action('elementor/widgets/register', array(__CLASS__, 'proxy'));
        // Device Visibility registers first at this priority and prunes hidden nodes.
        add_filter('elementor/frontend/builder_content_data', array(__CLASS__, 'filter'), PHP_INT_MAX, 2);
        add_action('elementor/frontend/widget/before_render', array(__CLASS__, 'begin'), 0);
        add_action('elementor/frontend/widget/after_render', array(__CLASS__, 'finish'), PHP_INT_MAX);
        add_action('elementor/frontend/before_render', array(__CLASS__, 'begin_atomic'), 0);
        add_action('elementor/frontend/after_render', array(__CLASS__, 'finish_atomic'), PHP_INT_MAX);
        add_filter('posts_results', array(__CLASS__, 'atomic_posts'), PHP_INT_MAX, 2);
        add_action('shutdown', array(__CLASS__, 'cleanup_locks'), 0);
        add_action('elementor/query/query_results', array(__CLASS__, 'query_results'), 0, 2);
        add_action('pre_get_posts', array(__CLASS__, 'guard_queries'), PHP_INT_MAX);
        add_action('updated_post_meta', array(__CLASS__, 'meta_changed'), 10, 4);
        add_action('added_post_meta', array(__CLASS__, 'meta_changed'), 10, 4);
        add_action('deleted_post_meta', array(__CLASS__, 'meta_changed'), 10, 4);
        add_action('update_option_istodata_utilities_settings', array(__CLASS__, 'settings_changed'), 10, 2);
        foreach (array('after_switch_theme', 'upgrader_process_complete', 'update_option_permalink_structure',
            'update_option_home', 'update_option_siteurl', 'update_option_elementor_active_kit',
            'update_option_icl_sitepress_settings', 'wp_update_nav_menu', 'wp_update_nav_menu_item', 'deleted_nav_menu') as $hook) {
            add_action($hook, array(__CLASS__, 'invalidate'), 10, 0);
        }
        add_action('before_delete_post', array(__CLASS__, 'menu_item_deleted'), 10, 2);
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_post_iu_fragment_purge', array(__CLASS__, 'purge'));
        add_action('admin_bar_menu', array(__CLASS__, 'admin_bar'), 100);
        add_action('admin_post_iu_fragment_purge_all', array(__CLASS__, 'purge_all'));
        add_action('wp_footer', array(__CLASS__, 'purge_feedback'));
        add_action('admin_notices', array(__CLASS__, 'purge_feedback'));
    }

    public static function controls() {
        foreach (array('elementor/element/common/section_effects/after_section_end',
            'elementor/element/common/section_advanced/after_section_end') as $hook) {
            add_action($hook, array(__CLASS__, 'add_controls'), 40);
        }
    }

    public static function add_controls($element) {
        if (!iu_elementor_fragment_enabled() || !method_exists($element, 'get_controls') || !method_exists($element, 'get_type') ||
            $element->get_type() !== 'widget' || isset($element->get_controls()['iu_fragment_cache'])) {
            return;
        }
        $element->start_controls_section('iu_fragment_cache_section', array(
            'label' => __('Advanced Element Cache', 'istodata-utilities'),
            'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
        ));
        $element->add_control('iu_fragment_cache', array(
            'label' => __('Cache this element', 'istodata-utilities'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes', 'default' => '',
            'description' => __('Εφαρμόζει τις ρυθμίσεις ορατότητας συσκευών. Οι παράμετροι URL χρησιμοποιούν την ίδια cache. Μην ενεργοποιείτε για στοιχεία που αλλάζουν ανά σελίδα, επισκέπτη ή παραμέτρους URL.', 'istodata-utilities'),
        ));
        $element->add_control('iu_fragment_cache_ttl', array(
            'label' => __('Διάρκεια', 'istodata-utilities'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => array('3600' => '1 ώρα', '21600' => '6 ώρες', '86400' => '24 ώρες', '604800' => '7 ημέρες'),
            'default' => '604800', 'condition' => array('iu_fragment_cache' => 'yes'),
        ));
        IU_Elementor_Fragment_Diagnostics::add_control($element);
        $element->end_controls_section();
    }

    public static function proxy($manager) {
        if (!self::versions_supported() || !class_exists('Elementor\\Widget_Base')) {
            return;
        }
        // Elementor may load Widget_Base after elementor/loaded. Instantiate
        // only at widget registration, when the parent is available.
        $manager->register(new class extends \Elementor\Widget_Base {
            public function get_name() { return 'iu-fragment-proxy'; }
            public function get_title() { return 'ISTODATA Fragment Proxy'; }
            public function get_icon() { return 'eicon-code'; }
            public function get_categories() { return array('general'); }
            public function show_in_panel() { return false; }
            public function print_element() {
                IU_Elementor_Fragment_Cache::output($this->get_data('_iu_fragment_payload'));
            }
        });
    }

    private static function request_ok() {
        self::$request_failure = '';
        if (!iu_elementor_fragment_enabled()) return self::reject_request('global-off');
        if (!self::versions_supported()) return self::reject_request('incompatible-elementor');
        // Elementor's zero-argument preview check uses the current post. Nested
        // templates can change that post while the preview request stays active.
        // Keep this explicit mode check even before Elementor initializes.
        if (isset($_GET['elementor-preview'])) return self::reject_request('editor-preview');
        $plugin = class_exists('Elementor\\Plugin') ? \Elementor\Plugin::$instance : null;
        if ($plugin && (($plugin->editor && $plugin->editor->is_edit_mode()) ||
            ($plugin->preview && $plugin->preview->is_preview_mode()))) {
            return self::reject_request('editor-preview');
        }
        $query_module = 'ElementorPro\\Modules\\QueryControl\\Module';
        if (class_exists($query_module) && (!is_callable(array($query_module, 'get_avoid_list_ids')) ||
            !is_callable(array($query_module, 'add_to_avoid_list')) ||
            !is_array($query_module::get_avoid_list_ids()))) {
            return self::reject_request('query-replay-api');
        }
        $cookies = $_COOKIE;
        if (isset($cookies['wp-wpml_current_language']) && defined('ICL_LANGUAGE_CODE') &&
            is_string($cookies['wp-wpml_current_language']) &&
            hash_equals((string) ICL_LANGUAGE_CODE, (string) $cookies['wp-wpml_current_language'])) {
            unset($cookies['wp-wpml_current_language']);
        }
        if (isset($cookies['wp-wpml_current_language'])) return self::reject_request('language-context');
        if (!empty($_SESSION)) return self::reject_request('session-context');
        if (!self::cookies_safe($cookies)) return self::reject_request('private-cookies');
        if (is_admin() || !empty($_POST) ||
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' ||
            !empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['PHP_AUTH_USER']) ||
            empty($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['REQUEST_URI']) ||
            (defined('REST_REQUEST') && REST_REQUEST) ||
            (defined('DOING_AJAX') && DOING_AJAX) ||
            (defined('WP_CLI') && WP_CLI) || is_preview() ||
            (function_exists('is_customize_preview') && is_customize_preview()) ||
            is_feed() ||
            (function_exists('iu_elementor_device_visibility_is_preview') && iu_elementor_device_visibility_is_preview())) {
            return self::reject_request('unsupported-request');
        }
        // Opt-in declares shared output regardless of ordinary URL parameters.
        // Actual request modes and privacy guards above remain authoritative.
        $other = get_option('elementor_element_cache_ttl');
        // In this renderer a missing option also enables Elementor's native
        // document cache. Only an explicit disable is safe for our proxy data.
        return $other === 'disable' ? true : self::reject_request('native-element-cache');
    }

    private static function reject_request($reason) { self::$request_failure = $reason; return false; }

    private static function cookies_safe($cookies) {
        $user = get_current_user_id();
        foreach (array('LOGGED_IN_COOKIE' => 'logged_in', 'AUTH_COOKIE' => 'auth', 'SECURE_AUTH_COOKIE' => 'secure_auth') as $constant => $scheme) {
            if (!defined($constant) || !isset($cookies[constant($constant)])) continue;
            $value = $cookies[constant($constant)];
            if (!$user || !is_string($value) || wp_validate_auth_cookie($value, $scheme) !== $user) return false;
            unset($cookies[constant($constant)]);
        }
        // These core cookies contain editor preferences, not frontend content.
        if ($user) {
            unset($cookies['wp-settings-' . $user], $cookies['wp-settings-time-' . $user]);
        }
        if (defined('TEST_COOKIE') && ($cookies[TEST_COOKIE] ?? null) === 'WP Cookie check') unset($cookies[TEST_COOKIE]);
        foreach (array_keys($cookies) as $name) {
            if (preg_match('/(?:session|phpsessid|sessid|token|auth|member|customer|personal|variant|wp-postpass|comment_author|woocommerce.*(?:cart|items)|wordpress_(?:logged_in|sec))/i', $name)) return false;
        }
        return true;
    }

    private static function versions_supported() {
        return iu_elementor_feature_supported('fragment');
    }

    private static function language() {
        if (defined('ICL_LANGUAGE_CODE')) {
            if ((defined('ICL_SITEPRESS_VERSION') || defined('WPML_PLUGIN_PATH')) &&
                apply_filters('wpml_current_language', null) !== ICL_LANGUAGE_CODE) return null;
            return ICL_LANGUAGE_CODE ?: null;
        }
        return (defined('ICL_SITEPRESS_VERSION') || defined('WPML_PLUGIN_PATH')) ? null : 'site:' . get_locale();
    }

    private static function eligible($node, $document_id) {
        IU_Elementor_Fragment_Graph::clear_rejection();
        unset(self::$policies[$document_id . ':' . ($node['id'] ?? '')]);
        $atomic = IU_Elementor_Fragment_Atomic::node($node);
        if ((!$atomic && (($node['elType'] ?? '') !== 'widget' || empty($node['widgetType']))) ||
            !IU_Elementor_Fragment_Atomic::opted_in($node) || empty($node['id']) ||
            !empty($node['settings']['__dynamic__'])) {
            return false;
        }
        // Opt-in declares reusable output. Inspect concrete structure and replay
        // requirements, not the site's arbitrary third-party hook implementations.
        $policy = $atomic ? IU_Elementor_Fragment_Atomic::inspect($node) : IU_Elementor_Fragment_Graph::inspect($node);
        if ($policy) self::$policies[$document_id . ':' . $node['id']] = $policy;
        $accepted = (bool) $policy;
        if ($atomic && !$accepted) return false;
        return (bool) apply_filters('iu_elementor_fragment_eligible', (bool) $accepted, $node, $document_id);
    }

    private static function hidden_on_device($settings) {
        if (!is_array($settings)) {
            return false;
        }
        $phone = self::phone();
        $kit_enabled = function_exists('iu_elementor_should_render_by_device');
        if ($phone) {
            return ($kit_enabled && ($settings['iu_hide_on_phone'] ?? '') === 'yes') ||
                ($settings['hide_mobile'] ?? '') === 'hidden-mobile';
        }
        if ($kit_enabled && ($settings['iu_hide_on_desktop_tablet'] ?? '') === 'yes') {
            return true;
        }
        return wp_is_mobile()
            ? ($settings['hide_tablet'] ?? '') === 'hidden-tablet'
            : ($settings['hide_desktop'] ?? '') === 'hidden-desktop';
    }

    private static function displayed() {
        $module = 'ElementorPro\\Modules\\QueryControl\\Module';
        return is_callable(array($module, 'get_avoid_list_ids')) ? $module::get_avoid_list_ids() : array();
    }

    private static function key($node, $document_id, $language, $prior) {
        $excerpt = IU_Elementor_Fragment_Excerpt::profile();
        if ($excerpt === null) return null;
        $extra = apply_filters('iu_elementor_fragment_key_context', array(), $node, $document_id);
        if (!is_array($extra)) {
            return null;
        }
        $independent = self::independent_query($node, $document_id);
        $context = array(self::FORMAT, (int) $document_id, $node,
            defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '',
            defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : '',
            (string) get_option(self::EPOCH, '0'),
            (string) get_option('iu_fragment_epoch_' . md5($document_id . ':' . $node['id']), '0'),
            strtolower($_SERVER['HTTP_HOST'] ?? ''), is_ssl(),
            function_exists('iu_elementor_should_render_by_device'),
            $language, self::device_variant(), $independent ? null : $prior, $extra,
            self::excerpt_structure($excerpt), self::media_context(),
            self::$policies[$document_id . ':' . $node['id']]['signature'] ?? null);
        $json = wp_json_encode($context);
        return is_string($json) ? 'iu_frag_' . hash('sha256', $json) : null;
    }

    private static function independent_query($node, $document_id) {
        return !empty(self::$policies[$document_id . ':' . ($node['id'] ?? '')]['independent_query']) ||
            (empty($node['elements']) && in_array($node['widgetType'] ?? '', array('heading', 'image', 'button', 'icon', 'divider', 'spacer'), true));
    }

    private static function excerpt_structure($profile) {
        if ($profile === null) return null;
        return array_map(function ($entry) {
            unset($entry['parent'], $entry['length']);
            return $entry;
        }, $profile);
    }

    private static function media_context() {
        global $wp_query;
        if (!function_exists('wp_increase_content_media_count') || !function_exists('wp_high_priority_element_flag')) return null;
        return array(wp_increase_content_media_count(0), wp_high_priority_element_flag(),
            doing_filter('the_content'), doing_filter('widget_text_content'), doing_filter('widget_block_content'),
            in_the_loop(), is_main_query(), !empty($wp_query->before_loop),
            (bool) did_action('get_header'), (bool) did_action('get_footer'));
    }

    private static function device_variant() {
        $phone = self::phone();
        return $phone ? 'phone' : (wp_is_mobile() ? 'tablet' : 'desktop');
    }

    private static function phone() {
        if (function_exists('iu_request_is_phone')) return iu_request_is_phone();
        // Same phone/tablet semantics as Device Visibility, also when that
        // integration is off. Its saved visibility controls remain inactive.
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        foreach (array('ipad', 'tablet', 'kindle', 'silk', 'playbook', 'nexus 7', 'nexus 9', 'tab') as $tablet) {
            if (strpos($ua, $tablet) !== false) return false;
        }
        if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipod') !== false) return true;
        if (strpos($ua, 'android') !== false) return strpos($ua, 'mobile') !== false;
        if (strpos($ua, 'windows phone') !== false) return true;
        return strpos($ua, 'mobile') !== false;
    }

    public static function filter($nodes, $document_id) {
        if (!iu_elementor_fragment_enabled()) {
            return $nodes;
        }
        $language = self::language();
        $request_ok = self::request_ok() && self::builder_tail_safe();
        do_action('iu_elementor_fragment_gate', $language, $request_ok, $document_id, $nodes);
        if (self::$capture && is_array($nodes)) {
            $interactions = class_exists('Elementor\\Modules\\Interactions\\Cache\\Interactions_Postmeta')
                ? (new \Elementor\Modules\Interactions\Cache\Interactions_Postmeta())->load_content($document_id) : array();
            if ($interactions || self::contains_interactions($nodes)) {
                foreach (self::$capture as &$frame) $frame['unsafe_interactions'] = true;
                unset($frame);
            }
        }
        if (!$language || !$request_ok) IU_Elementor_Fragment_Diagnostics::request_bypass($nodes, $document_id,
            !$language ? 'language-context' : (self::$request_failure ?: 'builder-order'));
        return is_array($nodes) && $language && $request_ok
            ? self::walk($nodes, (int) $document_id, $language) : $nodes;
    }

    private static function builder_tail_safe() {
        // All earlier callbacks still execute on a hit. Their filtered node is
        // part of the key. A later callback would receive a different widget
        // type/shape on a hit, so conservatively bypass the entire document.
        $after = false;
        foreach (($GLOBALS['wp_filter']['elementor/frontend/builder_content_data']->callbacks ?? array()) as $group) {
            foreach ($group as $callback) {
                if ($callback['function'] === array(__CLASS__, 'filter')) $after = true;
                elseif ($after) return false;
            }
        }
        return true;
    }

    private static function walk($nodes, $document_id, $language) {
        foreach ($nodes as $index => &$node) {
            if (!is_array($node)) {
                continue;
            }
            // A repeated builder pass must not inherit a previous reservation.
            // The old token remains tracked for cleanup, never for a second writer.
            unset($node['_iu_fragment_build']);
            // An opted-in descendant cannot execute beneath a device-hidden
            // ancestor, including native visibility and older Kit integrations.
            // Branches with no opt-in retain their existing renderer behavior.
            $visibility = $node['settings'] ?? array();
            if (IU_Elementor_Fragment_Atomic::node($node)) {
                foreach (array('iu_hide_on_phone','iu_hide_on_desktop_tablet') as $control) {
                    if (IU_Elementor_Fragment_Atomic::value($visibility,$control) === true) $visibility[$control] = 'yes';
                }
            }
            if (self::hidden_on_device($visibility) && self::contains_optin($node)) {
                unset($nodes[$index]);
                continue;
            }
            $eligible = self::eligible($node, $document_id);
            if (IU_Elementor_Fragment_Atomic::opted_in($node)) {
                do_action('iu_elementor_fragment_node', $node['id'] ?? '', $eligible, $document_id);
                if (!$eligible) IU_Elementor_Fragment_Diagnostics::record($document_id, $node['id'] ?? '', 'bypass',
                    (IU_Elementor_Fragment_Atomic::node($node) ? IU_Elementor_Fragment_Atomic::rejection() : IU_Elementor_Fragment_Graph::rejection()) ?: array('reason' => 'unsupported-element'));
            }
            if ($eligible) {
                $prior = self::displayed();
                $key = self::key($node, $document_id, $language, $prior);
                do_action('iu_elementor_fragment_before_substitution', $node, $document_id, $key);
                if ($key) {
                    $entry = get_transient($key);
                    if (self::valid_entry($entry) && $entry['fresh_until'] > time()) {
                        $node = self::replacement($node, $entry, $prior);
                        continue;
                    }
                    $lock = self::lock($key);
                    if ($lock) {
                        $ttl = (int) (IU_Elementor_Fragment_Atomic::node($node) ? IU_Elementor_Fragment_Atomic::value($node['settings'],'iu_fragment_cache_ttl',604800) : ($node['settings']['iu_fragment_cache_ttl'] ?? 604800));
                        $node['_iu_fragment_build'] = array('key' => $key, 'lock' => $lock,
                            'ttl' => in_array($ttl, array(3600, 21600, 86400, 604800), true) ? $ttl : 604800,
                            'document_id' => $document_id, 'element_id' => $node['id'], 'prior' => $prior,
                            'generation' => self::generation($document_id, $node['id']),
                            'post_context' => (int) get_the_ID(),
                            'independent_query' => self::independent_query($node, $document_id), 'media_prior' => self::media_context(),
                            'excerpt_prior' => IU_Elementor_Fragment_Excerpt::profile(),
                            'dependencies' => self::$policies[$document_id . ':' . $node['id']]['dependencies'] ?? array('styles' => array(), 'scripts' => array()));
                    } else {
                        if (self::valid_entry($entry)) {
                            $node = self::replacement($node, $entry, $prior, true);
                            continue;
                        }
                        $reason = '';
                        $entry = self::wait_for_entry($key, self::generation($document_id, $node['id']), $reason);
                        if (self::valid_entry($entry)) {
                            $node = self::replacement($node, $entry, $prior, $entry['fresh_until'] <= time());
                            continue;
                        }
                        IU_Elementor_Fragment_Diagnostics::record($document_id, $node['id'], 'bypass', array('reason' => $reason ?: 'concurrent-miss'));
                    }
                } else {
                    IU_Elementor_Fragment_Diagnostics::record($document_id, $node['id'], 'bypass', array('reason' => 'key-context'));
                }
            }
            if (isset($node['elements']) && is_array($node['elements'])) {
                $node['elements'] = self::walk($node['elements'], $document_id, $language);
            }
        }
        unset($node);
        return array_values($nodes);
    }

    private static function contains_optin($node) {
        if (!is_array($node)) return false;
        if (IU_Elementor_Fragment_Atomic::opted_in($node)) return true;
        foreach ((array) ($node['elements'] ?? array()) as $child) {
            if (self::contains_optin($child)) return true;
        }
        return false;
    }

    private static function contains_interactions($nodes) {
        foreach ($nodes as $node) {
            if (!empty($node['interactions']) || (!empty($node['elements']) && self::contains_interactions($node['elements']))) return true;
        }
        return false;
    }

    private static function fresh_entry($key) {
        if (wp_using_ext_object_cache()) {
            // Force a shared-cache read rather than a request-local negative
            // value. These entries have their own timestamp validity checks.
            return wp_cache_get($key, 'transient', true);
        }
        // Core's in-request option/notoptions caches otherwise keep a cold
        // miss invisible after another PHP worker publishes its transient.
        $names = array('_transient_' . $key, '_transient_timeout_' . $key);
        $negative = wp_cache_get('notoptions', 'options');
        if (is_array($negative)) {
            foreach ($names as $name) unset($negative[$name]);
            wp_cache_set('notoptions', $negative, 'options');
        }
        foreach ($names as $name) wp_cache_delete($name, 'options');
        return get_transient($key);
    }

    private static function lock($key) {
        $name = self::lock_name($key);
        if (isset(self::$owned_locks[$name])) return null;
        $token = time() . ':' . wp_generate_uuid4();
        if (add_option($name, $token, '', false)) {
            return self::$owned_locks[$name] = array($name, $token);
        }
        // Read the shared owner, not a request-local option cached before release.
        $old = self::lock_owner($name);
        if ($old === null || (is_string($old) && (int) $old <= time() - 120)) {
            global $wpdb;
            if ($old !== null) $wpdb->delete($wpdb->options, array('option_name' => $name, 'option_value' => $old), array('%s', '%s'));
            wp_cache_delete($name, 'options');
            wp_cache_delete('notoptions', 'options');
            if (add_option($name, $token, '', false)) {
                return self::$owned_locks[$name] = array($name, $token);
            }
        }
        return null;
    }

    private static function unlock($lock) {
        global $wpdb;
        // A stale builder must not remove a newer owner's lock.
        $wpdb->delete($wpdb->options, array('option_name' => $lock[0], 'option_value' => $lock[1]), array('%s', '%s'));
        wp_cache_delete($lock[0], 'options');
        if ((self::$owned_locks[$lock[0]] ?? null) === $lock) unset(self::$owned_locks[$lock[0]]);
    }

    private static function lock_name($key) {
        return 'iu_frag_lock_' . substr(hash('sha256', $key), 0, 32);
    }

    private static function lock_owner($name) {
        global $wpdb;
        $owner = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name));
        return !empty($wpdb->last_error) ? false : $owner;
    }

    public static function cleanup_locks() {
        // Best effort: shutdown cannot recover a worker killed by the OS. The
        // existing lease remains its recovery bound. Never publish during cleanup.
        foreach (self::$owned_locks as $lock) {
            try { self::unlock($lock); } catch (\Throwable $error) {
                // A failed cleanup leaves the existing logical lease to recover.
            }
        }
    }

    private static function wait_clock() {
        return function_exists('hrtime') ? hrtime(true) / 1000000000 : microtime(true);
    }

    private static function wait_for_entry($key, $generation, &$reason) {
        $name = self::lock_name($key);
        if (isset(self::$owned_locks[$name])) { $reason = 'self-lock'; return null; }
        // A holder must not wait on another holder; this also avoids reservation
        // cycles before render. Reentrant polling callbacks must not wait either.
        if (self::$owned_locks || self::$waiting) { $reason = 'owned-locks'; return null; }
        $start = self::wait_clock();
        self::$waiting = true;
        try {
            if (self::$wait_budget === null) {
                // Preserve the existing lowering filter, with a hard request-wide
                // ceiling. A zero budget performs no sleep and no polling.
                self::$wait_budget = max(0, min(self::COLD_WAIT_BUDGET,
                    (float) apply_filters('iu_elementor_fragment_cold_wait_seconds', self::COLD_WAIT_BUDGET)));
            }
            if (self::$wait_spent >= self::$wait_budget) { $reason = 'wait-budget'; return null; }
            $owner = self::lock_owner($name);
            while (true) {
                if (self::$wait_spent + self::wait_clock() - $start >= self::$wait_budget) { $reason = 'wait-budget'; return null; }
                if (self::$owned_locks) { $reason = 'owned-locks'; return null; }
                if (!self::generation_current($generation)) { $reason = 'generation-changed'; return null; }
                $entry = self::fresh_entry($key);
                if (self::valid_entry($entry)) { $reason = 'published'; return $entry; }
                $current = self::lock_owner($name);
                if (!is_string($owner) || $current !== $owner || (int) $owner <= time() - 120) {
                    // Publication can race the preceding read and then unlock.
                    // One last fresh read catches it, without waiting for a new owner.
                    $entry = self::fresh_entry($key);
                    if (self::valid_entry($entry) && self::generation_current($generation)) { $reason = 'published'; return $entry; }
                    $reason = $current === false ? 'lock-unknown' : ($current === null ? 'owner-released' : ($current !== $owner ? 'owner-replaced' : 'lock-expired'));
                    return null;
                }
                $remaining = self::$wait_budget - self::$wait_spent - (self::wait_clock() - $start);
                if ($remaining <= 0) { $reason = 'wait-budget'; return null; }
                usleep((int) min(50000, $remaining * 1000000));
            }
        } finally {
            $elapsed = max(0, self::wait_clock() - $start);
            self::$wait_spent += $elapsed;
            self::$waiting = false;
            do_action('iu_elementor_fragment_wait', $key, $reason, $elapsed, self::$wait_spent);
        }
    }

    private static function replacement($node, $entry, $prior, $stale = false) {
        do_action('iu_elementor_fragment_cache_selected', $node['id'], $stale);
        $original = $node;
        $node['elType'] = 'widget';
        $node['widgetType'] = 'iu-fragment-proxy';
        $node['_iu_fragment_payload'] = array('node' => $original, 'entry' => $entry,
            'prior' => $prior, 'excerpt_prior' => IU_Elementor_Fragment_Excerpt::profile(),
            'independent_query' => !empty($entry['independent_query']), 'media_prior' => self::media_context(),
            'post_context' => (int) get_the_ID(), 'stale' => $stale);
        return $node;
    }

    public static function output($payload) {
        if (!is_array($payload) || !is_array($payload['node'] ?? null)) {
            return;
        }
        // Replay at the original render position, never during builder traversal:
        // earlier siblings must not see IDs from a widget they have not reached.
        if (iu_elementor_fragment_enabled() && self::public_ids(array_merge($payload['entry']['displayed'], $payload['entry']['atomic_public_ids'] ?? array())) &&
            self::generation_current($payload['entry']['generation'] ?? null) &&
            (!isset($payload['media_prior']) || self::media_context() === $payload['media_prior']) &&
            (($payload['independent_query'] ?? false) || self::displayed() === $payload['prior']) &&
            self::excerpt_context_ok($payload['entry']) &&
            (int) get_the_ID() === $payload['post_context'] && self::loop_css_ready($payload['entry']) && self::replay($payload['entry'])) {
            echo self::loop_css_html($payload['entry']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            do_action('iu_elementor_fragment_result', !empty($payload['stale']) ? 'stale-hit' : 'hit',
                array('element_id' => $payload['node']['id'], 'document_id' => $payload['entry']['generation']['document_id'] ?? 0));
            return;
        }
        do_action('iu_elementor_fragment_result', 'replay-context',
            array('element_id' => $payload['node']['id'], 'document_id' => $payload['entry']['generation']['document_id'] ?? 0));
        $original = \Elementor\Plugin::$instance->elements_manager->create_element_instance($payload['node']);
        if ($original) {
            $original->print_element();
        }
    }

    private static function excerpt_context_ok($entry) {
        $live = IU_Elementor_Fragment_Excerpt::profile();
        // No-effect fragments preserve earlier siblings' live filters. Mutating
        // fragments match their actual render-slot input, not builder-time input.
        return $live !== null && (!empty($entry['preserve_excerpt']) ||
            (isset($entry['excerpt_before']) && self::excerpt_structure($live) === self::excerpt_structure($entry['excerpt_before'])));
    }

    private static function valid_entry($entry) {
        if (!is_array($entry) || ($entry['format'] ?? null) !== self::FORMAT ||
            !is_string($entry['html'] ?? null) ||
            !is_array($entry['styles'] ?? null) || !is_array($entry['scripts'] ?? null) ||
            !is_array($entry['displayed'] ?? null) || !is_array($entry['excerpt'] ?? null) || !is_int($entry['fresh_until'] ?? null) ||
            !is_int($entry['stale_until'] ?? null) || $entry['stale_until'] <= time()) {
            return false;
        }
        if (isset($entry['generation']['atomic']) && !is_array($entry['atomic_public_ids'] ?? null)) return false;
        return true;
    }

    private static function public_ids($ids) {
        foreach ($ids as $id) {
            $post = get_post($id);
            if (!$post || $post->post_status !== 'publish' || !empty($post->post_password)) return false;
        }
        return true;
    }

    private static function shared_markup_safe($html) {
        return !preg_match('~(?:nonce|wc-cart-fragments|wp-admin|wp-login|elementor-edit-(?:area|mode|link)|e-loop-first-edit|data-(?:user|session|token)|current_user|user_login|user_email)~i', $html);
    }

    private static function generation($document_id, $element_id) {
        $generation = array('document_id' => $document_id, 'element_id' => $element_id,
            'site' => (string) get_option(self::EPOCH, '0'),
            'element' => (string) get_option('iu_fragment_epoch_' . md5($document_id . ':' . $element_id), '0'));
        if (!empty(self::$policies[$document_id . ':' . $element_id]['atomic'])) {
            $generation['atomic'] = (string) get_option(IU_Elementor_Fragment_Atomic::EPOCH,'0');
        }
        return $generation;
    }

    private static function generation_current($generation) {
        if (!is_array($generation) || !isset($generation['document_id'], $generation['element_id'], $generation['site'], $generation['element'])) return false;
        global $wpdb;
        // Read the database, not this long request's stale options cache.
        $epochs = array(self::EPOCH => $generation['site'],
            'iu_fragment_epoch_' . md5($generation['document_id'] . ':' . $generation['element_id']) => $generation['element']);
        if (isset($generation['atomic'])) $epochs[IU_Elementor_Fragment_Atomic::EPOCH] = $generation['atomic'];
        foreach ($epochs as $name => $expected) {
            $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name));
            if (!empty($wpdb->last_error)) return false;
            if ((string) ($value === null ? '0' : $value) !== $expected) return false;
        }
        return true;
    }

    private static function replay($entry) {
        if (!self::valid_entry($entry)) {
            return false;
        }
        $module = 'ElementorPro\\Modules\\QueryControl\\Module';
        if ($entry['displayed'] && !class_exists($module)) {
            return false;
        }
        foreach ($entry['styles'] as $handle) {
            if (!is_string($handle) || !preg_match('/^[a-zA-Z0-9_-]+$/', $handle) ||
                (!preg_match('/^elementor-post-[0-9]+$/', $handle) && !wp_style_is($handle, 'registered'))) {
                return false;
            }
        }
        foreach ($entry['scripts'] as $handle) {
            if (!is_string($handle) || !preg_match('/^[a-zA-Z0-9_-]+$/', $handle) ||
                !wp_script_is($handle, 'registered')) {
                return false;
            }
        }
        foreach ($entry['styles'] as $handle) {
            if (preg_match('/^elementor-post-([0-9]+)$/', $handle, $m)) {
                if (!class_exists('Elementor\\Core\\Files\\CSS\\Post')) {
                    return false;
                }
                \Elementor\Core\Files\CSS\Post::create((int) $m[1])->enqueue();
            } else {
                wp_enqueue_style($handle);
            }
        }
        foreach ($entry['scripts'] as $handle) {
            wp_enqueue_script($handle);
        }
        if (empty($entry['preserve_excerpt']) && !IU_Elementor_Fragment_Excerpt::replay($entry['excerpt'])) return false;
        if ($entry['displayed']) {
            // A cached descendant inside an outer capture still contributes
            // its complete query IDs to that outer fragment's replay ledger.
            self::query_results((object) array('posts' => $entry['displayed']), null);
            $module::add_to_avoid_list($entry['displayed']);
        }
        return true;
    }

    private static function extras($dependencies, $keys) {
        $result = array();
        foreach ($dependencies->registered as $handle => $registered) {
            foreach ($keys as $key) {
                $value = $dependencies->get_data($handle, $key);
                if ($value) {
                    $result[$handle][$key] = $value;
                }
            }
        }
        return $result;
    }

    private static function render_hooks() {
        $snapshot = array();
        // Deferred output callbacks cannot be reconstructed from cached HTML.
        // Document/control initialization also registers hooks during native
        // rendering; comparing the entire registry confuses those with output
        // that would actually be lost at the end of this frontend request.
        foreach (array('wp_head', 'wp_footer', 'wp_enqueue_scripts', 'wp_print_styles',
            'wp_print_scripts', 'wp_print_footer_scripts') as $hook) {
            $object = $GLOBALS['wp_filter'][$hook] ?? null;
            foreach ($object->callbacks ?? array() as $priority => $callbacks) {
                foreach ($callbacks as $id => $callback) $snapshot[$hook][$priority][$id] = $callback;
            }
        }
        return $snapshot;
    }

    public static function begin($element) {
        if ($element instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base ||
            $element instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base) {
            foreach (self::$capture as &$frame) {
                if (!isset($frame['build']['generation']['atomic'])) continue;
                foreach (array('styles'=>'get_style_depends','scripts'=>'get_script_depends') as $kind=>$method) {
                    $items = $element->$method();
                    if (!is_array($items)) { $frame['unsafe_dependencies'] = true; continue; }
                    foreach ($items as $item) {
                        if (!is_string($item) || !preg_match('/^[a-zA-Z0-9_-]+$/',$item)) {
                            $frame['unsafe_dependencies'] = true;
                            continue;
                        }
                        $frame['dependencies'][$kind][] = $item;
                    }
                }
            }
            unset($frame);
        }
        $build = $element->get_data('_iu_fragment_build');
        if (!is_array($build) || empty($build['key'])) {
            return;
        }
        // Elementor enqueues root dependencies before before_render. Queue
        // deltas alone would miss them on a hit that skips the root widget.
        $dependencies = apply_filters('iu_elementor_fragment_dependencies', array(
            'styles' => array_values(array_unique(array_merge($element->get_style_depends(), $build['dependencies']['styles'] ?? array()))),
            'scripts' => array_values(array_unique(array_merge($element->get_script_depends(), $build['dependencies']['scripts'] ?? array())))), $element);
        self::$capture[] = array('id' => $element->get_id(), 'build' => $build, 'dependencies' => $dependencies,
            'styles' => wp_styles()->queue, 'scripts' => wp_scripts()->queue,
            'registered_styles' => array_keys(wp_styles()->registered), 'registered_scripts' => array_keys(wp_scripts()->registered),
            'render_hooks' => self::render_hooks(),
            'loop_css_before' => self::loop_css_state(),
            'style_extra' => self::extras(wp_styles(), array('after')),
            'script_extra' => self::extras(wp_scripts(), array('before', 'after', 'data')),
            'displayed' => self::displayed(), 'post_context' => (int) get_the_ID(),
            'excerpt_prior' => IU_Elementor_Fragment_Excerpt::profile(),
            'media_prior' => self::media_context(), 'query_ids' => array(),
            'buffer_level' => ob_get_level());
        ob_start();
    }

    public static function begin_atomic($element) {
        if ($element instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base) self::begin($element);
    }

    public static function finish_atomic($element) {
        if ($element instanceof \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base) self::finish($element);
    }

    public static function atomic_posts($posts, $query) {
        foreach (self::$capture as &$frame) {
            if (!isset($frame['build']['generation']['atomic'])) continue;
            foreach ($posts as $post) if (isset($post->ID)) $frame['query_ids'][] = (int) $post->ID;
        }
        unset($frame);
        return $posts;
    }

    public static function query_results($query, $widget) {
        self::guard_queries($query);
        foreach (self::$capture as &$frame) {
            if (empty($frame['build']['independent_query'])) continue;
            foreach ((array) $query->posts as $post) {
                $id = is_object($post) ? ($post->ID ?? 0) : $post;
                if (is_numeric($id) && (int) $id > 0) $frame['query_ids'][] = (int) $id;
            }
        }
        unset($frame);
    }

    public static function guard_queries($query) {
        if (!self::$capture || !is_object($query)) return;
        $vars = $query->query_vars ?? array();
        if ((is_callable(array($query, 'is_search')) && $query->is_search()) || !empty($vars['s']) ||
            isset($vars['_is_includes']) || isset($vars['_is_settings']) || isset($vars['_is_excludes']) ||
            !empty($vars['perm']) || !empty($vars['post_password']) || !empty($vars['has_password']) ||
            (!empty($vars['post_status']) && array_diff((array) $vars['post_status'], array('publish', 'inherit')))) {
            foreach (self::$capture as &$frame) $frame['unsafe_query'] = true;
            unset($frame);
        }
    }

    public static function finish($element) {
        if (!self::$capture || end(self::$capture)['id'] !== $element->get_id()) {
            return;
        }
        $start = array_pop(self::$capture);
        if (ob_get_level() !== $start['buffer_level'] + 1) {
            self::unlock($start['build']['lock']);
            do_action('iu_elementor_fragment_result', 'buffer-side-effect', $start['build']);
            return;
        }
        $html = ob_get_clean();
        $loop_css = self::loop_css_manifest($start['loop_css_before'], self::loop_css_state(), $html);
        $styles = array_values(array_diff(wp_styles()->queue, $start['styles']));
        $scripts = array_values(array_diff(wp_scripts()->queue, $start['scripts']));
        $reason = '';
        $excerpt = IU_Elementor_Fragment_Excerpt::profile();
        if (!is_array($start['dependencies']) || !is_array($start['dependencies']['styles'] ?? null) ||
            !is_array($start['dependencies']['scripts'] ?? null)) {
            $reason = 'dependency-manifest';
        } elseif (!is_string($html) || strlen($html) > self::MAX_BYTES) {
            $reason = 'html-size';
        } elseif ((empty($start['build']['independent_query']) && $start['displayed'] !== $start['build']['prior']) ||
            $start['post_context'] !== $start['build']['post_context']) {
            $reason = 'intervening-query';
        } elseif ($excerpt === null || $start['excerpt_prior'] === null) {
            $reason = 'excerpt-side-effect';
        } elseif (isset($start['build']['media_prior']) && ($start['media_prior'] !== $start['build']['media_prior'] ||
            self::media_context() !== $start['media_prior'])) {
            $reason = 'media-context-side-effect';
        } elseif (!self::public_ids(array_values(array_unique($start['query_ids']))) || !self::shared_markup_safe($html)) {
            $reason = 'volatile-markup';
        } elseif (self::extras(wp_styles(), array('after')) !== $start['style_extra']) {
            $reason = 'inline-style-side-effect';
        } elseif (self::extras(wp_scripts(), array('before', 'after', 'data')) !== $start['script_extra']) {
            $reason = 'inline-script-side-effect';
        } elseif (array_slice(array_values(wp_styles()->queue), 0, count($start['styles'])) !== array_values($start['styles']) ||
            array_slice(array_values(wp_scripts()->queue), 0, count($start['scripts'])) !== array_values($start['scripts'])) {
            $reason = 'asset-queue-side-effect';
        }
        global $wpdb;
        if (!$reason && $loop_css === null) $reason = 'loop-css-side-effect';
        if (!$reason && !empty($start['unsafe_query'])) $reason = 'query-context';
        if (!$reason && !empty($start['unsafe_dependencies'])) $reason = 'dependency-manifest';
        if (!$reason && !empty($start['unsafe_interactions'])) $reason = 'atomic-interactions';
        if (!$reason && self::render_hooks() !== $start['render_hooks']) $reason = 'render-hook-side-effect';
        if (!$reason && (int) get_the_ID() !== $start['post_context']) $reason = 'post-context-side-effect';
        if (!$reason) {
            foreach (array_unique(array_merge($start['dependencies']['styles'], $styles)) as $handle) {
                if (!in_array($handle, $start['registered_styles'], true) && !preg_match('/^elementor-post-[0-9]+$/', $handle)) $reason = 'asset-registration-side-effect';
            }
            foreach (array_unique(array_merge($start['dependencies']['scripts'], $scripts)) as $handle) {
                if (!in_array($handle, $start['registered_scripts'], true)) $reason = 'asset-registration-side-effect';
            }
        }
        if (!$reason && !empty($_SESSION)) $reason = 'session-context';
        if (!$reason && IU_Elementor_Fragment_Excerpt::externals($excerpt) !== IU_Elementor_Fragment_Excerpt::externals($start['excerpt_prior'])) $reason = 'excerpt-side-effect';
        if (!$reason && (!iu_elementor_fragment_enabled() || !self::generation_current($start['build']['generation']))) $reason = 'generation-changed';
        $owner = !$reason ? $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $start['build']['lock'][0])) : null;
        if (!$reason && ($owner !== $start['build']['lock'][1] ||
            (int) $start['build']['lock'][1] <= time() - 120)) {
            $reason = 'lock-expired';
        }
        if (!$reason) {
            $styles = self::ordered_assets(wp_styles()->queue, array_merge($start['dependencies']['styles'], $styles));
            $scripts = self::ordered_assets(wp_scripts()->queue, array_merge($start['dependencies']['scripts'], $scripts));
            $entry = array('format' => self::FORMAT, 'html' => $html, 'styles' => $styles,
                'loop_css' => $loop_css,
                'generation' => $start['build']['generation'],
                'scripts' => $scripts,
                'excerpt' => $excerpt,
                'excerpt_before' => $start['excerpt_prior'],
                'preserve_excerpt' => $excerpt === $start['excerpt_prior'],
                'independent_query' => !empty($start['build']['independent_query']),
                'fresh_until' => time() + $start['build']['ttl'],
                'stale_until' => time() + $start['build']['ttl'] + self::STALE_GRACE,
                'displayed' => !empty($start['build']['independent_query']) ? array_values(array_unique($start['query_ids'])) :
                    array_values(array_diff(self::displayed(), $start['displayed'])));
            if (isset($start['build']['generation']['atomic'])) {
                // Atomic's native query does not update the Classic avoid list.
                $entry['atomic_public_ids'] = array_values(array_unique($start['query_ids']));
                $entry['displayed'] = array_values(array_diff(self::displayed(), $start['displayed']));
            }
            if (set_transient($start['build']['key'], $entry, $start['build']['ttl'] + self::STALE_GRACE)) {
                self::index($start['build']);
            } else {
                $reason = 'store-failed';
            }
        }
        do_action('iu_elementor_fragment_result', $reason ?: 'stored', $start['build']);
        self::unlock($start['build']['lock']);
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private static function index($build) {
        add_option(self::EPOCH, '0', '', false);
        $name = self::INDEX_PREFIX . md5($build['document_id'] . ':' . $build['element_id']);
        add_option($name, array($build['document_id'], $build['element_id']), '', false);
    }

    public static function meta_changed($meta_id, $object_id, $meta_key, $meta_value) {
        // Elementor source/config changes invalidate immediately. Public content
        // freshness is the selected TTL + manual purge, not every runtime write.
        if (in_array($meta_key, array('_elementor_data', '_elementor_page_settings', '_elementor_template_type'), true)) {
            self::invalidate();
        }
    }

    public static function menu_item_deleted($post_id, $post) {
        if (is_object($post) && $post->post_type === 'nav_menu_item') self::invalidate();
    }

    public static function invalidate() {
        // Default-off sites do not acquire an epoch until their first stored fragment.
        if (get_option(self::EPOCH, false) !== false) {
            update_option(self::EPOCH, wp_generate_uuid4(), false);
        }
    }

    public static function settings_changed($old, $new) {
        if (!empty($old['optimizations']['elementor_fragment_cache']) !== !empty($new['optimizations']['elementor_fragment_cache'])) {
            // Also fences a first writer when the switch changes mid-request.
            update_option(self::EPOCH, wp_generate_uuid4(), false);
        }
    }

    public static function admin_menu() {
        add_management_page('Advanced Elements Cache', 'Advanced Elements Cache', 'manage_options',
            'iu-elementor-fragments', array(__CLASS__, 'admin_page'));
    }

    public static function admin_bar($bar) {
        if (!current_user_can('manage_options')) return;
        $return = is_admin() ? admin_url('tools.php?page=iu-elementor-fragments') : home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        $bar->add_node(array('id' => 'iu-fragment-purge-all',
            'title' => __('Εκκαθάριση Elements Cache', 'istodata-utilities'),
            'href' => wp_nonce_url(add_query_arg(array('action' => 'iu_fragment_purge_all', 'return_to' => $return),
                admin_url('admin-post.php')), 'iu_fragment_purge_all')));
    }

    public static function purge_all() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Δεν επιτρέπεται.', 'istodata-utilities'), '', array('response' => 403));
        check_admin_referer('iu_fragment_purge_all');
        // Always advance the site generation, including while the first writer is in flight.
        update_option(self::EPOCH, wp_generate_uuid4(), false);
        $return = isset($_GET['return_to']) && is_string($_GET['return_to'])
            ? wp_validate_redirect(wp_unslash($_GET['return_to']), home_url('/')) : home_url('/');
        $feedback = wp_create_nonce('iu_fragment_purged_' . get_option(self::EPOCH));
        wp_safe_redirect(add_query_arg('iu_fragment_purged', $feedback, $return));
        exit;
    }

    public static function purge_feedback() {
        if (!current_user_can('manage_options') || !isset($_GET['iu_fragment_purged']) ||
            !is_string($_GET['iu_fragment_purged']) || !wp_verify_nonce(wp_unslash($_GET['iu_fragment_purged']),
                'iu_fragment_purged_' . get_option(self::EPOCH))) return;
        $class = is_admin() ? 'notice notice-success' : 'iu-fragment-purge-feedback';
        echo '<div class="' . esc_attr($class) . '" role="status"><p>' .
            esc_html__('Η Fragment Cache εκκαθαρίστηκε. Τα fragments θα δημιουργηθούν στην επόμενη πραγματική αίτηση.', 'istodata-utilities') . '</p></div>';
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap"><h1>Advanced Elements Cache</h1><p>' .
            esc_html__('Η εκκαθάριση ανανεώνει το fragment στην επόμενη αίτηση. Εκκαθαρίστε χωριστά τυχόν full-page/CDN cache.', 'istodata-utilities') . '</p>';
        echo '<p>' . esc_html(iu_elementor_compatibility_description('fragment')) . '</p>';
        IU_Elementor_Fragment_Diagnostics::admin_panel();
        global $wpdb;
        $pattern = $wpdb->esc_like(self::INDEX_PREFIX) . '%';
        $names = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
            $pattern
        ));
        if (is_array($names)) {
            foreach ($names as $name) {
                $item = get_option($name);
                if (!is_array($item) || count($item) !== 2) {
                    continue;
                }
                $document_id = (int) $item[0];
                $element_id = sanitize_key($item[1]);
                if (!$document_id || !$element_id || !get_post($document_id)) {
                    continue;
                }
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                echo '<input type="hidden" name="action" value="iu_fragment_purge">';
                echo '<input type="hidden" name="document_id" value="' . esc_attr($document_id) . '">';
                echo '<input type="hidden" name="element_id" value="' . esc_attr($element_id) . '">';
                wp_nonce_field('iu_fragment_purge_' . $document_id . '_' . $element_id);
                echo '<p>' . esc_html(get_the_title($document_id)) . ' #' . esc_html($document_id) . ' / ' . esc_html($element_id) . ' ';
                submit_button(__('Εκκαθάριση', 'istodata-utilities'), 'secondary', 'submit', false);
                echo '</p></form>';
            }
        }
        echo '</div>';
    }

    private static function loop_css_state() {
        $class = 'ElementorPro\\Modules\\LoopBuilder\\Files\\Css\\Loop';
        if (!class_exists($class)) return array();
        // Read the exact supported Pro contract; never write its private state.
        try {
            if (!is_callable(array($class, 'create')) || !method_exists($class, 'print_all_css')) return null;
            $property = new \ReflectionProperty($class, 'printed_with_css');
            if (!$property->isStatic()) return null;
            if (PHP_VERSION_ID < 80100) $property->setAccessible(true);
            $state = $property->getValue();
            if (!is_array($state)) return null;
            foreach ($state as $handle => $value) {
                if (!is_string($handle) || !preg_match('/^loop-[1-9][0-9]*$/', $handle) || $value !== true) return null;
            }
            return array_keys($state);
        } catch (\Throwable $error) {
            return null;
        }
    }

    private static function loop_css_manifest($before, $after, $html) {
        if (!is_array($before) || !is_array($after) || array_diff($before, $after)) return null;
        preg_match_all('~<style id="(loop-[1-9][0-9]*)">.*?</style>~s', $html, $matches);
        $printed = array_values(array_diff($after, $before));
        if (count($matches[1]) !== count(array_unique($matches[1])) ||
            array_diff($printed, $matches[1]) || array_diff($matches[1], $printed)) return null;
        return array('before' => $before, 'printed' => $printed);
    }

    private static function loop_css_ready($entry) {
        $manifest = $entry['loop_css'] ?? array('before' => array(), 'printed' => array());
        if (!is_array($manifest) || !is_array($manifest['before'] ?? null) || !is_array($manifest['printed'] ?? null)) return false;
        if (!$manifest['before'] && !$manifest['printed']) return true;
        $current = self::loop_css_state();
        return is_array($current) && !array_diff($manifest['before'], $current) &&
            self::loop_css_manifest($manifest['before'], array_merge($manifest['before'], $manifest['printed']), $entry['html']) === $manifest;
    }

    private static function loop_css_html($entry) {
        if (empty($entry['loop_css']['printed'])) return $entry['html'];
        // Native public CSS printer marks the style as printed for subsequent
        // uncached loops, or emits nothing if an earlier sibling already did.
        // Only cached style slots are replayed; no widget or loop render runs.
        return preg_replace_callback('~<style id="loop-([1-9][0-9]*)">.*?</style>~s', static function ($match) {
            $level = ob_get_level();
            ob_start();
            try {
                \ElementorPro\Modules\LoopBuilder\Files\Css\Loop::create((int) $match[1])->print_all_css((int) $match[1]);
                return ob_get_clean();
            } finally {
                while (ob_get_level() > $level) ob_end_clean();
            }
        }, $entry['html']);
    }

    private static function ordered_assets($queue, $required) {
        // Keep the native miss order, including root dependencies queued before
        // capture. Dependencies resolved indirectly by WordPress remain available
        // after the explicit queue; unrelated page assets are not captured.
        $required = array_values(array_unique($required));
        $ordered = array_values(array_intersect($queue, $required));
        return array_values(array_unique(array_merge($ordered, $required)));
    }

    public static function purge() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Δεν επιτρέπεται.', 'istodata-utilities'), '', array('response' => 403));
        }
        $document_id = absint($_POST['document_id'] ?? 0);
        $element_id = sanitize_key(wp_unslash($_POST['element_id'] ?? ''));
        check_admin_referer('iu_fragment_purge_' . $document_id . '_' . $element_id);
        if ($document_id && $element_id) {
            update_option('iu_fragment_epoch_' . md5($document_id . ':' . $element_id), wp_generate_uuid4(), false);
        }
        wp_safe_redirect(admin_url('tools.php?page=iu-elementor-fragments'));
        exit;
    }
}

IU_Elementor_Fragment_Cache::boot();
