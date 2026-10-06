<?php
/** Exact pair and activation gates for the separate Atomic cache adapter. */
namespace Elementor { class Plugin { public static $instance; } }
namespace Elementor\Modules\AtomicWidgets { class Module { const EXPERIMENT_NAME='e_atomic_elements'; } }
namespace {
define('ABSPATH',__DIR__);
define('ELEMENTOR_VERSION','4.3.3');define('ELEMENTOR_PRO_VERSION','4.3.1');
function get_option($key,$default=false){return $key==='istodata_utilities_settings'?($GLOBALS['settings']??array()):$default;}
require dirname(__DIR__).'/includes/elementor-compatibility.php';
\Elementor\Plugin::$instance=(object)array('experiments'=>new class {
    function is_feature_active($name){return $GLOBALS['experiment'];}
});
function check($ok,$message){if(!$ok)throw new \RuntimeException($message);}
foreach(array(array(true,false,true,true),array(false,true,true,true),array(false,false,true,false),array(true,true,false,false)) as $case){
    list($cache,$device,$experiment,$expected)=$case;
    $GLOBALS['settings']=array('optimizations'=>array('elementor_fragment_cache'=>$cache),'additional'=>array('elementor_device_visibility'=>$device));
    $GLOBALS['experiment']=$experiment;
    check(!empty(iu_elementor_active_features()['fragment_atomic'])===$expected,'Activation gate');
}
check(iu_elementor_feature_supported('fragment_atomic'),'Tested installed pair missing');
check(iu_elementor_feature_supported('fragment_atomic',array('core'=>'4.3.4','pro'=>'4.3.1')),'Reviewed new pair missing');
foreach(array(array('4.3.5','4.3.1'),array('4.3.3','4.3.2'),array('4.2.3','4.2.2'),array('4.3.3',null)) as $pair){
    check(!iu_elementor_feature_supported('fragment_atomic',array('core'=>$pair[0],'pro'=>$pair[1])),'Unreviewed pair accepted');
}
$GLOBALS['settings']=array('additional'=>array('elementor_device_visibility'=>true));$GLOBALS['experiment']=true;
check(iu_elementor_pair_failures(array('core'=>'4.3.5','pro'=>'4.3.1'),iu_elementor_active_features())===array('fragment_atomic'),'Affected update not gated');
echo "PASS Atomic adapter exact pair, cache/device activation, experiment OFF and unsupported patch/update gates\n";
}
