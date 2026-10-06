<?php
/** Exercise the real updater callbacks with controlled WordPress/filesystem boundaries. */
define('ABSPATH', __DIR__ . '/');
define('IU_PLUGIN_PATH', dirname(__DIR__) . '/');
define('WP_PLUGIN_DIR', sys_get_temp_dir() . '/iu-update-test-' . getmypid());
mkdir(WP_PLUGIN_DIR);
foreach (array('elementor', 'elementor-pro') as $slug) mkdir(WP_PLUGIN_DIR . '/' . $slug);
register_shutdown_function(function () {
    foreach (array('elementor', 'elementor-pro') as $slug) {
        unlink(WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php');
        rmdir(WP_PLUGIN_DIR . '/' . $slug);
    }
    rmdir(WP_PLUGIN_DIR);
});
$hooks = array(); $site_id = 1; $multisite = false; $network_admin = false; $super = true; $cron = false;
$submenu = array(); $registered_screens = array();
$caps = array('update_plugins' => true, 'manage_options' => true, 'manage_network_plugins' => true);
$sites = array(); $site_options = array(); $network_plugins = array(); $stack = array();
$updates = (object) array('response' => array());
function add_filter($hook, $fn, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][] = array($fn, $priority, $args); }
function add_action($hook, $fn, $priority = 10, $args = 1) { add_filter($hook, $fn, $priority, $args); }
function is_multisite() { return $GLOBALS['multisite']; }
function is_network_admin() { return $GLOBALS['network_admin']; }
function add_submenu_page($parent, $title, $label, $cap, $slug, $callback) {
    $GLOBALS['submenu'][$parent][] = array($label, $cap, $slug);
    $GLOBALS['registered_screens'][$parent . '?page=' . $slug] = $callback;
}
function add_management_page($title, $label, $cap, $slug, $callback) {
    add_submenu_page('tools.php', $title, $label, $cap, $slug, $callback);
}
function remove_submenu_page($parent, $slug) {
    foreach ($GLOBALS['submenu'][$parent] ?? array() as $i => $item) {
        if ($item[2] === $slug) { unset($GLOBALS['submenu'][$parent][$i]); return $item; }
    }
    return false;
}
function is_super_admin() { return $GLOBALS['super']; }
function wp_doing_cron() { return $GLOBALS['cron']; }
function get_current_blog_id() { return $GLOBALS['site_id']; }
function current_user_can($cap) { return $GLOBALS['caps'][$cap] ?? false; }
function get_option($name, $default = false) { return $GLOBALS['site_options'][$GLOBALS['site_id']][$name] ?? $default; }
function get_site_transient($name) { return $GLOBALS['updates']; }
function wp_json_encode($data) { return json_encode($data); }
function wp_verify_nonce($nonce, $action) { return $nonce === hash('sha256', $action); }
function wp_nonce_field($action) { echo '<input name="_wpnonce" value="' . hash('sha256', $action) . '">'; }
function wp_unslash($value) { return stripslashes($value); }
function wp_die($message, $title = '', $args = array()) { throw new RuntimeException('Forbidden: ' . $message); }
function admin_url($path) { return 'https://example.test/wp-admin/' . $path; }
function network_admin_url($path) { return 'https://example.test/wp-admin/network/' . $path; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function submit_button($text) { echo '<button>' . esc_html($text) . '</button>'; }
class Plugin_Upgrader_Skin {
    public $options;
    public function __construct($options) { $this->options = $options + array('context' => false); }
}
function request_filesystem_credentials($url, $type, $error, $context, $fields, $relaxed) {
    $GLOBALS['credential_form'] = array($url, $fields);
    return false;
}
function plugin_basename($path) { return 'istodata-utilities/istodata-utilities.php'; }
function get_blog_option($id, $name, $default) { return $GLOBALS['site_options'][$id][$name] ?? $default; }
function get_network_option($id, $name, $default) { return $GLOBALS['network_plugins'][$id] ?? $default; }
function get_sites($args) { return array_slice($GLOBALS['sites'], $args['offset'], $args['number']); }
function switch_to_blog($id) { $GLOBALS['stack'][] = $GLOBALS['site_id']; $GLOBALS['site_id'] = $id; }
function restore_current_blog() { $GLOBALS['site_id'] = array_pop($GLOBALS['stack']); }
function get_file_data($file, $headers, $context) {
    preg_match('/Version: (.*)/', file_get_contents($file), $m);
    return array('Version' => trim($m[1]));
}
class WP_Error { public $code; public $message; public function __construct($code, $message) { $this->code = $code; $this->message = $message; } }
function is_wp_error($value) { return $value instanceof WP_Error; }
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
$wp_filesystem = new class {
    public $files = array();
    public function exists($file) { return isset($this->files[$file]); }
    public function get_contents($file) { return $this->files[$file]; }
};
require IU_PLUGIN_PATH . 'includes/elementor-update-guard.php';
IU_Elementor_Update_Guard::boot();
check(isset($hooks['auto_update_plugin'], $hooks['upgrader_pre_download'], $hooks['upgrader_source_selection']), 'All updater gates installed');
check(!isset($hooks['site_transient_update_plugins'], $hooks['pre_set_site_transient_update_plugins']), 'Never hide updates');
function installed($core, $pro) {
    file_put_contents(WP_PLUGIN_DIR . '/elementor/elementor.php', "<?php\n/*\nVersion: $core\n*/");
    file_put_contents(WP_PLUGIN_DIR . '/elementor-pro/elementor-pro.php', "<?php\n/*\nVersion: $pro\n*/");
}
function activation($atomic, $fragment, $id = 1) {
    $GLOBALS['site_options'][$id] = array('istodata_utilities_settings' => array('optimizations' => array(
        'elementor_atomic_interaction_breakpoints' => $atomic, 'elementor_fragment_cache' => $fragment)), 'elementor_element_cache_ttl' => 'disable',
        'active_plugins' => array('istodata-utilities/istodata-utilities.php'));

}
function update_item($plugin, $version) {
    $item = (object) array('plugin' => $plugin, 'new_version' => $version, 'package' => 'https://example.test/' . $plugin . '-' . $version . '.zip');
    $GLOBALS['updates']->response[$plugin] = $item;
    return $item;
}
function extracted($plugin, $version) {
    $GLOBALS['wp_filesystem']->files = array('/unpacked/' . basename($plugin) => "<?php\n/*\nPlugin Name: Elementor\nVersion: $version\n*/");
}
$matrix = array(
    array('4.3.3', '4.3.0', 'elementor-pro/elementor-pro.php', '4.3.1', true, true),
    array('4.3.2', '4.3.1', 'elementor/elementor.php', '4.3.3', true, true),
    array('4.3.3', '4.3.1', 'elementor/elementor.php', '4.3.4', true, true),
    array('4.3.4', '4.3.1', 'elementor/elementor.php', '4.3.5', false, false),
    array('4.3.3', '4.3.1', 'elementor-pro/elementor-pro.php', '4.3.2', false, false),
    // New patches used to pass the open-ended range; they now require a Kit compatibility release.
    array('4.3.2', '4.3.0', 'elementor/elementor.php', '4.3.3', false, false),
    array('4.3.2', '4.3.0', 'elementor-pro/elementor-pro.php', '4.3.1', false, false),
    array('4.3.1', '4.3.0', 'elementor/elementor.php', '4.3.2', true, false),
    array('4.3.2', '4.2.9', 'elementor-pro/elementor-pro.php', '4.3.0', true, false),
    array('4.2.3', '4.2.2', 'elementor/elementor.php', '4.2.3', false, true),
    array('4.2.3', '4.2.2', 'elementor-pro/elementor-pro.php', '4.2.2', false, true),
    array('4.2.3', '4.2.2', 'elementor/elementor.php', '4.3.2', false, false),
    array('4.2.3', '4.2.2', 'elementor-pro/elementor-pro.php', '4.3.0', false, false),
    array('4.3.2', '4.3.0', 'elementor/elementor.php', '4.4.0', false, false),
    array('4.3.2', '4.3.0', 'elementor-pro/elementor-pro.php', '4.4.0', false, false),
    array('4.3.2', '4.3.0', 'elementor/elementor.php', '4.3.3-beta1', false, false),
    array('4.3.2', '4.3.0', 'elementor/elementor.php', '', false, false),
    array('4.2.3', '4.2.2', 'elementor-pro/elementor-pro.php', null, false, false),
);
foreach ($matrix as [$core, $pro, $plugin, $target, $atomic_supported, $fragment_supported]) {
    installed($core, $pro);
    $item = update_item($plugin, $target);
    $extra = array('plugin' => $plugin, 'type' => 'plugin', 'action' => 'update');
    extracted($plugin, $target);
    foreach (array(false, true) as $atomic) foreach (array(false, true) as $fragment) {
        activation($atomic, $fragment);
        $blocked = ($atomic && !$atomic_supported) || ($fragment && !$fragment_supported);
        check(IU_Elementor_Update_Guard::decision($plugin, $target)['blocked'] === $blocked, 'Pair/activation decision');
        check(IU_Elementor_Update_Guard::auto_update(true, $item) === !$blocked, 'Automatic update boundary');
        check(IU_Elementor_Update_Guard::auto_update(false, $item) === false, 'Never enable an auto update');
        $result = IU_Elementor_Update_Guard::pre_download(false, $item->package, null, $extra);
        check(is_wp_error($result) === $blocked, 'Manual update boundary');
        $result = IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $extra);
        check(is_wp_error($result) === $blocked, 'Actual source update boundary');
        $bulk_extra = array('plugin' => $plugin, 'temp_backup' => array('slug' => dirname($plugin)));
        check(is_wp_error(IU_Elementor_Update_Guard::pre_download(false, $item->package, null, $bulk_extra)) === $blocked,
            'WordPress bulk/AJAX download metadata omits type/action');
        check(is_wp_error(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $bulk_extra)) === $blocked,
            'WordPress bulk/AJAX source metadata omits type/action');
        $result = IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, array('type' => 'plugin', 'action' => 'install'));
        check(is_wp_error($result) === $blocked, 'Uploaded ZIP replacement boundary');
    }
}
activation(false, false);
// Loaded module, empty/default settings and disabled widget do not count as active cache.
check(iu_elementor_active_features() === array('atomic' => false, 'fragment' => false), 'OFF/OFF defaults');
activation(false, true);
unset($site_options[1]['istodata_utilities_settings']['optimizations']['elementor_fragment_cache']);
check(!iu_elementor_active_features()['fragment'], 'Missing global switch is OFF despite saved opt-ins');
$site_options[1]['istodata_utilities_settings']['optimizations']['elementor_fragment_cache'] = false;
check(!iu_elementor_active_features()['fragment'], 'Global OFF preserves opt-ins without constraining updates');
installed('4.3.3', '4.3.1');
update_item('elementor/elementor.php', '4.4.0');
check(IU_Elementor_Update_Guard::auto_update(true, update_item('elementor/elementor.php', '4.4.0')) === true, 'Global OFF updater allows incompatible target');
activation(false, true);
$site_options[1]['elementor_element_cache_ttl'] = '86400';
check(iu_elementor_active_features()['fragment'], 'Global ON protects updates even with native cache active');
check(iu_elementor_active_features()['fragment'], 'Global ON protects updates without saved opt-ins');
$site_options[1]['istodata_utilities_settings']['optimizations']['elementor_fragment_cache'] = false;
check(!iu_elementor_active_features()['fragment'], 'Global OFF immediately removes fragment update constraint');

