<?php
/** Staging-only real menu item API edit and cleanup of that exact temporary title. */
if (get_option('home') !== 'https://wordpress-218158-6702910.cloudwaysapps.com' ||
    realpath(ABSPATH) !== '/home/218158.cloudwaysapps.com/manqbfzxjy/public_html') throw new RuntimeException('Wrong target');
$file='/home/master/applications/manqbfzxjy/tmp/iu-fragment-generic-20261002/menu-edit.json';
$mode=$args[0]??'';
if ($mode==='edit') {
    if (file_exists($file)) throw new RuntimeException('Pending edit cleanup');
    $menu=wp_get_nav_menu_object('services');
    $items=wp_get_nav_menu_items($menu->term_id);
    if (!$items) throw new RuntimeException('Missing actual source menu');
    $item=reset($items);$post=get_post($item->ID);
    $data=array('menu-item-object-id'=>$item->object_id,'menu-item-object'=>$item->object,'menu-item-parent-id'=>$item->menu_item_parent,
        'menu-item-position'=>$post->menu_order,'menu-item-type'=>$item->type,'menu-item-title'=>wp_slash($post->post_title),
        'menu-item-url'=>$item->url,'menu-item-description'=>wp_slash($post->post_content),'menu-item-attr-title'=>wp_slash($post->post_excerpt),
        'menu-item-target'=>$item->target,'menu-item-classes'=>implode(' ',$item->classes),'menu-item-xfn'=>$item->xfn,'menu-item-status'=>$post->post_status);
    $snapshot=array('menu'=>$menu->term_id,'item'=>$item->ID,'data'=>$data,'raw_title'=>$post->post_title);
    file_put_contents($file,wp_json_encode($snapshot));chmod($file,0600);
    $data['menu-item-title']=wp_slash($item->title.' [IU staging cache proof]');
} elseif ($mode==='restore') {
    $snapshot=json_decode(file_get_contents($file),true);$data=$snapshot['data'];
} else throw new RuntimeException('Invalid mode');
$before=get_option(IU_Elementor_Fragment_Cache::EPOCH);
$result=wp_update_nav_menu_item($snapshot['menu'],$snapshot['item'],$data);
if (is_wp_error($result) || $result!==$snapshot['item']) throw new RuntimeException('Menu item API failed');
if (get_option(IU_Elementor_Fragment_Cache::EPOCH)===$before) throw new RuntimeException('Menu item update did not invalidate');
if ($mode==='restore') {
    if (get_post($snapshot['item'])->post_title!==$snapshot['raw_title']) throw new RuntimeException('Title cleanup mismatch');
    unlink($file);
}
echo wp_json_encode(array('mode'=>$mode,'actual_menu_item_api'=>true,'generation_changed'=>true,'own_title_restored'=>$mode==='restore'));
