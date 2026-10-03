<?php
/** Verify waiters see another worker's publication with both cache backends. */
define('ABSPATH', __DIR__);
function add_action() {}
function add_filter() {}
function did_action() { return 0; }
function wp_using_ext_object_cache() { return $GLOBALS['external']; }
function wp_cache_get($key, $group, $force = false) {
    if ($group === 'transient') {
        if (!$force) throw new RuntimeException('Shared-cache poll did not force read');
        return $GLOBALS['published'];
    }
    return $GLOBALS['cache'][$key] ?? false;
}
function wp_cache_set($key, $value, $group) { $GLOBALS['cache'][$key] = $value; }
function wp_cache_delete($key, $group) { unset($GLOBALS['cache'][$key]); }
function get_transient($key) {
    if (isset($GLOBALS['cache']['notoptions']['_transient_' . $key]) ||
        array_key_exists('_transient_' . $key, $GLOBALS['cache'])) return false;
    return $GLOBALS['published'];
}
$include = getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
require $include;
$GLOBALS['published'] = array('html' => 'another worker');
$GLOBALS['cache'] = array('notoptions' => array('_transient_entry' => true, 'unrelated' => true), '_transient_entry' => false);
$GLOBALS['external'] = false;
$poll = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'fresh_entry');
if ($poll->invoke(null, 'entry') !== $GLOBALS['published'] || !isset($GLOBALS['cache']['notoptions']['unrelated'])) {
    throw new RuntimeException('Database transient remained hidden behind request-local miss');
}
$GLOBALS['external'] = true;
if ($poll->invoke(null, 'entry') !== $GLOBALS['published']) throw new RuntimeException('External cache publication invisible');
$valid = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'valid_entry');
$entry = array('format' => IU_Elementor_Fragment_Cache::FORMAT, 'html' => 'cached',
    'styles' => array(), 'scripts' => array(), 'excerpt' => array(), 'displayed' => array(),
    'fresh_until' => time() - 1, 'stale_until' => time() + 180);
if (!$valid->invoke(null, $entry)) throw new RuntimeException('Grace entry invalid');
$entry['stale_until'] = time() - 1;
if ($valid->invoke(null, $entry)) throw new RuntimeException('Expired grace entry remains replayable');
echo "fresh-poll-and-stale-expiry: OK\n";
