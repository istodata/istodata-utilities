"""Disposable Atomic Loop early-bypass experiment, staging only. No release assets."""
from pathlib import Path
from staging_asset_parity import capture as capture_css, verify as verify_assets
import hashlib, json, re, secrets, shlex, subprocess, tempfile, sys, time

ROOT = Path(__file__).resolve().parents[1]
WORK = Path(tempfile.mkdtemp(prefix='iu-atomic-probe-'))
REMOTE = '/home/master/applications/manqbfzxjy/tmp/' + WORK.name
WEB = '/home/master/applications/manqbfzxjy/public_html'
HOST = 'wordpress-218158-6702910.cloudwaysapps.com'
USER = 'master_hnrnsyahbd@104.248.132.240'
KEY = 'C:/Users/pe/.ssh/cloudways_server_1'
LOADER = WEB + '/wp-content/mu-plugins/iu-atomic-private-probe.php'

def command(args):
    p = subprocess.run(args, capture_output=True, timeout=180)
    if p.returncode:
        raise RuntimeError(p.stderr.decode(errors='replace')[:800])
    return p.stdout.decode(errors='replace')

def remote(cmd):
    return command(['ssh', '-i', KEY, '-o', 'BatchMode=yes', USER, cmd])

def put(name, data):
    (WORK/name).write_text(data, encoding='utf-8')
    command(['scp', '-O', '-i', KEY, str(WORK/name), USER+':'+REMOTE+'/'+name])

def wp(name):
    output = remote('cd '+WEB+' && wp eval-file '+REMOTE+'/'+name)
    # WordPress/Elementor can emit upgrade logs on stdout before eval-file JSON.
    # Read snapshots from a private file; command errors still fail explicitly.
    return remote('cat '+REMOTE+'/snapshot-result.json') if name == 'snapshot.php' else output

def prop(kind, value):
    return {'$$type': kind, 'value': value}

def element(id, type, settings, children):
    return dict(id=id, elType=type, settings=settings, elements=children,
                version=1, styles={}, interactions=[])

remote('test ! -e '+LOADER+' && mkdir -m 700 '+REMOTE+' && setfacl -m u:manqbfzxjy:rx '+REMOTE)
fixture = 0
report = {}
before = None
experiment_restore = False
engine = '--engine' in sys.argv
installed_release = '--installed-release' in sys.argv
standalone = '--standalone' in sys.argv
installed = False
review_core = next((arg.split('=',1)[1] for arg in sys.argv if arg.startswith('--review-core=')), None)
if review_core not in (None, '4.3.4'):
    raise RuntimeError('Only the explicitly reviewed staging candidate is allowed')
runtime_files = ['elementor-fragment-cache.php','elementor-fragment-cache-graph.php',
    'elementor-fragment-cache-atomic.php','elementor-compatibility.php',
    'elementor-device-visibility.php','elementor-fragment-cache-diagnostics.php',
    'elementor-fragment-cache-excerpt.php']
