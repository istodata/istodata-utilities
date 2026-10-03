from pathlib import Path
import runpy,uuid,json
m=runpy.run_path('tests/fragment-cache-access-check.py');out=[]
for mode in ['private-cookie','session','user-dynamic','global-off']:
 r=m['fetch']('site_manager',uuid.uuid4().hex,mode=mode);d=r['data']
 assert d['loop_any']==150 and not d['events'] and not d['keys'],d
 if mode=='global-off':assert not d['operations'] and d['diagnostic_writes']==0,d
 out.append({'mode':mode,'normal_loops':150,'fragment_operations':len(d['operations']),'diagnostic_writes':d['diagnostic_writes'],'not_published_or_hit':True,'rejection':d['reject']})
Path('docs/fragment-cache-optin-context.json').write_text(json.dumps(out,indent=2)+'\n')
print('PASS actual private-cookie/session/user-tag exclusions and global OFF without operations/diagnostic writes')
