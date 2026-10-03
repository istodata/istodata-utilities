"""Origin-only Kit staging checks; generic policy, fresh keys, no site edits."""
from concurrent.futures import ThreadPoolExecutor
from hashlib import sha256
from pathlib import Path
from urllib.request import Request, urlopen
from uuid import uuid4
import argparse
import re
import runpy
import json
import time

helpers = runpy.run_path(str(Path(__file__).with_name('fragment-cache-staging-check.py')))
assets = runpy.run_path(str(Path(__file__).with_name('fragment-cache-assets-check.py')))
UA = helpers['UA']
PHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
BASE = 'https://wordpress-218158-6702910.cloudwaysapps.com'

def fetch(path, generation, ua=UA, delay=0, wait=None):
    started = time.monotonic()
    url = f'{BASE}{path}?iu_kit_fragment_test=26fa46c6-20260930-a7d4e2&iu_kit_request={uuid4().hex}&iu_kit_generation={generation}&iu_kit_delay={delay}'
    if wait is not None:
        url += f'&iu_kit_wait={wait}'
    with urlopen(Request(url, headers={'User-Agent': ua}), timeout=180) as response:
        html = response.read().decode('utf-8')
        assert response.status == 200 and response.headers.get('X-Cache') == 'MISS'
    marker = re.search(r'IU_KIT_FRAGMENT_TEST ([^>]+)-->', html)
    assert marker, 'harness did not execute'
    values = dict(re.findall(r'(\w+)=([^ ]*)', marker.group(1)))
    parser = helpers['TargetParser']()
    parser.feed(html)
    fragment = ''
    if parser.start:
        start = helpers['offset'](html, parser.start)
        end = html.index('>', helpers['offset'](html, parser.end)) + 1
        fragment = html[start:end]
    asset_parser = assets['AssetTags']()
    asset_parser.feed(html)
    result = dict(path=path, phone=ua == PHONE, target=int(values['target']),
                  nested=int(values['nested']), loop=int(values['loop']), result=values['result'],
                  sha=sha256(fragment.encode()).hexdigest(), bytes=len(fragment.encode()),
                  asset_count=len(asset_parser.external))
    result['key'] = values.get('key')
    result['meta'] = values.get('meta')
    result['eligible'] = values.get('eligible')
    result['gate'] = values.get('gate')
    result['graph'] = values.get('graph')
    result['seconds'] = round(time.monotonic() - started, 2)
    trace = re.search(r'IU_KIT_FILTERED_NODE before=(\w+) keyed=(\w+)', html)
    if result['eligible'] == 'yes':
        assert trace and trace.group(1) == trace.group(2), 'filtered node differs from keyed node'
    order = re.search(r'IU_KIT_BUILDER_ORDER (.+?) -->', html)
    result['builder_order'] = json.loads(order.group(1)) if order else []
    excerpt = re.search(r'IU_KIT_EXCERPT_PROFILE (.+?) -->', html)
    result['excerpt_profile'] = json.loads(excerpt.group(1)) if excerpt else None
    lookup = re.search(r'IU_KIT_LOOKUP_MS (-?\d+)', html)
    result['lookup_ms'] = int(lookup.group(1)) if lookup else None
    report = {k: v for k, v in result.items() if k not in ['builder_order', 'excerpt_profile']}
    report['excerpt_profile'] = [{k: v for k, v in item.items() if k != 'parent'} for item in result['excerpt_profile'] or []]
    print(report, flush=True)
    return result, fragment, asset_parser.external

def pair(path, generation=None):
    generation = generation or uuid4().hex
    miss = fetch(path, generation)
    hit = fetch(path, generation)
    assert miss[0]['target'] == 1 and hit[0]['target'] == hit[0]['nested'] == hit[0]['loop'] == 0
    if miss[2] != hit[2]:
        print('miss_only_assets', miss[2] - hit[2], 'hit_only_assets', hit[2] - miss[2], flush=True)
    assert miss[1] == hit[1] and miss[2] == hit[2] and miss[0]['key'] == hit[0]['key']
    assert miss[0]['excerpt_profile'] == hit[0]['excerpt_profile']
    return miss, hit

def main():
    args = argparse.ArgumentParser()
    args.add_argument('--mode', choices=['pair', 'concurrency', 'variants'], default='pair')
    args.add_argument('--generation')
    args.add_argument('--path', default='/')
    args.add_argument('--audit-output')
    arguments = args.parse_args()
    mode = arguments.mode
    if mode == 'concurrency':
        generation = uuid4().hex
        with ThreadPoolExecutor(max_workers=3) as executor:
            results = list(executor.map(lambda _: fetch('/', generation), range(3)))
        assert sorted(r[0]['target'] for r in results) == [0, 0, 1]
        assert len({r[0]['sha'] for r in results}) == 1
    elif mode == 'variants':
        pair('/etairia/')
        pair('/en/')
        for path in ['/', '/en/']:
            result, fragment, _ = fetch(path, uuid4().hex, PHONE)
            assert not fragment and result['target'] == result['loop'] == 0
    else:
        miss, hit = pair(arguments.path, arguments.generation)
        if arguments.audit_output:
            Path(arguments.audit_output).write_text(json.dumps(miss[0]['builder_order'], indent=2) + '\n', encoding='utf-8')
    print(f'PASS: {mode}', flush=True)

if __name__ == '__main__':
    main()