// Override is explicitly bound to one target, package and installed pair, in this request only.
installed('4.3.2', '4.3.0'); activation(true, false);
$plugin = 'elementor/elementor.php'; $item = update_item($plugin, '4.4.0');
$extra = array('plugin' => $plugin, 'type' => 'plugin', 'action' => 'update');
$fingerprint = IU_Elementor_Update_Guard::fingerprint($plugin, $item);
$nonce = hash('sha256', 'iu_elementor_override_' . $fingerprint);
$skin = IU_Elementor_Update_Guard::override_skin($plugin, $fingerprint);
check($skin->request_filesystem_credentials() === false &&
    $credential_form === array(admin_url('admin-post.php'), array('action', 'plugin', 'fingerprint', 'confirm', '_wpnonce')),
    'Filesystem credential round-trip retains exact POST grant and reauthorization');
check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, false), 'Confirmation required');
check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, 'wrong', true), 'Nonce required');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = array('plugin' => $plugin, 'fingerprint' => $fingerprint, '_wpnonce' => $nonce, 'confirm' => 'yes');
try { IU_Elementor_Update_Guard::override_update(); throw new LogicException('GET override accepted'); }
catch (RuntimeException $error) { check(strpos($error->getMessage(), 'Forbidden:') === 0, 'Handler rejects GET'); }
$_SERVER['REQUEST_METHOD'] = 'POST'; $_POST['_wpnonce'] = 'wrong';
try { IU_Elementor_Update_Guard::override_update(); throw new LogicException('Bad nonce accepted'); }
catch (RuntimeException $error) { check(strpos($error->getMessage(), 'Forbidden:') === 0, 'Handler rejects invalid nonce before updater'); }
foreach (array('update_plugins', 'manage_options') as $cap) {
    $caps[$cap] = false;
    check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Capability required: ' . $cap);
    $caps[$cap] = true;
}
check(IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Authorized exact update');
check(!is_wp_error(IU_Elementor_Update_Guard::pre_download(false, $item->package, null, $extra)), 'Explicit manual bypass');
check(is_wp_error(IU_Elementor_Update_Guard::pre_download(false, $item->package . '?different', null, $extra)), 'Actual download URL must match override grant');
check(IU_Elementor_Update_Guard::auto_update(true, $item) === false, 'Override never grants automatic bypass');
$cron = true;
check(is_wp_error(IU_Elementor_Update_Guard::pre_download(false, $item->package, null, $extra)), 'Cron cannot use manual override');
$cron = false;
extracted($plugin, '4.4.0');
check(!is_wp_error(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $extra)), 'Exact extracted version override');
extracted($plugin, '4.4.1');
check(is_wp_error(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $extra)), 'Different extracted target rejected');
extracted($plugin, '4.4.0');
installed('4.3.3', '4.3.0');
check(is_wp_error(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $extra)), 'Changed installed pair invalidates grant');
check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Old form invalid after pair changes');
installed('4.3.2', '4.3.0');
$item->package .= '?new-package';
check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Package change invalidates form');
extracted($plugin, '4.4.0');
check(is_wp_error(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $extra)), 'No persisted bypass after failed authorization');

