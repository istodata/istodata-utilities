"""Compare official distribution archives without relying on a changelog."""
import difflib, hashlib, json, sys, zipfile
from pathlib import Path

folder = Path(sys.argv[1])
with zipfile.ZipFile(folder/'core433.zip') as old, zipfile.ZipFile(folder/'core434.zip') as new:
    changed = []
    diffs = []
    for name in new.namelist():
        if name.endswith('/'):
            continue
        data = new.read(name)
        previous = old.read(name) if name in old.namelist() else b''
        if data == previous:
            continue
        changed.append(name)
        if name.endswith('.php'):
            diffs.extend(difflib.unified_diff(previous.decode().splitlines(True), data.decode().splitlines(True), '4.3.3/'+name, '4.3.4/'+name))
    result = {'archives': {n: hashlib.sha256((folder/n).read_bytes()).hexdigest() for n in ['core433.zip','core434.zip']}, 'changed': changed,
              'removed': sorted(set(old.namelist())-set(new.namelist()))}
    (folder/'source-manifest.json').write_text(json.dumps(result, indent=2))
    (folder/'php-source.diff').write_text(''.join(diffs))
    print(json.dumps(result, indent=2))
