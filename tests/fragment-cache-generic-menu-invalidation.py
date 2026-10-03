"""Real source menu edit, generation change, changed HTML and exact title cleanup."""
from pathlib import Path
import runpy,uuid,json,subprocess
m=runpy.run_path('tests/fragment-cache-access-check.py');gen=uuid.uuid4().hex
def edit(mode):
    remote='cd /home/master/applications/manqbfzxjy/public_html && wp eval-file ../tmp/iu-fragment-generic-20261002/menu-edit.php '+mode
    r=subprocess.run(['ssh','-i','C:/Users/pe/.ssh/cloudways_server_1','-o','BatchMode=yes','master_hnrnsyahbd@104.248.132.240',remote],capture_output=True,text=True,timeout=60)
    assert r.returncode==0,r.stderr
    return json.loads(r.stdout)
before=m['fetch']('anonymous',gen);en=m['fetch']('anonymous',gen,'/en/')
cleanup_pending=False
try:
    cleanup_pending=True;changed=edit('edit')
    miss=m['fetch']('anonymous',gen);hit=m['fetch']('site_manager',gen)
    assert before['data']['keys']!=miss['data']['keys']
    assert all(e['result']=='stored' for e in miss['data']['events']) and all(e['result']=='hit' for e in hit['data']['events'])
    assert '[IU staging cache proof]' in miss['html'] and '[IU staging cache proof]' in hit['html']
    assert hit['data']['loop_any']==0
    assert miss['data']['custom_render_body']==1 and hit['data']['custom_render_body']==0
    enmiss=m['fetch']('anonymous',gen,'/en/');enhit=m['fetch']('site_manager',gen,'/en/')
    assert en['data']['keys']!=enmiss['data']['keys'] and enhit['data']['loop_any']==0
finally:
    if cleanup_pending: restored=edit('restore')
normal=m['fetch']('anonymous',gen)
assert '[IU staging cache proof]' not in normal['html']
Path('docs/fragment-cache-generic-menu-invalidation.json').write_text(json.dumps({'actual_menu_item_api':True,'all_language_generations_changed':True,'changed_title_in_miss_and_hit':True,'following_hit_zero_loops':True,'exact_own_title_restored':restored['own_title_restored'],'cleanup_rebuilt_without_test_title':True},indent=2)+'\n')
print('PASS real menu item update invalidation in both languages, changed HTML, lazy hits and exact own title cleanup')
