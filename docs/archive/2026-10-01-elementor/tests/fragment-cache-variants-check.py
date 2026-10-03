"""Read-only staging comparison of page, language and device variants."""

from concurrent.futures import ThreadPoolExecutor
from hashlib import sha256
from html.parser import HTMLParser
from pathlib import Path
import runpy
from urllib.request import Request, urlopen
from uuid import uuid4

probe = runpy.run_path(str(Path(__file__).with_name("fragment-cache-staging-check.py")))
TargetParser, offset, DESKTOP = probe["TargetParser"], probe["offset"], probe["UA"]
PHONE = "Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1"
BASE = "https://wordpress-218158-6702910.cloudwaysapps.com"


class MegaMenus(HTMLParser):
    def __init__(self):
        super().__init__()
        self.ids = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if values.get("data-widget_type") == "mega-menu.default":
            self.ids.append(values.get("data-id"))


def inspect(spec):
    label, path, ua = spec
    url = BASE + path + ("&" if "?" in path else "?") + "iu_variant=" + uuid4().hex
    with urlopen(Request(url, headers={"User-Agent": ua}), timeout=180) as response:
        html = response.read().decode("utf-8", "replace")
        x_cache = response.headers.get("X-Cache")
    menus = MegaMenus()
    menus.feed(html)
    target = TargetParser()
    target.feed(html)
    fragment = None
    if target.start and target.end:
        start, end = offset(html, target.start), offset(html, target.end)
        fragment = html[start : html.index(">", end) + 1]
    return {
        "label": label,
        "x_cache": x_cache,
        "mega_menu_ids": menus.ids,
        "target_present": fragment is not None,
        "target_bytes": len(fragment.encode()) if fragment else 0,
        "target_sha256": sha256(fragment.encode()).hexdigest() if fragment else None,
        "target_loop_1635": fragment.count('data-elementor-id="1635"') if fragment else 0,
    }


if __name__ == "__main__":
    specs = [
        ("GR home desktop", "/", DESKTOP),
        ("GR company desktop", "/etairia/", DESKTOP),
        ("GR home phone", "/", PHONE),
        ("EN home desktop", "/en/", DESKTOP),
        ("EN home phone", "/en/", PHONE),
    ]
    with ThreadPoolExecutor(max_workers=2) as pool:
        for result in pool.map(inspect, specs):
            print(result)
