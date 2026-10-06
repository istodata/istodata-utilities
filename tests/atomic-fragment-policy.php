<?php
/** Real installed Atomic SDK policy checks. WP-CLI only, private staging fixture. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit;
if (realpath(ABSPATH) !== '/home/218158.cloudwaysapps.com/manqbfzxjy/public_html') throw new RuntimeException('Wrong target');
$config = json_decode(file_get_contents(__DIR__.'/private.json'),true);
$node = json_decode(get_post_meta($config['document'],'_elementor_data',true),true)[0];
$_GET = array(); $_SERVER['REQUEST_URI'] = '/';
$results = array();
$check = static function($label,$value) use (&$results) {
    if (!$value) throw new RuntimeException($label);
    $results[] = $label;
};
$check('real-loop-policy',is_array(IU_Elementor_Fragment_Atomic::inspect($node)));
$nulls=$node;$nulls['settings']['query']['value']['date_before']=null;
$nulls['settings']['query']['value']['taxonomy_source']=null;
$check('native-save-null-fields',is_array(IU_Elementor_Fragment_Atomic::inspect($nulls)));
$check('typed-opt-in',IU_Elementor_Fragment_Atomic::opted_in($node));
$off=$node;unset($off['settings']['iu_fragment_cache']);
$check('default-off',!IU_Elementor_Fragment_Atomic::opted_in($off));
$bad=$node;$bad['settings']['iu_fragment_cache']['disabled']=true;
$check('disabled-opt-in',!IU_Elementor_Fragment_Atomic::opted_in($bad));
foreach (array('custom-query','current-query','relative-date','random-order','pagination','interaction','unknown-resolver') as $case) {
    $bad=$node;
    switch($case) {
        case 'custom-query': $bad['settings']['query']['value']['query_id']['value']='custom';break;
        case 'current-query': $bad['settings']['query']['value']['source']['value']='current_query';break;
        case 'relative-date': $bad['settings']['query']['value']['select_date']=array('$$type'=>'string','value'=>'past_week');break;
        case 'random-order': $bad['settings']['query']['value']['orderby']=array('$$type'=>'string','value'=>'rand');break;
        case 'pagination': $bad['settings']['pagination']=array('$$type'=>'boolean','value'=>true);break;
        case 'interaction': $bad['interactions']=array(array('trigger'=>'click'));break;
        case 'unknown-resolver': $bad['settings']['custom']=array('$$type'=>'unreviewed','value'=>'x');break;
    }
    $check('bypass-'.$case,IU_Elementor_Fragment_Atomic::inspect($bad)===null);
}
$heading=$node['elements'][0]['elements'][0]['elements'][0];
$check('bypass-post-tag-outside-loop',IU_Elementor_Fragment_Atomic::inspect($heading)===null);
$shared=IU_Elementor_Fragment_Atomic::inspect($node);
$_GET['foo']='bar';$_SERVER['REQUEST_URI']='/?foo=bar';
$check('shared-query-parameter',IU_Elementor_Fragment_Atomic::inspect($node)['signature']===$shared['signature']);$_GET=array();$_SERVER['REQUEST_URI']='/';
wp_set_current_user(1);$check('shared-user',IU_Elementor_Fragment_Atomic::inspect($node)['signature']===$shared['signature']);wp_set_current_user(0);
$private=$node;$private['elements'][0]['elements'][0]['elements'][0]['settings']['title']['value']['name']='user-info';
$check('bypass-user-resolver',IU_Elementor_Fragment_Atomic::inspect($private)===null);
$private['elements'][0]['elements'][0]['elements'][0]['settings']['title']['value']['name']='request-parameter';
$check('bypass-url-resolver',IU_Elementor_Fragment_Atomic::inspect($private)===null);
$loop=\Elementor\Plugin::$instance->elements_manager->get_element_types()['e-collection-loop'];
$schema=json_decode(wp_json_encode($loop->get_props_schema()),true);
$check('schema-default-off',$schema['iu_fragment_cache']['default']['value']===false);
$check('schema-default-seven-days',$schema['iu_fragment_cache_ttl']['default']['value']==='604800');
$controls=json_decode(wp_json_encode($loop->get_atomic_controls()),true);
$encoded=wp_json_encode($controls);
$check('real-native-controls',strpos($encoded,'Advanced Element Cache')!==false && strpos($encoded,'Cache this element')!==false);
echo wp_json_encode(array('passed'=>$results,'controls'=>$controls));
