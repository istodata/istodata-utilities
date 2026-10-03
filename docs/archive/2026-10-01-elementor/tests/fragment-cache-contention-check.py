"""Private staging-only cold (>15s), bounded fallback and stale-refresh gates."""
from concurrent.futures import ThreadPoolExecutor
from uuid import uuid4
import subprocess
import importlib.util
from pathlib import Path

spec = importlib.util.spec_from_file_location('kit_checks', Path(__file__).with_name('fragment-cache-kit-staging-check.py'))
kit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(kit)

def concurrent(generation, delay, wait=None, count=3):
    with ThreadPoolExecutor(max_workers=count) as executor:
        return list(executor.map(lambda _: kit.fetch('/', generation, delay=delay, wait=wait), range(count)))

generation = uuid4().hex
cold = concurrent(generation, 20)
assert sorted(r[0]['target'] for r in cold) == [0, 0, 1]
assert sorted(r[0]['result'] for r in cold) == ['hit', 'hit', 'stored']
assert len({r[0]['sha'] for r in cold}) == 1
assert all(r[2] == cold[0][2] for r in cold)
assert min(r[0]['lookup_ms'] for r in cold if r[0]['result'] == 'hit') > 15000
print('PASS: cold builder >15s; one build, two waiting hits', flush=True)

key = cold[0][0]['key']
assert key.startswith('iu_frag_') and len(key) == 72
command = 'cd /home/master/applications/manqbfzxjy/public_html && wp eval-file /tmp/iu-fragment-stale-fixture.php ' + key
subprocess.run(['ssh', '-i', 'C:/Users/pe/.ssh/cloudways_server_1', '-o', 'BatchMode=yes',
                'master_hnrnsyahbd@104.248.132.240', command], check=True)
stale = concurrent(generation, 20)
assert sorted(r[0]['target'] for r in stale) == [0, 0, 1]
assert sorted(r[0]['result'] for r in stale) == ['stale-hit', 'stale-hit', 'stored']
assert len({r[0]['sha'] for r in stale + cold}) == 1
assert all(r[2] == cold[0][2] for r in stale)
assert max(r[0]['lookup_ms'] for r in stale if r[0]['result'] == 'stale-hit') < 1000
print('PASS: bounded stale refresh; one builder, two immediate stale hits', flush=True)

generation = uuid4().hex
fallback = concurrent(generation, 5, wait=1, count=2)
assert sorted(r[0]['result'] for r in fallback) == ['none', 'stored']
assert all(r[0]['target'] == 1 and r[0]['loop'] == 150 for r in fallback)
assert len({r[0]['sha'] for r in fallback}) == 1
assert all(r[2] == fallback[0][2] for r in fallback)
hit = kit.fetch('/', generation)
assert hit[0]['result'] == 'hit' and hit[0]['target'] == hit[0]['loop'] == 0
print('PASS: bounded cold timeout renders normally; only lock owner publishes', flush=True)
