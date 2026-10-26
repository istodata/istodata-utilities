<?php
/** Additive accepted support: actual installed files and existing runtime consumers. */
require __DIR__ . '/elementor-update-guard.php';
function do_action() {}
function did_action() { return 0; }
function get_locale() { return 'el'; }
eval('namespace Elementor; class Widget_Base { public function get_data($key) { return null; } }');
require IU_PLUGIN_PATH . 'includes/elementor-atomic-interaction-breakpoints.php';
require IU_PLUGIN_PATH . 'includes/elementor-fragment-cache.php';
$contract=array(
    'atomic'=>array(array('4.3.2','4.3.0'),array('4.3.3','4.3.1'),array('4.3.4','4.3.1'),array('4.3.5','4.3.2')),
    'fragment_atomic'=>array(array('4.3.3','4.3.1'),array('4.3.4','4.3.1'),array('4.3.5','4.3.2')),
    'fragment'=>array(array('4.2.3','4.2.2'),array('4.2.3',null),array('4.3.3','4.3.1'),array('4.3.4','4.3.1'),array('4.3.5','4.3.2')));
$registry=iu_elementor_compatibility_registry();$all=array();$count=0;
foreach($contract as $feature=>$pairs)foreach($pairs as $pair){
    $rule=array('core'=>array('exact'=>$pair[0]),'pro'=>$pair[1]===null?null:array('exact'=>$pair[1]));
    check(in_array($rule,$registry[$feature]['accepted'],true),'Previously accepted declaration removed: '.$feature.' '.json_encode($pair));
    $all[json_encode($pair)]=$pair;$count++;
}
$versions=new ReflectionMethod('IU_Elementor_Fragment_Cache','versions_supported');
foreach($all as $pair){
    installed(...$pair);$saved=$settings;
    foreach($contract as $feature=>$pairs){
        $expected=in_array($pair,$pairs,true);
        check(iu_elementor_feature_supported($feature)===$expected,'Installed runtime path differs for '.$feature.' '.json_encode($pair));
        check(iu_elementor_feature_supported($feature,null,true)===($expected&&$pair[1]!==null),'Documented Pro dependency changed');
    }
    check(iu_atomic_interaction_breakpoints_supported()===in_array($pair,$contract['atomic'],true),'Existing Atomic runtime consumer');
    $fragment=in_array($pair,$contract['fragment'],true);check($versions->invoke(null)===$fragment,'Existing fragment engine gate');
    $manager=new class{public $widget;function register($widget){$this->widget=$widget;}};
    IU_Elementor_Fragment_Cache::proxy($manager);
    check(($manager->widget instanceof \Elementor\Widget_Base)===$fragment,'Classic/core-only proxy bootstrap path');
    if($fragment)check($manager->widget->get_name()==='iu-fragment-proxy','Shipped proxy ID preserved');
    check($settings===$saved,'Old-version runtime path does not rewrite settings/duration');
}
$active_plugins=array('elementor/elementor.php');installed('4.2.3','4.9.0');
check(iu_elementor_feature_supported('fragment')&&!iu_elementor_feature_supported('fragment',null,true),'Inactive installed Pro preserves documented core-only static path, not Pro adapters');
check(IU_Elementor_Update_Guard::installed_pair()['pro']==='4.9.0','Advisory still reads actual installed counterpart');
$network_plugins=array('elementor-pro/elementor-pro.php'=>123);installed('4.2.3','4.9.0');
check(!iu_elementor_feature_supported('fragment'),'Network-active Pro cannot be detached to widen support');
$network_plugins=array();$active_plugins=array('elementor/elementor.php','elementor-pro/elementor-pro.php');
$active_plugins=array();installed('4.3.4','4.3.1');
check(!iu_elementor_feature_supported('atomic')&&!iu_elementor_feature_supported('fragment'),'Installed but inactive Core cannot enable runtime adapters');
$active_plugins=array('elementor/elementor.php','elementor-pro/elementor-pro.php');
eval('namespace Elementor; class Plugin { public static $instance; }');
eval('namespace Elementor\\Modules\\AtomicWidgets; class Module { const EXPERIMENT_NAME="e_atomic_elements"; }');
\Elementor\Plugin::$instance=(object)array('experiments'=>new class {function is_feature_active($name){return true;}});
$settings=array('additional'=>array('elementor_device_visibility'=>true));installed('4.9.0','4.9.0');
check(!empty(iu_elementor_active_features()['fragment_atomic']),'Unsupported Atomic device path still reports its active bypass');
check(iu_elementor_pair_failures(iu_elementor_runtime_pair(),iu_elementor_active_features())===array('fragment_atomic'),'Only the affected active Atomic device feature is reported');
\Elementor\Plugin::$instance->experiments=new stdClass;
check(empty(iu_elementor_active_features()['fragment_atomic']),'Unavailable experiment API cannot cause an unsupported-version fatal');
echo 'PASS additive accepted contract: '.$count.' feature declarations / '.count($all)." distinct exact pairs including core-only, runtime consumers and proxy bootstrap\n";
