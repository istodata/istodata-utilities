<?php
/** Arbitrary-ID graph and builder-order safety regressions; no metrica adapter. */
namespace Elementor { class Plugin { public static $instance; } class Widget_Base {} }
namespace Elementor\Modules\Interactions\Cache { class Interactions_Postmeta {
    public function load_content($id) { return $GLOBALS['interaction_rows'][$id] ?? array(); }
} }
namespace {
define('ABSPATH', __DIR__ . '/');
define('ELEMENTOR_VERSION', '4.2.3');
define('ELEMENTOR_PRO_VERSION', '4.2.2');
function add_action() {}
function add_filter() {}
function did_action() { return 0; }
function apply_filters($hook, $value) { return $hook === 'iu_elementor_fragment_prototype_supported' ? ($GLOBALS['approved_fake_prototypes'] ?? false) : $value; }
function do_action($hook, ...$args) { if ($hook === 'iu_elementor_fragment_graph_rejected') $GLOBALS['rejection'] = $args[0]; }
function wp_json_encode($value) { return json_encode($value); }
function absint($value) { return abs((int) $value); }
function get_post_meta($id, $key, $single) { return json_encode($GLOBALS['templates'][$id] ?? null); }
function get_post_status($id) { return $GLOBALS['statuses'][$id] ?? 'publish'; }
function get_post_type_object($type) { return in_array($type, array('post', 'partner', 'product'), true) ? (object) array('public' => true) : null; }
function get_option($key, $default = false) { return $default; }
function get_locale() { return 'el'; }
function get_queried_object_id() { return 7; }
function is_front_page() { return true; }
function get_the_ID() { return 7; }
function is_ssl() { return true; }
function iu_request_is_phone() { return strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false; }
function wp_is_mobile() { return preg_match('/iPhone|iPad/', $_SERVER['HTTP_USER_AGENT']) === 1; }
$include = getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
require $include;
\Elementor\Plugin::$instance = (object) array('widgets_manager' => new class {
    public function get_widget_types($type) {
        if (empty($GLOBALS['approved_fake_prototypes'])) return null;
        return new class($type) extends \Elementor\Widget_Base {
            private $type;
            public function __construct($type) { $this->type = $type; }
            public function get_name() { return $this->type; }
            public function get_style_depends() { return array('widget-style'); }
            public function get_script_depends() { return array('widget-script'); }
        };
    }

});
$image = array('id' => 'image-A', 'elType' => 'widget', 'widgetType' => 'image', 'settings' => array(
    '__dynamic__' => array('image' => '[elementor-tag id="tag-A" name="post-featured-image" settings="%7B%7D"]')));
$GLOBALS['templates'][73] = array($image);
$loop = array('id' => 'loop-A', 'elType' => 'widget', 'widgetType' => 'loop-grid',
    'settings' => array('template_id' => 73, 'post_query_post_type' => 'partner'));
$root = array('id' => 'root-A', 'elType' => 'widget', 'widgetType' => 'mega-menu',
    'settings' => array('iu_fragment_cache' => 'yes'), 'elements' => array($loop));
if (IU_Elementor_Fragment_Graph::inspect($root)) throw new \RuntimeException('Unknown implementation admitted');
define('ICL_SITEPRESS_VERSION', '999.0.0');
define('IVORY_SEARCH_VERSION', '999.0.0');
define('WP_ROCKET_VERSION', '999.0.0');
define('ACF_VERSION', '999.0.0');
$GLOBALS['approved_fake_prototypes'] = true; // Standalone schema fixtures only, never staging eligibility.
$menu = array('id' => 'menu-A', 'elType' => 'widget', 'widgetType' => 'nav-menu',
    'settings' => array('menu' => 'arbitrary-menu', 'iu_fragment_cache' => 'yes'));
$menu_policy = IU_Elementor_Fragment_Graph::inspect($menu);
if (!$menu_policy || $menu_policy['dependencies'] !== array('styles' => array('widget-style'), 'scripts' => array('widget-script'))) {
    throw new \RuntimeException('WP Menu root or its explicit asset dependencies rejected');
}
$GLOBALS['approved_fake_prototypes'] = false;
if (IU_Elementor_Fragment_Graph::inspect($menu)) throw new \RuntimeException('Unknown WP Menu implementation admitted');
$GLOBALS['approved_fake_prototypes'] = true;
$private_menu = $menu; $private_menu['settings']['__dynamic__'] = array('menu' => '[elementor-tag name="user-info"]');
if (IU_Elementor_Fragment_Graph::inspect($private_menu)) throw new \RuntimeException('Private WP Menu dynamic setting admitted');
$interactive_menu = $menu; $interactive_menu['interactions'] = array('items' => array('interaction'));
if (IU_Elementor_Fragment_Graph::inspect($interactive_menu)) throw new \RuntimeException('WP Menu native interaction collection skipped');
$policy = IU_Elementor_Fragment_Graph::inspect($root);
if (!$policy || $policy['dependencies']['scripts'] !== array('widget-script')) throw new \RuntimeException('Generic arbitrary graph rejected');
$template = array('id' => 'dropdown-A', 'elType' => 'widget', 'widgetType' => 'template',
    'settings' => array('template_id' => 73, 'iu_fragment_cache' => 'yes'));
// A template's ordinary contents must not contain unresolved dynamic tags.
$GLOBALS['templates'][74] = array($loop);
$template['settings']['template_id'] = 74;
if (!IU_Elementor_Fragment_Graph::inspect($template)) throw new \RuntimeException('Generic template root rejected');
$GLOBALS['templates'][73][0]['settings']['image_size'] = 'large';
$changed = IU_Elementor_Fragment_Graph::inspect($root);
if ($policy['signature'] === $changed['signature']) throw new \RuntimeException('Referenced source change did not change signature');
function reject_graph($node, $label) {
    if (IU_Elementor_Fragment_Graph::inspect($node)) throw new \RuntimeException('Unsafe graph admitted: ' . $label);
}
$unsafe = $root; $unsafe['elements'][0]['interactions'] = array('items' => array('interaction'));
reject_graph($unsafe, 'source interactions require native collection');
$GLOBALS['interaction_rows'][73] = array('old-element' => array('interaction'));
reject_graph($root, 'cached interactions require native collection');
unset($GLOBALS['interaction_rows'][73]);
foreach (array('post_query_query_id' => 'custom-query', 'post_query_orderby' => 'rand',
    'pagination_type' => 'numbers', 'post_query_post_type' => 'product',
    'post_query_include' => array('current_post'), 'post_query_offset' => 2) as $field => $value) {
    $unsafe = $root; $unsafe['elements'][0]['settings'][$field] = $value;
    reject_graph($unsafe, $field);
}
foreach (array('text-editor', 'shortcode', 'arbitrary-custom-widget') as $type) {
    $generic = $menu; $generic['widgetType'] = $type;
    if (!IU_Elementor_Fragment_Graph::inspect($generic)) throw new \RuntimeException('Registered generic widget rejected: ' . $type);
}
$container = array('id' => 'container', 'elType' => 'container');
reject_graph($container, 'container root');
$unsafe = $root; $unsafe['elements'][] = array('widgetType' => 'posts', 'settings' => array('classic_show_excerpt' => 'yes'));
reject_graph($unsafe, 'visible excerpt can invoke arbitrary content filters');
$GLOBALS['templates'][73][0]['settings']['__dynamic__']['image'] = '[elementor-tag id="tag" name="user-info" settings="%7B%7D"]';
reject_graph($root, 'personalized dynamic tag');
$GLOBALS['templates'][73] = array($image);
$GLOBALS['statuses'][73] = 'draft'; reject_graph($root, 'draft template'); unset($GLOBALS['statuses'][73]);
$cycle = $loop; $cycle['widgetType'] = 'template';
$GLOBALS['templates'][73] = array($cycle); reject_graph($root, 'template cycle');
$GLOBALS['templates'][73] = array($image);
$self = array('IU_Elementor_Fragment_Cache', 'filter');
$unknown = function ($data) { return $data; };
$GLOBALS['wp_filter']['elementor/frontend/builder_content_data'] = (object) array('callbacks' => array(
    10 => array(array('function' => $unknown)), PHP_INT_MAX => array(array('function' => $self))));
$tail = new \ReflectionMethod('IU_Elementor_Fragment_Cache', 'builder_tail_safe');
if (!$tail->invoke(null)) throw new \RuntimeException('Earlier callback rejected by root substitution');
// Administrator opt-in declares shared output; unrelated hooks are not an allowlist.
$plain = $root; unset($plain['elements']);
if (!IU_Elementor_Fragment_Graph::inspect($plain) || !IU_Elementor_Fragment_Graph::inspect($root)) {
    throw new \RuntimeException('Unclassified earlier callback blocked declared shared output');
}
$GLOBALS['wp_filter']['pre_get_posts'] = (object) array('callbacks' => array(10 => array(array('function' => $unknown))));
if (!IU_Elementor_Fragment_Graph::inspect($root)) throw new \RuntimeException('Harmless unknown query callback blocked');
$GLOBALS['wp_filter']['elementor/frontend/builder_content_data']->callbacks[PHP_INT_MAX][] = array('function' => $unknown);
if ($tail->invoke(null)) throw new \RuntimeException('Later proxy-shape callback admitted');
unset($GLOBALS['wp_filter']);
$_SERVER = array('REQUEST_URI' => '/', 'HTTP_HOST' => 'example.test', 'HTTP_USER_AGENT' => 'Desktop Chrome');
$key = new \ReflectionMethod('IU_Elementor_Fragment_Cache', 'key');
$first = $key->invoke(null, $plain, 123, 'el', array());
$_SERVER['REQUEST_URI'] = '/another-page/';
if ($first !== $key->invoke(null, $plain, 123, 'el', array())) throw new \RuntimeException('Page path fragmented shared key');
$policies = new \ReflectionProperty('IU_Elementor_Fragment_Cache', 'policies');
$policies->setValue(null, array('123:' . $plain['id'] => array('independent_query' => true)));
if ($key->invoke(null, $plain, 123, 'el', array(15)) !== $key->invoke(null, $plain, 123, 'el', array(19))) {
    throw new \RuntimeException('Irrelevant prior displayed IDs fragmented shared key');
}
$policies->setValue(null, array());
$_SERVER['HTTP_USER_AGENT'] = 'Desktop Firefox';
if ($first !== $key->invoke(null, $plain, 123, 'el', array())) throw new \RuntimeException('Desktop UA fragmented key');
$plain['settings']['title'] = 'Filtered translation';
if ($first === $key->invoke(null, $plain, 123, 'el', array())) throw new \RuntimeException('Filtered node missing from key');
$plain['settings'] = array('iu_fragment_cache' => 'yes');
$device_keys = array($first);
foreach (array('iPhone', 'iPad') as $ua) {
    $_SERVER['HTTP_USER_AGENT'] = $ua;
    $device_keys[] = $key->invoke(null, $plain, 123, 'el', array());
}
if (count(array_unique($device_keys)) !== 3) throw new \RuntimeException('Device key collision');
echo "generic-graph-and-builder-order: OK\n";
}
