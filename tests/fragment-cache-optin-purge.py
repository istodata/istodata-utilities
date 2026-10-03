"""Exercise the real protected admin-bar handler without revealing nonces/cookies."""
from pathlib import Path
from urllib.request import Request,urlopen
from urllib.error import HTTPError
from urllib.parse import urlparse,parse_qs,urlencode,urlunparse
from html import unescape
from uuid import uuid4
import runpy,re,json
t=runpy.run_path(str(Path(__file__).with_name('fragment-cache-access-check.py')))
fetch=t['fetch'];sessions=t['sessions'];BASE=t['BASE'];UA=t['h']['UA']
gen=uuid4().hex
before=fetch('administrator',gen);enbefore=fetch('anonymous',gen,'/en/')
def request(role,url):
    s=sessions[role]
    cookie='; '.join(s[n]+'='+s[v] for n,v in [('name','value'),('auth_name','auth_value'),('secure_name','secure_value')])
    return urlopen(Request(url,headers={'User-Agent':UA,'Cookie':cookie}),timeout=180)
with request('site_manager',BASE+'/wp-admin/options-general.php?page=istodata-utilities&tab=elementor') as r:
    admin_html=r.read().decode();assert r.status==200
link=unescape(re.search(r'''id=["']wp-admin-bar-iu-fragment-purge-all["'].*?href=["']([^"']+)''',admin_html,re.S).group(1))
for role,bad_nonce in [('subscriber',False),('site_manager',True)]:
    url=link
    if bad_nonce:
        parts=urlparse(url);query=parse_qs(parts.query);query['_wpnonce']=['invalid'];url=urlunparse(parts._replace(query=urlencode(query,doseq=True)))
    try:
        with request(role,url) as r: raise AssertionError('Unauthorized purge was accepted')
    except HTTPError as e: assert e.code==403,e.code
    unchanged=fetch('anonymous',gen)
    assert unchanged['data']['keys']==before['data']['keys'] and unchanged['data']['loop_any']==0
with request('site_manager',link) as r:
    html=r.read().decode();assert r.status==200 and 'Fragment Cache εκκαθαρίστηκε' in html and 'role="status"' in html
after=fetch('anonymous',gen);afterhit=fetch('administrator',gen)
enafter=fetch('anonymous',gen,'/en/');enhit=fetch('anonymous',gen,'/en/')
assert before['data']['keys']!=after['data']['keys'] and enbefore['data']['keys']!=enafter['data']['keys']
assert after['data']['loop_any']==150 and enafter['data']['loop_any']==156
assert afterhit['data']['loop_any']==enhit['data']['loop_any']==0
assert before['fragments']==after['fragments']==afterhit['fragments']
assert enbefore['fragments']==enafter['fragments']==enhit['fragments']
(Path(__file__).resolve().parents[1]/'docs/fragment-cache-optin-purge.json').write_text(json.dumps({'capability_403':True,'invalid_nonce_403':True,'no_effect_on_rejected_requests':True,'return_feedback':True,'all_language_keys_changed':True,'after_purge_loops':[150,156],'following_hits_loops':[0,0],'html_identical':True},indent=2)+'\n')
print('PASS admin-bar purge: capability/nonce, feedback/return, both language generations, unchanged HTML and lazy rebuild')
