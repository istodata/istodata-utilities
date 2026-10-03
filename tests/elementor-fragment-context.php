<?php
/** Fail-closed request-context regression cases; run with PHP CLI. */
define('ABSPATH', __DIR__);
define('ICL_LANGUAGE_CODE', 'el');
define('ELEMENTOR_VERSION', '4.2.3');
define('ELEMENTOR_PRO_VERSION', '4.2.2');
$_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
$_SERVER['REQUEST_URI'] = '/';
$_COOKIE = array();
$_POST = array();
$_GET = array();
$logged = false;
$preview = false;
function add_action() {}
function add_filter() {}
function do_action() {}
function wp_json_encode($value) { return json_encode($value); }
function get_post_type_object($type) { return null; }
function did_action() { return 0; }
function is_admin() { return false; }
function is_user_logged_in() { return $GLOBALS['logged']; }
function get_current_user_id() { return $GLOBALS['logged'] ? 23 : 0; }
function wp_validate_auth_cookie($value, $scheme) { return $value === 'valid-session' ? 23 : false; }
define('LOGGED_IN_COOKIE', 'wordpress_logged_in_test');
function is_preview() { return $GLOBALS['preview']; }
function is_customize_preview() { return false; }
function is_feed() { return false; }
function is_search() { return false; }
function wp_is_mobile() { return true; }
function get_option($name) {
    if ($name === 'istodata_utilities_settings') return array('optimizations' => array('elementor_fragment_cache' => $GLOBALS['global_on'] ?? true));
    return $name === 'elementor_element_cache_ttl' ? ($GLOBALS['native_cache'] ?? 'disable') : false;
}
function apply_filters($name, $default, ...$args) {
    if ($name === 'wpml_current_language') return $GLOBALS['live_language'] ?? null;
    if ($name === 'iu_elementor_fragment_allow_query') return $GLOBALS['allow_query'] ?? $default;
    return $name === 'iu_elementor_fragment_prototype_supported' ? ($args[0] === 'heading') : $default;
}
$include = getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
require $include;
$method = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'request_ok');
function expect_request($want, $name) {
    global $method;
    if ($method->invoke(null) !== $want) {
        throw new RuntimeException($name . ' failed');
    }
}
expect_request(true, 'anonymous clean');
$GLOBALS['allow_query'] = true;
$_GET = array('elementor-preview' => '30');
expect_request(false, 'preview request before initialization or nested post switch');
$_GET = array('elementor-preview' => '');
expect_request(false, 'empty preview parameter fails closed');
$_GET = array();
expect_request(true, 'ordinary frontend with allowed query filter');
unset($GLOBALS['allow_query']);
$global_on = false;
expect_request(false, 'global OFF');
$global_on = true;
$GLOBALS['native_cache'] = false;
expect_request(false, 'missing option enables native cache');
$GLOBALS['native_cache'] = '24';
expect_request(false, 'native element cache active');
$GLOBALS['native_cache'] = 'disable';
$_COOKIE = array('wp-wpml_current_language' => 'el');
expect_request(true, 'matching WPML language cookie');
$_COOKIE['unknown_context'] = '1';
expect_request(true, 'unclassified cookie under administrator shared-output declaration');
$_COOKIE['PHPSESSID'] = 'private-session';
expect_request(false, 'PHP session cookie');
unset($_COOKIE['PHPSESSID']);
$_SESSION = array('customer' => 23);
expect_request(false, 'active PHP session');
$_SESSION = array();
$_COOKIE = array('wp-wpml_current_language' => 'en');
expect_request(false, 'mismatched WPML language cookie');
$_COOKIE = array();
$logged = true;
expect_request(true, 'logged-in safe frontend');
$_COOKIE = array(LOGGED_IN_COOKIE => 'valid-session', 'wp-settings-23' => 'editor=tinymce');
expect_request(true, 'validated WordPress session and preferences');
$_COOKIE[LOGGED_IN_COOKIE] = 'invalid-session';
expect_request(false, 'invalid auth cookie');
$logged = false;
$_COOKIE[LOGGED_IN_COOKIE] = 'valid-session';
expect_request(false, 'auth cookie without authenticated user');
$_COOKIE = array();
$logged = false;
$preview = true;
expect_request(false, 'preview');
$preview = false;
$_GET = array('unknown' => '1');
expect_request(false, 'query context');
$_GET = array();
$_SERVER['REQUEST_METHOD'] = 'HEAD';
expect_request(false, 'non-GET method');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test';
expect_request(false, 'authorization header');
unset($_SERVER['HTTP_AUTHORIZATION']);
eval('namespace Elementor; class Plugin { public static $instance; } class Widget_Base {}');
\Elementor\Plugin::$instance = (object) array('widgets_manager' => new class {
    public function get_widget_types($type) { return new class($type) extends \Elementor\Widget_Base {
        private $type; public function __construct($type) { $this->type=$type; }
        public function get_name() { return $this->type; }
        public function get_style_depends() { return array(); }
        public function get_script_depends() { return array(); }
    }; }
});
$eligible = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'eligible');
$node = array('id' => 'test', 'elType' => 'widget', 'widgetType' => 'heading',
    'settings' => array('iu_fragment_cache' => 'yes'));
