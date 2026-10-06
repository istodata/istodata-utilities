"""Authorized staging-only vendor swap with unconditional rollback."""
import hashlib, json, subprocess, sys, secrets, os
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
ARCHIVES='/home/master/applications/manqbfzxjy/tmp/iu-elementor-434-review-20261006'
REMOTE='/home/master/applications/manqbfzxjy/tmp/iu-elementor-434-resume-'+secrets.token_hex(5)
WEB='/home/master/applications/manqbfzxjy/public_html'
SSH=['ssh','-i','C:/Users/pe/.ssh/cloudways_server_1','-o','BatchMode=yes','master_hnrnsyahbd@104.248.132.240']
SCP=['scp','-O','-i','C:/Users/pe/.ssh/cloudways_server_1']
def remote(code):
    result=subprocess.run(SSH+[code],capture_output=True,timeout=180)
    if result.returncode: raise RuntimeError(result.stderr.decode(errors='replace')[:800])
    return result.stdout.decode()

report={'pair':{'core':'4.3.4','pro':'4.3.1'}}
installed=False
kit_installed=False
full_release='--full-release' in sys.argv
try:
    remote('mkdir -m 700 '+REMOTE+' && test "$(sha256sum '+ARCHIVES+'/core434.zip | cut -d\' \' -f1)" = 1b67ddd3acca7b245ac0b7e42e4f8ef958e01602d629e8919874b90607cdc12e')
    snapshot=r'''<?php
if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
if(ELEMENTOR_VERSION!=='4.3.3'||ELEMENTOR_PRO_VERSION!=='4.3.1')throw new RuntimeException('Unexpected pair');
global $wpdb;
$options=$wpdb->get_results("SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE 'elementor%' OR option_name='cron' OR option_name IN ('iu_elementor_fragment_epoch','iu_elementor_atomic_fragment_epoch')",ARRAY_A);
file_put_contents(__DIR__.'/options-before.json',wp_json_encode($options));
$metadata=$wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE meta_key IN ('_elementor_css','_elementor_page_assets','_elementor_element_cache')",ARRAY_A);
file_put_contents(__DIR__.'/metadata-before.json',wp_json_encode($metadata));
$headers=$wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE post_id IN (30,33000) ORDER BY meta_id",ARRAY_A);
file_put_contents(__DIR__.'/headers-before.json',wp_json_encode($headers));
$posts=$wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE ID IN (30,33000) ORDER BY ID",ARRAY_A);
file_put_contents(__DIR__.'/posts-before.json',wp_json_encode($posts));
$state=[];foreach([30,33000] as $id)$state['doc'.$id]=hash('sha256',serialize(get_post($id)).serialize(get_post_meta($id)));
foreach(['istodata_utilities_settings','elementor_element_cache_ttl','active_plugins'] as $k)$state[$k]=get_option($k);
file_put_contents(__DIR__.'/state-before.json',wp_json_encode($state));echo 'snapshot saved privately';
'''
    restore=r'''<?php
if(realpath(ABSPATH)!=='/home/218158.cloudwaysapps.com/manqbfzxjy/public_html')throw new RuntimeException('Wrong target');
global $wpdb;$old=json_decode(file_get_contents(__DIR__.'/options-before.json'),true);$names=array_column($old,'option_name');
foreach($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'elementor%' OR option_name IN ('iu_elementor_fragment_epoch','iu_elementor_atomic_fragment_epoch')") as $name)if(!in_array($name,$names,true))delete_option($name);
foreach($old as $row){$wpdb->replace($wpdb->options,$row);wp_cache_delete($row['option_name'],'options');}
wp_cache_delete('alloptions','options');wp_cache_delete('notoptions','options');
$rows=json_decode(file_get_contents(__DIR__.'/metadata-before.json'),true);
$affected=$wpdb->get_col("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_elementor_css','_elementor_page_assets','_elementor_element_cache')");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_elementor_css','_elementor_page_assets','_elementor_element_cache')");
foreach($rows as $row){if($wpdb->insert($wpdb->postmeta,$row)===false)throw new RuntimeException('Metadata restore failed');$affected[]=$row['post_id'];}
foreach(array_unique($affected) as $id)wp_cache_delete($id,'post_meta');
$headers=json_decode(file_get_contents(__DIR__.'/headers-before.json'),true);
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN (30,33000)");
foreach($headers as $row)if($wpdb->insert($wpdb->postmeta,$row)===false)throw new RuntimeException('Header metadata restore failed');
foreach(json_decode(file_get_contents(__DIR__.'/posts-before.json'),true) as $row){$id=$row['ID'];unset($row['ID']);$wpdb->update($wpdb->posts,$row,['ID'=>$id]);clean_post_cache($id);}
foreach([30,33000] as $id)wp_cache_delete($id,'post_meta');
$state=[];foreach([30,33000] as $id)$state['doc'.$id]=hash('sha256',serialize(get_post($id)).serialize(get_post_meta($id)));
foreach(['istodata_utilities_settings','elementor_element_cache_ttl','active_plugins'] as $k)$state[$k]=get_option($k);
$expected=json_decode(file_get_contents(__DIR__.'/state-before.json'),true);
$raw_headers=$wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE post_id IN (30,33000) ORDER BY meta_id",ARRAY_A);
$raw_posts=$wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE ID IN (30,33000) ORDER BY ID",ARRAY_A);
if($raw_headers!==$headers||$raw_posts!==json_decode(file_get_contents(__DIR__.'/posts-before.json'),true))throw new RuntimeException('Raw header state differs');
foreach(['istodata_utilities_settings','elementor_element_cache_ttl','active_plugins'] as $key)if($state[$key]!==$expected[$key])throw new RuntimeException('Saved options differ');
file_put_contents(__DIR__.'/restoration.json',wp_json_encode(['pair'=>[ELEMENTOR_VERSION,ELEMENTOR_PRO_VERSION],'settings_documents_unchanged'=>true,'cache_metadata_restored'=>true,'updater_cron_restored'=>true,'element_cache'=>get_option('elementor_element_cache_ttl')]));
'''
    for name,code in [('snapshot.php',snapshot),('restore-options.php',restore)]:
        path=ROOT/'tests'/('temporary-434-'+name)
        path.write_text(code)
        try:
            subprocess.run(SCP+[str(path),'master_hnrnsyahbd@104.248.132.240:'+REMOTE+'/'+name],check=True,capture_output=True)
        finally: path.unlink()
    remote('cd '+WEB+' && wp eval-file '+REMOTE+'/snapshot.php')
    remote('tar --acls --xattrs -cf '+REMOTE+'/generated-before.tar -C '+WEB+'/wp-content/uploads elementor')
    if full_release:
        package=ROOT.parent/'istodata-utilities.zip'
        subprocess.run(SCP+[str(package),'master_hnrnsyahbd@104.248.132.240:'+REMOTE+'/kit.zip'],check=True,capture_output=True)
        remote('test -d '+WEB+'/wp-content/plugins/istodata-utilities && unzip -q '+REMOTE+'/kit.zip -d '+REMOTE+'/kit-candidate')
        kit_installed=True
        remote('mv '+WEB+'/wp-content/plugins/istodata-utilities '+REMOTE+'/kit-original && mv '+REMOTE+'/kit-candidate/istodata-utilities '+WEB+'/wp-content/plugins/istodata-utilities')
        report['package_sha256']=hashlib.sha256(package.read_bytes()).hexdigest()
    # Exact original directory is moved, preserving modes and ACLs for rollback.
    remote('test ! -e '+REMOTE+'/elementor-original && test -d '+WEB+'/wp-content/plugins/elementor && unzip -q '+ARCHIVES+'/core434.zip -d '+REMOTE+'/candidate')
    installed=True
    remote('mv '+WEB+'/wp-content/plugins/elementor '+REMOTE+'/elementor-original && mv '+REMOTE+'/candidate/elementor '+WEB+'/wp-content/plugins/elementor')
    print('Official Core 4.3.4 temporarily installed on authorized staging',flush=True)
    args=[sys.executable,str(ROOT/'tests/atomic-fragment-staging.py'),'--engine','--dynamic-fixture','--review-core=4.3.4']
    if full_release:
        args+=['--installed-release','--classic','--context-acceptance','--variants']
        report['installed_version']=remote('cd '+WEB+' && wp eval "echo IU_PLUGIN_VERSION;"').strip().splitlines()[-1]
        if report['installed_version']!='2.23.0':raise RuntimeError('Unexpected installed Kit version')
    else:
        args+=['--classic'] if '--classic' in sys.argv else ['--context-acceptance','--variants']
    if '--browser-review' in sys.argv: args+=['--browser-review']
    result=subprocess.run(args,cwd=ROOT,env={**os.environ,'PYTHONUTF8':'1'})
    evidence=json.loads((ROOT/'docs/elementor-434-atomic-result.json').read_text())
    report['atomic']=evidence
    if result.returncode or evidence.get('result')!='PASS': raise RuntimeError('Atomic acceptance stopped; inspect exact finding')
    report['result']='PASS'
