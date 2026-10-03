"""Compare the page after two staging probe responses, supplied by request ID."""

from collections import Counter
from html.parser import HTMLParser
from pathlib import Path
import re
import runpy
import sys
from urllib.request import Request, urlopen

probe = runpy.run_path(str(Path(__file__).with_name("fragment-cache-staging-check.py")))
TargetParser, offset, UA = probe["TargetParser"], probe["offset"], probe["UA"]
BASE = "https://wordpress-218158-6702910.cloudwaysapps.com/"
TOKEN = "26fa46c6-20260930-a7d4e2"


class PostIdsOutsideRandomCarousels(HTMLParser):
    """Count rendered post classes, excluding independently random widgets."""

    def __init__(self):
        super().__init__()
        self.div_stack = []
        self.ids = Counter()
        self.random_ids = Counter()

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        inside_random = bool(self.div_stack and self.div_stack[-1])
        if tag == "div":
            inside_random |= attrs.get("data-id") in {"6f5dec7a", "8ece9dc"}
            self.div_stack.append(inside_random)
        ids = re.findall(r"\bpost-(\d+)\b", attrs.get("class") or "")
        (self.random_ids if inside_random else self.ids).update(ids)

    def handle_endtag(self, tag):
        if tag == "div" and self.div_stack:
            self.div_stack.pop()


def inspect(request_id):
    url = BASE + "?iu_fragment_probe=" + TOKEN + "&iu_probe_request=" + request_id
    with urlopen(Request(url, headers={"User-Agent": UA}), timeout=180) as response:
        html = response.read().decode("utf-8", "replace")
        page_cache = response.headers.get("X-Cache")
    target = TargetParser()
    target.feed(html)
    if not target.start or not target.end:
        raise RuntimeError("target missing: " + request_id)
    _, end = offset(html, target.start), offset(html, target.end)
    after = html[html.index(">", end) + 1 :]
    post_ids = PostIdsOutsideRandomCarousels()
    post_ids.feed(after)
    marker = re.search(r"<!-- IU_FRAGMENT_PROBE ([^>]*)-->", html)
    return {
        "page_cache": page_cache,
        "origin_probe": marker.group(1) if marker else None,
        "post_ids_after": Counter(re.findall(r"\bpost-(\d+)\b", after)),
        "post_ids_after_excluding_rand": post_ids.ids,
        "random_carousel_post_ids": post_ids.random_ids,
        "templates_after": Counter(re.findall(r'data-elementor-id="(\d+)"', after)),
    }


if __name__ == "__main__":
    if len(sys.argv) != 3:
        raise SystemExit("usage: script.py MISS_REQUEST_ID HIT_REQUEST_ID")
    miss, hit = (inspect(request_id) for request_id in sys.argv[1:])
    for label, item in (("miss", miss), ("hit", hit)):
        print(label, "page cache:", item["page_cache"], "origin probe:", item["origin_probe"])
    for field in (
        "post_ids_after",
        "post_ids_after_excluding_rand",
        "random_carousel_post_ids",
        "templates_after",
    ):
        print(field, "equal:", miss[field] == hit[field])
        print("miss only:", miss[field] - hit[field])
        print("hit only:", hit[field] - miss[field])
    if miss["post_ids_after_excluding_rand"] != hit["post_ids_after_excluding_rand"]:
        raise SystemExit("deterministic downstream post IDs differ")
    if miss["templates_after"] != hit["templates_after"]:
        raise SystemExit("downstream Elementor template IDs differ")
