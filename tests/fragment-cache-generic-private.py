"""Real generic widget token/permission and request-level privacy exclusions."""
from pathlib import Path
import runpy,uuid,json
m=runpy.run_path('tests/fragment-cache-access-check.py');out=[]
for mode,reason in [('custom-token','volatile-markup'),('custom-permission','query-context')]:
    gen=uuid.uuid4().hex;pair=[]
    for role in ['anonymous','site_manager']:
        d=m['fetch'](role,gen,mode=mode)['data']
        own=[e for e in d['events'] if e['id']=='iu-custom-proof']
        assert own==[{'id':'iu-custom-proof','result':reason}],own
        assert d['custom_render_body']==1
        pair.append({'role':role,'custom_result':own,'custom_render_body':d['custom_render_body']})
    out.append({'mode':mode,'not_published_or_hit':True,'requests':pair})
for mode in ['private-cookie','session','global-off']:
    d=m['fetch']('site_manager',uuid.uuid4().hex,mode=mode)['data']
    assert not d['events'] and not d['keys'] and d['custom_render_body']==1
    if mode=='global-off':assert not d['operations'] and d['diagnostic_writes']==0
    out.append({'mode':mode,'cache_operations':len(d['operations']),'no_hits_or_publication':True})
Path('docs/fragment-cache-generic-private.json').write_text(json.dumps(out,indent=2)+'\n')
print('PASS actual generic token/permission publication rejection and private session/global-OFF request bypass')
