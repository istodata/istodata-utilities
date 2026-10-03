<?php
/** Run with wp eval-file on a disposable staging site after the cache include is available. */
require_once (getenv('IU_FRAGMENT_INCLUDE') ?: WP_PLUGIN_DIR . '/iu-kit-fragment-harness/elementor-fragment-cache.php');
wp_set_current_user(1);

class IU_Fragment_Control_Dummy {
    public $added = array();
    private $type;
    public function __construct($type) { $this->type = $type; }
    public function get_type() { return $this->type; }
    public function get_controls() { return $this->added; }
    public function start_controls_section($id, $options) {}
    public function add_control($id, $options) { $this->added[$id] = $options; }
    public function end_controls_section() {}
}
$widget = new IU_Fragment_Control_Dummy('widget');
IU_Elementor_Fragment_Cache::add_controls($widget);
if (($widget->added['iu_fragment_cache']['default'] ?? null) !== '' ||
    ($widget->added['iu_fragment_cache_ttl']['default'] ?? null) !== '86400') {
    throw new RuntimeException('Widget opt-in defaults are incorrect');
}
$container = new IU_Fragment_Control_Dummy('container');
IU_Elementor_Fragment_Cache::add_controls($container);
if ($container->added) {
    throw new RuntimeException('Unsupported container received cache controls');
}
$real_widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($args[1] ?? 'mega-menu');
if (!$real_widget || !isset($real_widget->get_controls()['iu_fragment_cache'])) {
    throw new RuntimeException('Mega-menu editor control was not registered');
}
ob_start();
IU_Elementor_Fragment_Cache::admin_page();
$html = ob_get_clean();
if (strpos($html, 'iu_fragment_purge') === false ||
    strpos($html, $args[0] ?? '26fa46c6') === false) {
    throw new RuntimeException('Per-element purge form missing');
}
echo "controls-default-off-and-admin-purge: OK\n";
