"""Compare staging probe misses and hits, including frontend asset tags."""

from hashlib import sha256
from html.parser import HTMLParser
from pathlib import Path
import runpy
from urllib.request import Request, urlopen
from uuid import uuid4

probe = runpy.run_path(str(Path(__file__).with_name("fragment-cache-staging-check.py")))
URL, UA, MARKER = probe["URL"], probe["UA"], probe["MARKER"]
TargetParser, offset = probe["TargetParser"], probe["offset"]


class AssetTags(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=False)
        self.external = set()
        self.inline = []
        self.collecting = None
        self.buffer = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == "script":
            if values.get("src"):
                self.external.add(("script", values["src"]))
            else:
                self.collecting = ("script", values.get("id", ""), values.get("type", ""))
                self.buffer = []
        if tag == "style":
            self.collecting = ("style", values.get("id", ""), "")
            self.buffer = []
        if tag == "link" and values.get("rel") == "stylesheet" and values.get("href"):
            self.external.add(("style", values["href"]))

    def handle_data(self, data):
        if self.collecting:
            self.buffer.append(data)

    def handle_endtag(self, tag):
        if self.collecting and tag == self.collecting[0]:
            content = "".join(self.buffer)
            self.inline.append((*self.collecting, sha256(content.encode()).hexdigest(), len(content)))
            self.collecting = None
            self.buffer = []


def fetch(label):
    url = URL + "&iu_probe_request=" + uuid4().hex
    with urlopen(Request(url, headers={"User-Agent": UA}), timeout=180) as response:
        html = response.read().decode("utf-8", "replace")
        x_cache = response.headers.get("X-Cache")
    marker = MARKER.search(html)
    assert marker, f"{label}: probe marker absent"
    assets = AssetTags()
    assets.feed(html)
    target = TargetParser()
    target.feed(html)
    assert target.start and target.end, f"{label}: target fragment absent"
    start, end = offset(html, target.start), offset(html, target.end)
    fragment = html[start : html.index(">", end) + 1]
    target_hash = sha256(fragment.encode()).hexdigest()
    print(label, "url=", url, "x_cache=", x_cache, "marker=", marker.group(0), "target_sha256=", target_hash)
    return assets, x_cache, marker.group(1), target_hash


if __name__ == "__main__":
    miss, miss_cache, miss_state, miss_hash = fetch("miss")
    hit, hit_cache, hit_state, hit_hash = fetch("hit")
    assert (miss_state, hit_state) == ("miss", "hit")
    assert miss_cache == hit_cache == "MISS", "Both responses must reach WordPress"
    print("external only on miss:", sorted(miss.external - hit.external))
    print("external only on hit:", sorted(hit.external - miss.external))
    print("external assets equal:", miss.external == hit.external)
    print("target fragment equal:", miss_hash == hit_hash)
    print("outside-target inline styles require separate attribution:", miss.inline != hit.inline)
    assert miss.external == hit.external, "External asset parity failed"
    assert miss_hash == hit_hash, "Target fragment changed"
