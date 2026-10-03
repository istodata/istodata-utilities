<?php
/** Staging-only temporary identities and scoped rollback state. Run once via WP CLI. */
if (get_option('home') !== 'https://wordpress-218158-6702910.cloudwaysapps.com' ||
    realpath(ABSPATH) !== '/home/218158.cloudwaysapps.com/manqbfzxjy/public_html') throw new RuntimeException('Wrong target');
$private = '/home/master/applications/manqbfzxjy/tmp/iu-fragment-access-20261001';
if (!is_dir($private)) throw new RuntimeException('Missing private backup');
if (!file_exists($private . '/options-rollback.json')) file_put_contents($private . '/options-rollback.json', wp_json_encode(array(
    'istodata_utilities_settings' => get_option('istodata_utilities_settings'),
    'active_plugins' => get_option('active_plugins'),
    'iu_elementor_fragment_epoch' => get_option('iu_elementor_fragment_epoch'))));
$users = array();
foreach (array('administrator','subscriber') as $role) {
    $login = 'iu_fragment_acceptance_' . $role;
    if (username_exists($login)) throw new RuntimeException('Temporary identity already exists');
    $id = wp_insert_user(array('user_login' => $login, 'user_pass' => wp_generate_password(48),
        'user_email' => $login . '@example.invalid', 'role' => $role));
    if (is_wp_error($id)) throw new RuntimeException('Temporary identity creation failed');
    $expiry = time()+7200;
    $token = WP_Session_Tokens::get_instance($id)->create($expiry);
    $users[$role] = array('id'=>$id,'name'=>LOGGED_IN_COOKIE,'value'=>wp_generate_auth_cookie($id,$expiry,'logged_in',$token),
        'auth_name'=>SECURE_AUTH_COOKIE,'auth_value'=>wp_generate_auth_cookie($id,$expiry,'secure_auth',$token));
}
file_put_contents($private.'/test-sessions.json',wp_json_encode($users));
chmod($private.'/test-sessions.json',0600);
$settings=get_option('istodata_utilities_settings',array());
$settings['optimizations']['elementor_fragment_cache']=true;
update_option('istodata_utilities_settings',$settings);
echo "Verified staging; global ON; two temporary identities ready (credentials withheld).\n";
