<?php
/**
 * Plugin Name: IU Saved Kit Acceptance Harness
 * Version: 0.1.0
 * Description: Staging-only observation of saved Kit opt-ins.
 */
if (!defined('ABSPATH')) exit;
function iu_cross_probe_enabled() {
    return ($_SERVER['HTTP_HOST'] ?? '') === 'wordpress-218158-6702910.cloudwaysapps.com' &&
        ($_GET['iu_kit_fragment_test'] ?? '') === 'cross-page-20261001' && !is_admin() && !is_user_logged_in();
}
function iu_cross_media() {
    return array('count'=>wp_increase_content_media_count(0), 'priority'=>wp_high_priority_element_flag(),
        'content'=>doing_filter('the_content'), 'loop'=>in_the_loop(), 'main'=>is_main_query());
}
add_action('plugins_loaded', function () {
    if (!iu_cross_probe_enabled()) return;
    $GLOBALS['iu_cross'] = array('target'=>0,'nested'=>0,'loop'=>0,'loop_any'=>0,'grids'=>array(),'events'=>array(),'keys'=>array(),'reject'=>array(),'template_renders'=>array());
    add_filter('iu_elementor_fragment_allow_query', function ($allowed, $keys) {
        return count(array_diff($keys, array('iu_kit_fragment_test','iu_kit_request','iu_kit_generation','iu_kit_mode','iu_kit_delay'))) === 0;
    },10,2);
    add_filter('iu_elementor_fragment_key_context', function ($context) {
        $context['generation']=sanitize_key($_GET['iu_kit_generation'] ?? 'default'); return $context;
    });
    add_filter('iu_elementor_fragment_graph_callback_allowed', function ($allowed,$callback) {
        return $allowed || ($callback instanceof Closure && (new ReflectionFunction($callback))->getFileName() === __FILE__);
    },10,2);
    add_filter('elementor/frontend/builder_content_data', function ($nodes,$doc) {
        $GLOBALS['iu_cross']['docs'][]=(int)$doc;
        if (($_GET['iu_kit_mode'] ?? '') !== 'baseline') return $nodes;
        $walk=function($nodes) use (&$walk) {
            foreach ($nodes as &$node) {
                if (($node['widgetType'] ?? '') === 'template' && in_array($node['id'],array('417d1ae','c60daee'),true)) {
                    $node['settings']['iu_fragment_cache']='';
                    $node['settings']['iu_fragment_cache_ttl']='86400';
                    $GLOBALS['iu_cross']['injected'][]=$node['id'];
                }
                if (!empty($node['elements'])) $node['elements']=$walk($node['elements']);
            }
            unset($node); return $nodes;
        }; return $walk($nodes);
    },PHP_INT_MAX-1,2);
    add_action('elementor/frontend/widget/before_render',function($widget) {
        if ($widget->get_id()==='26fa46c6') { $GLOBALS['iu_cross']['target']++; $GLOBALS['iu_cross']['inside']=true; }
        if (in_array($widget->get_id(),array('417d1ae','c60daee'),true)) $GLOBALS['iu_cross']['template_renders'][]=$widget->get_id();
        if ($widget->get_name()==='loop-grid' && !empty($GLOBALS['iu_cross']['inside'])) $GLOBALS['iu_cross']['grids'][$widget->get_id()]=array('before'=>iu_cross_media());
        if (in_array($widget->get_id(),array('417d1ae','c60daee'),true) && is_array($widget->get_data('_iu_fragment_build'))) {
            $delay=min(20,absint($_GET['iu_kit_delay'] ?? 0)); if ($delay) sleep($delay);
        }
    },-10);
    add_action('elementor/frontend/widget/after_render',function($widget) {
        if ($widget->get_name()==='loop-grid' && !empty($GLOBALS['iu_cross']['inside'])) $GLOBALS['iu_cross']['grids'][$widget->get_id()]['after']=iu_cross_media();
        if ($widget->get_id()==='26fa46c6') {
            $GLOBALS['iu_cross']['inside']=false;
            $GLOBALS['iu_cross']['displayed']=\ElementorPro\Modules\QueryControl\Module::get_avoid_list_ids();
            $GLOBALS['iu_cross']['excerpt']=IU_Elementor_Fragment_Excerpt::profile();
        }
    },PHP_INT_MAX);
    add_action('elementor/frontend/before_get_builder_content',function($document) {
        if (!empty($GLOBALS['iu_cross']['inside']) && (int)$document->get_post()->ID===1635) $GLOBALS['iu_cross']['loop']++;
        if (!empty($GLOBALS['iu_cross']['inside']) && strpos(get_class($document),'LoopBuilder\\Documents\\Loop') !== false) $GLOBALS['iu_cross']['loop_any']++;
    });
    add_action('elementor/frontend/before_render',function() {
        if (!empty($GLOBALS['iu_cross']['inside'])) $GLOBALS['iu_cross']['nested']++;
    });
    add_action('iu_elementor_fragment_result',function($result,$build) {
        $GLOBALS['iu_cross']['events'][]=array('id'=>$build['element_id'] ?? '', 'result'=>$result);
    },10,2);
    add_action('iu_elementor_fragment_before_substitution',function($node,$doc,$key) {
        $GLOBALS['iu_cross']['keys'][$node['id']]=array('doc'=>$doc,'key'=>$key);
    },10,3);
    add_action('iu_elementor_fragment_graph_rejected',function($reason,$node) {
        $GLOBALS['iu_cross']['reject'][$node['id']]=$reason;
    },10,2);
    add_action('shutdown',function(){ echo '\n<!-- IU_CROSS_PAGE '.wp_json_encode($GLOBALS['iu_cross']).' -->'; },PHP_INT_MAX);
    // Actual full Kit owns module registration; this harness observes it.
},0);
