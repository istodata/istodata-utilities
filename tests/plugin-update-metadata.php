<?php
/** Real updater functions, isolated requests, controlled WordPress HTTP/cache boundaries. */
$cases = array('manual', 'routine', 'unauthorized', 'frontend', 'wrong-screen', 'wrong-hook', 'no-user',
    'cron', 'ajax', 'rest', 'cli', 'post', 'action', 'array-param', 'missing-param', 'network',
    'multisite-site', 'network-no-cap', 'network-wrong-screen', 'error', '429', '403', 'bad-json',
    'missing-package', 'bad-tag', 'retry-date', 'fallback', 'no-fallback', 'throttled', 'cold', 'earlier-fetch', 'earlier-cache');
if (!isset($argv[1])) {
    foreach ($cases as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $exit);
        if ($exit) exit($exit);
    }
    echo 'PASS Kit update metadata: ' . count($cases) . " isolated manual/routine/security/fallback/budget requests\n";
    exit;
}
$case = $argv[1];
function check($value, $message) { if (!$value) throw new RuntimeException($GLOBALS['case'] . ': ' . $message); }
define('DAY_IN_SECONDS', 86400); define('HOUR_IN_SECONDS', 3600); define('MINUTE_IN_SECONDS', 60);
define('IU_GITHUB_API_URL', 'https://api.github.com/repos/istodata/istodata-utilities/releases/latest');
define('IU_GITHUB_REPO_URL', 'https://github.com/istodata/istodata-utilities');
define('IU_PLUGIN_VERSION', '2.23.0'); define('IU_PLUGIN_SLUG', 'istodata-utilities');
define('IU_PLUGIN_BASENAME', 'istodata-utilities/istodata-utilities.php'); define('IU_PLUGIN_URL', 'https://example.test/kit/');
if ($case === 'rest') define('REST_REQUEST', true);
if ($case === 'cli') define('WP_CLI', true);
$hooks = array(); $transients = array(); $site_transients = array(); $options = array(); $ttl = array();
$http_calls = 0; $current_hook = 'load-update-core.php'; $admin = true; $logged_in = true;
$screen = (object) array('base' => 'update-core', 'id' => 'update-core'); $pagenow = 'update-core.php';
$multisite = false; $network = false; $caps = array('update_plugins' => true, 'manage_network_plugins' => true);
$_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = array('force-check' => '1');
function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][$priority][] = $callback; }
function add_action($hook, $callback, $priority = 10, $args = 1) { add_filter($hook, $callback, $priority, $args); }
function current_filter() { return $GLOBALS['current_hook']; }
function get_current_screen() { return $GLOBALS['screen']; }
function is_admin() { return $GLOBALS['admin']; }
function is_user_logged_in() { return $GLOBALS['logged_in']; }
function current_user_can($cap) { return $GLOBALS['caps'][$cap] ?? false; }
function is_multisite() { return $GLOBALS['multisite']; }
function is_network_admin() { return $GLOBALS['network']; }
function wp_doing_cron() { return $GLOBALS['case'] === 'cron'; }
function wp_doing_ajax() { return $GLOBALS['case'] === 'ajax'; }
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key, $value, $seconds) { $GLOBALS['transients'][$key] = $value; $GLOBALS['ttl'][$key] = $seconds; }
function get_site_transient($key) { return $GLOBALS['site_transients'][$key] ?? false; }
function set_site_transient($key, $value, $seconds = 0) {
    if ($key === 'update_plugins') {
        foreach ($GLOBALS['hooks']['pre_set_site_transient_update_plugins'][10] as $callback) $value = $callback($value);
    }
    $GLOBALS['site_transients'][$key] = $value; $GLOBALS['ttl'][$key] = $seconds;
}
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, $autoload = false) { $GLOBALS['options'][$key] = $value; }
class WP_Error {}
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_bloginfo($key) { return '6.9'; }
function get_site_url() { return 'https://example.test'; }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function wp_remote_get($url, $args) {
    $GLOBALS['http_calls']++; check($url === IU_GITHUB_API_URL && $args['sslverify'] === true, 'Expected verified official request');
    return $GLOBALS['reply'];
}
function wp_remote_retrieve_response_code($r) { return $r['code'] ?? 0; }
function wp_remote_retrieve_body($r) { return $r['body'] ?? ''; }
function wp_remote_retrieve_header($r, $name) { return $r['headers'][$name] ?? ''; }
$source = file_get_contents(dirname(__DIR__) . '/istodata-utilities.php');
$start = strpos($source, '// Hook into WordPress update system');
$end = strpos($source, '// Clear update cache after plugin update', $start);
check($start !== false && $end !== false, 'Updater source boundaries exist');
eval(substr($source, $start, $end - $start));
check(in_array('iu_manual_plugin_update_check', $hooks['load-update-core.php'][1], true), 'Refresh runs before core load-update callbacks');
$key = 'iu_remote_version_' . md5(IU_GITHUB_API_URL); $fallback = 'iu_remote_version_fallback_' . md5(IU_GITHUB_API_URL);
$retry = 'iu_remote_version_retry_' . md5(IU_GITHUB_API_URL);
$old = (object) array('new_version' => '2.23.1', 'download_url' => 'https://github.com/old.zip',
    'name' => 'ISTODATA Kit', 'slug' => IU_PLUGIN_SLUG, 'author' => 'ISTODATA', 'homepage' => IU_GITHUB_REPO_URL,
    'requires' => '5.0', 'tested' => '6.8.1', 'requires_php' => '7.4', 'last_updated' => '', 'description' => '', 'changelog' => '');