except Exception as error:
    report.update(result='STOP',finding=str(error))
    print('STOP: '+str(error),flush=True)
finally:
    if installed:
        # Linux paths are fixed to this verified staging application/private folder.
        try:
            remote('test -d '+REMOTE+'/elementor-original && mv '+WEB+'/wp-content/plugins/elementor '+REMOTE+'/elementor-tested && mv '+REMOTE+'/elementor-original '+WEB+'/wp-content/plugins/elementor')
            if kit_installed:
                remote('test -d '+REMOTE+'/kit-original && mv '+WEB+'/wp-content/plugins/istodata-utilities '+REMOTE+'/kit-tested && mv '+REMOTE+'/kit-original '+WEB+'/wp-content/plugins/istodata-utilities')
                kit_installed=False
            remote('mv '+WEB+'/wp-content/uploads/elementor '+REMOTE+'/generated-tested && tar --acls --xattrs -xf '+REMOTE+'/generated-before.tar -C '+WEB+'/wp-content/uploads')
            remote('cd '+WEB+' && wp eval-file '+REMOTE+'/restore-options.php')
            report['restoration']=json.loads(remote('cat '+REMOTE+'/restoration.json'))
            report['restoration']['generated_files_restored']=True
        except Exception as error:
            report.update(result='STOP',restoration_error=str(error))
    if kit_installed:
        try:
            remote('test -d '+REMOTE+'/kit-original && mv '+WEB+'/wp-content/plugins/istodata-utilities '+REMOTE+'/kit-tested && mv '+REMOTE+'/kit-original '+WEB+'/wp-content/plugins/istodata-utilities')
        except Exception as error:
            report.update(result='STOP',restoration_error=str(error))
    report['private_backup_directory']=REMOTE
    (ROOT/'docs/elementor-434-staging-result.json').write_text(json.dumps(report,indent=2)+'\n')
    if full_release:(ROOT/'docs/release-2.23.0-staging.json').write_text(json.dumps(report,indent=2)+'\n')
    print(json.dumps({k:v for k,v in report.items() if k!='atomic'}),flush=True)
if report.get('result')!='PASS':sys.exit(1)
