"""Actual UA branches, separate from browser viewport-only checks."""
from pathlib import Path
from urllib.request import Request,urlopen
from uuid import uuid4
import json,re
base='https://wordpress-218158-6702910.cloudwaysapps.com'
r=json.loads(Path('docs/kit-candidate-cross-page-el.json').read_text())
uas={
 'firefox':'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0',
 'tablet':'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
 'phone':'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'}
results={}
for kind,ua in uas.items():
 url=base+r['page_b']+'?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+r['generation']
 with urlopen(Request(url,headers={'User-Agent':ua}),timeout=180) as response:
  html=response.read().decode();assert response.status==200 and response.headers.get('X-Cache')=='MISS'
 d=json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',html).group(1))
 assert d['loop']==0
 if kind=='firefox': assert [e['result'] for e in d['events']]==['hit','hit']
 else:
  assert d['target']==0 and not d['events'] and not d['template_renders']
  assert 'data-id="26fa46c6"' not in html
 results[kind]={'target':d['target'],'loops':d['loop'],'events':d['events']}
 print(kind,results[kind],flush=True)
Path('docs/kit-candidate-device.json').write_text(json.dumps(results,indent=2)+'\n')
print('PASS: normalized desktop hit, native tablet/phone heavy header exclusion')