// Actual ZIP takes precedence over supported update metadata.
$item = update_item($plugin, '4.3.2');
check(!is_wp_error(IU_Elementor_Update_Guard::pre_download(false, $item->package, null, $extra)), 'Supported advertised update');
check(is_wp_error(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, $extra)), 'Unsupported actual ZIP blocked');
$other = (object) array('plugin' => 'other/plugin.php', 'new_version' => '99.0.0');
check(IU_Elementor_Update_Guard::auto_update(true, $other) === true, 'Other plugins unconstrained');
check(IU_Elementor_Update_Guard::source_selection('/unpacked/', '', null, array('type' => 'theme')) === '/unpacked/', 'Themes unconstrained');
$error = new WP_Error('prior', 'Prior failure');
check(IU_Elementor_Update_Guard::pre_download($error, '', null, $extra) === $error, 'Preserve other updater failure');

// Sequential/bulk updates read actual files, not stale constants or an imaginary final pair.
define('ELEMENTOR_VERSION', '4.2.3'); define('ELEMENTOR_PRO_VERSION', '4.2.2');
installed('4.2.3', '4.2.2');
check(IU_Elementor_Update_Guard::decision($plugin, '4.3.2')['blocked'], 'Unsafe intermediate core pair blocked');
check(IU_Elementor_Update_Guard::decision('elementor-pro/elementor-pro.php', '4.3.0')['blocked'], 'Unsafe intermediate Pro pair blocked');
installed('4.3.2', '4.2.2');
check(!IU_Elementor_Update_Guard::decision('elementor-pro/elementor-pro.php', '4.3.0')['blocked'], 'Second step reads newly installed core');
activation(true, true);
check(IU_Elementor_Update_Guard::decision('elementor-pro/elementor-pro.php', '4.3.0')['blocked'], 'No shared pair while both features enabled');

