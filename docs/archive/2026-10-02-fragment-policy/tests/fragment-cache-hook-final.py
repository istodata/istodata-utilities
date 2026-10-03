import runpy,uuid,json
from pathlib import Path
m=runpy.run_path('tests/fragment-cache-access-check.py');out=[]
for p,dest in [('/','/techniki-ypostirixi/'),('/en/','/en/the-company/')]:
 gen=uuid.uuid4().hex;a=m['fetch']('anonymous',gen,p);b=m['fetch']('site_manager',gen,dest)
 assert [e['result']for e in a['data']['events']]==['stored','stored'];assert [e['result']for e in b['data']['events']]==['hit','hit']
 assert b['data']['loop_any']==0 and not b['data']['template_renders'];assert a['fragments']==b['fragments'];assert a['data']['keys']==b['data']['keys']
 out.append({'language':'en' if p=='/en/' else 'el','miss_loops':a['data']['loop_any'],'hit_loops':0,'dropdown_renders':0,'html_keys_equal':True})
manifest=json.loads(Path('docs/fragment-cache-hook-review-build.json').read_text());Path('docs/fragment-cache-hook-final-origin.json').write_text(json.dumps({'tested_zip_sha256':manifest['zip_sha256'],'requests':out},indent=2)+'\n');print('PASS final ZIP actual origin el/en early hits')
