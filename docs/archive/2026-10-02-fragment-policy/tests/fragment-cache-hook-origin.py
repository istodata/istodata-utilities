from pathlib import Path
from hashlib import sha256
import runpy,uuid,json,re
m=runpy.run_path('tests/fragment-cache-access-check.py');fetch=m['fetch'];out=[]
for path,dest in [('/','/techniki-ypostirixi/'),('/en/','/en/the-company/')]:
 for builder,consumer in [('anonymous','site_manager'),('site_manager','anonymous'),('subscriber','anonymous')]:
  gen=uuid.uuid4().hex;miss=fetch(builder,gen,path);hit=fetch(consumer,gen,dest);base=fetch(consumer,gen,dest,'baseline')
  md,hd,bd=[x['data'] for x in [miss,hit,base]]
  assert [e['result'] for e in md['events']]==['stored','stored'],md
  assert [e['result'] for e in hd['events']]==['hit','hit'],hd
  assert md['keys']==hd['keys'];assert hd['loop_any']==0 and not hd['template_renders'] and hd['target']==1
  assert miss['fragments']==hit['fragments']==base['fragments'];assert hit['assets']==base['assets']
  assert hd['excerpt']==bd['excerpt'] and hd['displayed']==bd['displayed']
  assert md['query_profile'] and all(not q['search'] and not q['s'] and not q['ivory'] for q in md['query_profile']),md['query_profile']
  assert len(hd['query_profile'])<len(md['query_profile'])
  assert not re.search('iuFragmentDiagnostics|iu-fragment-diagnostic-result|fragment-diagnostics.js',hit['html'])
  out.append({'language':'en' if '/en/'==path else 'el','builder':builder,'consumer':consumer,'miss_loops':md['loop_any'],'hit_loops':0,'root_renders':1,'dropdown_renders':0,'queries_miss':len(md['query_profile']),'queries_hit':len(hd['query_profile']),'html_assets_excerpt_displayed_equal':True,'same_key_cross_page_user':True,'public_diagnostics_absent':True,'sha256':{k:sha256(v.encode()).hexdigest()for k,v in hit['fragments'].items()}})
ua='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'
for role in ['anonymous','site_manager']:
 r=fetch(role,uuid.uuid4().hex,ua=ua);d=r['data'];assert not d['keys'] and not d['events'] and not d['loop_any']
 out.append({'device':'phone','role':role,'heavy_keys_events_loops':0})
r=fetch('site_manager',uuid.uuid4().hex,mode='unknown');d=r['data'];assert d['reject'] and all(v=='unknown-hook:pre_get_posts' for v in d['reject'].values());assert not d['events'] and d['loop_any']==150
out.append({'unsafe_hook_bypass':True,'normal_loops':150,'reject':d['reject']})
Path('docs/fragment-cache-hook-origin.json').write_text(json.dumps(out,indent=2)+'\n');print('PASS real origin cross-page/user/lang fragments, asset/query effects, device bypass and unknown-hook rejection')
