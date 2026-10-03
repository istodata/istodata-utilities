"""Record the failed acceptance gate and precise excerpt-context mismatch."""
from pathlib import Path
import runpy,uuid,json
m=runpy.run_path('tests/fragment-cache-access-check.py');gen=uuid.uuid4().hex
results=[]
for role,page in [('anonymous','/'),('site_manager','/techniki-ypostirixi/')]:
    result=m['fetch'](role,gen,page);d=result['data']
    results.append(dict(role=role,page=page,events=d['events'],keys=d['keys'],nav_renders=d['nav_renders'],
        nav_construction=d['nav_construction'],loop_renders=d['loop_any'],menu_excerpt_context=d['menu_excerpt_context']))
Path('docs/fragment-cache-nav-blocker.json').write_text(json.dumps(results,indent=2)+'\n')
assert any(e['result']=='excerpt-side-effect' for e in results[0]['events']),'Expected blocker disappeared: investigate before acceptance'
print('Recorded actual failure; this is not an acceptance pass')