$transients[$key] = $old; $options[$fallback] = $old;
$release = (object) array('tag_name' => 'v2.23.2', 'assets' => array((object) array('name' => 'istodata-utilities.zip', 'browser_download_url' => 'https://github.com/new.zip')), 'published_at' => '2026-10-26', 'body' => 'Fix');
$reply = array('code' => 200, 'body' => json_encode($release), 'headers' => array());
$other = (object) array('new_version' => '9.0');
$site_transients['update_plugins'] = (object) array('checked' => array(IU_PLUGIN_BASENAME => IU_PLUGIN_VERSION), 'last_checked' => 123, 'response' => array('other/plugin.php' => $other));
$denied = in_array($case, array('routine', 'unauthorized', 'frontend', 'wrong-screen', 'wrong-hook', 'no-user', 'cron', 'ajax', 'rest', 'cli', 'post', 'action', 'array-param', 'missing-param', 'multisite-site', 'network-no-cap', 'network-wrong-screen'), true);
switch ($case) {
    case 'routine': case 'missing-param': $_GET = array(); break;
    case 'unauthorized': $caps['update_plugins'] = false; break;
    case 'frontend': $admin = false; break;
    case 'wrong-screen': $screen->base = 'plugins'; break;
    case 'wrong-hook': $current_hook = 'admin_init'; break;
    case 'no-user': $logged_in = false; break;
    case 'post': $_SERVER['REQUEST_METHOD'] = 'POST'; break;
    case 'action': $_GET['action'] = 'do-plugin-upgrade'; break;
    case 'array-param': $_GET['force-check'] = array('1'); break;
    case 'network': case 'network-no-cap': case 'network-wrong-screen':
        $multisite = $network = true; $screen->id = 'update-core-network';
        if ($case === 'network-no-cap') $caps['manage_network_plugins'] = false;
        if ($case === 'network-wrong-screen') $screen->id = 'update-core';
        break;
    case 'multisite-site': $multisite = true; break;
    case 'error': case 'fallback': case 'no-fallback': $reply = new WP_Error; break;
    case '429': $reply = array('code' => 429, 'headers' => array('retry-after' => '300')); break;
    case '403': $reply = array('code' => 403, 'headers' => array('x-ratelimit-reset' => (string) (time() + 600))); break;
    case 'bad-json': $reply['body'] = '{'; break;
    case 'missing-package': $reply['body'] = '{"tag_name":"v2.23.2"}'; break;
    case 'bad-tag': $reply['body'] = json_encode((object) array('tag_name' => 'v', 'zipball_url' => 'https://github.com/source.zip')); break;
    case 'retry-date': $reply = array('code' => 429, 'headers' => array('retry-after' => gmdate('D, d M Y H:i:s', time() + 300) . ' GMT')); break;
    case 'throttled': $site_transients[$retry] = true; break;
}
if (in_array($case, array('fallback', 'no-fallback', 'cold', 'earlier-fetch'), true)) unset($transients[$key]);
if ($case === 'no-fallback') unset($options[$fallback]);
if ($case === 'earlier-fetch' || $case === 'earlier-cache') {
    $current_hook = 'admin_init'; iu_get_remote_version(); $current_hook = 'load-update-core.php';
}
iu_manual_plugin_update_check();
$result = iu_get_remote_version(true); // Public true argument alone must never grant a force bypass.
$updates = iu_check_for_plugin_update(clone $site_transients['update_plugins']);
$info = iu_plugin_api_call(false, 'plugin_information', (object) array('slug' => IU_PLUGIN_SLUG));
iu_get_remote_version();
if ($denied) {
    check($http_calls === 0 && $result === $old, 'Unauthorized/nonmanual context retains cached data');
    check(!isset($site_transients[$retry]) && !isset($GLOBALS['iu_manual_plugin_update_status']), 'No manual throttle or notice state');
} elseif (in_array($case, array('error', '429', '403', 'bad-json', 'missing-package', 'bad-tag', 'retry-date', 'fallback', 'no-fallback', 'throttled'), true)) {
    check($http_calls === ($case === 'throttled' ? 0 : 1), 'At most one failed attempt including plugins-info');
    check($result === ($case === 'no-fallback' ? false : $old), 'Preserve last known good result');
    check(($options[$fallback] ?? false) === ($case === 'no-fallback' ? false : $old), 'Never overwrite fallback on failure');
    if ($result) check($ttl[$key] === 6 * HOUR_IN_SECONDS, 'Fallback cached for six hours');
    if ($case === '429') check($ttl[$retry] === 300, 'Respect Retry-After');
    if ($case === 'retry-date') check($ttl[$retry] >= 299 && $ttl[$retry] <= 300, 'Respect HTTP-date Retry-After');
    if ($case === '403') check($ttl[$retry] >= 599, 'Respect rate-limit reset');
    ob_start(); iu_manual_plugin_update_notice(); $notice = ob_get_clean(); check(strpos($notice, 'notice-warning') !== false, 'Failure/cooldown is disclosed');
} else {
    check($http_calls === 1, 'One HTTP fetch shared with WP update/plugin-info callbacks');
    check($result->new_version === '2.23.2' && $info->version === '2.23.2', 'New release visible in both callbacks');
    check($site_transients['update_plugins']->response[IU_PLUGIN_BASENAME]->new_version === '2.23.2', 'Existing WP checked transient updated during load hook');
    check($site_transients['update_plugins']->last_checked === 123 && $updates->response['other/plugin.php'] === $other, 'Preserve WP timing and unrelated response');
    check($ttl[$key] === DAY_IN_SECONDS && $ttl[$retry] === MINUTE_IN_SECONDS, 'Routine24h cache and bounded retry budget');
    check($GLOBALS['iu_manual_plugin_update_status'] === 'fresh', 'Only successful response is fresh');
}
