from urllib.request import Request,urlopen
from pathlib import Path
import json
out=[]
base='https://wordpress-218158-6702910.cloudwaysapps.com'
ua='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0 Safari/537.36'
for p in ['/','/en/']:
    with urlopen(Request(base+p,headers={'User-Agent':ua}),timeout=180) as r:
        html=r.read().decode()
        assert r.status==200 and 'IU_CROSS_PAGE' not in html and 'data-id="26fa46c6"' in html
        out.append({'path':p,'status':r.status,'observer_absent':True,'menu_present':True})
Path('docs/fragment-cache-preview-final-public.json').write_text(json.dumps(out,indent=2)+'\n')
print(out)
