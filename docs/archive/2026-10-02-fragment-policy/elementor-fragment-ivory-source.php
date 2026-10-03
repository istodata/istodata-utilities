<?php
/** Pass the official plugin directory; vendor sources stay outside this repository. */
define('ABSPATH', __DIR__.'/');
define('WP_PLUGIN_DIR', dirname($argv[1]));
define('IS_VERSION', 'deliberately-not-an-approved-version');
function add_action(){} function add_filter(){} function get_option($k,$d=false){return $d;}
function apply_filters($hook,$value){return $value;}
require $argv[1].'/public/class-is-public.php';
require dirname(__DIR__).'/includes/elementor-fragment-cache-graph.php';
$graph=new IU_Elementor_Fragment_Graph;
$check=new ReflectionMethod($graph,'callback_safe');
$object=new IS_Public;
if(($argv[2]??'')==='reject'){
if($check->invoke($graph,[$object,'pre_get_posts'],'pre_get_posts'))throw new RuntimeException('Changed source accepted');
echo "PASS changed method source rejected despite callback name and origin\n";exit;
}
foreach(['pre_get_posts','posts_join','posts_distinct_request','wp_nav_menu_items']as$method){
$hook=$method==='posts_distinct_request'?'posts_distinct_request':$method;
if(!$check->invoke($graph,[$object,$method],$hook))throw new RuntimeException('Actual source rejected: '.$method);
if($check->invoke($graph,[$object,$method],'wrong_hook'))throw new RuntimeException('Wrong hook accepted');
}
$query=new class {public $query_vars=['post_type'=>'post','s'=>''];function is_search(){return false;}};
$before=get_object_vars($query);$object->pre_get_posts($query);
if(get_object_vars($query)!==$before)throw new RuntimeException('Non-search query changed');
function is_admin(){return false;}
if($object->posts_join('JOIN ORIGINAL',$query)!=='JOIN ORIGINAL'||$object->posts_distinct_request('ORIGINAL',$query)!=='ORIGINAL')throw new RuntimeException('Non-search SQL changed');
$object->opt=['menus'=>[''=>true]];
if($check->invoke($graph,[$object,'wp_nav_menu_items'],'wp_nav_menu_items'))throw new RuntimeException('Search injection accepted');
echo "PASS actual Ivory method source, version independent, wrong-hook rejection, non-search no-op and menu-injection rejection\n";
