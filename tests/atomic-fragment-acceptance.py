"""Integrated engine checks on an already-running disposable staging fixture."""
from pathlib import Path
import base64,concurrent.futures,hashlib,json,re,shlex,subprocess,sys,tempfile,uuid
work=Path(sys.argv[1]).resolve()
assert work.parent==Path(tempfile.gettempdir()).resolve()
assert re.fullmatch(r'iu-atomic-probe-[a-z0-9_]+',work.name)
config=json.loads((work/'private.json').read_text());doc=int(config['document'])
remote='/home/master/applications/manqbfzxjy/tmp/'+work.name
web='/home/master/applications/manqbfzxjy/public_html'
args=['ssh','-i','C:/Users/pe/.ssh/cloudways_server_1','-o','BatchMode=yes','master_hnrnsyahbd@104.248.132.240']
rows=[]
def ssh(cmd):
    return subprocess.run(args+[cmd],capture_output=True,check=True,timeout=120).stdout.decode()
def php(code):
    return ssh('cd '+web+' && wp eval '+shlex.quote('eval(base64_decode("'+base64.b64encode(code.encode()).decode()+'"));'))
def request(label,path='/',phone=False,hold=False,logged=False,baseline=False):
    token=uuid.uuid4().hex
    ua='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari/604.1' if phone else 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0'
    cmd='curl --silent --show-error --max-time 90 -H @'+remote+'/acceptance-headers.txt'
    cmd+=' -H '+shlex.quote('X-IU-Atomic-Request: '+token)+' -H '+shlex.quote('User-Agent: '+ua)
    if hold:cmd+=' -H "X-IU-Atomic-Hold: test"'
    if logged:cmd+=' -H @'+remote+'/auth-headers.txt'
    if baseline:cmd+=' -H "X-IU-Atomic-Mode: baseline"'
    url='http://127.0.0.1:8086'+path+('?' if '?' not in path else '&')+'iu_atomic_private=acceptance'
    html=ssh(cmd+' '+shlex.quote(url));m=re.search(r'<!-- IU_ATOMIC_PROBE (.*?) -->',html)
    assert m,'Missing observer '+label
    obs=json.loads(m[1]);assert obs['request']==token,'Full page response reused'
    assert obs['authenticated']==logged,'Authentication state mismatch '+label
    main=re.search(r'<main>(.*?)</main>',html,re.S)[1]
    obs.update(label=label,items=main.count('role="listitem"'),sha=hashlib.sha256(main.encode()).hexdigest())
    rows.append(obs);print(json.dumps(obs),flush=True);return obs
def mutate(code):
    php('$id='+str(doc)+';$nodes=json_decode(get_post_meta($id,"_elementor_data",true),true);'+code+'update_post_meta($id,"_elementor_data",wp_slash(wp_json_encode($nodes)));')
