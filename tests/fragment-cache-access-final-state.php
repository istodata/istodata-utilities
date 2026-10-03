<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com')throw new RuntimeException('Wrong target');
$saved=array();
foreach(array(30,33000)as$id){
    $walk=function($nodes)use(&$walk,&$saved,$id){foreach((array)$nodes as$n){
        if(in_array($n['id']??'',array('417d1ae','c60daee','26fa46c6'),true))$saved[]=array('document'=>$id,'element'=>$n['id'],
            'cache'=>$n['settings']['iu_fragment_cache']??null,'ttl'=>$n['settings']['iu_fragment_cache_ttl']??'86400');
        if(!empty($n['elements']))$walk($n['elements']);
    }};
    $walk(json_decode(get_post_meta($id,'_elementor_data',true),true));
}
$features=iu_elementor_active_features();
$mode=true;
$settings=get_option('istodata_utilities_settings');
add_filter('pre_option_istodata_utilities_settings',function()use(&$mode,$settings){$settings['optimizations']['elementor_fragment_cache']=$mode;$settings['optimizations']['elementor_atomic_interaction_breakpoints']=false;return$settings;});
$decisions=array();
foreach(array(true,false)as$mode)$decisions[$mode?'on':'off']=IU_Elementor_Update_Guard::decision('elementor/elementor.php','999.0.0')['blocked'];
echo wp_json_encode(array('pair'=>iu_elementor_runtime_pair(),'kit'=>IU_PLUGIN_VERSION,'features'=>$features,
    'native_cache'=>get_option('elementor_element_cache_ttl'),'saved'=>$saved,'fragment_only_future_update_blocked'=>$decisions,
    'cleanup'=>array('temporary_users_absent'=>!username_exists('iu_fragment_acceptance_administrator')&&!username_exists('iu_fragment_acceptance_subscriber'),
        'observer_absent'=>!is_dir(ABSPATH.'wp-content/plugins/iu-fragment-access-harness'),
        'private_session_file_absent'=>!file_exists('/home/master/applications/manqbfzxjy/tmp/iu-fragment-access-20261001/test-sessions.json'))),JSON_PRETTY_PRINT)."\n";
