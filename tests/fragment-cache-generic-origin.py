"""Real staging menu-root bypass and unchanged wrapper/assets acceptance."""
from pathlib import Path
from hashlib import sha256
import runpy,uuid,json,re
m=runpy.run_path('tests/fragment-cache-access-check.py');fetch=m['fetch'];out=[]
menus=['d7ebc85','a1ff108','e62c115','c28398e'];targets=menus+['417d1ae','c60daee','iu-core-proof','iu-custom-proof']
def fragments(result):
    found={}
    for target in targets:
        parser=m['Fragment'](target);parser.feed(result['html'])
        if parser.start: found[target]=result['html'][m['h']['offset'](result['html'],parser.start):result['html'].index('>',m['h']['offset'](result['html'],parser.end))+1]
    return found
def normalize_current(html):
    html=re.sub(r'\saria-current="[^"]*"','',html)
    return re.sub(r'\s(?:current-menu-item|current-menu-parent|current-menu-ancestor|current_page_item|current_page_parent|current_page_ancestor|elementor-item-active|page_item|page-item-\d+)\b','',html)
for language,source,dest in [('el','/','/techniki-ypostirixi/'),('en','/en/','/en/the-company/')]:
    for builder,consumer in [('anonymous','site_manager'),('site_manager','anonymous')]:
        gen=uuid.uuid4().hex
        miss=fetch(builder,gen,source);hit=fetch(consumer,gen,dest)
        native_source=fetch(builder,gen,source,'baseline');native_dest=fetch(consumer,gen,dest,'baseline')
        md,hd=miss['data'],hit['data'];mf,hf,sf,df=map(fragments,[miss,hit,native_source,native_dest])
        assert set(md['keys'])==set(targets),md
        assert all(e['result']=='stored' for e in md['events']) and len(md['events'])==8,md
        assert len(hd['events'])==8 and all(e['result']=='hit' for e in hd['events']),hd
        assert md['keys']==hd['keys']
        assert set(md['proof_renders'])=={'iu-core-proof','iu-custom-proof'} and not hd['proof_renders']
        assert set(md['nav_renders']).intersection(menus)==set(menus) and not set(hd['nav_renders']).intersection(menus),hd
        assert not any(any(t in call for t in menus) for call in hd['nav_construction']),hd
        assert len([call for call in md['nav_construction'] if any(t in call for t in menus)])==8
        assert md['loop_any']>0 and hd['loop_any']==0 and not hd['template_renders']
        assert mf==hf==sf,'Stored menu or full wrapper differs from native source'
        assert all(normalize_current(hf.get(k,''))==normalize_current(df.get(k,'')) for k in targets),'Difference beyond explicitly shared current-page attributes'
        assert hit['assets']==native_dest['assets'],'Required assets mismatch'
        assert hd['excerpt']==native_dest['data']['excerpt'] and hd['displayed']==native_dest['data']['displayed'],'Subsequent query/excerpt state mismatch'
        assert hd['future_consumer']==native_dest['data']['future_consumer'],'Actual subsequent public query/excerpt output mismatch'
        out.append(dict(language=language,builder=builder,consumer=consumer,miss_loops=md['loop_any'],hit_loops=0,
            miss_menu_renders=md['nav_renders'],hit_menu_renders=hd['nav_renders'],hit_menu_construction=hd['nav_construction'],
            keys_equal=True,source_native_full_html_equal=True,consumer_equal_except_current_attributes=True,assets_equal=True,consumer_excerpt_displayed_equal=True,original_core_custom_hit_renders=hd['proof_renders'],
            frozen_current_attributes=[k for k in menus if hf.get(k,'')!=df.get(k,'')],empty_native_fragments=[k for k in targets if k not in hf],resolved_menu_items=md['nav_resolved'],
            fragment_sha256={k:sha256(v.encode()).hexdigest() for k,v in hf.items()}))
        Path('docs/fragment-cache-generic-origin.json').write_text(json.dumps(out,indent=2)+'\n')
print('PASS eight actual roots including core text editor and custom CSS/JS widget, Greek/English cross-page/user, zero original menu construction and loop renders on hits')
