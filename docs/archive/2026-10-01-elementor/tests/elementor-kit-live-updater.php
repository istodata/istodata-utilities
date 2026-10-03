<?php
if (get_option('home') !== 'https://wordpress-218158-6702910.cloudwaysapps.com') { echo "WRONG_TARGET\n"; return; }
require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
wp_set_current_user(1);
if (!current_user_can('update_plugins')) { echo "NO_ADMIN_CAPABILITY\n"; return; }
$settings=get_option('istodata_utilities_settings');
$raw=get_post_meta(30,'_elementor_data',true);
$before=IU_Elementor_Update_Guard::installed_pair();
$hashes=[]; foreach(IU_Elementor_Update_Guard::plugins() as $p=>$k) $hashes[$p]=hash_file('sha256',WP_PLUGIN_DIR.'/'.$p);
$rows=[];
try {
 foreach([false,true] as $atomic) foreach([false,true] as $fragment) {
  $opt=$settings; $opt['optimizations']['elementor_atomic_interaction_breakpoints']=$atomic?1:0; update_option('istodata_utilities_settings',$opt);
  $nodes=json_decode($raw,true);
  $walk=function($nodes) use (&$walk,$fragment) {foreach($nodes as &$n){if(in_array($n['id']??'',['417d1ae','c60daee'],true)) $n['settings']['iu_fragment_cache']=$fragment?'yes':''; if(!empty($n['elements'])) $n['elements']=$walk($n['elements']);}unset($n);return $nodes;};
  update_post_meta(30,'_elementor_data',wp_slash(wp_json_encode($walk($nodes))));
  $t=get_site_transient('update_plugins'); $item=$t->response['elementor-pro/elementor-pro.php']??null;
  if(!$item) {echo "NO_PRO_UPDATE_METADATA\n";break 2;}
  $row=['atomic'=>$atomic,'fragment'=>$fragment,'saved_active'=>iu_elementor_active_features(), 'auto_gate'=>apply_filters('auto_update_plugin',true,$item)];
  $u=new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin()); $r=$u->bulk_upgrade(['elementor-pro/elementor-pro.php']); $v=$r['elementor-pro/elementor-pro.php']??$u->skin->result;
  $row['bulk_ajax_result']=is_wp_error($v)?$v->get_error_code():gettype($v);
  set_site_transient('update_plugins',$t);
  $u=new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin()); $v=$u->upgrade('elementor-pro/elementor-pro.php'); $e=$u->skin->get_errors(); $row['manual_result']=is_wp_error($v)?$v->get_error_code():($e->has_errors()?$e->get_error_code():gettype($v));
  if($atomic||$fragment){$u=new WP_Upgrader(new WP_Ajax_Upgrader_Skin()); $v=$u->install_package(['source'=>'/home/master/applications/manqbfzxjy/tmp/iu-kit-acceptance-20261001T112014Z/vendor-elementor/elementor/','destination'=>WP_PLUGIN_DIR,'hook_extra'=>['plugin'=>'elementor/elementor.php']]); $row['actual_source']=is_wp_error($v)?$v->get_error_code():gettype($v);}
  $rows[]=$row;
 }
} finally { update_option('istodata_utilities_settings',$settings); update_post_meta(30,'_elementor_data',wp_slash($raw)); }
$after=IU_Elementor_Update_Guard::installed_pair(); $unchanged=$before===$after;
foreach($hashes as $p=>$hash) $unchanged=$unchanged&&hash_file('sha256',WP_PLUGIN_DIR.'/'.$p)===$hash;
echo wp_json_encode(['rows'=>$rows,'plugin_files_unchanged'=>$unchanged,'settings_restored'=>get_option('istodata_utilities_settings')===$settings&&get_post_meta(30,'_elementor_data',true)===$raw,'final_pair'=>$after])."\n";
