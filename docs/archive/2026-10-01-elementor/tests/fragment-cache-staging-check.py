"""Check two anonymous desktop responses from the staging-only PHP probe."""

from hashlib import sha256
from html.parser import HTMLParser
import re
from uuid import uuid4
from urllib.request import Request, urlopen


URL = (
    "https://wordpress-218158-6702910.cloudwaysapps.com/"
    "?iu_fragment_probe=26fa46c6-20260930-a7d4e2"
)
UA = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36"
)
VOID_TAGS = {
    "area", "base", "br", "col", "embed", "hr", "img", "input", "link",
    "meta", "param", "source", "track", "wbr",
}
MARKER = re.compile(
    r"IU_FRAGMENT_PROBE state=(\S+) target_render=(\d+) "
    r"nested_elements=(\d+) template_1635=(\d+)"
)


class TargetParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=False)
        self.depth = 0
        self.start = None
        self.end = None

    def handle_starttag(self, tag, attrs):
        if self.end is not None:
            return
        values = dict(attrs)
        if self.start is None:
            if values.get("data-id") != "26fa46c6":
                return
            self.start = self.getpos()
            self.depth = 1
        elif tag not in VOID_TAGS:
            self.depth += 1

    def handle_startendtag(self, tag, attrs):
        if self.start is None and dict(attrs).get("data-id") == "26fa46c6":
            raise AssertionError("Target is unexpectedly self-closing")

    def handle_endtag(self, tag):
        if self.start is None or self.end is not None:
            return
        self.depth -= 1
        if self.depth == 0:
            self.end = self.getpos()


def offset(html, position):
    line, column = position
    lines = html.splitlines(keepends=True)
    return sum(map(len, lines[: line - 1])) + column


def fetch(label, url):
    request = Request(url, headers={"User-Agent": UA, "Cache-Control": "no-cache"})
    with urlopen(request, timeout=180) as response:
        html = response.read().decode("utf-8", "replace")
        cache = response.headers.get("X-Cache", "unknown")
        status = response.status
    assert status == 200, (label, status)
    marker = MARKER.search(html)
    assert marker, f"{label}: probe did not run"
    parser = TargetParser()
    parser.feed(html)
    assert parser.start and parser.end, f"{label}: target HTML missing"
    start = offset(html, parser.start)
    end = offset(html, parser.end)
    end = html.index(">", end) + 1
    fragment = html[start:end]
    result = {
        "state": marker.group(1),
        "target_render": int(marker.group(2)),
        "nested_elements": int(marker.group(3)),
        "template_1635": int(marker.group(4)),
        "fragment_sha256": sha256(fragment.encode()).hexdigest(),
        "fragment_bytes": len(fragment.encode()),
        "template_1635_in_fragment": fragment.count('data-elementor-id="1635"'),
        "x_cache": cache,
    }
    print(label, result)
    return result


if __name__ == "__main__":
    first = fetch("first", URL)
    second = fetch("second", URL + "&iu_probe_request=" + uuid4().hex)
    assert first["state"] == "miss", "Expected a new fragment on the first request"
    assert second["state"] == "hit", "Expected a cache hit on the second request"
    assert first["target_render"] == 1 and first["nested_elements"] > 0
    assert second["target_render"] == 0 and second["nested_elements"] == 0
    assert second["x_cache"] == "MISS", "Second request must reach WordPress"
    assert first["template_1635_in_fragment"] == second["template_1635_in_fragment"] == 150
    assert first["fragment_sha256"] == second["fragment_sha256"]
    print("PASS: early bypass and byte-identical target HTML")
