<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com')throw new RuntimeException('Wrong target');
$posts=array_merge(array(get_post(30)),array_values(wp_get_post_revisions(30,array('numberposts'=>8))));
foreach($posts as$p){$rows=array();$walk=function($nodes)use(&$walk,&$rows){foreach((array)$nodes as$n){if(in_array($n['id']??'',array('26fa46c6','417d1ae','c60daee'),true))$rows[]=array('id'=>$n['id'],'cache'=>$n['settings']['iu_fragment_cache']??null,'ttl'=>$n['settings']['iu_fragment_cache_ttl']??null);if(!empty($n['elements']))$walk($n['elements']);}};$walk(json_decode(get_post_meta($p->ID,'_elementor_data',true),true));echo wp_json_encode(array('post'=>$p->ID,'modified'=>$p->post_modified_gmt,'rows'=>$rows))."\n";}
