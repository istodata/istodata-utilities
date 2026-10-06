"""Observe real header rendering under the already installed private candidate."""
from pathlib import Path
from staging_asset_parity import capture as capture_css, verify as verify_assets
import json, re, hashlib, subprocess, sys, uuid, shlex

ROOT=Path(__file__).resolve().parents[1]
work=Path(sys.argv[1]); config=json.loads((work/'private.json').read_text())
remote='/home/master/applications/manqbfzxjy/tmp/'+work.name
web='/home/master/applications/manqbfzxjy/public_html'
user='master_hnrnsyahbd@104.248.132.240'
key='C:/Users/pe/.ssh/cloudways_server_1'
loader=web+'/wp-content/mu-plugins/iu-434-classic-review.php'
def ssh(code):
    p=subprocess.run(['ssh','-i',key,'-o','BatchMode=yes',user,code],capture_output=True,timeout=100)
    if p.returncode:raise RuntimeError(p.stderr.decode()[:500])
    return p.stdout.decode()
def put(name,text):
    (work/name).write_text(text)
    subprocess.run(['scp','-O','-i',key,str(work/name),user+':'+remote+'/'+name],capture_output=True,check=True)

observer=r'''<?php
if(!defined('ABSPATH')||($_SERVER['HTTP_HOST']??'')!=='wordpress-218158-6702910.cloudwaysapps.com')return;
$cfg=json_decode(file_get_contents(__DIR__.'/private.json'),true);
if(!hash_equals($cfg['key'],$_SERVER['HTTP_X_IU_CLASSIC_PROBE']??''))return;
if(!defined('DONOTCACHEPAGE'))define('DONOTCACHEPAGE',true);
$GLOBALS['iu434']=['request'=>$_SERVER['HTTP_X_IU_CLASSIC_REQUEST']??'','events'=>[],'keys'=>[],'widgets'=>[],'loops'=>0,'loops_all'=>0,'in_header'=>false];
if(($_SERVER['HTTP_X_IU_CLASSIC_MODE']??'')==='native')add_filter('iu_elementor_fragment_eligible','__return_false');
add_action('iu_elementor_fragment_before_substitution',function($node,$doc,$key){if(in_array((int)$doc,[30,33000],true)&&is_string($key))$GLOBALS['iu434']['keys'][$doc.':'.$node['id']]=$key;},10,3);
add_action('iu_elementor_fragment_result',function($state,$build){if(in_array((int)($build['document_id']??0),[30,33000],true))$GLOBALS['iu434']['events'][]=['state'=>$state,'id'=>$build['element_id']];},10,2);
add_action('elementor/frontend/widget/before_render',function($widget){$GLOBALS['iu434']['widgets'][]=$widget->get_id();if($widget->get_id()==='26fa46c6')$GLOBALS['iu434']['in_header']=true;},-100);
add_action('elementor/frontend/widget/after_render',function($widget){if($widget->get_id()==='26fa46c6')$GLOBALS['iu434']['in_header']=false;},PHP_INT_MAX);
add_action('elementor/frontend/before_get_builder_content',function($document){if((int)$document->get_post()->ID===1635){++$GLOBALS['iu434']['loops_all'];if($GLOBALS['iu434']['in_header'])++$GLOBALS['iu434']['loops'];}});
add_action('shutdown',function(){echo '<!-- IU434_CLASSIC '.wp_json_encode($GLOBALS['iu434']).' -->';});
'''
rows=[];allkeys=set();result={}
try:
    put('classic-observer.php',observer)
    put('classic-loader.php','<?php require '+repr(remote+'/classic-observer.php')+';')
    ssh('test ! -e '+loader+' && setfacl -m u:manqbfzxjy:r '+remote+'/classic-observer.php && cp '+remote+'/classic-loader.php '+loader)
    targets=['d7ebc85','417d1ae','a1ff108','c60daee','e62c115','c28398e']
    # Parse each target's outer HTML using the original checked parser.
    import runpy
    parser=runpy.run_path(str(ROOT/'tests/fragment-cache-staging-check.py'))
    class Fragment(parser['TargetParser']):
        def __init__(self,target):super().__init__();self.target=target
        def handle_starttag(self,tag,attrs):
            attrs=[(k,'26fa46c6' if v==self.target else 'other') if k=='data-id' else (k,v) for k,v in attrs]
            super().handle_starttag(tag,attrs)
    def request(label,path,mode='',phone=False):
        req=uuid.uuid4().hex
        ua='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari/604.1' if phone else parser['UA']
        headers='X-IU-Classic-Probe: '+config['key']+'\nX-IU-Classic-Request: '+req+'\nX-IU-Classic-Mode: '+mode+'\nHost: wordpress-218158-6702910.cloudwaysapps.com\nX-Forwarded-Proto: https\nUser-Agent: '+ua+'\n'
        put('classic-headers.txt',headers)
        html=ssh('curl --silent --show-error --max-time 90 -H @'+remote+'/classic-headers.txt '+shlex.quote('http://127.0.0.1:8086'+path+('&' if '?' in path else '?')+'iu434_request='+req))
        (work/('classic-'+label+'.html')).write_text(html,encoding='utf-8')
        m=re.search(r'<!-- IU434_CLASSIC (.*?) -->',html);assert m,'No fresh origin PHP observation'
        data=json.loads(m[1]);assert data['request']==req
        # wp_json_encode emits [] for an empty PHP associative accumulator.
        if data['keys']==[]:data['keys']={}
        hashes={};menu_normalized_hashes={}
        for target in targets:
            p=Fragment(target);p.feed(html)
            fragment=html[parser['offset'](html,p.start):html.index('>',parser['offset'](html,p.end))+1] if p.start else ''
            hashes[target]=hashlib.sha256(fragment.encode()).hexdigest()
            # Locked opt-in policy accepts frozen menu highlighting only.
            normalized=fragment
            if target in ['a1ff108','e62c115']:
                def classes(match):
                    tokens=[x for x in match[1].split() if x not in ['current-menu-item','current-menu-parent','current-menu-ancestor','current_page_item','current_page_parent','current_page_ancestor','elementor-item-active','page_item'] and not re.fullmatch(r'page-item-[0-9]+',x)]
                    return 'class="'+' '.join(tokens)+'"'
                normalized=re.sub(r'class="([^"]*)"',classes,normalized)
                normalized=re.sub(r'\s+aria-current="[^"]*"','',normalized)
            menu_normalized_hashes[target]=hashlib.sha256(normalized.encode()).hexdigest()
        assets=re.findall(r'<(?:script|link)\b[^>]*(?:src|href)=[\"\x27]([^\"\x27]+)',html)
        allkeys.update(data['keys'].values())
        row=dict(label=label,**data,hashes=hashes,menu_normalized_hashes=menu_normalized_hashes,assets=assets,generated_css=capture_css(assets,ssh),
                 inline_style_sha256=[hashlib.sha256(x.encode()).hexdigest() for x in re.findall(r'<style\b[^>]*>(.*?)</style>',html,re.S)],
                 inline_sha256=[hashlib.sha256(x.encode()).hexdigest() for x in re.findall(r'<(?:style|script)\b[^>]*>(.*?)</(?:style|script)>',html,re.S)])
        rows.append(row);print(label+' loops='+str(data['loops'])+' events='+str(data['events']),flush=True)
        return row
    def clear(keys):
        assert all(re.fullmatch('iu_frag_[a-f0-9]{64}',k) for k in keys)
        put('classic-clear.php','<?php foreach(json_decode('+repr(json.dumps(list(keys)))+',true) as $key)delete_transient($key);')
        ssh('cd '+web+' && wp eval-file '+remote+'/classic-clear.php')
    native=request('native','/techniki-ypostirixi/','native')
    seed=request('existing','/techniki-ypostirixi/');assert len(seed['keys'])==6
    clear(seed['keys'].values())
    miss=request('miss','/techniki-ypostirixi/');hit=request('hit-random','/techniki-ypostirixi/?unknown='+uuid.uuid4().hex)
    assert miss['loops']==150 and len(miss['events'])==6 and all(e['state']=='stored' for e in miss['events'])
    assert hit['loops']==0 and len(hit['events'])==6 and all(e['state']=='hit' for e in hit['events'])
    assert not set(targets)&set(hit['widgets']), 'Original cached widgets executed'
    assert native['hashes']==miss['hashes']==hit['hashes'],'Native/miss/hit HTML differs'
    changes=[verify_assets(native,miss),verify_assets(native,hit)]
    assert native['inline_style_sha256']==miss['inline_style_sha256']==hit['inline_style_sha256'], 'Inline CSS count/content/order differs'
    page=request('other-page-hit','/etairia/');assert page['keys']==hit['keys'] and page['loops']==0
    pn=request('other-page-native','/etairia/','native');assert page['menu_normalized_hashes']==pn['menu_normalized_hashes'], 'Cross-page HTML differs beyond accepted menu highlighting';changes.append(verify_assets(pn,page))
    assert page['inline_style_sha256']==pn['inline_style_sha256'], 'Cross-page inline CSS count/content/order differs'
    mobile=request('phone','/techniki-ypostirixi/',phone=True);assert mobile['loops']==0
    result={'result':'PASS','requests':rows,'verified_css_version_changes':changes,'limits':'Classic Greek header desktop cross-page and random URL parity; phone visibility. WPML Atomic variants tested separately.'}
except Exception as error:
    result={'result':'STOP','finding':str(error),'requests':rows};raise
finally:
    ssh('if test -e '+loader+'; then rm -- '+loader+'; fi')
    if allkeys:clear(allkeys)
    ssh('rm -f -- '+' '.join(remote+'/'+f for f in ['classic-observer.php','classic-loader.php','classic-headers.txt','classic-clear.php']))
    (work/'classic-headers.txt').unlink(missing_ok=True)
    (ROOT/'docs/elementor-434-classic-result.json').write_text(json.dumps(result,indent=2)+'\n')
