<?php
/** Offline pipeline regression: real cache/filter/capture/proxy methods, simulated WP/Elementor. */
define('ABSPATH', __DIR__);
define('ELEMENTOR_VERSION', '4.2.3');
define('ELEMENTOR_PRO_VERSION', '4.2.2');
$_SERVER = array('REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/', 'HTTP_HOST' => 'example.test', 'HTTP_USER_AGENT' => 'Desktop Browser');
$_GET = $_POST = $_COOKIE = $_SESSION = array();
$options = $transients = $events = array();
$renders = $loops = 0;
function add_action() {}
function add_filter() {}
function did_action() { return 0; }
function apply_filters($hook, $value) {
    if ($hook === 'iu_elementor_fragment_allow_query') throw new RuntimeException('Allow filter must not be needed');
    return $value;
}
function do_action($hook, ...$args) {
    if ($hook === 'iu_elementor_fragment_result') $GLOBALS['events'][] = $args[0];
}
function is_admin() { return false; }
function is_preview() { return false; }
function is_feed() { return false; }
function is_search() { return $GLOBALS['search'] ?? false; }
function is_404() { return $GLOBALS['not_found'] ?? false; }
function is_ssl() { return false; }
function get_current_user_id() { return 0; }
function get_locale() { return 'el'; }
function get_the_ID() { return 7; }
function wp_is_mobile() { return strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false; }
function get_option($key, $default = false) {
    if ($key === 'istodata_utilities_settings') return array('optimizations' => array('elementor_fragment_cache' => true));
    if ($key === 'elementor_element_cache_ttl') return 'disable';
    return $GLOBALS['options'][$key] ?? $default;
}
function add_option($key, $value, ...$args) {
    if (array_key_exists($key, $GLOBALS['options'])) return false;
    $GLOBALS['options'][$key] = $value; return true;
}
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['transients'][$key] = $value; return true; }
function wp_cache_delete() {}
function wp_generate_uuid4() { return 'test-owner'; }
function wp_json_encode($value) { return json_encode($value); }
function get_post_type_object() { return null; }
class Query_Queue {
    public $queue = array();
    public $registered = array('widget-css' => true, 'widget-js' => true);
    public function get_data() { return false; }
}
$styles = new Query_Queue(); $scripts = new Query_Queue();
function wp_styles() { return $GLOBALS['styles']; }
function wp_scripts() { return $GLOBALS['scripts']; }
function wp_style_is($handle, $state) { return isset(wp_styles()->registered[$handle]); }
function wp_script_is($handle, $state) { return isset(wp_scripts()->registered[$handle]); }
function wp_enqueue_style($handle) { $GLOBALS['styles']->queue[] = $handle; }
function wp_enqueue_script($handle) { $GLOBALS['scripts']->queue[] = $handle; }
$wpdb = new class {
    public $options = 'options';
    public function prepare($sql, $key) { return $key; }
    public function get_var($key) { return $GLOBALS['options'][$key] ?? null; }
    public function delete($table, $where, $formats) {
        if (($GLOBALS['options'][$where['option_name']] ?? null) === $where['option_value']) unset($GLOBALS['options'][$where['option_name']]);
    }
};
eval('namespace Elementor; class Plugin { public static $instance; } class Widget_Base {
    protected $data; public function __construct($data = array()) { $this->data = $data; }
    public function get_data($key) { return $this->data[$key] ?? null; }
    public function get_id() { return $this->data["id"] ?? ""; }
}');
class Query_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'public-query-fixture'; }
    public function get_style_depends() { return array('widget-css'); }
    public function get_script_depends() { return array('widget-js'); }
    public function print_element() {
        wp_enqueue_style('widget-css'); wp_enqueue_script('widget-js');
        IU_Elementor_Fragment_Cache::begin($this);
        ++$GLOBALS['renders'];
        echo '<nav>';
        for ($i = 0; $i < 3; ++$i) { ++$GLOBALS['loops']; echo '<a href="/public/">Public</a>'; }
        echo '</nav>';
        IU_Elementor_Fragment_Cache::finish($this);
    }
}
$manager = new class {
    public $proxy;
    public function register($widget) { $this->proxy = $widget; }
    public function get_widget_types($name) { return $name === 'public-query-fixture' ? new Query_Widget() : null; }
    public function create_element_instance($node) {
        if ($node['widgetType'] === 'iu-fragment-proxy') { $class = get_class($this->proxy); return new $class($node); }
        return new Query_Widget($node);
    }
};
\Elementor\Plugin::$instance = (object) array('editor' => null, 'preview' => null, 'widgets_manager' => $manager, 'elements_manager' => $manager);
require getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
IU_Elementor_Fragment_Cache::proxy($manager);
$node = array('id' => 'query-fixture', 'elType' => 'widget', 'widgetType' => 'public-query-fixture', 'settings' => array('iu_fragment_cache' => 'yes'));
function request_fragment($query, $path = '/') {
    global $node, $manager;
    $_GET = $query;
    $_SERVER['QUERY_STRING'] = http_build_query($query);
    $_SERVER['REQUEST_URI'] = $path . ($query ? '?' . $_SERVER['QUERY_STRING'] : '');
    $GLOBALS['styles']->queue = $GLOBALS['scripts']->queue = array();
    $nodes = IU_Elementor_Fragment_Cache::filter(array($node), 30);
    ob_start(); $manager->create_element_instance($nodes[0])->print_element(); $html = ob_get_clean();
    return array($html, $nodes[0]);
}
list($html, $miss) = request_fragment(array());
if (empty($miss['_iu_fragment_build']) || $renders !== 1 || $loops !== 3 || count($transients) !== 1 || end($events) !== 'stored') throw new RuntimeException('Clean GET did not capture/publish');
$key = array_key_first($transients);
foreach (array(array(), array('utm_source' => 'newsletter', 'utm_campaign' => 'autumn'), array('gclid' => 'test-click'),
    array('sort' => 'name', 'page' => '2'), array('filter' => array('brand' => 'public'), 'custom' => 'value'),
    array('preview' => 'false'), array('lang' => 'el')) as $query) {
    list($hit_html, $hit) = request_fragment($query);
    if ($hit['widgetType'] !== 'iu-fragment-proxy' || $hit_html !== $html || $renders !== 1 || $loops !== 3 ||
        array_keys($transients) !== array($key) || end($events) !== 'hit' || wp_styles()->queue !== array('widget-css') || wp_scripts()->queue !== array('widget-js')) {
        throw new RuntimeException('Query failed same-fragment zero-render hit: ' . json_encode($query));
    }
}
echo "clean-miss-and-query-hits: OK (1 widget / 3 loop bodies total; 7 hits, same HTML/key, CSS/JS replay)\n";
foreach (array('search' => array('s' => 'public term'), 'not_found' => array()) as $mode => $query) {
    $GLOBALS[$mode] = true;
    list($mode_html, $mode_node) = request_fragment($query, $mode === 'not_found' ? '/missing-page/' : '/');
    $GLOBALS[$mode] = false;
    if ($mode_node['widgetType'] !== 'iu-fragment-proxy' || $mode_html !== $html ||
        $renders !== 1 || $loops !== 3 || array_keys($transients) !== array($key) || end($events) !== 'hit' ||
        wp_styles()->queue !== array('widget-css') || wp_scripts()->queue !== array('widget-js')) {
        throw new RuntimeException($mode . ' request did not reuse the public fragment without rendering');
    }
}
echo "search-and-404-same-fragment-hits: OK (zero additional widget/loop bodies)\n";
// Reverse order: a query-bearing first request must seed the same fragment too.
$transients = array();
list($first_html) = request_fragment(array('utm_source' => 'first'));
request_fragment(array());
if ($renders !== 2 || $loops !== 6 || count($transients) !== 1 || $first_html !== $html || end($events) !== 'hit') throw new RuntimeException('Query-first miss did not share with clean GET');
echo "query-first-miss-clean-hit: OK\n";
$key_method = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'key');
if ($key_method->invoke(null, $node, 30, 'el', array()) === $key_method->invoke(null, $node, 30, 'en', array())) throw new RuntimeException('Language variant collapsed');
$_SERVER['HTTP_USER_AGENT'] = 'iPhone Mobile';
list($phone_html, $phone) = request_fragment(array('gclid' => 'phone'));
if (empty($phone['_iu_fragment_build']) || $renders !== 3 || count($transients) !== 2) throw new RuntimeException('Device variant collapsed');
request_fragment(array());
if ($renders !== 3 || $loops !== 9 || end($events) !== 'hit') throw new RuntimeException('Phone query fragment not reused');
echo "device-and-language-variants: OK\n";
$before = count($transients);
list($preview_html, $preview_node) = request_fragment(array('elementor-preview' => '30', 'utm_source' => 'editor'));
if (isset($preview_node['_iu_fragment_build']) || $preview_node['widgetType'] === 'iu-fragment-proxy' ||
    $renders !== 4 || $loops !== 12 || count($transients) !== $before || $preview_html !== $html) throw new RuntimeException('Explicit preview did not retain uncached pipeline');
echo "preview-full-pipeline-bypass: OK\n";
