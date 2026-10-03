import runpy,uuid,json
from urllib.request import Request,urlopen
m=runpy.run_path('tests/fragment-cache-access-check.py')
s=m['sessions']['site_manager']
c='; '.join(s[n]+'='+s[v] for n,v in [('name','value'),('auth_name','auth_value'),('secure_name','secure_value')])
u=m['BASE']+'/?elementor-preview=30&ver=1&iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid.uuid4().hex
with urlopen(Request(u,headers={'Cookie':c,'User-Agent':m['h']['UA']}),timeout=180) as r:
    print('final_url',r.url)
out=[]
ua='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'
for role in ['anonymous','site_manager']:
    p=m['fetch'](role,uuid.uuid4().hex,ua=ua)
    d=p['data']
    assert not d['keys'] and not d['events'] and d['loop_any']==0
    out.append({'role':role,'keys':d['keys'],'events':d['events'],'loops':d['loop_any']})
m['Path']('docs/fragment-cache-access-device.json').write_text(json.dumps(out,indent=2))
