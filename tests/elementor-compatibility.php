<?php
/** Isolated policy/runtime regressions; no WordPress/server mutation. */
if (!isset($argv[1])) {
    foreach (array('atomic', 'fragment', 'core-only', 'core-patch', 'pro-patch', 'both-patch', 'accepted-new', 'future', 'missing') as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' -n ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $status);
        if ($status) exit($status);
    }
    exit;
}
define('ABSPATH', __DIR__);
define('IU_PLUGIN_PATH', dirname(__DIR__) . '/');
$pairs = array('atomic' => array('4.3.2', '4.3.0'), 'fragment' => array('4.2.3', '4.2.2'),
    'core-only' => array('4.2.3', null), 'core-patch' => array('4.3.3', '4.3.0'),
    'pro-patch' => array('4.3.2', '4.3.1'), 'both-patch' => array('4.3.3', '4.3.1'),
    'accepted-new' => array('4.3.4', '4.3.1'), 'future' => array('4.4.0', '4.4.0'), 'missing' => array(null, null));
[$core, $pro] = $pairs[$argv[1]];
if ($core !== null) define('ELEMENTOR_VERSION', $core);
if ($pro !== null) define('ELEMENTOR_PRO_VERSION', $pro);
function add_action() {}
function add_filter() {}
function do_action() {}
function did_action() { return 0; }
function get_locale() { return 'el'; }
function get_option($name, $default = false) {
    if ($name === 'istodata_utilities_settings') return array('optimizations' => array('elementor_atomic_interaction_breakpoints' => true, 'elementor_fragment_cache' => true));
    return $name === 'elementor_element_cache_ttl' ? 'disable' : $default;
}
function current_user_can($cap) { return $GLOBALS['authorized'] ?? true; }
function is_network_admin() { return false; }
function get_current_blog_id() { return 1; }
function get_site_transient($name) { return (object) array('response' => array()); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function apply_filters($hook, $value) { return $hook === 'iu_elementor_fragment_versions_supported' ? true : $value; }
function expect($value, $message) { if (!$value) throw new RuntimeException($message); }
require IU_PLUGIN_PATH . 'includes/elementor-atomic-interaction-breakpoints.php';
require IU_PLUGIN_PATH . 'includes/elementor-fragment-cache.php';

$targets = array(
    array('4.3.2', '4.3.0', true, false), array('4.3.100', '4.3.100', false, false),
    array('4.3.3', '4.3.0', false, false), array('4.3.2', '4.3.1', false, false),
    array('4.3.3', '4.3.1', true, true), array('4.3.4', '4.3.1', true, true), array('4.3.5', '4.3.1', false, false), array('4.3.3', '4.3.2', false, false),
    array('4.2.3', '4.2.2', false, true), array('4.2.3', null, false, true),
    array('4.3.1', '4.3.0', false, false), array('4.3.2', '4.2.2', false, false),
    array('4.2.3', '4.3.0', false, false), array('4.3.2', null, false, false),
    array('4.2.4', '4.2.2', false, false), array('4.2.3', '4.2.3', false, false),
    array('4.4.0', '4.3.0', false, false), array('4.3.2', '4.4.0', false, false),
    array('4.3.2-beta1', '4.3.0', false, false), array('4.3.2', '4.3.0-rc1', false, false),
    array('4.3.2.1', '4.3.0', false, false), array(4.3, '4.3.0', false, false),
    array(null, null, false, false), array('5.0.0', '5.0.0', false, false),
);
foreach ($targets as [$a, $b, $atomic, $fragment]) {
    $pair = array('core' => $a, 'pro' => $b);
    expect(iu_elementor_feature_supported('atomic', $pair) === $atomic, 'Atomic policy drift');
    expect(iu_elementor_feature_supported('fragment', $pair) === $fragment, 'Fragment policy drift');
    expect(iu_elementor_feature_supported('fragment', $pair, true) === ($fragment && $b !== null), 'Pro adapter boundary');
    foreach (array(false, true) as $atomic_on) foreach (array(false, true) as $fragment_on) {
        $failed = iu_elementor_pair_failures($pair, array('atomic' => $atomic_on, 'fragment' => $fragment_on));
        expect(in_array('atomic', $failed, true) === ($atomic_on && !$atomic), 'Inactive Atomic must not constrain pair');
        expect(in_array('fragment', $failed, true) === ($fragment_on && !$fragment), 'Inactive Fragment must not constrain pair');
    }
}
expect(iu_elementor_feature_supported('unknown') === false, 'Unknown features fail closed');
foreach (iu_elementor_compatibility_registry() as $entry) foreach ($entry['accepted'] as $pair) foreach ($pair as $rule) {
    expect($rule === null || array_keys($rule) === array('exact'), 'Approval must be explicit; no unbounded ranges');
}
expect(!iu_elementor_version_matches('4.3.3', array('series' => '4.3', 'min' => '4.3.2')), 'Legacy open-ended rule must fail closed');
expect(iu_elementor_common_pair(array('atomic', 'fragment')) === array('core' => '4.3.3', 'pro' => '4.3.1'), 'Derive tested common pair');
expect(iu_elementor_common_pair(array('atomic')) === array('core' => '4.3.2', 'pro' => '4.3.0'), 'Derive Atomic baseline from registry');
expect(iu_atomic_interaction_breakpoints_supported() === in_array($argv[1], array('atomic', 'both-patch', 'accepted-new'), true), 'Atomic runtime consumes registry');
$method = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'versions_supported');
$fragment_supported = in_array($argv[1], array('fragment', 'core-only', 'both-patch', 'accepted-new'), true);
expect($method->invoke(null) === $fragment_supported, 'Fragment runtime cannot be widened by legacy filter');
if (!$fragment_supported) {
    $data = array(array('id' => 'x', 'elType' => 'widget', 'widgetType' => 'template',
        'settings' => array('iu_fragment_cache' => 'yes')));
    expect(IU_Elementor_Fragment_Cache::filter($data, 1) === $data, 'Unsupported version retains normal renderer data');
    expect(IU_Elementor_Fragment_Graph::inspect($data[0]) === null, 'Graph adapter uses registry');
    $manager = new class { public function register() { throw new RuntimeException('Unsupported proxy registered'); } };
    IU_Elementor_Fragment_Cache::proxy($manager);
    expect(IU_Elementor_Fragment_Excerpt::replay(array(array('hook' => 'excerpt_more', 'priority' => 20,
        'args' => 1, 'kind' => 'classic'))) === false, 'Excerpt adapter fails closed');
}
require IU_PLUGIN_PATH . 'includes/elementor-update-guard.php';
ob_start(); IU_Elementor_Update_Guard::notices(); $notice = ob_get_clean();
expect((strpos($notice, 'η ενεργή λειτουργία παρακάμπτεται') !== false) === !$fragment_supported, 'Fragment unsupported runtime notice');
ob_start(); iu_atomic_interaction_breakpoints_notice(); $notice = ob_get_clean();
expect(($notice !== '') === !in_array($argv[1], array('atomic', 'both-patch', 'accepted-new'), true), 'Atomic unsupported runtime notice');
$authorized = false;
ob_start(); IU_Elementor_Update_Guard::notices(); iu_atomic_interaction_breakpoints_notice(); $notice = ob_get_clean();
expect($notice === '', 'Runtime notices restricted to administrators');
echo 'PASS policy/runtime ' . $argv[1] . ' (' . count($targets) . ' pairs × four activation states)' . PHP_EOL;
