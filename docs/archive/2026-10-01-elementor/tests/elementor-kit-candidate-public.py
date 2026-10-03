"""Final anonymous smoke after temporary observation/Atomic fixture cleanup."""
from pathlib import Path
from urllib.request import Request, urlopen
from uuid import uuid4
import json

base = 'https://wordpress-218158-6702910.cloudwaysapps.com'
uas = {
    'desktop': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
    'phone': 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
}
results = []
for route in ['/techniki-ypostirixi/', '/en/the-company/']:
    for device, ua in uas.items():
        url = base + route + '?iu_final_smoke=' + uuid4().hex
        with urlopen(Request(url, headers={'User-Agent': ua}), timeout=180) as response:
            html = response.read().decode()
            status, cache = response.status, response.headers.get('X-Cache')
        assert status == 200 and 'id="wpadminbar"' not in html
        assert 'IU_CROSS_PAGE' not in html
        assert 'There has been a critical error' not in html
        desktop_header = 'data-id="26fa46c6"' in html
        # Greek has saved iu_hide_on_phone=yes; English has only native CSS hiding.
        # Preserve the independently saved language settings.
        assert desktop_header == (device == 'desktop' or route.startswith('/en/'))
        result = {'route': route, 'device': device, 'status': status,
                  'cache': cache, 'desktop_header': desktop_header,
                  'test_harness_comment': False}
        results.append(result)
        print(result, flush=True)
Path('docs/kit-candidate-final-public.json').write_text(json.dumps(results, indent=2) + '\n', encoding='utf-8')
print('PASS: anonymous final Greek/English desktop/phone, observation harness absent')
