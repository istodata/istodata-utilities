from pathlib import Path
from urllib.request import Request,urlopen
from uuid import uuid4
from concurrent.futures import ThreadPoolExecutor
import runpy,re,json,subprocess,time
m=runpy.run_path('tests/fragment-cache-access-check.py');gen=uuid4().hex
url=m['BASE']+'/?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+gen+'&iu_kit_delay=20'
def producer():
 with urlopen(Request(url,headers={'User-Agent':m['h']['UA']}),timeout=180) as r:return json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',r.read().decode()).group(1))
with ThreadPoolExecutor(max_workers=2) as pool:
 first=pool.submit(producer);ready=False
 for i in range(5):
  time.sleep(1)
  r=subprocess.run(['ssh','-i','C:/Users/pe/.ssh/cloudways_server_1','-o','BatchMode=yes','master_hnrnsyahbd@104.248.132.240','cd /home/master/applications/manqbfzxjy/public_html && wp transient get iu_access_signal_'+gen],capture_output=True,text=True,timeout=30)
  if r.returncode==0 and r.stdout.startswith('iu_frag_'):ready=True;break
 assert ready,'No actual capture signal'
 second=pool.submit(m['fetch'],'site_manager',gen)
 a=first.result();b=second.result()['data']
assert [e['result'] for e in a['events']]==['stored','stored'],a['events']
assert [e['result'] for e in b['events']]==['hit','hit'] and b['loop_any']==0 and not b['template_renders'],b
assert a['keys']==b['keys']
Path('docs/fragment-cache-optin-concurrent.json').write_text(json.dumps({'same_key':True,'producer_loops':a['loop_any'],'concurrent_consumer_loops':0,'duplicate_widget_renders':0,'producer_events':a['events'],'consumer_events':b['events']},indent=2))
print('PASS actual concurrent cold requests: one producer, waiting consumer hits without duplicate widget renders')
