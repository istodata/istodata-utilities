<?php
/** Exercise the coordinator against the real guard callbacks and mutable installed files. No network/WP installation. */
require __DIR__ . '/elementor-update-guard.php';

class WP_Upgrader {
    public static $locked = false;
    public static function create_lock($name, $ttl) { if (self::$locked) return false; self::$locked = true; return true; }
    public static function release_lock($name) { self::$locked = false; }
}
function wp_clean_plugins_cache() { $GLOBALS['cache_cleaned'] = true; }

function pair_setup() {
    $GLOBALS['multisite'] = false; $GLOBALS['network_admin'] = false; $GLOBALS['cron'] = false;
    $GLOBALS['caps'] = array('update_plugins' => true, 'manage_options' => true, 'manage_network_plugins' => true);
    $GLOBALS['super'] = true; WP_Upgrader::$locked = false;
    installed('4.3.4', '4.3.1'); activation(true, true);
    update_item('elementor/elementor.php', '4.3.5'); update_item('elementor-pro/elementor-pro.php', '4.3.2');
    $plan = IU_Elementor_Update_Guard::pair_plan(); check($plan !== null, 'Approved destination plan exists');
    $fp = IU_Elementor_Update_Guard::pair_fingerprint($plan);
    return array($plan, $fp, hash('sha256', 'iu_elementor_pair_' . $fp));
}
class Pair_Upgrade_Fixture {
    public $calls = array(); public $mode; public $after_first;
    public $maintenance = false;
    public function maintenance_mode($enabled) { $this->maintenance = $enabled; }
    public function cleanup_pair_maintenance() { $this->maintenance_mode(false); }
    public function __construct($mode = 'success', $after_first = null) { $this->mode = $mode; $this->after_first = $after_first; }
    public function upgrade($plugin, $args) { throw new LogicException('Single-plugin upgrade would deactivate the plugin'); }
    public function bulk_upgrade($plugins, $args) {
        check(count($plugins) === 1, 'One native bulk step at a time; stop before the second plugin on failure');
        return array($plugins[0] => $this->install_step($plugins[0], $args));
    }
    private function install_step($plugin, $args) {
        $this->calls[] = $plugin;
        check($args === array('clear_update_cache' => false), 'Do not clear pending Pro metadata between steps');
        $item = $GLOBALS['updates']->response[$plugin];
        $extra = array('plugin' => $plugin, 'temp_backup' => array('slug' => dirname($plugin)));
        $caller = $this->mode === 'other-upgrader' ? new stdClass() : $this;
        $download = IU_Elementor_Update_Guard::pre_download(false, $item->package, $caller, $extra);
        if (is_wp_error($download)) return $download;
        if ($this->mode === 'package-during-download') $item->package .= '?changed';
        extracted($plugin, $this->mode === 'wrong-source' ? '4.3.4' : $item->new_version);
        $source = IU_Elementor_Update_Guard::source_selection('/unpacked/', '', $this, $extra);
        if (is_wp_error($source)) return $source;
        if ($this->mode === 'false-first' || ($this->mode === 'false-second' && count($this->calls) === 2)) return false;
        if ($this->mode === 'fail-first' || ($this->mode === 'fail-second' && count($this->calls) === 2)) return new WP_Error('fixture_failure', 'Disk copy failure');
        if ($this->mode === 'throw-second' && count($this->calls) === 2) { $this->maintenance = true; throw new RuntimeException('Interrupted installer'); }
        $pair = IU_Elementor_Update_Guard::installed_pair();
        $pair[IU_Elementor_Update_Guard::plugins()[$plugin]] = $item->new_version;
        if ($this->mode === 'wrong-installed-second' && count($this->calls) === 2) $pair['pro'] = '4.3.1';
        installed($pair['core'], $pair['pro']);
        if (count($this->calls) === 1) {
            if ($pair === array('core' => '4.3.5', 'pro' => '4.3.1')) {
                foreach (array('atomic', 'fragment_atomic', 'fragment') as $feature) {
                    check(!iu_elementor_feature_supported($feature), 'Unsupported intermediate disabled despite stale PHP constants: ' . $feature);
                }
            }
            if ($this->after_first) call_user_func($this->after_first);
        }
        return true;
    }
}

