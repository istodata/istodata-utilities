from pathlib import Path
import runpy,json,sys,uuid,re
m=runpy.run_path('tests/fragment-cache-access-check.py');phase=sys.argv[1];out=[]
for lang,base in [('el','/'),('en','/en/')]:
 for term in ['Leica','Metrica']:
  r=m['fetch']('anonymous',uuid.uuid4().hex,base+'?s='+term+'&id=6361')
  d=r['data'];assert d['main_preget']['search'] and d['main_preget']['ivory'],d['search_main']
  assert not d['events'] and not d['keys'],'Search cache should bypass'
  out.append({'language':lang,'term':term,'query':d['main_preget'],'results':d['main_results'],'no_cache':True})
p=Path('docs/fragment-cache-optin-search-'+phase+'.json')
if phase=='after':assert out==json.loads(Path('docs/fragment-cache-optin-search-before.json').read_text()),'Search result behavior changed'
p.write_text(json.dumps(out,indent=2)+'\n');print('PASS real origin Ivory search '+phase)
