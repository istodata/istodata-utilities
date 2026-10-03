"""Read-only comparison of the target's anonymous desktop HTML across pages."""

from concurrent.futures import ThreadPoolExecutor
from hashlib import sha256
from pathlib import Path
import runpy
from urllib.request import Request, urlopen
from uuid import uuid4

probe = runpy.run_path(str(Path(__file__).with_name("fragment-cache-staging-check.py")))
TargetParser, offset, UA = probe["TargetParser"], probe["offset"], probe["UA"]


BASE = "https://wordpress-218158-6702910.cloudwaysapps.com"


def inspect(path):
    url = BASE + path + ("&" if "?" in path else "?") + "iu_context=" + uuid4().hex
    with urlopen(Request(url, headers={"User-Agent": UA}), timeout=180) as response:
        html = response.read().decode("utf-8", "replace")
        x_cache = response.headers.get("X-Cache")
    parser = TargetParser()
    parser.feed(html)
    assert parser.start and parser.end, path
    start, end = offset(html, parser.start), offset(html, parser.end)
    fragment = html[start : html.index(">", end) + 1]
    return {
        "path": path,
        "x_cache": x_cache,
        "bytes": len(fragment.encode()),
        "sha256": sha256(fragment.encode()).hexdigest(),
        "loop_1635": fragment.count('data-elementor-id="1635"'),
    }


if __name__ == "__main__":
    with ThreadPoolExecutor(max_workers=2) as pool:
        results = list(pool.map(inspect, ("/", "/etairia/")))
    for result in results:
        print(result)
    print("same fragment:", results[0]["sha256"] == results[1]["sha256"])
