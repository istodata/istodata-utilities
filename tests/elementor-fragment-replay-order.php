<?php
/** Prove replay happens at the render slot and unsafe intervening context falls back. */
namespace ElementorPro\Modules\QueryControl {
    class Module {
        public static $ids = array(10);
        public static function get_avoid_list_ids() { return self::$ids; }
        public static function add_to_avoid_list($ids) { self::$ids = array_merge(self::$ids, $ids); }
    }
}
namespace Elementor { class Plugin { public static $instance; } }
namespace {
    define('ABSPATH', __DIR__);
    function add_action() {}
    function add_filter() {}
    function did_action() { return 0; }
    function get_the_ID() { return 7; }
    function get_option($key) { return $key === 'istodata_utilities_settings' ? array('optimizations' => array('elementor_fragment_cache' => $GLOBALS['global_on'] ?? true)) : 'existing-epoch'; }
    function get_post($id) { return (object) array('post_status' => $GLOBALS['post_status'] ?? 'publish', 'post_password' => ''); }
    $wpdb = new class {
        public $options = 'options';
        public function prepare($sql, $name) { return $name; }
        public function get_var($name) { return $GLOBALS['db_epoch'] ?? 'existing-epoch'; }
    };
    function do_action() {}
    function update_option() { ++$GLOBALS['invalidations']; }
    function wp_generate_uuid4() { return 'test-epoch'; }
    $include = getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
    require $include;
    \Elementor\Plugin::$instance = (object) array('elements_manager' => new class {
        public $renders = 0;
        public function create_element_instance($node) {
            ++$this->renders;
            return new class { public function print_element() { echo 'fallback'; } };
        }
    });
    $entry = array('format' => IU_Elementor_Fragment_Cache::FORMAT, 'html' => 'cached',
        'generation' => array('document_id' => 1, 'element_id' => 'target', 'site' => 'existing-epoch', 'element' => 'existing-epoch'),
        'fresh_until' => time() + 3600, 'stale_until' => time() + 3780,
        'styles' => array(), 'scripts' => array(), 'excerpt' => array(), 'excerpt_before' => array(), 'displayed' => array(20));
    $node = array('id' => 'target', 'widgetType' => 'heading');
    $replacement = new \ReflectionMethod('IU_Elementor_Fragment_Cache', 'replacement');
    $proxy = $replacement->invoke(null, $node, $entry, array(10));
    if (\ElementorPro\Modules\QueryControl\Module::$ids !== array(10)) {
        throw new \RuntimeException('Builder traversal replayed IDs too early');
    }
    ob_start();
    IU_Elementor_Fragment_Cache::output($proxy['_iu_fragment_payload']);
    $html = ob_get_clean();
    if ($html !== 'cached' || \ElementorPro\Modules\QueryControl\Module::$ids !== array(10, 20) ||
        \Elementor\Plugin::$instance->elements_manager->renders !== 0) {
        throw new \RuntimeException('Hit failed to bypass original widget at render position');
    }
    \ElementorPro\Modules\QueryControl\Module::$ids = array(10, 11);
    ob_start();
    IU_Elementor_Fragment_Cache::output($proxy['_iu_fragment_payload']);
    $html = ob_get_clean();
    if ($html !== 'fallback' || \ElementorPro\Modules\QueryControl\Module::$ids !== array(10, 11)) {
        throw new \RuntimeException('Intervening query did not safely bypass cache');
    }
    $entry['independent_query'] = true;
    $entry['preserve_excerpt'] = true;
    $entry['displayed'] = array(10, 20);
    $proxy = $replacement->invoke(null, $node, $entry, array(99));
    \ElementorPro\Modules\QueryControl\Module::$ids = array(11);
    ob_start(); IU_Elementor_Fragment_Cache::output($proxy['_iu_fragment_payload']); $html = ob_get_clean();
    if ($html !== 'cached' || \ElementorPro\Modules\QueryControl\Module::$ids !== array(11, 10, 20)) {
        throw new \RuntimeException('Independent query failed to replay all IDs, including IDs present in the builder request');
    }
    // Earlier siblings register a live external excerpt filter after key traversal.
    $filter = function ($excerpt) { return $excerpt; };
    $GLOBALS['wp_filter']['excerpt_more'] = (object) array('callbacks' => array(20 => array('external' => array('function' => $filter, 'accepted_args' => 1))));
    $live_before = IU_Elementor_Fragment_Excerpt::profile();
    ob_start(); IU_Elementor_Fragment_Cache::output($proxy['_iu_fragment_payload']); $html = ob_get_clean();
    if ($html !== 'cached' || IU_Elementor_Fragment_Excerpt::profile() !== $live_before) throw new \RuntimeException('No-effect hit mutated live preceding filters');
    $mutating = $proxy['_iu_fragment_payload']; $mutating['entry']['preserve_excerpt'] = false;
    ob_start(); IU_Elementor_Fragment_Cache::output($mutating); $html = ob_get_clean();
    if ($html !== 'fallback') throw new \RuntimeException('Mutating fragment ignored changed render-slot input');
    unset($GLOBALS['wp_filter']['excerpt_more']);
    $capture = new \ReflectionProperty('IU_Elementor_Fragment_Cache', 'capture');
    $capture->setValue(null, array(array('build' => array('independent_query' => true), 'query_ids' => array())));
    IU_Elementor_Fragment_Cache::query_results((object) array('posts' => array((object) array('ID' => 10), (object) array('ID' => 20))), null);
    if ($capture->getValue(null)[0]['query_ids'] !== array(10, 20)) throw new \RuntimeException('Query observer lost full result IDs');
    $capture->setValue(null, array());
    foreach (array('global_on' => false, 'db_epoch' => 'purged', 'post_status' => 'private') as $flag => $value) {
        $GLOBALS[$flag] = $value;
        ob_start(); IU_Elementor_Fragment_Cache::output($proxy['_iu_fragment_payload']); $html = ob_get_clean();
        if ($html !== 'fallback') throw new \RuntimeException('Unsafe reuse admitted: ' . $flag);
        unset($GLOBALS[$flag]);
    }
    $GLOBALS['invalidations'] = 0;
    IU_Elementor_Fragment_Cache::meta_changed(1, 30, '_elementor_page_assets', array());
    IU_Elementor_Fragment_Cache::meta_changed(1, 30, '_elementor_data', 'changed');
    IU_Elementor_Fragment_Cache::meta_changed(1, 42, 'brand_image', 'changed');
    IU_Elementor_Fragment_Cache::meta_changed(1, 30, '_elementor_page_settings', 'changed');
    if ($GLOBALS['invalidations'] !== 2) throw new \RuntimeException('Content invalidation guard failed');
    IU_Elementor_Fragment_Cache::settings_changed(array(),array());
    if ($GLOBALS['invalidations'] !== 2) throw new \RuntimeException('Default OFF acquired generation');
    IU_Elementor_Fragment_Cache::settings_changed(array('optimizations'=>array('elementor_fragment_cache'=>true)),array());
    if ($GLOBALS['invalidations'] !== 3) throw new \RuntimeException('Global OFF failed to fence in-flight writers');
    echo "replay-order-and-content-invalidation: OK\n";
}