list($plan, $fp, $nonce) = pair_setup();
$_POST = array('checked' => array_keys(IU_Elementor_Update_Guard::plugins()));
$bulk = (object) array('plugins' => $_POST['checked']);
foreach ($plan['steps'] as $plugin => $step) {
    check(IU_Elementor_Update_Guard::decision($plugin, $step['version'])['blocked'], 'Reproduce ordinary two-selection deadlock');
    $blocked = IU_Elementor_Update_Guard::pre_download(false, $step['package'], $bulk, array('plugin' => $plugin, 'temp_backup' => array('slug' => dirname($plugin))));
    check(is_wp_error($blocked), 'Standard bulk is not implicitly authorized by selected plugin names');
    check(strlen($blocked->message) < 1400 && strpos($blocked->message, 'Ελεγμένοι:') === false, 'Short blocking error without tested evidence');
    foreach (array('4.3.4', '4.3.1', '4.3.5', '4.3.2', 'href=', 'Εγκεκριμένος προορισμός') as $text) check(strpos($blocked->message, $text) !== false, 'Actionable block: ' . $text);
}
ob_start(); IU_Elementor_Update_Guard::page(); $page = ob_get_clean();
check(strpos($page, 'iu_elementor_update_pair') !== false && strpos($page, 'όχι ατομικά') !== false, 'Explicit sequential pair flow, no atomic promise');
check(strpos($page, '<details>') !== false && strpos($page, 'Ελεγμένοι:') !== false, 'Keep evidence in detail disclosure');
$skin = IU_Elementor_Update_Guard::pair_skin($fp);
check($skin->request_filesystem_credentials() === false && $credential_form[1] === array('action', 'fingerprint', 'confirm', '_wpnonce'), 'Credentials round-trip retains exact POST plan');

foreach (array(array(false, $nonce), array(true, 'bad')) as $case) {
    $upgrader = new Pair_Upgrade_Fixture();
    check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $case[1], $case[0])) && !$upgrader->calls, 'Confirmation and nonce required before mutation');
}
foreach (array('update_plugins', 'manage_options') as $cap) {
    $caps[$cap] = false; $upgrader = new Pair_Upgrade_Fixture();
    check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $nonce, true)) && !$upgrader->calls, 'Pair capability ' . $cap);
    $caps[$cap] = true;
}
foreach (array('GET', 'POST') as $method) {
    $_SERVER['REQUEST_METHOD'] = $method; $_POST = array('fingerprint' => $fp, '_wpnonce' => 'bad', 'confirm' => 'yes');
    try { IU_Elementor_Update_Guard::pair_update(); throw new LogicException('Invalid handler reached installer'); }
    catch (RuntimeException $e) { check(strpos($e->getMessage(), 'Forbidden:') === 0, 'Handler rejects unauthenticated POST/GET'); }
}
$cron = true; check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update(new Pair_Upgrade_Fixture(), $fp, $nonce, true)), 'No cron pair bypass'); $cron = false;
foreach (array('super', 'network-capability') as $denial) {
    $multisite = true;
    if ($denial === 'super') $super = false; else $caps['manage_network_plugins'] = false;
    check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update(new Pair_Upgrade_Fixture(), $fp, $nonce, true)), 'Network authorization: ' . $denial);
    $super = true; $caps['manage_network_plugins'] = true; $multisite = false;
}
WP_Upgrader::$locked = true; check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update(new Pair_Upgrade_Fixture(), $fp, $nonce, true)), 'No concurrent pair workflow'); WP_Upgrader::$locked = false;

