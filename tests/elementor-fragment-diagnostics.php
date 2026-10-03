<?php
/** Bounded admin observations and on-demand reasons, without rendering. */
define('ABSPATH',__DIR__.'/');
function add_action(){}function add_filter(){}function __($s){return $s;}
function current_user_can(){return $GLOBALS['admin']??false;}function is_admin(){return false;}
function get_locale(){return 'el';}function get_option($k,$d=[]){return $GLOBALS['options'][$k]??$d;}
function update_option($k,$v,$autoload){if($autoload!==false)throw new RuntimeException('Autoload');$GLOBALS['options'][$k]=$v;$GLOBALS['writes']=($GLOBALS['writes']??0)+1;}
function get_post_meta(){return json_encode([['id'=>'item','widgetType'=>'heading','settings'=>['iu_fragment_cache'=>'yes']]]);}
function iu_elementor_fragment_enabled(){return $GLOBALS['global']??true;}
function iu_elementor_feature_supported(){return true;}function wp_date($f,$t){return date($f,$t);}
class IU_Elementor_Fragment_Graph {static function inspect(){return false;}static function rejection(){return ['reason'=>'dynamic-context'];}}
require dirname(__DIR__).'/includes/elementor-fragment-cache-diagnostics.php';
IU_Elementor_Fragment_Diagnostics::record(30,'item','hit');IU_Elementor_Fragment_Diagnostics::flush();
if(!empty($GLOBALS['options']))throw new RuntimeException('Anonymous telemetry');
$GLOBALS['admin']=true;$GLOBALS['options']['elementor_element_cache_ttl']='disable';
$r=IU_Elementor_Fragment_Diagnostics::status(30,'item');
if($r['static_review']!=='blocked'||$r['detail']['reason']!=='dynamic-context'||!empty($r['observed']))throw new RuntimeException('Reason or static status');
IU_Elementor_Fragment_Diagnostics::record(30,'item','hit');IU_Elementor_Fragment_Diagnostics::flush();
IU_Elementor_Fragment_Diagnostics::flush();if($GLOBALS['writes']!==1)throw new RuntimeException('Repeated write spam');
for($i=0;$i<75;$i++)IU_Elementor_Fragment_Diagnostics::record(30,'item'.$i,'bypass');
IU_Elementor_Fragment_Diagnostics::flush();if(count($GLOBALS['options']['iu_fragment_diagnostics'])!==50)throw new RuntimeException('Unbounded observation');
$GLOBALS['global']=false;$r=IU_Elementor_Fragment_Diagnostics::status(30,'item');
if($r['detail']['reason']!=='global-off'||!$r['configured'])throw new RuntimeException('Configured vs global');
$writes=$GLOBALS['writes'];IU_Elementor_Fragment_Diagnostics::record(30,'disabled','bypass');IU_Elementor_Fragment_Diagnostics::flush();
if($GLOBALS['writes']!==$writes)throw new RuntimeException('Observation write while global OFF');
$GLOBALS['global']=true;$_GET['elementor-preview']='30';
IU_Elementor_Fragment_Diagnostics::record(30,'preview','bypass');IU_Elementor_Fragment_Diagnostics::flush();
if($GLOBALS['writes']!==$writes)throw new RuntimeException('Observation write in native preview');
echo "PASS admin-only diagnostics, structural rejection, configured/global distinction, OFF inactivity, throttle and 50-row bound\n";
