<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com')throw new RuntimeException('Wrong target');
$file='/home/master/applications/manqbfzxjy/tmp/iu-fragment-access-20261001/test-sessions.json';
$sessions=json_decode(file_get_contents($file),true);
foreach($sessions as &$s){
    $cookie=wp_parse_auth_cookie($s['value'],'logged_in');
    $s['secure_name']=$s['auth_name'];$s['secure_value']=$s['auth_value'];
    $s['auth_name']=AUTH_COOKIE;$s['auth_value']=wp_generate_auth_cookie($s['id'],$cookie['expiration'],'auth',$cookie['token']);
    wp_set_current_user($s['id']);
    echo 'test_role='.$s['id'].' manage_options='.(int)current_user_can('manage_options').' read='.(int)current_user_can('read')."\n";
}
file_put_contents($file,wp_json_encode($sessions));