try:
    put('snapshot.php', r"""<?php
$out=[];
$out['versions']=['core'=>ELEMENTOR_VERSION,'pro'=>ELEMENTOR_PRO_VERSION];
$p=\Elementor\Plugin::$instance;
$out['atomic_widgets_active']=$p->experiments->is_feature_active(\Elementor\Modules\AtomicWidgets\Module::EXPERIMENT_NAME);
$out['atomic_loop_registered']=isset($p->elements_manager->get_element_types()['e-collection-loop']);
$missing=new stdClass();$value=get_option('elementor_experiment-e_atomic_elements',$missing);
$out['atomic_option']=['exists'=>$value!==$missing,'value'=>$value===$missing?null:$value];
foreach(['active_plugins','istodata_utilities_settings','elementor_element_cache_ttl'] as $k)$out[$k]=get_option($k);
foreach([30,33000] as $id)$out['document_'.$id]=hash('sha256',serialize(get_post($id)).serialize(get_post_meta($id)));
file_put_contents(__DIR__.'/snapshot-result.json',wp_json_encode($out));
""")
    before = wp('snapshot.php')
    put('before.json', before)
    state = json.loads(before)
    assert state['versions']=={'core':review_core or '4.3.3','pro':'4.3.1'}, 'Staging pair changed; stop before runtime replacement'
    if engine and not installed_release:
        # Preserve the installed Kit version and all unrelated modules. Restore
        # these narrowly selected files byte-for-byte even on an acceptance stop.
        put('install.php', "<?php\n"+r'''
if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
$files=json_decode(file_get_contents(__DIR__.'/runtime.json'),true);$backup=[];
foreach($files as $file){$path=WP_PLUGIN_DIR.'/istodata-utilities/includes/'.$file;
$backup[$file]=is_file($path)?base64_encode(file_get_contents($path)):null;}
$opts=[];foreach(['iu_elementor_atomic_fragment_epoch','iu_elementor_fragment_epoch'] as $k){$m=new stdClass();$v=get_option($k,$m);$opts[$k]=['exists'=>$v!==$m,'value'=>$v===$m?null:$v];}
file_put_contents(__DIR__.'/runtime-backup.json',wp_json_encode(['files'=>$backup,'options'=>$opts]));
foreach($files as $file){if(file_put_contents(WP_PLUGIN_DIR.'/istodata-utilities/includes/'.$file,file_get_contents(__DIR__.'/candidate-'.$file))===false)throw new RuntimeException('Install failed');}
echo 'installed';
''')
        put('restore-runtime.php', "<?php\n"+r'''
if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
$old=json_decode(file_get_contents(__DIR__.'/runtime-backup.json'),true);
foreach($old['files'] as $file=>$data){$path=WP_PLUGIN_DIR.'/istodata-utilities/includes/'.$file;if($data===null){if(is_file($path))unlink($path);}else{file_put_contents($path,base64_decode($data));if(hash_file('sha256',$path)!==hash('sha256',base64_decode($data)))throw new RuntimeException('Restore mismatch');}}
foreach($old['options'] as $k=>$v){if($v['exists'])update_option($k,$v['value'],false);else delete_option($k);}
echo 'byte-identical originals restored';
''')
        put('runtime.json',json.dumps(runtime_files))
        for file in runtime_files:
            source=(ROOT/'includes'/file).read_text(encoding='utf-8')
            if file == 'elementor-compatibility.php' and review_core:
                # Private test copy only: no local/runtime release approval is inferred.
                source=source.replace("'exact' => '4.3.3'", "'exact' => '"+review_core+"'")
            put('candidate-'+file,source)
        remote('setfacl -m u:manqbfzxjy:rwx '+REMOTE)
        installed=True
        report['runtime_install']=wp('install.php').strip()
        put('runtime-check.php', "<?php\n"+r'''
$p=\Elementor\Plugin::$instance;
$loop=$p->elements_manager->get_element_types()['e-collection-loop'];
$schema=$loop->get_props_schema();
echo wp_json_encode(['pair'=>iu_elementor_feature_supported('fragment_atomic'),'global'=>iu_elementor_fragment_enabled(),
'schema'=>array_intersect_key($schema,array_flip(['iu_fragment_cache','iu_fragment_cache_ttl','iu_hide_on_phone','iu_hide_on_desktop_tablet']))]);
''')
        report['runtime_check']=json.loads(wp('runtime-check.php'))
        assert report['runtime_check']['pair'] and report['runtime_check']['global'], 'Runtime cache/pair unavailable'
    if '--enable-atomic-temporarily' in sys.argv:
        put('restore-experiment.php', """<?php
if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
$old=json_decode(file_get_contents(__DIR__.'/before.json'),true)['atomic_option'];
if($old['exists'])update_option('elementor_experiment-e_atomic_elements',$old['value']);
else delete_option('elementor_experiment-e_atomic_elements');
echo 'restored';
""")
        put('activate.php', "<?php\nif(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');update_option('elementor_experiment-e_atomic_elements','active');echo 'enabled';")
        experiment_restore = True
        wp('activate.php')
        state = json.loads(wp('snapshot.php'))
        report['temporary_atomic_state'] = {k:state[k] for k in ['atomic_widgets_active','atomic_loop_registered']}
    if not state['atomic_widgets_active'] or not state['atomic_loop_registered']:
        raise RuntimeError('Atomic Widgets inactive or e-collection-loop unregistered: native render cannot validate cache')
    heading = element('iuapheading', 'widget', {'title': prop('escaped-html', 'Atomic probe item')}, [])
    heading['widgetType'] = 'e-heading'
    advanced = '--dynamic-fixture' in sys.argv
    if advanced:
        heading['settings'] = {'title': prop('dynamic', {'name':'post-title','group':'post','settings':{}}),
            'link':prop('link',{'destination':prop('dynamic',{'name':'post-url','group':'post','settings':{}})}),
            'classes':prop('classes',['iuapstyle'])}
        heading['styles'] = {'iuapstyle': {'id':'iuapstyle','type':'class','label':'Atomic probe style',
            'variants':[{'meta':{'breakpoint':'desktop','state':None},'props':{'color':prop('color','#123456')}},
                        {'meta':{'breakpoint':'mobile','state':None},'props':{'color':prop('color','#654321')}}]}}
    item = element('iuapitem', 'e-collection-loop-item', {}, [heading])
    layout = element('iuaplayout', 'e-collection-loop-layout', {}, [item])
    loop = element('iuaploop', 'e-collection-loop', {'query': prop('loop-query', {
        'template_type': prop('string','post'), 'source': prop('string','post'),
        'posts_per_page': prop('number',3), 'query_id': prop('string','' if engine else 'iu_atomic_probe_query')})}, [layout])
    if engine:
        loop['settings']['iu_fragment_cache']=prop('boolean',True)
        loop['settings']['iu_fragment_cache_ttl']=prop('string','604800')
    if standalone:
        assert engine
        heading['id']='iuaploop'
        heading['settings']['title']=prop('escaped-html','Atomic standalone heading')
        heading['settings'].pop('link',None)
        heading['settings']['iu_fragment_cache']=prop('boolean',True)
        loop=heading
    raw = json.dumps([loop], separators=(',', ':'))
    put('create.php', "<?php\n$id=wp_insert_post(['post_type'=>'elementor_library','post_status'=>'draft','post_title'=>'Private disposable Atomic cache prototype'],true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message()); update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_template_type','section');update_post_meta($id,'_elementor_data',wp_slash("+repr(raw)+"));echo $id;")
    fixture = int(wp('create.php').strip())
    config = dict(key=secrets.token_hex(32), document=fixture)
    if engine: config['engine']=True
    if '--browser-review' in sys.argv: config['browser']=secrets.token_hex(18)
    put('private.json', json.dumps(config))
    put('probe.php', (ROOT/'tests/atomic-fragment-probe.php').read_text(encoding='utf-8'))
    put('loader.php', '<?php\n$probe='+repr(REMOTE+'/probe.php')+'; if(is_readable($probe)) require $probe;\n')
    remote('touch '+REMOTE+'/entry.json && chmod 600 '+REMOTE+'/entry.json && setfacl -m u:manqbfzxjy:rw '+REMOTE+'/entry.json && setfacl -m u:manqbfzxjy:r '+REMOTE+'/private.json '+REMOTE+'/probe.php && cp '+REMOTE+'/loader.php '+LOADER)
    results = []
    route = secrets.token_hex(12)
    for mode in ['baseline','candidate','candidate']:
        request = secrets.token_hex(12)
        headers = 'X-IU-Atomic-Probe: '+config['key']+'\nX-IU-Atomic-Mode: '+mode+'\nX-IU-Atomic-Request: '+request+'\nHost: '+HOST+'\nX-Forwarded-Proto: https\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0\n'
        put('headers.txt', headers)
        remote('chmod 600 '+REMOTE+'/headers.txt')
        html = remote('curl --silent --show-error --max-time 90 -H @'+REMOTE+'/headers.txt '+shlex.quote('http://127.0.0.1:8086/?iu_atomic_private='+route))
        (WORK/(mode+str(len(results))+'.html')).write_text(html, encoding='utf-8')
        marker = re.search(r'<!-- IU_ATOMIC_PROBE (.*?) -->', html)
        if not marker: raise RuntimeError('No PHP observer marker: '+html[:400])
        assert 'var elementorFrontendConfig =' in html, 'Native Elementor frontend config missing'
        observed = json.loads(marker[1])
        assert observed['request'] == request, 'Response was not generated by this PHP request'
        main = re.search(r'<main>(.*?)</main>', html, re.S).group(1)
        assets = re.findall(r'<(?:script|link)\b[^>]*(?:src|href)=[\"\x27]([^\"\x27]+)', html)
        inline = re.findall(r'<(?:style|script)\b[^>]*>(.*?)</(?:style|script)>', html, re.S)
        observed.update(html_sha256=hashlib.sha256(main.encode()).hexdigest(), item_count=main.count('Atomic standalone heading') if standalone else (main.count('role="listitem"') if advanced else main.count('Atomic probe item')), assets=assets, generated_css=capture_css(assets,remote), inline_sha256=[hashlib.sha256(x.encode()).hexdigest() for x in inline])
        results.append(observed)
        report['requests'] = results
        print(json.dumps({k:v for k,v in observed.items() if k not in ['assets','generated_css','inline_sha256']}), flush=True)
        if mode == 'baseline':
            assert observed['queries'] == (0 if standalone else 1) and observed['item_count'] == (1 if standalone else 3), 'Native baseline did not execute expected output'
            if standalone: assert observed['originals']==[{'id':'iuaploop','type':'e-heading'}], 'Atomic widget baseline did not execute'
    report['requests'] = results
    assert results[0]['queries'] == results[1]['queries'] == (0 if standalone else 1)
    assert results[0]['item_count'] == results[1]['item_count'] == results[2]['item_count'] == (1 if standalone else 3)
    assert results[1]['selected'] == 'miss' and results[2]['selected'] == 'hit'
    assert results[2]['queries'] == 0 and results[2]['originals'] == []
    assert len({r['html_sha256'] for r in results}) == 1, 'HTML differs'
    report['verified_css_version_changes']=[verify_assets(results[0],r) for r in results[1:]]
    assert all(r['inline_sha256']==results[0]['inline_sha256'] for r in results), 'Inline asset manifest differs'
    report['result'] = 'PASS'
    if '--context-acceptance' in sys.argv:
        command([sys.executable,str(ROOT/'tests/atomic-fragment-acceptance.py'),str(WORK)])
        put('policy.php',(ROOT/'tests/atomic-fragment-policy.php').read_text(encoding='utf-8'))
        (ROOT/'docs/atomic-fragment-policy-result.json').write_text(wp('policy.php'),encoding='utf-8')
        remote('rm -- '+REMOTE+'/policy.php')
    if '--variants' in sys.argv:
        command([sys.executable,str(ROOT/'tests/atomic-fragment-variants.py'),str(WORK)])
    if '--classic' in sys.argv:
        command([sys.executable,str(ROOT/'tests/elementor-434-classic.py'),str(WORK)])
    if '--browser-review' in sys.argv:
        print('BROWSER_READY '+str(WORK)+' document='+str(fixture),flush=True)
        (WORK/'browser-ready.json').write_text(json.dumps({'url':'https://'+HOST+'/?iu_atomic_view='+config['browser'],'document':fixture}),encoding='utf-8')
        deadline=time.monotonic()+900
        while not (WORK/'browser-complete').exists() and time.monotonic()<deadline: time.sleep(1)
        if not (WORK/'browser-complete').exists(): raise RuntimeError('Browser review timed out; restored automatically')
