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
  def outer_html(result):
   parser=m['Fragment']('26fa46c6');parser.feed(result['html'])
   return result['html'][m['h']['offset'](result['html'],parser.start):result['html'].index('>',m['h']['offset'](result['html'],parser.end))+1]
  assert outer_html(hit)==outer_html(base),'Live central navigation/current-page HTML differs from normal consumer'

  assert miss['fragments']==hit['fragments']==base['fragments'];assert hit['assets']==base['assets']
  assert hd['excerpt']==bd['excerpt'] and hd['displayed']==bd['displayed']
  assert md['query_profile'] and all(not q['search'] and not q['s'] and not q['ivory'] for q in md['query_profile']),md['query_profile']
  assert len(hd['query_profile'])<len(md['query_profile'])
  assert not re.search('iuFragmentDiagnostics|iu-fragment-diagnostic-result|fragment-diagnostics.js',hit['html'])
  out.append({'language':'en' if '/en/'==path else 'el','builder':builder,'consumer':consumer,'miss_loops':md['loop_any'],'hit_loops':0,'root_renders':1,'dropdown_renders':0,'queries_miss':len(md['query_profile']),'queries_hit':len(hd['query_profile']),'html_assets_excerpt_displayed_equal':True,'same_key_cross_page_user':True,'public_diagnostics_absent':True,'live_central_navigation_matches_current_page_baseline':True,'sha256':{k:sha256(v.encode()).hexdigest()for k,v in hit['fragments'].items()}})
ua='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'
for role in ['anonymous','site_manager']:
 r=fetch(role,uuid.uuid4().hex,ua=ua);d=r['data'];assert not d['keys'] and not d['events'] and not d['loop_any']
 out.append({'device':'phone','role':role,'heavy_keys_events_loops':0})
gen=uuid.uuid4().hex
r=fetch('site_manager',gen,mode='unknown'); hit=fetch('anonymous',gen,'/techniki-ypostirixi/','unknown')
assert [e['result'] for e in r['data']['events']]==['stored','stored'] and [e['result'] for e in hit['data']['events']]==['hit','hit']
assert hit['data']['loop_any']==0 and not r['data']['reject']
out.append({'unknown_noop_query_hook_admitted':True,'miss_loops':150,'hit_loops':0})
for mode in ['token','permission']:
 gen=uuid.uuid4().hex;r=fetch('site_manager',gen,mode=mode);second=fetch('anonymous',gen,mode=mode)
 for result in [r,second]:
  assert result['data']['loop_any']==150 and not any(e['result'] in ['stored','hit'] for e in result['data']['events']),result['data']
 out.append({'negative_control':mode,'not_published':True,'results':r['data']['events']})
Path('docs/fragment-cache-optin-origin.json').write_text(json.dumps(out,indent=2)+'\n');print('PASS real origin cross-page/user/lang fragments, asset/query effects, device bypass and concrete privacy guards')
