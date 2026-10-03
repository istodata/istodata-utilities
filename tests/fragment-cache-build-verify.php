<?php
if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com'||realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
$manifest=json_decode(file_get_contents('/home/master/applications/manqbfzxjy/tmp/iu-fragment-access-20261001/final-build-manifest.json'),true);
$root=realpath(ABSPATH.'wp-content/plugins/istodata-utilities').'/';
foreach($manifest['files']as$name=>$hash){
    $file=realpath($root.$name);
    if(!$file||strpos($file,$root)!==0||!hash_equals($hash,hash_file('sha256',$file)))throw new RuntimeException('Build mismatch: '.$name);
}
echo wp_json_encode(array('zip_sha256'=>$manifest['zip_sha256'],'verified_files'=>count($manifest['files']),'installed_matches_zip'=>true),JSON_PRETTY_PRINT)."\n";