if (!$eligible->invoke(null, $node, 1)) throw new RuntimeException('Static heading rejected');
foreach (array('text-editor', 'mega-menu', 'shortcode', 'arbitrary-custom-widget') as $type) {
    $node['widgetType'] = $type;
    if (!$eligible->invoke(null, $node, 1)) throw new RuntimeException('Registered generic widget rejected: ' . $type);
}
$node['widgetType'] = 'heading';
$node['settings']['__dynamic__'] = array('title' => '[elementor-tag]');
if ($eligible->invoke(null, $node, 1)) throw new RuntimeException('Dynamic heading admitted');
$hidden = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'hidden_on_device');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 iPhone Mobile';
if ($hidden->invoke(null, array('iu_hide_on_phone' => 'yes'))) {
    throw new RuntimeException('Disabled Device Visibility was applied by cache');
}
if (!$hidden->invoke(null, array('hide_mobile' => 'hidden-mobile'))) {
    throw new RuntimeException('Native phone visibility was ignored');
}
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 iPad Mobile';
if ($hidden->invoke(null, array('hide_mobile' => 'hidden-mobile')) ||
    !$hidden->invoke(null, array('hide_tablet' => 'hidden-tablet'))) throw new RuntimeException('Tablet mistaken for phone with Device Visibility off');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 iPhone Mobile';
eval('function iu_elementor_should_render_by_device() { return true; }');
if (!$hidden->invoke(null, array('iu_hide_on_phone' => 'yes'))) {
    throw new RuntimeException('Enabled Device Visibility was ignored');
}
$walk = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'walk');
$cached_child = array('id' => 'cached-child', 'elType' => 'widget', 'widgetType' => 'heading',
    'settings' => array('iu_fragment_cache' => 'yes'));
foreach (array(array('hide_mobile' => 'hidden-mobile'), array('iu_hide_on_phone' => 'yes')) as $settings) {
    $ancestor = array('id' => 'hidden-parent', 'elType' => 'container', 'settings' => $settings,
        'elements' => array($cached_child));
    if ($walk->invoke(null, array($ancestor), 1, 'el') !== array()) {
        throw new RuntimeException('Hidden ancestor allowed opted-in child lookup/render');
    }
    $ancestor['elements'][0]['settings'] = array();
    if ($walk->invoke(null, array($ancestor), 1, 'el') !== array($ancestor)) {
        throw new RuntimeException('Default-off branch behavior changed');
    }
}
echo "context-gates: OK\n";
define('ICL_SITEPRESS_VERSION', '4.9.7');
$language = new ReflectionMethod('IU_Elementor_Fragment_Cache', 'language');
$GLOBALS['live_language'] = 'el';
if ($language->invoke(null) !== 'el') throw new RuntimeException('Resolved WPML language rejected');
$GLOBALS['live_language'] = 'en';
if ($language->invoke(null) !== null) throw new RuntimeException('Switched/inconsistent WPML context cached');
echo "wpml-context-consistency: OK\n";
$capture = new ReflectionProperty('IU_Elementor_Fragment_Cache', 'capture');
foreach (array(array(), array('s'=>'term'), array('_is_settings'=>array()), array('_is_includes'=>array()), array('perm'=>'readable'), array('post_status'=>array('publish','private')), array('post_password'=>'protected')) as $vars) {
    $capture->setValue(null, array(array('id'=>'capture')));
    $query = new class { public $query_vars; public function is_search() { return false; } };
    $query->query_vars = $vars;
    IU_Elementor_Fragment_Cache::guard_queries($query);
    if (!empty($capture->getValue()[0]['unsafe_query']) !== (bool) $vars || $query->query_vars !== $vars) throw new RuntimeException('Query safety observation changed query or missed search markers');
}
$capture->setValue(null, array());
echo "search-private-permission-query-publication-guard: OK\n";
