"""Three real staging cold followers; each dropdown builds once despite >15s delay."""
from urllib.request import Request,urlopen
from concurrent.futures import ThreadPoolExecutor
from uuid import uuid4
import json,re,time
G=uuid4().hex
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/127.0 Safari/537.36'
def fetch(i):
    start=time.monotonic()
    url='https://wordpress-218158-6702910.cloudwaysapps.com/?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+G+'&iu_kit_delay=20'
    with urlopen(Request(url,headers={'User-Agent':UA}),timeout=180) as r:
        source=r.read().decode();assert r.status==200 and r.headers.get('X-Cache')=='MISS'
    d=json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',source).group(1))
    print(i,'seconds',round(time.monotonic()-start,2),'loops',d['loop'],'nested',d['nested'],'templates',d['template_renders'],'events',d['events'],flush=True)
    return d
with ThreadPoolExecutor(max_workers=3) as e: results=list(e.map(fetch,range(3)))
assert sorted(d['loop'] for d in results)==[0,0,150]
for element in ['417d1ae','c60daee']:
    assert sorted(ev['result'] for d in results for ev in d['events'] if ev['id']==element)==['hit','hit','stored']
print('PASS: cold >15-second builders; one build per dropdown, two hits without loops')
