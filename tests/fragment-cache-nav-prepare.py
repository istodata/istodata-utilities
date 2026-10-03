"""Prepare staging-only fixtures; no credentials are printed or stored in the repository."""
from pathlib import Path
import tempfile, json, hashlib, zipfile, shutil
root=Path(__file__).resolve().parents[1]
temp=Path(tempfile.gettempdir())
fixtures=temp/'iu-nav-fixtures'; fixtures.mkdir(exist_ok=True)
private='/home/master/applications/manqbfzxjy/tmp/iu-fragment-nav-20261002'
old='/home/master/applications/manqbfzxjy/tmp/iu-fragment-root-20261002'
for name in ['setup.php','admin-session.php','manager-session.php','cleanup.php']:
    text=(temp/'iu-root-fixtures'/name).read_text(encoding='utf8').replace(old,private)
    (fixtures/name).write_text(text,encoding='utf8')
targets=['d7ebc85','a1ff108','e62c115','c28398e','417d1ae','c60daee']
guard="if(get_option('home')!=='https://wordpress-218158-6702910.cloudwaysapps.com'||realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');"
(fixtures/'configure.php').write_text('''<?php
'''+guard+'''
$before=[];$report=[];foreach([30,33000]as$id){$raw=get_post_meta($id,'_elementor_data',true);$before[$id]=$raw;$found=[];
$walk=function($nodes)use(&$walk,&$found,$id,&$report){foreach($nodes as &$n){$eid=$n['id']??'';
if(in_array($eid,['d7ebc85','a1ff108','e62c115','c28398e','417d1ae','c60daee'],true)){
$n['settings']['iu_fragment_cache']='yes';$n['settings']['iu_fragment_cache_ttl']='604800';$found[]=$eid;
$report[]=array('document'=>$id,'id'=>$eid,'type'=>$n['widgetType'],'menu'=>$n['settings']['menu']??null);}
if($eid==='26fa46c6')$n['settings']['iu_fragment_cache']='';
if(!empty($n['elements']))$n['elements']=$walk($n['elements']);}unset($n);return $nodes;};
$data=$walk(json_decode($raw,true));if(count($found)!==6)throw new RuntimeException('Missing expected counterparts in header '.$id);
file_put_contents('''+repr(private+'/element-data-before-'+str(0)+'.json')+''',wp_json_encode($before));
update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($data)));}
echo wp_json_encode($report,JSON_PRETTY_PRINT);
''',encoding='utf8')
probe=(root/'tests/fragment-cache-optin-harness.php').read_text(encoding='utf8')
probe=probe.replace("($node['widgetType'] ?? '') === 'template' && in_array($node['id'],array('417d1ae','c60daee'),true)","in_array($node['id'],array('d7ebc85','a1ff108','e62c115','c28398e','417d1ae','c60daee'),true)")
probe=probe.replace("$GLOBALS['iu_cross']['rendered_widgets']=0;",'''$GLOBALS['iu_cross']['rendered_widgets']=0;
    $GLOBALS['iu_cross']['nav_renders']=array();$GLOBALS['iu_cross']['nav_construction']=array();$GLOBALS['iu_cross']['nav_resolved']=array();
    add_action('elementor/frontend/widget/before_render',function($w){if($w->get_name()==='nav-menu')$GLOBALS['iu_cross']['nav_renders'][]=$w->get_id();},-20);
    add_filter('wp_nav_menu_args',function($a){$GLOBALS['iu_cross']['nav_construction'][]=$a['menu_id']??'';return $a;},PHP_INT_MAX);
    add_filter('wp_nav_menu_objects',function($items,$a){$GLOBALS['iu_cross']['nav_resolved'][]=array('menu_id'=>$a->menu_id,'items'=>array_map(function($i){return array('id'=>$i->ID,'title'=>$i->title,'url'=>$i->url);},$items));return $items;},PHP_INT_MAX,2);
''')
(fixtures/'probe.php').write_text(probe,encoding='utf8')
archive=root.parent/'istodata-utilities.zip'
shutil.copy2(archive,fixtures/'accepted-before.zip')
(fixtures/'baseline-main.php').write_text((root/'includes/elementor-fragment-cache.php').read_text(),encoding='utf8')
print(str(fixtures))
