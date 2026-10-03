<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com'||realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
$file='/home/master/applications/manqbfzxjy/tmp/iu-fragment-access-20261001/test-sessions.json';
$sessions=json_decode(file_get_contents($file),true);
require_once ABSPATH.'wp-admin/includes/user.php';
foreach(array('administrator','subscriber')as$role){
    $id=(int)$sessions[$role]['id'];$user=get_userdata($id);
    if(!$user||$user->user_login!=='iu_fragment_acceptance_'.$role)throw new RuntimeException('Identity mismatch');
    WP_Session_Tokens::get_instance($id)->destroy_all();
    if(!wp_delete_user($id))throw new RuntimeException('Delete failed');
}
if(isset($sessions['site_manager']))WP_Session_Tokens::get_instance((int)$sessions['site_manager']['id'])->destroy($sessions['site_manager']['token']);
deactivate_plugins('iu-fragment-access-harness/probe.php');
if(!unlink($file))throw new RuntimeException('Credential cleanup failed');
echo "Temporary identities, observer activation and own manager session removed.\n";