foreach (array('package', 'target', 'installed') as $change) {
    list($plan, $fp, $nonce) = pair_setup();
    if ($change === 'package') $updates->response['elementor-pro/elementor-pro.php']->package .= '?changed';
    if ($change === 'target') $updates->response['elementor-pro/elementor-pro.php']->new_version = '4.3.1';
    if ($change === 'installed') installed('4.3.3', '4.3.1');
    $upgrader = new Pair_Upgrade_Fixture();
    check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $nonce, true)) && !$upgrader->calls, 'Changed confirmation: ' . $change);
}
list($plan, $fp, $nonce) = pair_setup();
$updates->response['elementor-pro/elementor-pro.php']->new_version = '4.4.0';
check(IU_Elementor_Update_Guard::pair_plan() === null, 'No coordinated plan for an unapproved final destination');
foreach (array('wrong-source', 'package-during-download', 'other-upgrader', 'fail-first', 'false-first', 'fail-second', 'false-second', 'throw-second', 'wrong-installed-second') as $mode) {
    list($plan, $fp, $nonce) = pair_setup(); $upgrader = new Pair_Upgrade_Fixture($mode);
    check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $nonce, true)), 'Stop failed or altered step: ' . $mode);
    check(!WP_Upgrader::$locked, 'Release own lock in finally');
    check(!$upgrader->maintenance, 'Exception does not retain native maintenance marker');
    if (in_array($mode, array('fail-second', 'false-second', 'throw-second', 'wrong-installed-second'), true)) {
        check(IU_Elementor_Update_Guard::installed_pair() === array('core' => '4.3.5', 'pro' => '4.3.1'), 'Retain truthful partial state, no rollback promise');
        foreach (array('atomic', 'fragment_atomic', 'fragment') as $feature) check(!iu_elementor_feature_supported($feature), 'Partial failure leaves no supported intermediate: ' . $feature);
        check(!IU_Elementor_Update_Guard::decision('elementor-pro/elementor-pro.php', '4.3.2')['blocked'], 'Recovery Pro update can complete the approved pair');
    } else check(count($upgrader->calls) === 1, 'Never attempt second plugin after first failure');
    $extra = array('plugin' => 'elementor/elementor.php');
    $item = $updates->response[$extra['plugin']];
    check(is_wp_error(IU_Elementor_Update_Guard::pre_download(false, $item->package, $upgrader, $extra)), 'Request-local authorization removed after failure');
}
foreach (array('package', 'target', 'installed', 'capability') as $change) {
    list($plan, $fp, $nonce) = pair_setup();
    $upgrader = new Pair_Upgrade_Fixture('success', function () use ($change) {
        if ($change === 'package') $GLOBALS['updates']->response['elementor-pro/elementor-pro.php']->package .= '?new';
        elseif ($change === 'target') $GLOBALS['updates']->response['elementor-pro/elementor-pro.php']->new_version = '4.4.0';
        elseif ($change === 'capability') $GLOBALS['caps']['update_plugins'] = false;
        else installed('4.3.5', '4.3.0');
    });
    check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $nonce, true)) && count($upgrader->calls) === 1, 'Revalidate between steps: ' . $change);
}
list($plan, $fp, $nonce) = pair_setup();
$upgrader = new Pair_Upgrade_Fixture('success', function () { unset($GLOBALS['updates']->response['elementor/elementor.php']); });
$result = IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $nonce, true);
check(!is_wp_error($result) && $result['complete'] && count($upgrader->calls) === 2, 'Complete pinned pair even if completed step metadata disappears');
foreach (array('atomic', 'fragment_atomic', 'fragment') as $feature) check(iu_elementor_feature_supported($feature), 'Normal supported final pair: ' . $feature);
check(IU_Elementor_Update_Guard::installed_pair() === array('core' => '4.3.5', 'pro' => '4.3.2'), 'Exact final pair installed');
check(!iu_elementor_feature_supported('fragment', array('core' => '4.3.5', 'pro' => '4.3.1')), 'Mixed pair never added to registry');
pair_setup(); installed('4.3.5', '4.3.1');
$recovery = IU_Elementor_Update_Guard::pair_plan();
check(array_keys($recovery['steps']) === array('elementor-pro/elementor-pro.php'), 'Recovery skips already-installed Core in stale metadata');
$fp = IU_Elementor_Update_Guard::pair_fingerprint($recovery); $nonce = hash('sha256', 'iu_elementor_pair_' . $fp);
$upgrader = new Pair_Upgrade_Fixture();
check(!is_wp_error(IU_Elementor_Update_Guard::run_pair_update($upgrader, $fp, $nonce, true)) && count($upgrader->calls) === 1, 'Freshly confirmed recovery completes only Pro');
require IU_PLUGIN_PATH . 'includes/elementor-atomic-interaction-breakpoints.php';
installed('4.3.5', '4.3.1');
ob_start(); IU_Elementor_Update_Guard::notices(); iu_atomic_interaction_breakpoints_notice(); $notices = ob_get_clean();
check(strlen($notices) < 1800 && strpos($notices, 'Ελεγμένοι:') === false && strpos($notices, 'href=') !== false, 'Runtime notices concise and linked');
echo "PASS coordinated pair: bulk deadlock, explicit UI/POST, capabilities/nonce, package/source/installed changes, first/second failure, runtime fail-closed, recovery and success\n";
class Plugin_Upgrader extends WP_Upgrader {
    public $skin;
    public function __construct($skin) { $this->skin = $skin; }
}
define('FS_CHMOD_FILE', 0644);
$wp_filesystem = new class {
    public $files = array();
    public function abspath() { return '/wp/'; }
    public function exists($p) { return isset($this->files[$p]); }
    public function get_contents($p) { return $this->files[$p] ?? false; }
    public function put_contents($p, $v, $m) { $this->files[$p] = $v; return true; }
    public function delete($p) { unset($this->files[$p]); return true; }
};
$maintenance_skin = new class { public function feedback($message) {} };
$owned = IU_Elementor_Update_Guard::pair_upgrader($maintenance_skin);
$wp_filesystem->files['/wp/.maintenance'] = 'foreign';
try { $owned->maintenance_mode(true); throw new LogicException('Overwrote another maintenance marker'); }
catch (RuntimeException $e) {}
$owned->cleanup_pair_maintenance(); check($wp_filesystem->files['/wp/.maintenance'] === 'foreign', 'Pre-existing foreign marker retained');
unset($wp_filesystem->files['/wp/.maintenance']);
$owned->maintenance_mode(true); check(strpos($wp_filesystem->files['/wp/.maintenance'], 'iu-pair:') !== false, 'Flow wrote its own uniquely identified marker');
$owned->cleanup_pair_maintenance(); check(!$wp_filesystem->exists('/wp/.maintenance'), 'Remove owned marker');
$owned->maintenance_mode(true); $wp_filesystem->files['/wp/.maintenance'] = 'replacement by another updater';
$owned->cleanup_pair_maintenance(); check($wp_filesystem->exists('/wp/.maintenance'), 'Never remove a replacement marker');
echo "PASS maintenance ownership: pre-existing, own and replaced marker\n";
