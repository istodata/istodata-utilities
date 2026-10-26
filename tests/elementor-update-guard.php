<?php
/** Native updater passthrough and registry-based advisories; no server mutation. */
define('ABSPATH', __DIR__ . '/');
define('IU_PLUGIN_PATH', dirname(__DIR__) . '/');
define('WP_PLUGIN_DIR', sys_get_temp_dir() . '/iu-advisory-' . getmypid());
mkdir(WP_PLUGIN_DIR); foreach (array('elementor', 'elementor-pro') as $slug) mkdir(WP_PLUGIN_DIR . '/' . $slug);
register_shutdown_function(function () {
    foreach (array('elementor', 'elementor-pro') as $slug) {
        $file = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php'; if (is_file($file)) unlink($file);
        rmdir(dirname($file));
    }
    rmdir(WP_PLUGIN_DIR);
});
$hooks = array(); $multisite = false; $network_admin = false; $authorized = true;
$settings = array('optimizations' => array('elementor_atomic_interaction_breakpoints' => true, 'elementor_fragment_cache' => true),
    'additional' => array('elementor_device_visibility' => false), 'saved_duration' => 86400);
$updates = (object) array('response' => array());
$active_plugins = array('elementor/elementor.php', 'elementor-pro/elementor-pro.php');
$network_plugins = array();
function add_filter($hook, $fn, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][$priority][] = $fn; }
function add_action($hook, $fn, $priority = 10, $args = 1) { add_filter($hook, $fn, $priority, $args); }
function apply_filters($hook, $value, ...$args) {
    $callbacks = $GLOBALS['hooks'][$hook] ?? array(); ksort($callbacks);
    foreach ($callbacks as $group) foreach ($group as $fn) $value = $fn($value, ...$args);
    return $value;
}
function is_multisite() { return $GLOBALS['multisite']; }
function is_network_admin() { return $GLOBALS['network_admin']; }
function current_user_can($cap) { return $GLOBALS['authorized']; }
function get_option($key, $default = false) {
    if ($key === 'active_plugins') return $GLOBALS['active_plugins'];
    return $key === 'istodata_utilities_settings' ? $GLOBALS['settings'] : $default;
}
function get_site_option($key, $default = false) { return $key === 'active_sitewide_plugins' ? $GLOBALS['network_plugins'] : $default; }
function update_option() { throw new RuntimeException('Advisory/runtime policy must not change saved settings'); }
function get_site_transient($key) { return $GLOBALS['updates']; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return esc_html($value); }
function admin_url($path) { return 'https://example.test/wp-admin/' . $path; }
function network_admin_url($path) { return 'https://example.test/wp-admin/network/' . $path; }
function wp_die($message, $title = '', $args = array()) { throw new RuntimeException('Retired:' . ($args['response'] ?? 0)); }
function get_file_data($file, $headers, $context) {
    preg_match('/Version: (.*)/', file_get_contents($file), $m); return array('Version' => trim($m[1]));
}
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
class WP_Error { public $code; public function __construct($code, $message = '') { $this->code = $code; } }
function is_wp_error($v) { return $v instanceof WP_Error; }
function installed($core, $pro) {
    foreach (array('elementor' => $core, 'elementor-pro' => $pro) as $slug => $version) {
        $file = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
        if ($version === null) { if (is_file($file)) unlink($file); }
        else file_put_contents($file, "<?php /* Version: " . $version . "\n */");
    }
    if (class_exists('IU_Elementor_Update_Guard', false)) {
        $reply = new WP_Error('native_result');
        check(apply_filters('upgrader_post_install', $reply, array('plugin'=>'elementor/elementor.php')) === $reply,
            'Passive post-install observation preserves native result');
    }
}
function item($version) { return (object) array('new_version' => $version, 'package' => 'https://example.test/vendor.zip'); }
require IU_PLUGIN_PATH . 'includes/elementor-update-guard.php';
IU_Elementor_Update_Guard::boot();
foreach (array('auto_update_plugin', 'upgrader_pre_download', 'upgrader_source_selection') as $hook) check(!isset($hooks[$hook]), 'No Kit native updater gate: ' . $hook);
check(!isset($hooks['pre_set_site_transient_update_plugins'], $hooks['site_transient_update_plugins']), 'Advisory never hides updates');
$matrix = array(array('4.3.4','4.3.1'), array('4.3.5','4.3.1'), array('4.3.4','4.3.2'), array('4.3.5','4.3.2'), array('4.4.0','4.4.0'), array('4.2.3',null), array(null,null));
foreach ($matrix as $pair) foreach (array(false,true) as $on) {
    installed(...$pair); $settings['optimizations']['elementor_fragment_cache'] = $on;
    $settings['optimizations']['elementor_atomic_interaction_breakpoints'] = $on;
    foreach (array_keys(IU_Elementor_Update_Guard::plugins()) as $plugin) {
        foreach (array(false,true,null) as $allow) {
            check(apply_filters('auto_update_plugin', $allow, (object) array('plugin'=>$plugin,'new_version'=>'4.9.0')) === $allow, 'Native automatic decision unchanged');
            check(IU_Elementor_Update_Guard::auto_update($allow,item('4.9.0')) === $allow, 'Legacy automatic shim preserves native result');
        }
        foreach (array(array('plugin'=>$plugin),array('plugin'=>$plugin,'type'=>'plugin','action'=>'update'),array('type'=>'plugin','action'=>'install')) as $extra) {
            foreach (array(false,new WP_Error('vendor_denied'),'native-download.zip') as $reply) {
                check(apply_filters('upgrader_pre_download',$reply,'https://vendor.test',null,$extra) === $reply, 'Manual/bulk download preserves native/vendor decision');
                check(IU_Elementor_Update_Guard::pre_download($reply,'',null,$extra) === $reply, 'Legacy download shim is inert');
            }
            check(apply_filters('upgrader_source_selection','/native/source','',null,$extra) === '/native/source', 'Extracted/upload source is not compatibility-blocked');
            check(IU_Elementor_Update_Guard::source_selection('/native/source','',null,$extra) === '/native/source', 'Legacy source shim is inert');
        }
    }
}
$vendor = new WP_Error('native_vendor_denied'); add_filter('upgrader_pre_download', function()use($vendor){return $vendor;});
check(apply_filters('upgrader_pre_download',false) === $vendor,'Native/vendor rejection is not bypassed');
$settings['optimizations'] = array('elementor_atomic_interaction_breakpoints'=>false,'elementor_fragment_cache'=>false);
installed('4.3.4','4.3.1'); $updates->response = array('elementor-pro/elementor-pro.php'=>item('4.3.2'), 'elementor/elementor.php'=>item('4.3.5'));
foreach (array('elementor/elementor.php'=>'4.3.5','elementor-pro/elementor-pro.php'=>'4.3.2') as $plugin=>$version) {
    $a=IU_Elementor_Update_Guard::advisory($plugin,item($version));check($a['state']==='needs_both','Both native rows explain available final combination');
    check($a['affected'] && $a['destination']===array('core'=>'4.3.5','pro'=>'4.3.2'),'Intermediate not verified, final accepted');
    ob_start();IU_Elementor_Update_Guard::update_message($plugin,item($version));$html=ob_get_clean();
    check(strpos($html,'Το ενδιάμεσο')!==false && strpos($html,'εφόσον είναι ενεργές')!==false,'Advisory works before update even with featuresOFF');
    check(strpos($html,'<p>')===false && strpos($html,'iu-elementor-compatibility-advice')!==false,'Inline native hook does not acquire invalid nested paragraphs/extra icons');
    check(count(preg_split('//u',strip_tags($html),-1,PREG_SPLIT_NO_EMPTY))<800 && strpos($html,'Checksum')===false,'Concise row notice, no technical evidence dump');
}
installed('4.3.5','4.3.1');$a=IU_Elementor_Update_Guard::advisory('elementor-pro/elementor-pro.php',item('4.3.2'));check($a['state']==='verified'&&!$a['affected'],'Target plus installed counterpart accepted for every relevant feature');
installed('4.3.1','4.3.0');$updates->response=array();$a=IU_Elementor_Update_Guard::advisory('elementor/elementor.php',item('4.3.2'));check($a['state']==='unverified'&&$a['supported']===array('atomic'),'Per-feature partial support, not blanket failure');
installed('4.2.2',null);$a=IU_Elementor_Update_Guard::advisory('elementor/elementor.php',item('4.2.3'));check($a['supported']===array('fragment')&&in_array('atomic',$a['affected'],true),'Only documented core-only feature is accepted');
installed('4.3.4','4.3.1');foreach(array(null,(object)array(),item('4.9.0'),item('<script>bad</script>')) as $response){$a=IU_Elementor_Update_Guard::advisory('elementor-pro/elementor-pro.php',$response);check($a['state']==='unverified','Unknown/missing target never looks like absent Pro or accepted evidence');}
$updates->response=array('elementor-pro/elementor-pro.php'=>item('4.3.2'));unset($updates->response['elementor-pro/elementor-pro.php']->package);
check(IU_Elementor_Update_Guard::advisory('elementor/elementor.php',item('4.3.5'))['state']==='unverified','Missing counterpart package cannot promise an available two-update destination');
$multisite=$network_admin=true;check(strpos(IU_Elementor_Update_Guard::page_url(),'/network/')!==false,'Preserve network information URL');
check(IU_Elementor_Update_Guard::advisory('elementor-pro/elementor-pro.php',item('4.3.2'))['state']==='unverified','Network row uses shared actual files regardless per-site settings');
$multisite=$network_admin=false;
ob_start();IU_Elementor_Update_Guard::page();$page=ob_get_clean();check(strpos($page,'<form')===false && strpos($page,'admin-post')===false && strpos($page,'ενημέρωση ζεύγους')===false,'Read-only information screen, no hidden mutation UI');
echo "PASS native update passthrough and verified/needs-both/unverified/partial/core-only/network advisories\n";
