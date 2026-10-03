"""Actual origin anonymous/authenticated reuse, never write credentials into evidence."""
from pathlib import Path
from urllib.request import Request,urlopen
from uuid import uuid4
from hashlib import sha256
from html.parser import HTMLParser
import json,re,runpy,tempfile
h=runpy.run_path(str(Path(__file__).with_name('fragment-cache-staging-check.py')))
a=runpy.run_path(str(Path(__file__).with_name('fragment-cache-assets-check.py')))
BASE='https://wordpress-218158-6702910.cloudwaysapps.com'
sessions=json.loads((Path(tempfile.gettempdir())/'iu-fragment-access-sessions.json').read_text())
class Fragment(h['TargetParser']):
    def __init__(self,target): super().__init__();self.target=target
    def handle_starttag(self,tag,attrs):
        attrs=[(k,'26fa46c6' if v==self.target else 'other') if k=='data-id' else (k,v) for k,v in attrs]
        super().handle_starttag(tag,attrs)
def fetch(role,generation,path='/',mode=None,ua=None):
    url=BASE+path+('&' if '?' in path else '?')+'iu_kit_fragment_test=cross-page-20261001&iu_kit_request='+uuid4().hex+'&iu_kit_generation='+generation
    if mode: url+='&iu_kit_mode='+mode
    headers={'User-Agent':ua or h['UA']}
    if role!='anonymous':
        s=sessions[role];headers['Cookie']='; '.join(s[n]+'='+s[v] for n,v in [('name','value'),('auth_name','auth_value'),('secure_name','secure_value')])
    with urlopen(Request(url,headers=headers),timeout=180) as r:
        html=r.read().decode();assert r.status==200
        if role=='anonymous': assert r.headers.get('X-Cache')=='MISS'
    marker=re.search(r'IU_CROSS_PAGE (.+?) -->',html);assert marker,'Observer absent'
    data=json.loads(marker.group(1)); assert data['user_id']==(0 if role=='anonymous' else sessions[role]['id'])
    fragments={}
    for target in ['417d1ae','c60daee']:
        p=Fragment(target);p.feed(html)
        if p.start: fragments[target]=html[h['offset'](html,p.start):html.index('>',h['offset'](html,p.end))+1]
    assets=a['AssetTags']();assets.feed(html)
    assert data['optin_scans']==0,'Frontend scanned saved opt-ins'
    print(role,path,'loops',data['loop_any'],'events',data['events'],'reject',data['reject'],flush=True)
    return {'data':data,'fragments':fragments,'assets':assets.external,'html':html,'url':url}
if __name__=='__main__':
    report=[]
    for path,bpath in [('/','/techniki-ypostirixi/'),('/en/','/en/the-company/')]:
        for builder,consumer in [('anonymous','administrator'),('administrator','anonymous'),('subscriber','anonymous')]:
            gen=uuid4().hex
            miss=fetch(builder,gen,path);hit=fetch(consumer,gen,bpath);baseline=fetch(consumer,gen,bpath,'baseline')
            assert [e['result'] for e in miss['data']['events']]==['stored','stored'], miss['data']
            assert [e['result'] for e in hit['data']['events']]==['hit','hit'], hit['data']
            assert miss['data']['keys']==hit['data']['keys']
            assert miss['data']['loop_any']>0 and hit['data']['loop_any']==0 and not hit['data']['template_renders']
            assert hit['data']['target']==1
            assert miss['fragments']==hit['fragments']==baseline['fragments'], 'Shared fragment differs from normal consumer'
            assert hit['assets']==baseline['assets'],'Consumer assets differ from normal rendering'
            assert hit['data']['excerpt']==baseline['data']['excerpt'] and hit['data']['displayed']==baseline['data']['displayed']
            for fragment in hit['fragments'].values():
                assert not re.search(r'nonce|wp-admin|wp-login|elementor-edit-(area|mode|link)|data-(user|session|token)',fragment,re.I)
            report.append({'builder':builder,'consumer':consumer,'path':path,'loops_miss':miss['data']['loop_any'],'loops_hit':0,
                'assets':len(hit['assets']),'keys_equal':True,'fragment_sha256':{k:sha256(v.encode()).hexdigest() for k,v in hit['fragments'].items()},
                'html_assets_effects_equal':True,'frontend_optin_scans':0})
    (Path(__file__).resolve().parents[1]/'docs/fragment-cache-access-staging.json').write_text(json.dumps(report,indent=2)+'\n')
    print('PASS actual anonymous/admin/subscriber reuse in both languages; no private fragment markup; zero frontend opt-in scans')