old=php('echo get_post_meta('+str(doc)+',"_elementor_data",true);')
headers='X-IU-Atomic-Probe: '+config['key']+'\nHost: wordpress-218158-6702910.cloudwaysapps.com\nX-Forwarded-Proto: https\n'
(work/'acceptance-headers.txt').write_text(headers)
subprocess.run(['scp','-O','-i',args[2],str(work/'acceptance-headers.txt'),args[-1]+':'+remote+'/acceptance-headers.txt'],capture_output=True,check=True)
auth_created=False
try:
    # Saved Elementor ON value must remain eligible after editor round-trip.
    a=request('saved-on');b=request('saved-on-hit');assert b['selected']=='hit' and b['queries']==0
    shared_key=b['fragment_key'];shared_sha=b['sha']
    for i in range(5):
        variant=request('random-url-'+str(i),'/?unreviewed='+uuid.uuid4().hex+'&noise='+uuid.uuid4().hex)
        assert variant['selected']=='hit' and variant['queries']==0 and variant['originals']==[]
        assert variant['fragment_key']==shared_key and variant['sha']==shared_sha
    # Use a real, short-lived WordPress auth session without changing passwords
    # or printing cookie/session material. Destroy only this session afterward.
    php('$users=get_users(["role"=>"administrator","number"=>1,"fields"=>"ID"]);if(!$users)throw new RuntimeException("No staging admin");$uid=(int)$users[0];$expiry=time()+600;$token=WP_Session_Tokens::get_instance($uid)->create($expiry);file_put_contents('+json.dumps(remote+'/auth-session.json')+',wp_json_encode(["user"=>$uid,"token"=>$token]));file_put_contents('+json.dumps(remote+'/auth-headers.txt')+',"Cookie: ".LOGGED_IN_COOKIE."=".wp_generate_auth_cookie($uid,$expiry,"logged_in",$token)."\\n");')
    auth_created=True
    ssh('chmod 600 '+remote+'/auth-session.json '+remote+'/auth-headers.txt')
    native=request('logged-native',logged=True,baseline=True)
    logged=request('logged-shared-hit',logged=True)
    assert logged['selected']=='hit' and logged['queries']==0 and logged['originals']==[]
    assert native['queries']==1 and native['sha']==logged['sha']==shared_sha and logged['fragment_key']==shared_key
    mixed=request('logged-random-url','/?unreviewed='+uuid.uuid4().hex,logged=True)
    assert mixed['selected']=='hit' and mixed['queries']==0 and mixed['fragment_key']==shared_key and mixed['sha']==shared_sha
    php('update_option("iu_fragment_epoch_".md5("'+str(doc)+':iuaploop"),wp_generate_uuid4(),false);')
    writer=request('logged-cold-writer',logged=True);reader=request('anonymous-after-logged')
    assert writer['selected']=='miss' and writer['engine_result']=='stored' and writer['queries']==1
    assert reader['selected']=='hit' and reader['queries']==0 and reader['originals']==[]
    assert writer['fragment_key']==reader['fragment_key'] and writer['sha']==reader['sha']==shared_sha
    # Different actual page/path contexts have separate keys, even same output.
    p=request('other-page','/etairia/');q=request('other-page-hit','/etairia/')
    assert p['selected']=='miss' and q['selected']=='hit' and q['queries']==0
    mutate('$nodes[0]["settings"]["iu_fragment_cache"]["value"]=false;')
    a=request('off');b=request('off-again');assert a['selected']==b['selected']=='native' and a['queries']==b['queries']==1
    mutate("$nodes[0]['settings']['iu_fragment_cache']['value']=true;$nodes[0]['settings']['iu_hide_on_phone']=['$$type'=>'boolean','value'=>true];")
    a=request('visibility-desktop');b=request('visibility-desktop-hit');h=request('hidden-phone',phone=True)
    assert a['selected']=='miss' and b['selected']=='hit' and h['items']==h['queries']==0 and h['originals']==[]
    mutate('unset($nodes[0]["settings"]["iu_hide_on_phone"]);')
    # Purge just the disposable element, then overlap two real PHP requests.
    php('update_option("iu_fragment_epoch_".md5("'+str(doc)+':iuaploop"),wp_generate_uuid4(),false);')
    with concurrent.futures.ThreadPoolExecutor(2) as pool:
        group=list(pool.map(lambda x:request('concurrent-'+str(x),hold=True),range(2)))
    assert sorted(x['selected'] for x in group)==['hit','miss'] and sum(x['queries'] for x in group)==1 and len({x['sha'] for x in group})==1
    # No existing public content edits: invalidate using a disposable draft post.
    source=int(php('echo wp_insert_post(["post_type"=>"post","post_status"=>"draft","post_title"=>"Disposable Atomic invalidation fixture"]);'))
    try:
        a=request('source-invalidated');b=request('source-invalidated-hit');assert a['selected']=='miss' and b['selected']=='hit'
    finally:php('wp_delete_post('+str(source)+',true);')
    result={'result':'PASS','requests':rows}
except Exception as e:
    result={'result':'STOP','finding':str(e),'requests':rows};raise
finally:
    if auth_created:
        php('$session=json_decode(file_get_contents('+json.dumps(remote+'/auth-session.json')+'),true);WP_Session_Tokens::get_instance($session["user"])->destroy($session["token"]);')
        ssh('rm -- '+remote+'/auth-session.json '+remote+'/auth-headers.txt')
    php('update_post_meta('+str(doc)+',"_elementor_data",wp_slash(base64_decode("'+base64.b64encode(old.encode()).decode()+'")));')
    ssh('rm -f -- '+remote+'/acceptance-headers.txt')
    (work/'acceptance-headers.txt').unlink(missing_ok=True)
    Path('docs/atomic-fragment-integrated-acceptance.json').write_text(json.dumps(result,indent=2)+'\n')
