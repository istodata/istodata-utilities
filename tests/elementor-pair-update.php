<?php
/** The former special workflow is retired by the 2026-10-26 product decision. */
require __DIR__ . '/elementor-update-guard.php';
$before=$settings;
foreach(array('admin_post_iu_elementor_update_override','admin_post_iu_elementor_update_pair') as $hook){
    $callbacks=$hooks[$hook][10];check(count($callbacks)===1,'One explicit retired endpoint');
    try{call_user_func($callbacks[0]);throw new LogicException('Retired endpoint executed');}
    catch(RuntimeException $e){check($e->getMessage()==='Retired:410','Old endpoint returns410 without an installer');}
}
foreach(array('override_update','pair_update') as $method){try{IU_Elementor_Update_Guard::$method();throw new LogicException('Old callable executed');}catch(RuntimeException $e){check($e->getMessage()==='Retired:410','Old callable is inert');}}
check(!IU_Elementor_Update_Guard::authorize_override('elementor/elementor.php','old-fingerprint','valid-old-nonce',true),'No hidden override grant');
foreach(array('pair_upgrader','pair_skin','override_skin','pair_authorized','override_matches') as $method)check(!method_exists('IU_Elementor_Update_Guard',$method),'Old mutation factory/grant removed: '.$method);
check(is_wp_error(IU_Elementor_Update_Guard::run_pair_update(new stdClass,'old','old',true)),'Old coordinator does not invoke upgrader');
define('ELEMENTOR_VERSION','4.3.4');define('ELEMENTOR_PRO_VERSION','4.3.1');
$settings['optimizations']=array('elementor_atomic_interaction_breakpoints'=>true,'elementor_fragment_cache'=>true);
$saved=$settings;
installed('4.3.5','4.3.1');
check(!iu_elementor_feature_supported('atomic')&&!iu_elementor_feature_supported('fragment'),'Actual mixed files fail closed despite stale accepted constants');
check($settings===$saved,'Bypass preserves opt-ins, saved duration and settings');
ob_start();IU_Elementor_Update_Guard::notices();IU_Elementor_Update_Guard::notices();$notice=ob_get_clean();check(substr_count($notice,'notice-warning')===1,'One short runtime notice across active features');
check(strpos($notice,'Checksum')===false && strpos($notice,'μπλοκ')===false,'No repeated evidence or update-blocking notice');
installed('4.3.5','4.3.2');check(iu_elementor_feature_supported('atomic')&&iu_elementor_feature_supported('fragment'),'Automatic runtime recovery on supported files');check($settings===$saved,'Recovery retains all settings');
installed('4.3.2','4.3.0');check(iu_elementor_feature_supported('atomic')&&!iu_elementor_feature_supported('fragment'),'Partial runtime support preserves unaffected feature');
echo "PASS retired mutation endpoints, stale-constant bypass, settings retention and per-feature recovery\n";
