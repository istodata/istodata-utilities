from pathlib import Path
import runpy,json,uuid,difflib,hashlib,re
m=runpy.run_path('tests/fragment-cache-access-check.py');out=[]
def normalize_state(html):
 def classes(match):
  tokens=[x for x in match[1].split() if x not in ['current-menu-item','current_page_item','page_item','elementor-item-active','current-menu-parent','current-menu-ancestor','current_page_ancestor'] and not re.fullmatch(r'page-item-\d+',x)]
  return 'class="'+' '.join(tokens)+'"'
 return re.sub(r'\saria-current="page"','',re.sub(r'class="([^"]*)"',classes,html))

def root(r):
 p=m['Fragment']('26fa46c6');p.feed(r['html']);return r['html'][m['h']['offset'](r['html'],p.start):r['html'].index('>',m['h']['offset'](r['html'],p.end))+1]
for lang,path,dest in [('el','/','/techniki-ypostirixi/'),('en','/en/','/en/the-company/')]:
 for builder,consumer in [('anonymous','site_manager'),('site_manager','anonymous')]:
  gen=uuid.uuid4().hex;miss=m['fetch'](builder,gen,path);hit=m['fetch'](consumer,gen,dest);base=m['fetch'](consumer,gen,dest,'baseline');sourcebase=m['fetch'](builder,gen,path,'baseline')
  md,hd=[r['data'] for r in [miss,hit]];a,b,c,d=[root(r) for r in [miss,hit,base,sourcebase]]
  row={'language':lang,'builder':builder,'consumer':consumer,'miss_events':md['events'],'hit_events':hd['events'],'miss_root_renders':md['target'],'hit_root_renders':hd['target'],'miss_loops':md['loop_any'],'hit_loops':hd['loop_any'],'hit_original_templates':hd['template_renders'],'bytes':len(a.encode()),'keys_equal':md['keys']==hd['keys'],'stored_hit_equal':a==b,'source_normal_equal':a==d,'consumer_normal_equal':b==c,'normal_cross_page_equal':c==d,'assets_equal':hit['assets']==base['assets'],'sha256':hashlib.sha256(b.encode()).hexdigest(),'page_state_attrs':{name:re.findall(r'<[^>]*(?:aria-current|e-current|current-menu|current_page)[^>]*>',html) for name,html in [('source',d),('consumer',c)]}}
  row['consumer_equal_except_current_page_attributes']=normalize_state(b)==normalize_state(c)
  row['normal_cross_page_equal_except_current_page_attributes']=normalize_state(c)==normalize_state(d)
  out.append(row);Path('docs/fragment-cache-root-origin.json').write_text(json.dumps(out,indent=2)+'\n')
  if b!=c:Path('docs/fragment-cache-root-page-state.patch').write_text(''.join(difflib.unified_diff(b.splitlines(True),c.splitlines(True),fromfile='cached-source-menu',tofile='normal-consumer-menu')))
  assert [e['result'] for e in md['events']]==['stored'],row
  assert [e['result'] for e in hd['events']]==['hit'] and hd['target']==0 and hd['loop_any']==0 and not hd['template_renders'],row
  if 'final_excerpt' in hd:
   assert hd['final_excerpt']==base['data']['final_excerpt'] and hd['final_displayed']==base['data']['final_displayed'],'Root replay effects differ from consumer'
  assert a==b==d and normalize_state(b)==normalize_state(c) and row['assets_equal'],'Additional root menu difference beyond measured current-page attributes'
  assert hd['final_excerpt']==base['data']['final_excerpt'] and hd['final_displayed']==base['data']['final_displayed'],'Root replay effects differ from native consumer'
print('PASS actual root-cache el/en anonymous/logged-in cross-page bypass and menu/assets equality except explicitly measured current-page attributes (acceptance pending administrator choice)')
