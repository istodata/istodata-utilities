"""Purge after an actual miss acquires its old generation, before it finishes."""
from pathlib import Path
from urllib.request import Request,urlopen
from uuid import uuid4
from html import unescape
from concurrent.futures import ThreadPoolExecutor
import runpy,re,json,subprocess,time
t=runpy.run_path(str(Path(__file__).with_name('fragment-cache-access-check.py')))
BASE=t['BASE'];UA=t['h']['UA'];s=t['sessions']['site_manager']
cookie='; '.join(s[n]+'='+s[v] for n,v in [('name','value'),('auth_name','auth_value'),('secure_name','secure_value')])
with urlopen(Request(BASE+'/wp-admin/options-general.php?page=istodata-utilities&tab=elementor',headers={'Cookie':cookie,'User-Agent':UA}),timeout=120) as r:
    admin=r.read().decode()
link=unescape(re.search(r'''id=["']wp-admin-bar-iu-fragment-purge-all["'].*?href=["']([^"']+)''',admin,re.S).group(1))
gen=uuid4().hex
url=BASE+'/?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+gen+'&iu_kit_delay=20'
def build():
    with urlopen(Request(url,headers={'User-Agent':UA}),timeout=180) as r:
        return json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',r.read().decode()).group(1))
with ThreadPoolExecutor(max_workers=1) as pool:
    future=pool.submit(build)
    ready=False
    for attempt in range(5):
        time.sleep(1)
        remote='cd /home/master/applications/manqbfzxjy/public_html && wp transient get iu_access_signal_'+gen
        result=subprocess.run(['ssh','-i','C:/Users/pe/.ssh/cloudways_server_1','-o','BatchMode=yes','master_hnrnsyahbd@104.248.132.240',remote],capture_output=True,text=True,timeout=30)
        if result.returncode==0 and result.stdout.startswith('iu_frag_'):ready=True;break
    assert ready,'Actual build never signalled its acquired key'
    print('Actual old-generation miss is in flight; invoking protected site purge',flush=True)
    with urlopen(Request(link,headers={'Cookie':cookie,'User-Agent':UA}),timeout=120) as r: assert r.status==200
    old=future.result()
assert [x['result'] for x in old['events']]==['generation-changed','generation-changed'],old['events']
after=t['fetch']('anonymous',gen);hit=t['fetch']('site_manager',gen)
assert after['data']['keys']!=old['keys'] and after['data']['loop_any']==150 and hit['data']['loop_any']==0
assert [e['result'] for e in after['data']['events']]==['stored','stored']
(Path(__file__).resolve().parents[1]/'docs/fragment-cache-optin-race.json').write_text(json.dumps({'old_writer_events':old['events'],'old_writer_loops':old['loop_any'],'generation_changed':True,'new_miss_loops':150,'new_hit_loops':0},indent=2)+'\n')
print('PASS real purge during cold miss: both old writers rejected, fresh generation builds, next authenticated request hits')