// Network update considers sites with Kit enabled, across networks, and restores blog state.
$multisite = true; installed('4.3.2', '4.3.0');
$sites = array((object) array('blog_id' => 1, 'site_id' => 1), (object) array('blog_id' => 2, 'site_id' => 2));
activation(false, false, 1); activation(true, false, 2);
check(IU_Elementor_Update_Guard::decision($plugin, '4.4.0')['blocked'], 'Subsite protects shared plugin files');
check($site_id === 1 && !$stack, 'Restore switched blog');
$site_options[2]['active_plugins'] = array();
check(!IU_Elementor_Update_Guard::decision($plugin, '4.4.0')['blocked'], 'Inactive Kit subsite does not constrain update');
$network_plugins[2] = array('istodata-utilities/istodata-utilities.php' => 1);
check(IU_Elementor_Update_Guard::decision($plugin, '4.4.0')['blocked'], 'Network-active Kit on another network');
$item = update_item($plugin, '4.4.0'); $fingerprint = IU_Elementor_Update_Guard::fingerprint($plugin, $item);
$nonce = hash('sha256', 'iu_elementor_override_' . $fingerprint);
$super = false;
check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Network requires super admin');
$super = true; $caps['manage_network_plugins'] = false;
check(!IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Network capability required');
$caps['manage_network_plugins'] = true;
check(IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, $nonce, true), 'Super admin exact update override');
IU_Elementor_Update_Guard::authorize_override($plugin, $fingerprint, '', true);

