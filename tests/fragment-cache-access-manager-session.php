<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com')throw new RuntimeException('Wrong target');
$id=0;
foreach((array)wlcms_field_setting('wlcms_admin') as $candidate){if(user_can($candidate,'manage_options')){$id=(int)$candidate;break;}}
if(!$id)throw new RuntimeException('No existing authorized manager');
$file='/home/master/applications/manqbfzxjy/tmp/iu-fragment-access-20261001/test-sessions.json';
$sessions=json_decode(file_get_contents($file),true);
if(isset($sessions['site_manager']))throw new RuntimeException('Session already created');
$expiry=time()+7200;$token=WP_Session_Tokens::get_instance($id)->create($expiry);
$sessions['site_manager']=array('id'=>$id,'token'=>$token,'name'=>LOGGED_IN_COOKIE,'value'=>wp_generate_auth_cookie($id,$expiry,'logged_in',$token),
    'auth_name'=>AUTH_COOKIE,'auth_value'=>wp_generate_auth_cookie($id,$expiry,'auth',$token),
    'secure_name'=>SECURE_AUTH_COOKIE,'secure_value'=>wp_generate_auth_cookie($id,$expiry,'secure_auth',$token));
file_put_contents($file,wp_json_encode($sessions));
echo "Temporary session for existing authorized manager prepared; no profile or role change.\n";
