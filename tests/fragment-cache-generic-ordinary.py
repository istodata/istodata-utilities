"""Ordinary authenticated frontend requests after observer removal, no probe parameters."""
from pathlib import Path
from urllib.request import Request,urlopen
import tempfile,json
session=json.loads((Path(tempfile.gettempdir())/'iu-fragment-access-sessions.json').read_text())['site_manager']
cookie='; '.join(session[n]+'='+session[v] for n,v in [('name','value'),('auth_name','auth_value'),('secure_name','secure_value')])
report=[]
for path in ['/','/en/']:
    for request_number in [1,2]:
        with urlopen(Request('https://wordpress-218158-6702910.cloudwaysapps.com'+path,headers={'Cookie':cookie,'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0.0.0 Safari/537.36'}),timeout=180) as r:
            html=r.read().decode();assert r.status==200
        assert 'IU_CROSS_PAGE' not in html and 'iu-fragment-access-harness' not in html and 'iu-custom-proof' not in html
        assert 'data-id="26fa46c6"' in html and 'data-id="417d1ae"' in html and 'data-id="c60daee"' in html
        assert '[IU staging cache proof]' not in html
        report.append({'path':path,'request':request_number,'status':200,'no_probe_parameters':True,'observer_and_custom_fixture_absent':True,'real_header_present':True})
Path('docs/fragment-cache-generic-ordinary.json').write_text(json.dumps(report,indent=2)+'\n')
print('PASS normal GR/EN requests after observer removal; real header, no test title or fixture assets')
