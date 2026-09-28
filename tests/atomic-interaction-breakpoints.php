<?php
// Isolated WordPress loader/notice checks. Each case uses a new PHP process (static guards).
$version_cases = array(
    'elementor-patch' => array('4.3.3', '4.3.0'),
    'pro-patch' => array('4.3.2', '4.3.1'),
    'both-patch' => array('4.3.8', '4.3.5'),
    'large-patch' => array('4.3.100', '4.3.100'),
    'unsupported' => array('4.4.0', '4.3.0'),
    'pro-minor' => array('4.3.2', '4.4.0'),
    'both-minor' => array('4.4.1', '4.4.1'),
    'old-elementor' => array('4.3.1', '4.3.0'),
    'old-minor' => array('4.2.9', '4.3.0'),
    'old-pro' => array('4.3.2', '4.2.9'),
    'prerelease' => array('4.3.3-beta1', '4.3.0'),
    'pro-prerelease' => array('4.3.2', '4.3.1-rc1'),
    'malformed' => array('4.3.3.1', '4.3.0'),
    'invalid-type' => array(4.3, '4.3.0'),
    'major' => array('5.0.0', '5.0.0'),
);
$allowed_cases = array('supported', 'late', 'elementor-patch', 'pro-patch', 'both-patch', 'large-patch');
$rejected_cases = array_merge(array('missing'), array_diff(array_keys($version_cases), $allowed_cases));
if (!isset($argv[1])) {
    foreach (array_merge(array('off', 'missing', 'no-handle', 'done', 'admin', 'supported', 'late', 'retry'), array_keys($version_cases)) as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $status);
        if ($status !== 0) {
            exit($status);
        }
    }
    exit;
}
$case = $argv[1];
define('ABSPATH', __DIR__ . '/');
define('IU_PLUGIN_PATH', dirname(__DIR__) . '/');
if ($case !== 'missing') {
    $versions = isset($version_cases[$case]) ? $version_cases[$case] : array('4.3.2', '4.3.0');
    define('ELEMENTOR_VERSION', $versions[0]);
    define('ELEMENTOR_PRO_VERSION', $versions[1]);
}
$settings = array('optimizations' => array('elementor_atomic_interaction_breakpoints' => $case !== 'off'));
$enqueued = !in_array($case, array('no-handle', 'late'), true);
$calls = array();
$hooks = array();
$allow_inline = $case !== 'retry';
function get_option($name, $default = array()) { return $GLOBALS['settings']; }
function is_admin() { return $GLOBALS['case'] === 'admin'; }
function current_user_can($capability) { return true; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_script_is($handle, $state) {
    if ($handle !== 'elementor-interactions-pro') { throw new RuntimeException('Unexpected handle'); }
    return $state === 'enqueued' ? $GLOBALS['enqueued'] : $GLOBALS['case'] === 'done';
}
function wp_add_inline_script($handle, $script, $position) {
    $GLOBALS['calls'][] = array($handle, $script, $position);
    return $GLOBALS['allow_inline'];
}
function add_action($hook, $callback, $priority = 10) { $GLOBALS['hooks'][] = array($hook, $callback, $priority); }
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
require IU_PLUGIN_PATH . 'includes/elementor-atomic-interaction-breakpoints.php';
iu_init_atomic_interaction_breakpoints();
iu_init_atomic_interaction_breakpoints();
check(count($hooks) === 3, 'Initializer must be idempotent');
check($hooks[0][2] === 1000 && $hooks[1][2] === 1, 'Enqueue and footer priorities');
iu_attach_atomic_interaction_breakpoints();
if ($case === 'late') { check(count($calls) === 0, 'Not enqueued yet'); $enqueued = true; }
if ($case === 'retry') { $allow_inline = true; }
iu_attach_atomic_interaction_breakpoints();
iu_attach_atomic_interaction_breakpoints();
$expected = in_array($case, $allowed_cases, true) ? 1 : ($case === 'retry' ? 2 : 0);
check(count($calls) === $expected, 'OFF/version/admin/handle guards and once-only successful attach');
if ($expected) {
    check($calls[0][2] === 'before', 'Must precede Pro capture');
    check($calls[0][1] === file_get_contents(IU_PLUGIN_PATH . 'assets/js/elementor-atomic-interaction-breakpoints.js'), 'Exact plugin JS payload');
}
ob_start();
iu_atomic_interaction_breakpoints_notice();
$notice = ob_get_clean();
check(($notice !== '') === in_array($case, $rejected_cases, true), 'Only enabled version outside the accepted range produces notice');
echo 'PASS: PHP loader ' . $case . PHP_EOL;