ob_start(); IU_Elementor_Update_Guard::page(); $html = ob_get_clean();
check(strpos($html, 'Δεν υπάρχει κοινός αποδεκτός συνδυασμός') === false && strpos($html, '4.3.3') !== false && strpos($html, '4.3.1') !== false, 'Tested common pair indication');
check(strpos($html, 'name="confirm"') !== false && strpos($html, 'name="_wpnonce"') !== false, 'Explicit protected UI');
check(strpos($html, 'Ελεγμένοι:') !== false && strpos($html, 'αποδεκτοί συνδυασμοί') !== false, 'Separate tested and accepted versions');
check($updates->response[$plugin] === $item, 'Update stays visible');
// Third-party Pro update metadata need not include a plugin property for row notices.
unset($item->plugin);
ob_start(); $hooks['in_plugin_update_message-' . $plugin][0][0](array(), $item); $row_html = ob_get_clean();
check(strpos($row_html, 'η ενημέρωση μπλοκάρεται') !== false, 'Plugin-row callback binds its own plugin identity');
// Hide only navigation: blocked-update links must still reach the protected forms.
foreach (array(false, true) as $network) {
    $multisite = $network; $network_admin = $network;
    $parent = $network ? 'settings.php' : 'tools.php';
    $submenu[$parent] = array(array('Other tool', 'manage_options', 'other-tool'));
    IU_Elementor_Update_Guard::menu();
    check(count($submenu[$parent]) === 1 && $submenu[$parent][0][2] === 'other-tool', 'Remove only Kit compatibility navigation');
    $callback = $registered_screens[$parent . '?page=iu-elementor-compatibility'] ?? null;
    check(is_callable($callback), 'Hidden compatibility callback remains registered');
    activation(true, false); installed('4.3.2', '4.3.0');
    $item = update_item($plugin, '4.4.0');
    ob_start(); IU_Elementor_Update_Guard::update_message($plugin, $item); $row = ob_get_clean();
    check(strpos($row, $parent . '?page=iu-elementor-compatibility') !== false, 'Blocked update retains compatibility link');
    ob_start(); IU_Elementor_Update_Guard::notices(); $global_notice = ob_get_clean();
    check(strpos($global_notice, 'η ενημέρωση μπλοκάρεται') === false, 'Blocked available update does not create a global admin banner');
    check(strpos($row, 'η ενημέρωση μπλοκάρεται') !== false, 'Blocked available update keeps its plugin-row warning');
    ob_start(); call_user_func($callback); $screen = ob_get_clean();
    check(strpos($screen, 'iu_elementor_update_override') !== false && strpos($screen, '_wpnonce') !== false, 'Linked screen retains protected override form');
    $cap = $network ? 'manage_network_plugins' : 'manage_options'; $caps[$cap] = false;
    ob_start(); call_user_func($callback); $denied = ob_get_clean();
    check($denied === '', 'Hidden screen still checks capabilities');
    $caps[$cap] = true;
}
echo 'PASS updater: ' . (count($matrix) * 4) . " activation/target scenarios × automatic/manual/extracted/upload gates; new-patch rejection, override, sequential updates, global activation, multisite and UI\n";
