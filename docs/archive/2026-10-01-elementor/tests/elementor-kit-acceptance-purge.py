"""Purge one dropdown after retaining a stale entry; another dropdown must stay a hit."""
from pathlib import Path
from urllib.request import Request,urlopen
from uuid import uuid4
from hashlib import sha256
import subprocess,re,json,runpy
h=runpy.run_path(str(Path(__file__).with_name('fragment-cache-staging-check.py')))
r=json.loads(Path('docs/kit-acceptance-cross-page-el.json').read_text(encoding='utf-8'))
key=r['keys']['417d1ae']['key']; assert re.fullmatch('iu_frag_[a-f0-9]{64}',key)
for i in range(2):
    url='https://wordpress-218158-6702910.cloudwaysapps.com'+r['page_b']+'?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+r['generation']
    with urlopen(Request(url,headers={'User-Agent':h['UA']}),timeout=180) as response:
        html=response.read().decode();assert response.status==200 and response.headers.get('X-Cache')=='MISS'
    d=json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',html).group(1))
    print(i,'loops',d['loop'],'events',d['events'],flush=True)
    assert d['keys']['417d1ae']['key']!=key and d['keys']['c60daee']==r['keys']['c60daee']
    assert [e['result'] for e in d['events']]==(['stored','hit'] if i==0 else ['hit','hit'])
    assert d['loop']==(72 if i==0 else 0)
    p=h['TargetParser']();p.feed(html)
    fragment=html[h['offset'](html,p.start):html.index('>',h['offset'](html,p.end))+1]
    assert sha256(fragment.encode()).hexdigest()==r['header_sha_b']
print('PASS: per-dropdown purge defeats stale retention; other dropdown remains a hit')
