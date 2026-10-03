"""Actual desktop UA sharing and native-hidden tablet/phone exclusion."""
from pathlib import Path
from uuid import uuid4
import importlib.util
import json

spec = importlib.util.spec_from_file_location('kit', Path(__file__).with_name('fragment-cache-kit-staging-check.py'))
kit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(kit)
generation = uuid4().hex
miss, hit = kit.pair('/', generation)
Path('docs/fragment-cache-builder-order-staging.json').write_text(
    json.dumps(miss[0]['builder_order'], indent=2) + '\n', encoding='utf-8')
firefox = kit.fetch('/', generation, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0')
assert firefox[0]['result'] == 'hit' and firefox[0]['target'] == firefox[0]['nested'] == firefox[0]['loop'] == 0
assert firefox[0]['key'] == miss[0]['key'] and firefox[1] == miss[1] and firefox[2] == miss[2]
tablet = 'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
for ua in [tablet, kit.PHONE]:
    response = kit.fetch('/', generation, ua)
    assert response[0]['target'] == response[0]['nested'] == response[0]['loop'] == 0 and not response[1]
print('PASS: normalized desktop UA sharing; hidden tablet and phone; actual callback order saved')