except Exception as error:
    report['result'] = 'STOP'
    report['finding'] = str(error)
    print('STOP: '+str(error), flush=True)
finally:
    remote('if test -e '+LOADER+'; then rm -- '+LOADER+'; fi')
    if fixture:
        put('cleanup.php', '<?php\nif(is_file(__DIR__."/keys.txt")){foreach(array_unique(file(__DIR__."/keys.txt",FILE_IGNORE_NEW_LINES)) as $key){if(preg_match("/^iu_frag_[a-f0-9]{64}$/",$key))delete_transient($key);}}\\Elementor\\Core\\Files\\CSS\\Post::create('+str(fixture)+')->delete();wp_delete_post('+str(fixture)+',true);delete_option("iu_fragment_entry_".md5("'+str(fixture)+':iuaploop"));delete_option("iu_fragment_epoch_".md5("'+str(fixture)+':iuaploop"));echo get_post('+str(fixture)+')===null?"removed":"FAILED";')
        report['fixture_cleanup'] = wp('cleanup.php').strip()
    if experiment_restore:
        report['experiment_restoration'] = wp('restore-experiment.php').strip()
    if installed: report['runtime_restoration']=wp('restore-runtime.php').strip()
    report['settings_and_real_documents_unchanged'] = before == wp('snapshot.php')
    names = ['snapshot.php','snapshot-result.json','create.php','private.json','probe.php','loader.php','entry.json','headers.txt','cleanup.php','before.json','restore-experiment.php','activate.php','keys.txt','policy.php']
    if engine and not installed_release: names+=['runtime.json','runtime-backup.json','install.php','restore-runtime.php','runtime-check.php']+['candidate-'+file for file in runtime_files]
    remote('rm -f -- '+' '.join(REMOTE+'/'+n for n in names)+' && rmdir '+REMOTE)
    if (WORK/'private.json').exists(): (WORK/'private.json').unlink()
    if (WORK/'headers.txt').exists(): (WORK/'headers.txt').unlink()
    evidence = 'atomic-fragment-integrated-result.json' if engine else ('atomic-fragment-dynamic-result.json' if '--dynamic-fixture' in sys.argv else 'atomic-fragment-prototype-result.json')
    if standalone: evidence='atomic-fragment-widget-result.json'
    if review_core: evidence='elementor-'+review_core.replace('.','')+'-atomic-result.json'
    (ROOT/'docs'/evidence).write_text(json.dumps(report, indent=2)+'\n', encoding='utf-8')
    print(json.dumps({k:v for k,v in report.items() if k!='requests'}), flush=True)
