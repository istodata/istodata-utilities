<?php
if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
$before=json_decode(file_get_contents(__DIR__.'/state-before.json'),true);
$state=[];foreach([30,33000] as $id)$state['doc'.$id]=hash('sha256',serialize(get_post($id)).serialize(get_post_meta($id)));
foreach(['istodata_utilities_settings','elementor_element_cache_ttl','active_plugins'] as $k)$state[$k]=get_option($k);
$out=['pair'=>[ELEMENTOR_VERSION,ELEMENTOR_PRO_VERSION],'different_fields'=>[]];
$out['native_element_cache']=get_option('elementor_element_cache_ttl');
$out['document_data']=[];
foreach([30,33000] as $id)$out['document_data'][(string)$id]=hash('sha256',get_post_meta($id,'_elementor_data',true));
$out['plugin_version']=IU_PLUGIN_VERSION;
foreach($state as $key=>$value)if($value!==$before[$key])$out['different_fields'][]=['field'=>$key,'same_json'=>wp_json_encode($value)===wp_json_encode($before[$key]),'before_type'=>gettype($before[$key]),'after_type'=>gettype($value)];
echo wp_json_encode($out);
