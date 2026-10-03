<?php
/** Temporary native Elementor fixture, staging only. Run through wp eval-file. */
if (realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html' || get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com') throw new Exception('Wrong staging');
wp_set_current_user(1);
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\PropTypes\Escaped_Html_Prop_Type as Html;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type as Str;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type as BoolProp;
use Elementor\Modules\Interactions\Props\Interaction_Item_Prop_Type as Item;
use Elementor\Modules\Interactions\Props\Interaction_Breakpoints_Prop_Type as Breakpoints;
use Elementor\Modules\Interactions\Props\Excluded_Breakpoints_Prop_Type as Excluded;
use Elementor\Modules\Interactions\Props\Animation_Preset_Prop_Type as Animation;
use Elementor\Modules\Interactions\Props\Animation_Config_Prop_Type as Config;
use Elementor\Modules\Interactions\Props\Timing_Config_Prop_Type as Timing;
use Elementor\Modules\Interactions\Props\Time_Size_Prop_Type as TimeSize;
$nodes=[];
foreach(['load','hover','click','scrollIn','scrollOut','scrollOn'] as $i=>$trigger){
 $node=Atomic_Heading::generate()->settings(['title'=>Html::generate('IU QA '.$trigger),'tag'=>Str::generate('h2')])->build();
 $node['id']='iuqa'.($i+1);
 $node['interactions']=['version'=>1,'items'=>[Item::generate([
  'interaction_id'=>Str::generate('iuqa-'.$trigger),'trigger'=>Str::generate($trigger),
  'breakpoints'=>Breakpoints::generate(['excluded'=>Excluded::generate(array_map([Str::class,'generate'],['mobile','mobile_extra','tablet','tablet_extra','laptop']))]),
  'animation'=>Animation::generate(['effect'=>Str::generate('fade'),'type'=>Str::generate('in'),
    'config'=>Config::generate(['replay'=>BoolProp::generate(true),'easing'=>Str::generate('easeIn')]),
    'timing_config'=>Timing::generate(['duration'=>TimeSize::generate(['size'=>600,'unit'=>'ms']),'delay'=>TimeSize::generate(['size'=>0,'unit'=>'ms'])])])
 ])]];
 $nodes[]=$node;
}
$data=[['id'=>'iuqaouter','elType'=>'container','settings'=>['content_width'=>'full'],'elements'=>$nodes]];
$existing=get_page_by_path('iu-kit-native-atomic-acceptance-20261001');
$id=$existing?$existing->ID:wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'IU staging native Atomic acceptance','post_name'=>'iu-kit-native-atomic-acceptance-20261001'],true);
if(is_wp_error($id))throw new Exception($id->get_error_code());
update_post_meta($id,'_elementor_edit_mode','builder');
update_post_meta($id,'_elementor_template_type','wp-page');
update_post_meta($id,'_wp_page_template','elementor_canvas');
update_post_meta($id,'rank_math_robots',['noindex','nofollow']);
$doc=Elementor\Plugin::$instance->documents->get($id);
$ok=$doc->save(['elements'=>$data,'settings'=>['custom_css'=>'selector [data-id^="iuqa"] { opacity: .8; } selector [data-id="iuqaouter"] { gap: 180px; padding: 80px 30px 1200px; }']]);
echo wp_json_encode(['id'=>$id,'saved'=>$ok,'url'=>get_permalink($id),'nodes'=>count($nodes)]);

