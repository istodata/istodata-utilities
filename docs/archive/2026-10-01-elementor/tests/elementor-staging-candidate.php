<?php
/** Staging-only experimental API adapter. No registry approval or update bypass. */
if(!defined('ABSPATH')) exit;
function iu_staging_candidate_feature_supported($feature,$pair=null,$requires_pro=false){
 if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html'||get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com') return false;
 $pair=$pair===null?iu_elementor_runtime_pair():$pair;
 return in_array($feature,['atomic','fragment'],true)&&($pair['core']??null)==='4.3.3'&&($pair['pro']??null)==='4.3.1';
}
add_action('admin_notices',function(){if(current_user_can('manage_options'))echo '<div class="notice notice-warning"><p>STAGING TEST ONLY: experimental Atomic/Fragment API adapter for core 4.3.3 / Pro 4.3.1. This pair is not yet approved in the Kit registry.</p></div>';});
