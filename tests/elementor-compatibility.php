<?php
/** Isolated policy/runtime regressions; no WordPress/server mutation. */
if (!isset($argv[1])) {
    foreach (array('atomic', 'fragment', 'core-only', 'core-patch', 'pro-patch', 'both-patch', 'accepted-new', 'accepted-current', 'accepted-436', 'accepted-433', '436-old-pro', 'future-patch', 'future-pro', 'core-first', 'pro-first', 'future', 'missing') as $case) {
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
    'accepted-new' => array('4.3.4', '4.3.1'),
    'accepted-current' => array('4.3.5', '4.3.2'), 'core-first' => array('4.3.5', '4.3.1'),
    'pro-first' => array('4.3.4', '4.3.2'),
    'accepted-436' => array('4.3.6', '4.3.2'), 'accepted-433' => array('4.3.6', '4.3.3'), '436-old-pro' => array('4.3.6', '4.3.1'),
    'future-patch' => array('4.3.7', '4.3.2'), 'future-pro' => array('4.3.6', '4.3.4'),
    'future' => array('4.4.0', '4.4.0'), 'missing' => array(null, null));
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
function is_multisite() { return false; }
function get_current_blog_id() { return 1; }
function get_site_transient($name) { return (object) array('response' => array()); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return esc_html($value); }
function admin_url($path) { return 'https://example.test/wp-admin/' . $path; }
function network_admin_url($path) { return admin_url($path); }
function apply_filters($hook, $value) { return $hook === 'iu_elementor_fragment_versions_supported' ? true : $value; }
function expect($value, $message) { if (!$value) throw new RuntimeException($message); }
require IU_PLUGIN_PATH . 'includes/elementor-atomic-interaction-breakpoints.php';
require IU_PLUGIN_PATH . 'includes/elementor-fragment-cache.php';

$targets = array(
    array('4.3.2', '4.3.0', true, false), array('4.3.100', '4.3.100', true, true),
    array('4.3.3', '4.3.0', false, false), array('4.3.2', '4.3.1', false, false),
    array('4.3.3', '4.3.1', true, true), array('4.3.4', '4.3.1', true, true), array('4.3.5', '4.3.1', false, false), array('4.3.3', '4.3.2', false, false),
    // Only the reviewed final exact pair is accepted; intermediate pairs remain closed.
    array('4.3.5', '4.3.2', true, true), array('4.3.4', '4.3.2', false, false),
    array('4.3.6', '4.3.2', true, true), array('4.3.6', '4.3.1', false, false),
    array('4.3.7', '4.3.2', false, false), array('4.3.6', '4.3.3', true, true), array('4.3.6', '4.3.4', true, true),
    array('4.2.3', '4.2.2', false, true), array('4.2.3', null, false, true),
    array('4.3.1', '4.3.0', false, false), array('4.3.2', '4.2.2', false, false),
    array('4.2.3', '4.3.0', false, false), array('4.3.2', null, false, false),
    array('4.2.4', '4.2.2', false, false), array('4.2.3', '4.2.3', false, false),
    array('4.4.0', '4.4.0', true, true), array('4.4.1', '4.4.2', true, true), array('4.5.0', '4.4.0', false, false),
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
// Frozen additive contract: every previously accepted pair/core-only path plus 4.3.6/4.3.3.
$accepted_contract = array(
    'atomic' => array(array('4.3.2','4.3.0'),array('4.3.3','4.3.1'),array('4.3.4','4.3.1'),array('4.3.5','4.3.2'),array('4.3.6','4.3.2'),array('4.3.6','4.3.3')),
    'fragment_atomic' => array(array('4.3.3','4.3.1'),array('4.3.4','4.3.1'),array('4.3.5','4.3.2'),array('4.3.6','4.3.2'),array('4.3.6','4.3.3')),
    'fragment' => array(array('4.2.3','4.2.2'),array('4.2.3',null),array('4.3.3','4.3.1'),array('4.3.4','4.3.1'),array('4.3.5','4.3.2'),array('4.3.6','4.3.2'),array('4.3.6','4.3.3')),
);
foreach (iu_elementor_compatibility_registry() as $feature => $entry) {
    $actual = array_map(static function ($declaration) {
        return array($declaration['core']['exact'], $declaration['pro']['exact'] ?? null);
    }, $entry['accepted']);
    expect($actual === $accepted_contract[$feature], 'Keep the complete accepted contract additive and exact');
    foreach ($accepted_contract[$feature] as [$a, $b]) {
        expect(iu_elementor_feature_supported($feature, array('core'=>$a,'pro'=>$b)), 'Every historic accepted pair remains supported');
    }
    foreach (array('4.3.5','4.3.6') as $reviewed_core) {
        $evidence = array_filter($entry['tested'], static function ($row) use ($reviewed_core) {
            return $row['core'] === $reviewed_core && $row['pro'] === '4.3.2';
        });
        expect(count($evidence) === 1, 'Keep old/new evidence separate and unique');
    }
    $review = array_filter($entry['tested'], static function ($row) {
        return $row['core'] === '4.3.6' && $row['pro'] === '4.3.3';
    });
    expect(count($review) === 1 && strpos(reset($review)['scope'], 'unverified') !== false, 'Keep 4.3.3 review limits explicit');
}


$policy = array('core'=>'4.4.0','pro'=>'4.4.0');
expect(iu_elementor_feature_status('atomic', $policy) === 'policy', '4.4 interactions policy allowance');
expect(iu_elementor_feature_status('fragment', $policy) === 'policy' && iu_elementor_feature_status('fragment_atomic', $policy) === 'policy', '4.4 cache allowed under policy, not labeled individually tested');
foreach (array('atomic','fragment','fragment_atomic') as $feature) {
    expect(iu_elementor_feature_status($feature, array('core'=>'4.3.6','pro'=>'4.3.3')) === 'accepted', 'Old pair remains accepted');
    expect(iu_elementor_feature_status($feature, array('core'=>'4.4.0','pro'=>'4.4.0')) === 'policy', '4.4 initial pair allowed under policy');
    expect(iu_elementor_feature_status($feature, array('core'=>'4.4.9','pro'=>'4.4.10')) === 'policy', 'Future patches allowed only under policy');
    foreach (array('4.4.0-rc1','4.5.0','5.0.0','4.4.0.1') as $bad) expect(!iu_elementor_feature_supported($feature,array('core'=>$bad,'pro'=>'4.4.0')), 'Range boundary or prerelease');
    expect(iu_elementor_feature_status($feature, array('core'=>'4.3.6','pro'=>'4.3.3')) === 'accepted', 'Old pair restores after unsupported transition');
    $actual440 = array_filter(iu_elementor_compatibility_registry()[$feature]['tested'], static function ($row) { return $row['core'] === '4.4.0' && $row['pro'] === '4.4.0'; });
    expect(count($actual440) === 1, '4.4 evidence is unique');
    $scope440 = reset($actual440)['scope'];
    expect(strpos($scope440, 'not tested') !== false || strpos($scope440, 'no 4.4') !== false, '4.4 evidence records limits');
    foreach (iu_elementor_compatibility_registry()[$feature]['tested'] as $evidence) expect($evidence['core'] !== '4.4.9', 'Future policy allowance cannot fabricate test evidence');
}
$entry = array('accepted'=>array(array('core'=>array('exact'=>'4.3.6'),'pro'=>array('exact'=>'4.3.3'))),
 'policy_ranges'=>array(array('core'=>array('min'=>'4.3.6','before'=>'4.4.0'),'pro'=>array('min'=>'4.3.3','before'=>'4.4.0'))),
 'known_bad'=>array(array('core'=>array('exact'=>'4.3.6'),'pro'=>array('exact'=>'4.3.3'),'reason'=>'test')));
expect(iu_elementor_entry_status($entry,array('core'=>'4.3.6','pro'=>'4.3.3')) === 'blocked', 'Known bad overrides exact and range');
expect(iu_elementor_entry_status($entry,array('core'=>'4.3.7','pro'=>'4.3.4')) === 'policy', 'Unaffected patch remains policy allowed');
expect(!iu_elementor_version_matches('4.3.9',array('min'=>'4.3.0','before'=>'5.0.0')), 'Cross-major range rejected');

$core_deny = $entry;
$core_deny['known_bad'] = array(array('core'=>array('exact'=>'4.3.7'),'pro'=>array('any'=>true),'reason'=>'Core issue'));
foreach (array('4.3.3','4.3.4','4.3.999',null) as $counterpart) expect(iu_elementor_entry_status($core_deny,array('core'=>'4.3.7','pro'=>$counterpart)) === 'blocked', 'Bad core across any counterpart');
$pro_deny = $entry;
$pro_deny['known_bad'] = array(array('core'=>array('any'=>true),'pro'=>array('exact'=>'4.3.4'),'reason'=>'Pro issue'));
foreach (array('4.3.6','4.3.7','4.3.999',null) as $counterpart) expect(iu_elementor_entry_status($pro_deny,array('core'=>$counterpart,'pro'=>'4.3.4')) === 'blocked', 'Bad Pro across any counterpart');
expect(iu_elementor_entry_status($entry,array('core'=>'4.3.7','pro'=>'4.3.4')) === 'policy', 'Other feature remains unaffected');
expect(!iu_elementor_pair_matches(array('core'=>'4.3.7','pro'=>'4.3.4'),array('core'=>array('any'=>true),'pro'=>array('exact'=>'4.3.4'))), 'Wildcard cannot grant support');


expect(iu_elementor_feature_supported('unknown') === false, 'Unknown features fail closed');
foreach (array('atomic', 'fragment', 'fragment_atomic') as $feature) {
    $evidence = array_filter(iu_elementor_compatibility_registry()[$feature]['tested'], function ($pair) {
        return $pair['core'] === '4.3.5' && $pair['pro'] === '4.3.2';
    });
    expect(count($evidence) === 1, 'Keep tested evidence separate and unique');
    expect(iu_elementor_feature_supported($feature, array('core' => '4.3.5', 'pro' => '4.3.2')), 'Reviewed final exact pair accepted');
}
foreach (iu_elementor_compatibility_registry() as $entry) foreach ($entry['accepted'] as $pair) foreach ($pair as $rule) {
    expect($rule === null || array_keys($rule) === array('exact'), 'Approval must be explicit; no unbounded ranges');
}
expect(!iu_elementor_version_matches('4.3.3', array('series' => '4.3', 'min' => '4.3.2')), 'Legacy open-ended rule must fail closed');
expect(iu_elementor_common_pair(array('atomic', 'fragment')) === array('core' => '4.3.3', 'pro' => '4.3.1'), 'Derive tested common pair');
expect(iu_elementor_common_pair(array('atomic')) === array('core' => '4.3.2', 'pro' => '4.3.0'), 'Derive Atomic baseline from registry');
expect(iu_atomic_interaction_breakpoints_supported() === in_array($argv[1], array('atomic', 'both-patch', 'accepted-new', 'accepted-current', 'accepted-436', 'accepted-433', 'future-pro', 'future'), true), 'Atomic runtime consumes registry');
$method = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'versions_supported');
$fragment_supported = in_array($argv[1], array('fragment', 'core-only', 'both-patch', 'accepted-new', 'accepted-current', 'accepted-436', 'accepted-433', 'future-pro', 'future'), true);
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
expect(($notice !== '') === !($fragment_supported && iu_atomic_interaction_breakpoints_supported()), 'One combined unsupported-feature notice');
expect(substr_count($notice, 'notice-warning') <= 1, 'Runtime features aggregate into one notice');
ob_start(); iu_atomic_interaction_breakpoints_notice(); $notice = ob_get_clean();
expect($notice === '', 'Legacy Atomic notice does not duplicate shared notice');
$authorized = false;
ob_start(); IU_Elementor_Update_Guard::notices(); iu_atomic_interaction_breakpoints_notice(); $notice = ob_get_clean();
expect($notice === '', 'Runtime notices restricted to administrators');
echo 'PASS policy/runtime ' . $argv[1] . ' (' . count($targets) . ' pairs × four activation states)' . PHP_EOL;
