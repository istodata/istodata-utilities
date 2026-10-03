<?php
/** Verify that the proxy registers after Elementor loads Widget_Base. */
define('ABSPATH', __DIR__);
define('ELEMENTOR_VERSION', '4.2.3');
function apply_filters($hook, $value) { return $value; }
$hooks = array();
function add_action($name, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['hooks'][$name][] = $callback;
}
function add_filter($name, $callback, $priority = 10, $accepted_args = 1) {
    add_action($name, $callback, $priority, $accepted_args);
}
function did_action($name) { return 0; }
function get_option($name, $default = false) { return $GLOBALS['test_epoch'] ?? $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['epoch_writes'][] = $name; }
function wp_generate_uuid4() { return 'new-test-epoch'; }
$include = getenv('IU_FRAGMENT_INCLUDE') ?: dirname(__DIR__) . '/includes/elementor-fragment-cache.php';
require $include;
if (class_exists('Elementor\\Widget_Base', false)) {
    throw new RuntimeException('Widget_Base should not exist when cache include loads');
}
eval('namespace Elementor; class Widget_Base { public function get_data($key) { return null; } }');
class IU_Fragment_Test_Manager {
    public $widget;
    public function register($widget) { $this->widget = $widget; }
}
$manager = new IU_Fragment_Test_Manager();
IU_Elementor_Fragment_Cache::proxy($manager);
if (!$manager->widget instanceof \Elementor\Widget_Base ||
    $manager->widget->get_name() !== 'iu-fragment-proxy') {
    throw new RuntimeException('Proxy was not registered after Widget_Base became available');
}
echo "bootstrap-order: OK\n";
if (!in_array(array('IU_Elementor_Fragment_Cache', 'invalidate'), $hooks['wp_update_nav_menu_item'] ?? array(), true) ||
    !in_array(array('IU_Elementor_Fragment_Cache', 'menu_item_deleted'), $hooks['before_delete_post'] ?? array(), true)) {
    throw new RuntimeException('Menu item API invalidation hooks missing');
}
$GLOBALS['epoch_writes'] = array();
IU_Elementor_Fragment_Cache::menu_item_deleted(7, (object) array('post_type' => 'nav_menu_item'));
if ($GLOBALS['epoch_writes']) throw new RuntimeException('Default-off site acquired cache epoch');
$GLOBALS['test_epoch'] = 'existing-epoch';
IU_Elementor_Fragment_Cache::menu_item_deleted(8, (object) array('post_type' => 'post'));
if ($GLOBALS['epoch_writes']) throw new RuntimeException('Unrelated post deletion purged fragments');
IU_Elementor_Fragment_Cache::menu_item_deleted(7, (object) array('post_type' => 'nav_menu_item'));
if ($GLOBALS['epoch_writes'] !== array(IU_Elementor_Fragment_Cache::EPOCH)) throw new RuntimeException('Deleted menu item failed to invalidate');
echo "menu-item-api-invalidation: OK\n";
