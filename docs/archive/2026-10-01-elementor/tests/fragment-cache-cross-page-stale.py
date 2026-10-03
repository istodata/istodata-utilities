"""Same-key stale refresh for both dropdowns; one owner per fragment."""
from urllib.request import Request,urlopen
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from uuid import uuid4
import json,re,subprocess
r=json.loads(Path('docs/fragment-cache-cross-page-staging.json').read_text(encoding='utf-8'))
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/127.0 Safari/537.36'
def fetch(delay=0):
    url='https://wordpress-218158-6702910.cloudwaysapps.com'+r['page_b']+'?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+r['generation']+'&iu_kit_delay='+str(delay)
    with urlopen(Request(url,headers={'User-Agent':UA}),timeout=180) as response:
        s=response.read().decode(); assert response.status==200 and response.headers.get('X-Cache')=='MISS'
    return json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',s).group(1))
seed=fetch()
for item in seed['keys'].values():
    key=item['key'];assert re.fullmatch('iu_frag_[a-f0-9]{64}',key)
    subprocess.run(['ssh','-i','C:/Users/pe/.ssh/cloudways_server_1','-o','BatchMode=yes','master_hnrnsyahbd@104.248.132.240','cd /home/master/applications/manqbfzxjy/public_html && wp eval-file /tmp/fragment-cache-stale-fixture.php '+key],check=True)
with ThreadPoolExecutor(max_workers=3) as e: results=list(e.map(lambda _:fetch(5),range(3)))
for d in results: print('loops',d['loop'],'events',d['events'],flush=True)
for element in ['417d1ae','c60daee']:
    assert sorted(ev['result'] for d in results for ev in d['events'] if ev['id']==element)==['stale-hit','stale-hit','stored']
assert sum(d['loop'] for d in results)==150
print('PASS: one stale refresher per dropdown, followers replay without duplicate loops')
