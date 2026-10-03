<?php
/**
 * Plugin Name: IU Fragment Access Acceptance Harness
 * Version: 0.1.0
 * Description: Staging-only observation of saved Kit opt-ins.
 */
if (!defined('ABSPATH')) exit;
if (!defined('SAVEQUERIES')) define('SAVEQUERIES', true);
if (($_SERVER['HTTP_HOST'] ?? '') === 'wordpress-218158-6702910.cloudwaysapps.com') {
    add_action('shutdown',function(){
        if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) return;
        if (($_GET['iu_admin_check'] ?? '') !== '1') return;
        echo '<!-- IU_ADMIN_CONTEXT '.wp_json_encode(array(
            'user_id'=>get_current_user_id(),'roles'=>wp_get_current_user()->roles,'manage_options'=>current_user_can('manage_options'),
            'admin_bar'=>is_admin_bar_showing(),'kit_menu_callback'=>has_action('admin_menu','iu_add_admin_menu'),
            'registered'=>isset($GLOBALS['_registered_pages']['settings_page_istodata-utilities']),
            'admin_menu_ran'=>did_action('admin_menu'),'kit_loaded'=>defined('IU_PLUGIN_VERSION'),
            'parent'=>is_admin()?get_admin_page_parent():null,'hook'=>is_admin()?get_plugin_page_hookname('istodata-utilities',get_admin_page_parent()):null,
            'no_priv'=>$GLOBALS['_wp_submenu_nopriv'] ?? array(),
            'menus'=>array_map(function($m){return $m[2];},$GLOBALS['submenu']['options-general.php'] ?? array()),
            'admin_hooks'=>array_map(function($g){return array_map(function($c){$f=$c['function'];return is_string($f)?$f:(is_array($f)?(is_object($f[0])?get_class($f[0]):$f[0]).'::'.$f[1]:'closure');},$g);},$GLOBALS['wp_filter']['admin_menu']->callbacks ?? array()))).' -->';
    });
}
function iu_cross_probe_enabled() {
    return ($_SERVER['HTTP_HOST'] ?? '') === 'wordpress-218158-6702910.cloudwaysapps.com' &&
        ((($_GET['iu_kit_fragment_test'] ?? '') === 'cross-page-20261001' && !is_admin()) ||
        ($_SERVER['HTTP_X_IU_PREVIEW_PROBE'] ?? '') === 'native-preview-20261001');
}
function iu_unreviewed_acceptance_query($query) {}
function iu_cross_media() {
    return array('count'=>wp_increase_content_media_count(0), 'priority'=>wp_high_priority_element_flag(),
        'content'=>doing_filter('the_content'), 'loop'=>in_the_loop(), 'main'=>is_main_query());
}
add_action('plugins_loaded', function () {
    if (!iu_cross_probe_enabled()) return;
    $GLOBALS['iu_cross'] = array('target'=>0,'nested'=>0,'loop'=>0,'loop_any'=>0,'grids'=>array(),'events'=>array(),'keys'=>array(),'reject'=>array(),'template_renders'=>array());
    $GLOBALS['iu_cross']['gates']=array();
    $GLOBALS['iu_cross']['query_profile']=array();
    add_filter('posts_results',function($posts,$q){if($q->is_main_query())$GLOBALS['iu_cross']['main_results']=array('ids'=>array_map(function($p){return $p->ID;},$posts),'found'=>$q->found_posts);return $posts;},PHP_INT_MAX,2);
    if (($_GET['iu_kit_mode'] ?? '') === 'unknown') add_action('pre_get_posts','iu_unreviewed_acceptance_query',10);
    add_action('pre_get_posts',function($q){
        if($q->is_main_query()) $GLOBALS['iu_cross']['main_preget']=array('search'=>$q->is_search(),'id'=>$q->query_vars['id']??null,'s'=>$q->query_vars['s']??null,'ivory'=>isset($q->query_vars['_is_includes'])||isset($q->query_vars['_is_settings']), 'post_type'=>$q->query_vars['post_type']??null);
        if (empty($GLOBALS['iu_cross']['inside'])) return;
        $v=$q->query_vars;
        $GLOBALS['iu_cross']['query_profile'][]=array('post_type'=>$v['post_type']??'', 's'=>$v['s']??'', 'search'=>$q->is_search(), 'ivory'=>isset($v['_is_includes'])||isset($v['_is_settings']));
    },PHP_INT_MAX);

    $GLOBALS['iu_cross']['operations']=array();
    $GLOBALS['iu_cross']['rendered_widgets']=0;
    add_action('iu_elementor_fragment_gate',function($language,$ok,$doc,$nodes){
        $p=\Elementor\Plugin::$instance;
        $optins=array();$walk=function($nodes)use(&$walk,&$optins){foreach((array)$nodes as$n){
            if(($n['settings']['iu_fragment_cache']??'')==='yes')$optins[]=$n['id'];
            if(!empty($n['elements']))$walk($n['elements']);
        }};$walk($nodes);
        $GLOBALS['iu_cross']['gates'][]=array('document'=>(int)$doc,'eligible'=>$ok,
            'request_context_ok'=>(new ReflectionMethod('IU_Elementor_Fragment_Cache','request_ok'))->invoke(null),
            'query_allowed'=>apply_filters('iu_elementor_fragment_allow_query',false,array_keys($_GET)),
            'preview'=>$p->preview&&$p->preview->is_preview_mode(),
            'preview_request'=>$p->preview&&isset($_GET['elementor-preview'])&&$p->preview->is_preview_mode((int)$_GET['elementor-preview']),
            'editor'=>$p->editor&&$p->editor->is_edit_mode(),'admin'=>is_admin(),
            'preview_parameter'=>isset($_GET['elementor-preview']),'optins'=>$optins,
            'operations_before'=>count($GLOBALS['iu_cross']['operations']));
    },10,4);
    add_action('all',function($hook){
        if(preg_match('/^(pre_transient_|pre_set_transient_|set_transient_|pre_option_|add_option_|update_option_).*iu_frag_/',$hook))
            $GLOBALS['iu_cross']['operations'][]=$hook;
    });
    add_action('iu_elementor_fragment_node',function(){ $GLOBALS['iu_cross']['operations'][]='walk-node'; });
    add_filter('iu_elementor_fragment_allow_query', function ($allowed, $keys) {
        return count(array_diff($keys, array('iu_kit_fragment_test','iu_kit_request','iu_kit_generation','iu_kit_mode','iu_kit_delay','elementor-preview','elementor_library','ver'))) === 0;
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
        $GLOBALS['iu_cross']['rendered_widgets']++;
        if ($widget->get_id()==='26fa46c6') { $GLOBALS['iu_cross']['target']++; $GLOBALS['iu_cross']['inside']=true; }
        if (in_array($widget->get_id(),array('417d1ae','c60daee'),true)) $GLOBALS['iu_cross']['template_renders'][]=$widget->get_id();
        if ($widget->get_name()==='loop-grid' && !empty($GLOBALS['iu_cross']['inside'])) $GLOBALS['iu_cross']['grids'][$widget->get_id()]=array('before'=>iu_cross_media());
        if (in_array($widget->get_id(),array('417d1ae','c60daee'),true) && is_array($widget->get_data('_iu_fragment_build'))) {
            $delay=min(20,absint($_GET['iu_kit_delay'] ?? 0));
            if ($delay) {
                set_transient('iu_access_signal_' . sanitize_key($_GET['iu_kit_generation'] ?? ''), $widget->get_data('_iu_fragment_build')['key'], 120);
                sleep($delay);
            }
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
    add_action('shutdown',function(){
        $GLOBALS['iu_cross']['elementor_preview']=\Elementor\Plugin::$instance->preview->is_preview_mode();
        if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) return;
        $q=$GLOBALS['wp_query'];
        $GLOBALS['iu_cross']['search_main']=array('search'=>$q->is_search(),'ivory'=>isset($q->query_vars['_is_settings']),'ids'=>array_map(function($p){return $p->ID;},(array)$q->posts),'found'=>$q->found_posts);
        $GLOBALS['iu_cross']['request_ok']=(new ReflectionMethod('IU_Elementor_Fragment_Cache','request_ok'))->invoke(null);
        echo '\n<!-- IU_CROSS_PAGE '.wp_json_encode(array_merge($GLOBALS['iu_cross'], array('user_id'=>get_current_user_id(),'optin_scans'=>count(array_filter($GLOBALS['wpdb']->queries ?? array(), function ($q) { return strpos($q[0], 'SELECT m.meta_id, m.meta_value') !== false; }))))).' -->';
    },PHP_INT_MAX);
    // Actual full Kit owns module registration; this harness observes it.
},0);

