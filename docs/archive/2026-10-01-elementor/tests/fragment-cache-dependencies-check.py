"""Read-only inventory of template and post references inside the target."""

from collections import Counter
from html.parser import HTMLParser
from pathlib import Path
import re
import runpy
from urllib.request import Request, urlopen
from uuid import uuid4

probe = runpy.run_path(str(Path(__file__).with_name("fragment-cache-staging-check.py")))
TargetParser, offset, UA = probe["TargetParser"], probe["offset"], probe["UA"]
URL = "https://wordpress-218158-6702910.cloudwaysapps.com/?iu_dependency_read=" + uuid4().hex


class References(HTMLParser):
    def __init__(self):
        super().__init__()
        self.elementor_documents = Counter()
        self.post_ids = Counter()
        self.widget_types = Counter()
        self.hrefs = Counter()

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if values.get("data-elementor-id"):
            self.elementor_documents[values["data-elementor-id"]] += 1
        if values.get("data-post-id"):
            self.post_ids[values["data-post-id"]] += 1
        if values.get("data-widget_type"):
            self.widget_types[values["data-widget_type"]] += 1
        if tag == "a" and values.get("href"):
            self.hrefs[values["href"]] += 1


with urlopen(Request(URL, headers={"User-Agent": UA}), timeout=180) as response:
    html = response.read().decode("utf-8", "replace")
    print("page cache:", response.headers.get("X-Cache"))
target = TargetParser()
target.feed(html)
assert target.start and target.end
start, end = offset(html, target.start), offset(html, target.end)
fragment = html[start : html.index(">", end) + 1]
refs = References()
refs.feed(fragment)
print("Elementor document IDs:", refs.elementor_documents.most_common())
print("post IDs:", refs.post_ids.most_common(20))
print("widget types:", refs.widget_types.most_common(20))
print("post class IDs:", Counter(re.findall(r'\bpost-([0-9]+)\b', fragment)).most_common(20))
print("unique links:", len(refs.hrefs))
print("sample internal links:", [url for url in refs.hrefs if 'wordpress-218158-6702910.cloudwaysapps.com/' in url][:20])
