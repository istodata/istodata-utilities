"""Final installed-build bypass and unchanged source HTML after the queue guard."""
from pathlib import Path
from hashlib import sha256
import runpy,uuid,json
m=runpy.run_path('tests/fragment-cache-access-check.py');prior=json.loads(Path('docs/fragment-cache-generic-origin.json').read_text());out=[]
for lang,source,dest in [('el','/','/techniki-ypostirixi/'),('en','/en/','/en/the-company/')]:
    gen=uuid.uuid4().hex;miss=m['fetch']('anonymous',gen,source);hit=m['fetch']('site_manager',gen,dest)
    md,hd=miss['data'],hit['data']
    assert len(md['events'])==len(hd['events'])==8
    assert all(e['result']=='stored' for e in md['events']) and all(e['result']=='hit' for e in hd['events'])
    assert hd['loop_any']==0 and not hd['template_renders'] and not hd['proof_renders'] and hd['custom_render_body']==0
    assert not set(hd['nav_renders']).intersection(['d7ebc85','a1ff108','e62c115','c28398e'])
    hashes={}
    for target in ['d7ebc85','a1ff108','e62c115','c28398e','417d1ae','c60daee','iu-core-proof','iu-custom-proof']:
        p=m['Fragment'](target);p.feed(miss['html'])
        if p.start:
            html=miss['html'][m['h']['offset'](miss['html'],p.start):miss['html'].index('>',m['h']['offset'](miss['html'],p.end))+1]
            hashes[target]=sha256(html.encode()).hexdigest()
    expected=next(p for p in prior if p['language']==lang and p['builder']=='anonymous')
    assert hashes==expected['fragment_sha256'],'Final source HTML changed from accepted native-source parity'
    out.append({'language':lang,'miss_loops':md['loop_any'],'hit_loops':0,'hit_custom_render_body':0,'all_eight_stored_then_hit':True,'source_html_matches_prior_native_parity':True,'source_fragment_sha256':hashes})
Path('docs/fragment-cache-generic-final-origin.json').write_text(json.dumps(out,indent=2)+'\n')
print('PASS final queue-guard build: both languages, all roots stored/hit, zero original bodies/menus/loops, unchanged HTML')
