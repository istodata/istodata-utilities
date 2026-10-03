"""Manual purge must prevent even a retained stale fragment from being reused."""
import importlib.util
from pathlib import Path
from uuid import uuid4
import subprocess

spec = importlib.util.spec_from_file_location('kit', Path(__file__).with_name('fragment-cache-kit-staging-check.py'))
kit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(kit)
generation = uuid4().hex
miss, hit = kit.pair('/', generation)
key = miss[0]['key']
assert key.startswith('iu_frag_') and len(key) == 72
for command in ['wp eval-file /tmp/iu-fragment-stale-fixture.php ' + key,
                'wp eval-file /tmp/elementor-fragment-purge.php']:
    subprocess.run(['ssh', '-i', 'C:/Users/pe/.ssh/cloudways_server_1', '-o', 'BatchMode=yes',
                    'master_hnrnsyahbd@104.248.132.240',
                    'cd /home/master/applications/manqbfzxjy/public_html && ' + command], check=True)
fresh, following_hit = kit.pair('/', generation)
assert fresh[0]['key'] != key and fresh[0]['result'] == 'stored'
assert following_hit[0]['result'] == 'hit'
assert fresh[1] == miss[1] and fresh[2] == miss[2]
print('PASS: manual purge changes key, rebuilds and never reuses old stale entry')
