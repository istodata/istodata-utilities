<?php
/** Run against unmodified official WP sources, passed as update-core.php and includes/update.php paths. */
if (count($argv) !== 3) throw new RuntimeException('Pass official wp-admin/update-core.php and wp-admin/includes/update.php paths');
$fixture_root = sys_get_temp_dir() . '/iu-native-screen-' . getmypid();
mkdir($fixture_root); mkdir($fixture_root . '/wp-admin'); mkdir($fixture_root . '/wp-admin/includes');
file_put_contents($fixture_root . '/wp-admin/includes/plugin-install.php', '<?php');
register_shutdown_function(function () use ($fixture_root) {
    unlink($fixture_root . '/wp-admin/includes/plugin-install.php');
    rmdir($fixture_root . '/wp-admin/includes'); rmdir($fixture_root . '/wp-admin'); rmdir($fixture_root);
});
define('ABSPATH', $fixture_root . '/');
require __DIR__ . '/elementor-update-guard.php';
$screen_base = 'update-core';
function get_current_screen() { return (object) array('base' => $GLOBALS['screen_base'] ?? ''); }
function load_native_function($path, $name) {
    $source = file_get_contents($path);
    check((bool) preg_match('/^function ' . preg_quote($name, '/') . '\(.*?^\}/ms', $source, $m), 'Official native function found: ' . $name);
    eval($m[0]); // Only explicit local official source inputs; no updater or remote execution.
    echo 'SOURCE ' . $name . ' ' . hash('sha256', $m[0]) . "\n";
}
load_native_function($argv[1], 'list_plugin_updates');
load_native_function($argv[2], 'get_plugin_updates');
load_native_function($argv[2], 'wp_plugin_update_row');
function get_plugins() {
    $out = array();
    foreach ($GLOBALS['updates']->response as $plugin => $item) $out[$plugin] = array('Name' => $plugin, 'Version' => 'installed', 'PluginURI' => 'https://example.test');
    return $out;
}
function wp_get_wp_version() { return '7.0'; }
function get_core_updates() { return array((object) array('response' => 'latest')); }
function __($s) { return $s; }
function _e($s) { echo $s; }
function _x($s, $context) { return $s; }
function esc_attr($s) { return esc_html($s); }
function esc_attr_e($s) { echo esc_attr($s); }
function number_format_i18n($n) { return (string) $n; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="native-nonce">'; }
function wp_nonce_url($url, $action) { return $url . '&_wpnonce=native-nonce'; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function wp_is_auto_update_enabled_for_type($type) { return false; }
function _get_plugin_data_markup_translate($file, $data, $markup, $translate) { return $data; }
function is_php_version_compatible($version) { return $version !== '999.0'; }
function wp_get_update_php_url() { return 'https://example.test/php'; }
function wp_get_update_php_annotation() { return ''; }
function wp_update_php_annotation($before = '', $after = '') {}
function self_admin_url($path) { return admin_url($path); }
function wp_kses($s, $tags) { return $s; }
function _get_list_table($class, $args) { return new class { function get_column_count() { return 3; } }; }
function is_plugin_active($file) { return in_array($file, $GLOBALS['active_plugins'], true); }
function is_plugin_active_for_network($file) { return isset($GLOBALS['network_plugins'][$file]); }
function do_action($hook, ...$args) {
    $callbacks = $GLOBALS['hooks'][$hook] ?? array(); ksort($callbacks);
    foreach ($callbacks as $group) foreach ($group as $fn) $fn(...$args);
}
function render_updates() { ob_start(); list_plugin_updates(); return ob_get_clean(); }
function render_plugins($plugin) { ob_start(); wp_plugin_update_row($plugin, get_plugins()[$plugin]); return ob_get_clean(); }
eval('namespace Elementor; class Plugin { public static $instance; }');
eval('namespace Elementor\\Modules\\AtomicWidgets; class Module { const EXPERIMENT_NAME="e_atomic_elements"; }');
$atomic_experiment = false;
\Elementor\Plugin::$instance = (object) array('experiments' => new class {
    function is_feature_active($name) { return $GLOBALS['atomic_experiment']; }
});
$core = 'elementor/elementor.php'; $pro = 'elementor-pro/elementor-pro.php';
$updates->response = array($core => item('4.3.5'), $pro => item('4.3.2'), 'unrelated/plugin.php' => item('1.0.0'));
foreach ($updates->response as $file => $item) { $item->slug = dirname($file); $item->tested = '99.0'; }
$updates->response[$core]->upgrade_notice = '<strong>Vendor notice &amp; terms</strong>';
$updates->last_checked = 123; $updates->checked = array($core => '4.3.4');
installed('4.3.4','4.3.1');
$settings['optimizations'] = array('elementor_atomic_interaction_breakpoints'=>false,'elementor_fragment_cache'=>false);
$settings['additional']['elementor_device_visibility'] = false;
$original = serialize($updates);
check(strpos(render_updates(), 'ISTODATA Kit:')===false && strpos(render_plugins($core),'ISTODATA Kit:')===false, 'All OFF: neither native screen adds advice');
$settings['optimizations'] = array('elementor_atomic_interaction_breakpoints'=>true,'elementor_fragment_cache'=>true);
$html = render_updates();
check(substr_count($html,'ISTODATA Kit:')===2, 'Exactly one advice per Core/Pro, none for unrelated plugin');
foreach (array($core,$pro) as $file) {
    $text = esc_html(IU_Elementor_Update_Guard::advisory_text(IU_Elementor_Update_Guard::advisory($file,$updates->response[$file])));
    check(strpos($html,$text)!==false && strpos(render_plugins($file),$text)!==false, 'Both unmodified native renderers use identical central needs-both text');
}
check(strpos($html,'Compatibility with WordPress')<strpos($html,'ISTODATA Kit:'), 'Advice follows native WP compatibility');
check(strpos($html,'Vendor notice &amp; terms')!==false && substr_count($html,'name="checked[]"')===3, 'Vendor notice, unrelated row and native update checkboxes retained');
check($original===serialize($updates), 'No cached objects, versions, packages, checked or timestamps changed');
check(get_site_transient('update_plugins')===$updates, 'Direct read on Updates screen remains raw, including metadata refresh cycles');
check(get_plugin_updates()[$core]->update===$updates->response[$core], 'Non-render get_plugin_updates call remains raw');
check(substr_count(render_updates(),'ISTODATA Kit:')===2 && $original===serialize($updates), 'Repeated render does not duplicate/persist advice; counterpart reads do not recurse');
$updates->response[$core]->package='';
check(strpos(render_plugins($core),'Automatic update is unavailable')!==false,'Empty vendor package retains native unavailable decision');
$updates->response[$core]->package='https://example.test/vendor.zip';
$updates->response[$core]->requires_php='999.0';
$html=render_updates();
check(strpos($html,'This update does not work with your version of PHP.')!==false && substr_count($html,'name="checked[]"')===2,'Native PHP incompatibility still removes only its update checkbox');
unset($updates->response[$core]->requires_php);
check($original===serialize($updates),'Display adapter leaves native/vendor decision metadata untouched after negative cases');
$screen_base = 'plugins'; check(strpos(render_updates(),'ISTODATA Kit:')===false,'Adapter cannot leak onto another screen');
$screen_base = 'update-core'; $authorized = false;
check(strpos(render_updates(),'ISTODATA Kit:')===false && strpos(render_plugins($core),'ISTODATA Kit:')===false,'No capability: neither adapter adds Kit advice');
$authorized = true;
installed('4.3.1','4.3.0'); $updates->response[$core]->new_version='4.3.2'; unset($updates->response[$pro]);
$settings['optimizations']['elementor_fragment_cache']=false;
$html=render_updates();$text=IU_Elementor_Update_Guard::advisory_text(IU_Elementor_Update_Guard::advisory($core,$updates->response[$core]));
check(strpos($html,esc_html($text))!==false && strpos($text,'παρακάμπτονται')===false,'Atomic-only accepted target does not inherit inactive cache failures/needs-both');
$settings['optimizations']['elementor_fragment_cache']=true;
$text=IU_Elementor_Update_Guard::advisory_text(IU_Elementor_Update_Guard::advisory($core,$updates->response[$core]));
check(strpos(render_updates(),esc_html($text))!==false && strpos(render_plugins($core),esc_html($text))!==false && strpos($text,'Επαληθευμένο για:')!==false,'Partial support names only active affected/supported functions on both screens');
$settings['optimizations']=array();$settings['additional']['elementor_device_visibility']=true;$atomic_experiment=true;
$advice=IU_Elementor_Update_Guard::advisory($core,$updates->response[$core]);
check($advice['affected']===array('fragment_atomic') && !$advice['supported'],'Atomic device-only path is related even with both optimization switches OFF');
$atomic_experiment=false;check(IU_Elementor_Update_Guard::advisory($core,$updates->response[$core])===null,'Inactive Atomic experiment plus OFF optimizations yields no advice');
$settings['optimizations']['elementor_atomic_interaction_breakpoints']=true;
$active_plugins=array();installed('4.3.4','4.3.1');check(IU_Elementor_Update_Guard::advisory($core,$updates->response[$core])===null,'Inactive Core cannot make saved settings into active features');
$active_plugins=array($core,$pro);installed('4.3.4','4.3.1');
$multisite=$network_admin=true;$advisory_blog_id=42;
$text=IU_Elementor_Update_Guard::advisory_text(IU_Elementor_Update_Guard::advisory($core,$updates->response[$core]));
check(strpos($text,'ιστότοπος #42 (όχι συνολικός έλεγχος δικτύου)')!==false && strpos(render_updates(),esc_html($text))!==false && strpos(render_plugins($core),esc_html($text))!==false,'Both network screens explicitly identify current-site scope');
$network_admin=false;check(strpos(render_updates(),'ISTODATA Kit:')===false,'Multisite site admin does not receive network updater advice');
$multisite=$network_admin=false;
$updates->response[$core]->new_version='<script>bad</script>';
$html=render_updates();check(strpos($html,'ISTODATA Kit:')!==false && strpos($html,'λείπουν έγκυρα στοιχεία στόχου')!==false,'Missing/invalid target is never verified by display adapter');
// Native Core also prints the target itself without escaping; verify Kit output separately.
ob_start();IU_Elementor_Update_Guard::update_message($core,$updates->response[$core]);$kit=ob_get_clean();
check(strpos($kit,'<script>')===false && strpos($kit,'άγνωστη')!==false,'Kit display uses bounded labels and escapes its own output');
echo "PASS two official native renderers: OFF/ON/partial/needs-both/device-only, scope/capability, no metadata writes or duplicate advice\n";
