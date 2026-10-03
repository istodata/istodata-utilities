from pathlib import Path
from html.parser import HTMLParser
from urllib.request import Request,urlopen
from urllib.parse import urlencode
from uuid import uuid4
import runpy,json
m=runpy.run_path('tests/fragment-cache-access-check.py');gen=uuid4().hex
before=m['fetch']('anonymous',gen);en=m['fetch']('anonymous',gen,'/en/')
s=m['sessions']['site_manager'];cookie='; '.join(s[n]+'='+s[v] for n,v in [('name','value'),('auth_name','auth_value'),('secure_name','secure_value')])
class Forms(HTMLParser):
 def __init__(self):super().__init__();self.forms=[];self.current=None
 def handle_starttag(self,t,a):
  a=dict(a)
  if t=='form':self.current={'action':a.get('action',''),'data':{}}
  if t=='input' and self.current and a.get('name'):self.current['data'][a['name']]=a.get('value','')
 def handle_endtag(self,t):
  if t=='form' and self.current:self.forms.append(self.current);self.current=None
with urlopen(Request(m['BASE']+'/wp-admin/tools.php?page=iu-elementor-fragments',headers={'Cookie':cookie,'User-Agent':m['h']['UA']}),timeout=180) as r:p=Forms();p.feed(r.read().decode())
f=next(f for f in p.forms if f['data'].get('action')=='iu_fragment_purge' and f['data'].get('document_id')=='30' and f['data'].get('element_id')=='417d1ae')
with urlopen(Request(f['action'],data=urlencode(f['data']).encode(),headers={'Cookie':cookie,'User-Agent':m['h']['UA']}),timeout=180) as r:assert r.status==200
after=m['fetch']('anonymous',gen);hit=m['fetch']('site_manager',gen);enhit=m['fetch']('anonymous',gen,'/en/')
assert before['data']['keys']['417d1ae']!=after['data']['keys']['417d1ae']
assert before['data']['keys']['c60daee']==after['data']['keys']['c60daee'] and en['data']['keys']==enhit['data']['keys']
assert [e['result'] for e in after['data']['events']]==['stored','hit'] and hit['data']['loop_any']==enhit['data']['loop_any']==0
assert before['fragments']==after['fragments']==hit['fragments']
Path('docs/fragment-cache-optin-selective.json').write_text(json.dumps({'real_protected_tools_post':True,'only_target_key_changed':True,'other_element_language_preserved':True,'partial_miss_events':after['data']['events'],'partial_miss_loops':after['data']['loop_any'],'following_hits_zero_loops':True,'html_equal':True},indent=2)+'\n')
print('PASS real selective purge: only target rebuilds, sibling/language hits and identical HTML')
