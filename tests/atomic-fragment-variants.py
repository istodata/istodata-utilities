"""Language/device parity for an already running private staging prototype."""
from pathlib import Path
import hashlib, json, re, subprocess, sys, uuid

work = Path(sys.argv[1]).resolve()
assert work.parent == Path(__import__('tempfile').gettempdir()).resolve()
assert re.fullmatch(r'iu-atomic-probe-[a-z0-9_]+', work.name)
config = json.loads((work/'private.json').read_text())
remote = '/home/master/applications/manqbfzxjy/tmp/'+work.name
ssh_key = 'C:/Users/pe/.ssh/cloudways_server_1'
user = 'master_hnrnsyahbd@104.248.132.240'
rows = []
try:
    for language, prefix, ua, mobile in [
        ('en', '/en/', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0', False),
        ('el', '/', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari/604.1', True)]:
        group = []
        for mode in ['baseline', 'candidate', 'candidate']:
            request = uuid.uuid4().hex
            header_path = work/'variant-headers.txt'
            header_path.write_text('X-IU-Atomic-Probe: '+config['key']+'\nX-IU-Atomic-Mode: '+mode+
                '\nX-IU-Atomic-Request: '+request+'\nHost: wordpress-218158-6702910.cloudwaysapps.com'+
                '\nX-Forwarded-Proto: https\nUser-Agent: '+ua+'\n')
            subprocess.run(['scp','-O','-i',ssh_key,str(header_path),user+':'+remote+'/headers.txt'],capture_output=True,check=True)
            route='http://127.0.0.1:8086'+prefix+'?iu_atomic_private=variants'
            result=subprocess.run(['ssh','-i',ssh_key,user,'curl --silent --show-error --max-time 90 -H @'+
                remote+'/headers.txt '+repr(route)],capture_output=True,check=True,timeout=100)
            html=result.stdout.decode()
            marker=re.search(r'<!-- IU_ATOMIC_PROBE (.*?) -->',html)
            assert marker, 'Missing fresh PHP observer'
            observed=json.loads(marker[1])
            assert observed['request']==request and observed['language']==language and observed['mobile']==mobile, 'Request/language/device does not match'
            main=re.search(r'<main>(.*?)</main>',html,re.S)[1]
            row={k:observed[k] for k in ['selected','queries','language','mobile']}
            row.update(items=main.count('role="listitem"'),html_sha256=hashlib.sha256(main.encode()).hexdigest(),
                links=re.findall(r'href="([^"]+)"',main),
                assets=re.findall(r'<(?:script|link)\b[^>]*(?:src|href)=[\"\x27]([^\"\x27]+)',html),
                inline_sha256=[hashlib.sha256(x.encode()).hexdigest() for x in re.findall(r'<(?:style|script)\b[^>]*>(.*?)</(?:style|script)>',html,re.S)])
            group.append(row); rows.append(row)
            print(json.dumps({k:v for k,v in row.items() if k not in ['assets','inline_sha256']}),flush=True)
        assert group[0]['queries']==group[1]['queries']==1 and group[2]['queries']==0 and group[2]['selected']=='hit'
        assert len({r['html_sha256'] for r in group})==1, 'Variant HTML mismatch'
        assert all(r['assets']==group[0]['assets'] and r['inline_sha256']==group[0]['inline_sha256'] for r in group), 'Variant asset mismatch'
    Path('docs/atomic-fragment-language-device.json').write_text(json.dumps(rows,indent=2)+'\n')
finally:
    if (work/'variant-headers.txt').exists(): (work/'variant-headers.txt').unlink()
