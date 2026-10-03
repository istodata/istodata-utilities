"""Cross-page dropdown reuse; origin counters, normal header/assets and effects."""
from pathlib import Path
from urllib.request import Request,urlopen
from uuid import uuid4
from hashlib import sha256
import re,json,runpy,sys
h=runpy.run_path(str(Path(__file__).with_name('fragment-cache-staging-check.py')))
a=runpy.run_path(str(Path(__file__).with_name('fragment-cache-assets-check.py')))
BASE='https://wordpress-218158-6702910.cloudwaysapps.com'
english='--en' in sys.argv
A='/en/' if english else '/'
B='/en/the-company/' if english else '/techniki-ypostirixi/'
generation=uuid4().hex
results={}
for path,label in [(A,'a'),(B,'b'),(B,'baseline')]:
    url=BASE+path+'?iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+generation+('&iu_kit_mode=baseline' if label=='baseline' else '')
    with urlopen(Request(url,headers={'User-Agent':h['UA']}),timeout=180) as r:
        html=r.read().decode(); assert r.status==200 and r.headers.get('X-Cache')=='MISS'
    p=h['TargetParser']();p.feed(html)
    assert p.start and p.end
    fragment=html[h['offset'](html,p.start):html.index('>',h['offset'](html,p.end))+1]
    assets=a['AssetTags']();assets.feed(html)
    data=json.loads(re.search(r'IU_CROSS_PAGE (.+?) -->',html).group(1))
    results[label]=(fragment,assets.external,data)
    print(label, 'target',data['target'],'loops',data['loop'],'templates',data['template_renders'],'events',data['events'], 'assets',len(assets.external),flush=True)
assert results['a'][2]['loop_any']>0
if not english: assert results['a'][2]['loop']==150
assert results['a'][2]['keys']==results['b'][2]['keys']
assert results['b'][2]['target']==1 and results['b'][2]['loop']==0 and results['b'][2]['template_renders']==[]
assert [e['result'] for e in results['b'][2]['events']]==['hit','hit']
assert results['b'][0]==results['baseline'][0], 'Header HTML differs from ordinary rendering on B'
assert results['b'][1]==results['baseline'][1], 'Required assets differ'
assert results['b'][2]['excerpt']==results['baseline'][2]['excerpt'], 'Excerpt side effect differs'
assert results['b'][2]['displayed']==results['baseline'][2]['displayed'], 'Displayed ID side effect differs'
if not english:
    assert results['a'][0]!=results['b'][0], 'Menu page context did not change'
    assert 'current-menu-item' in results['b'][0] and 'aria-current="page"' in results['b'][0]
report={'generation':generation,'page_a':A,'page_b':B,'loop_a':results['a'][2]['loop_any'],'loop_b':0,'menu_renders_b':1,
        'dropdown_renders_b':0,'assets_b':len(results['b'][1]),'header_sha_b':sha256(results['b'][0].encode()).hexdigest(),
        'keys':results['b'][2]['keys']}
Path('docs/kit-candidate-cross-page-en.json' if english else 'docs/kit-candidate-cross-page-el.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print('PASS: first B origin hits A dropdowns; exact normal HTML/assets and query/excerpt effects' + ('; Greek active menu verified' if not english else '; translated loops verified'))
