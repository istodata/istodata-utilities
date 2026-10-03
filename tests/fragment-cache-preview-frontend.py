import runpy,uuid,json
m=runpy.run_path('tests/fragment-cache-access-check.py')
out=[]
for path,destination in [('/','/techniki-ypostirixi/'),('/en/','/en/the-company/')]:
    gen=uuid.uuid4().hex
    miss=m['fetch']('anonymous',gen,path)
    hit=m['fetch']('site_manager',gen,destination)
    baseline=m['fetch']('site_manager',gen,destination,'baseline')
    assert [e['result'] for e in miss['data']['events']]==['stored','stored']
    assert [e['result'] for e in hit['data']['events']]==['hit','hit']
    assert hit['data']['loop_any']==0 and hit['data']['target']==1 and not hit['data']['template_renders']
    assert miss['data']['keys']==hit['data']['keys']
    assert hit['fragments']==baseline['fragments'] and hit['assets']==baseline['assets']
    assert hit['data']['operations'],'Observer must detect actual frontend cache operations'
    assert any(g['eligible'] and not g['preview'] and not g['editor'] for g in hit['data']['gates'])
    out.append({'path':path,'miss_loops':miss['data']['loop_any'],'hit_loops':0,'keys_html_assets_equal':True,
        'frontend_gate':hit['data']['gates'],'cache_operations':len(hit['data']['operations'])})
m['Path']('docs/fragment-cache-preview-frontend.json').write_text(json.dumps(out,indent=2)+'\n')
print('PASS logged-in frontend still caches in both languages; positive operation observer control')
