<?php
/** Staging fixture only. Not distributed with Kit. */
if (!defined('ABSPATH') || ($_SERVER['HTTP_HOST'] ?? '') !== 'wordpress-218158-6702910.cloudwaysapps.com') return;
add_action('elementor/widgets/register', function ($manager) {
    class IU_Generic_Proof_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'iu-generic-proof'; }
        public function get_title() { return 'Generic cache acceptance fixture'; }
        public function get_icon() { return 'eicon-code'; }
        public function get_script_depends() { return array('iu-generic-proof'); }
        public function get_style_depends() { return array('iu-generic-proof'); }
        protected function register_controls() {
            $this->start_controls_section('proof', array('label'=>'Proof'));
            $this->add_control('proof_enabled', array('type'=>\Elementor\Controls_Manager::SWITCHER, 'default'=>'yes', 'frontend_available'=>true));
            $this->end_controls_section();
        }
        protected function render() {
            $GLOBALS['iu_cross']['custom_render_body']=($GLOBALS['iu_cross']['custom_render_body']??0)+1;
            if (($_GET['iu_kit_mode']??'')==='custom-token') echo '<span data-token="private-proof">fixture</span>';
            if (($_GET['iu_kit_mode']??'')==='custom-permission') new \WP_Query(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'perm'=>'readable'));
            echo '<button class="iu-generic-proof-button" aria-pressed="false">Cache interaction proof</button>';
        }
    }
    $manager->register(new IU_Generic_Proof_Widget());
});
add_action('wp_enqueue_scripts', function () {
    wp_register_script('iu-generic-proof', plugins_url('proof.js', __FILE__), array('jquery','elementor-frontend'), '1', true);
    wp_register_style('iu-generic-proof', plugins_url('proof.css', __FILE__), array(), '1');
});
add_filter('elementor/frontend/builder_content_data', function ($nodes, $id) {
    if (!function_exists('iu_cross_probe_enabled') || !iu_cross_probe_enabled() || !in_array((int)$id, array(30,33000), true)) return $nodes;
    $on = ($_GET['iu_kit_mode'] ?? '') !== 'baseline';
    if (($_GET['iu_kit_mode'] ?? '') === 'custom-only') $nodes=array();
    if (isset($_GET['elementor-preview'])) {
        // Native preview can select an older autosave. Exercise all opt-ins in
        // the render copy without changing the saved document or autosave.
        $preview_optins = function ($items) use (&$preview_optins) {
            foreach ($items as &$item) {
                if (in_array($item['id'] ?? '', array('d7ebc85','a1ff108','e62c115','c28398e','417d1ae','c60daee'), true)) $item['settings']['iu_fragment_cache']='yes';
                if (!empty($item['elements'])) $item['elements']=$preview_optins($item['elements']);
            }
            unset($item); return $items;
        };
        $nodes=$preview_optins($nodes);
        $GLOBALS['iu_cross']['preview_render_copy_optins']=true;
    }
    if (wp_is_mobile()) {
        $find = function ($items) use (&$find) {
            foreach ($items as $item) {
                if (($item['id'] ?? '') === 'a1ff108') return $item;
                if (!empty($item['elements']) && ($match = $find($item['elements']))) return $match;
            }
            return null;
        };
        $mobile_menu = $find($nodes);
        if ($mobile_menu) {
            $mobile_menu['id'] = 'iu-mobile-menu';
            $mobile_menu['settings'] = array('menu'=>$mobile_menu['settings']['menu'], 'layout'=>'horizontal',
                'dropdown'=>'mobile','toggle'=>'burger','iu_fragment_cache'=>$on?'yes':'','iu_fragment_cache_ttl'=>'604800');
            $nodes[] = $mobile_menu;
        }
    }
    foreach (array('iu-core-proof'=>'text-editor', 'iu-custom-proof'=>'iu-generic-proof') as $element=>$type) {
        $nodes[]=array('id'=>$element,'elType'=>'widget','widgetType'=>$type,'settings'=>array(
            'iu_fragment_cache'=>$on?'yes':'','iu_fragment_cache_ttl'=>'604800',
            'editor'=>'<p class="iu-core-proof">Generic core cache proof</p>','proof_enabled'=>'yes'));
    }
    return $nodes;
}, PHP_INT_MAX-2, 2);
add_action('plugins_loaded', function () {
    if (!function_exists('iu_cross_probe_enabled') || !iu_cross_probe_enabled()) return;
    $GLOBALS['iu_cross']['proof_renders']=array();
    $GLOBALS['iu_cross']['custom_render_body']=0;
    add_action('elementor/frontend/widget/before_render', function ($w) {
        if (in_array($w->get_id(), array('iu-core-proof','iu-custom-proof'), true)) $GLOBALS['iu_cross']['proof_renders'][]=$w->get_id();
    }, -20);
}, 1);
