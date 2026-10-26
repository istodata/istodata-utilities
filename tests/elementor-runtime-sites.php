<?php
/** Runtime activation cache must follow switch_to_blog and shared file updates. */
$blog_id=1;$network_id=1;
require __DIR__ . '/elementor-update-guard.php';
function get_current_blog_id(){return $GLOBALS['blog_id'];}
function get_current_network_id(){return $GLOBALS['network_id'];}
installed('4.2.3','4.9.0');
check(!iu_elementor_feature_supported('fragment'),'Active unsupported Pro on first site');
$blog_id=2;$active_plugins=array('elementor/elementor.php');
check(iu_elementor_feature_supported('fragment'),'Second site with inactive Pro retains core-only support without a manual refresh');
$blog_id=1;$active_plugins=array('elementor/elementor.php','elementor-pro/elementor-pro.php');
check(!iu_elementor_feature_supported('fragment'),'Restored first-site activation must not inherit second-site result');
$blog_id=2;$active_plugins=array('elementor/elementor.php');
installed('4.3.5','4.3.2');
check(!iu_elementor_feature_supported('fragment'),'Post-install refresh invalidates core-only cache');
$blog_id=1;$active_plugins=array('elementor/elementor.php','elementor-pro/elementor-pro.php');
check(iu_elementor_feature_supported('fragment'),'Shared file update invalidates cached results on every switched site');
echo "PASS site/network-aware runtime activation cache and shared post-install invalidation\n";
