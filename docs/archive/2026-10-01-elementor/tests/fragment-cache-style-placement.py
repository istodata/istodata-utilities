"""Locate loop style tags relative to the target widget in a staging response."""

from html.parser import HTMLParser
from pathlib import Path
import runpy
from urllib.request import Request, urlopen
from uuid import uuid4

probe = runpy.run_path(str(Path(__file__).with_name("fragment-cache-staging-check.py")))
TargetParser, offset, UA = probe["TargetParser"], probe["offset"], probe["UA"]
URL = "https://wordpress-218158-6702910.cloudwaysapps.com/?iu_style_read=" + uuid4().hex


class Styles(HTMLParser):
    def __init__(self):
        super().__init__()
        self.items = []

    def handle_starttag(self, tag, attrs):
        if tag == "style":
            style_id = dict(attrs).get("id", "")
            if style_id.startswith("loop-"):
                self.items.append((style_id, self.getpos()))


with urlopen(Request(URL, headers={"User-Agent": UA}), timeout=180) as response:
    html = response.read().decode("utf-8", "replace")
    print("page cache:", response.headers.get("X-Cache"))
target = TargetParser()
target.feed(html)
start, end = offset(html, target.start), offset(html, target.end)
styles = Styles()
styles.feed(html)
print("target span:", start, end)
for style_id, position in styles.items:
    index = offset(html, position)
    print(style_id, index, "inside" if start < index < end else "outside")
