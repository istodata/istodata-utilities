<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com'){echo "WRONG_TARGET\n";return;}
wp_set_current_user(1); require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
wp_update_plugins();$plugin='elementor-pro/elementor-pro.php';$t=get_site_transient('update_plugins');$item=$t->response[$plugin]??null;
if(!$item||$item->new_version!=='4.3.1'||IU_Elementor_Update_Guard::installed_pair()!==['core'=>'4.3.3','pro'=>'4.2.2']){echo "TARGET_CHANGED\n";return;}
$f=IU_Elementor_Update_Guard::fingerprint($plugin,$item);$ok=IU_Elementor_Update_Guard::authorize_override($plugin,$f,wp_create_nonce('iu_elementor_override_'.$f),true);
if(!$ok){echo "OVERRIDE_DENIED\n";return;}
$skin=new WP_Ajax_Upgrader_Skin();$u=new Plugin_Upgrader($skin);$r=$u->upgrade($plugin);$e=$skin->get_errors();
echo wp_json_encode(['authorized_exact_override'=>$ok,'result'=>is_wp_error($r)?$r->get_error_code():($e->has_errors()?$e->get_error_code():$r),'pair'=>IU_Elementor_Update_Guard::installed_pair()])."\n";
