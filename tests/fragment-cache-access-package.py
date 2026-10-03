"""Build a clean copy using the reviewed existing package's runtime file manifest."""
from pathlib import Path
import zipfile, tempfile, shutil, hashlib, json
root=Path(__file__).resolve().parents[1]
output=root.parent/'istodata-utilities.zip'
base=Path(tempfile.mkdtemp(prefix='iu-fragment-access-baseline-'))
with zipfile.ZipFile(output) as old:
    assert old.testzip() is None
    names=[n for n in old.namelist() if not n.endswith('/')]
    old.extractall(base)
stage=Path(tempfile.mkdtemp(prefix='iu-fragment-access-package-'))
for name in names:
    assert name.startswith('istodata-utilities/') and '\\' not in name and '..' not in Path(name).parts
    relative=name.split('/',1)[1]
    source=root/relative
    assert source.is_file(), relative
    dest=stage/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(source,dest)
with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED) as archive:
    for source in sorted(stage.rglob('*')):
        if source.is_file(): archive.write(source,source.relative_to(stage).as_posix())
with zipfile.ZipFile(output) as archive:
    entries=archive.namelist()
    assert archive.testzip() is None
    assert 'istodata-utilities/istodata-utilities.php' in entries
    assert 'istodata-utilities.php' not in entries
    assert 'istodata-utilities/istodata-utilities/istodata-utilities.php' not in entries
    assert all(n.startswith('istodata-utilities/') and '\\' not in n and not any(x in n for x in ['.git/','.github/','.claude/','Zone.Identifier','/tests/','/docs/']) and not n.endswith('.zip') for n in entries)
previous=root/'docs/fragment-cache-access-zip.json'
original=json.loads(previous.read_text(encoding='utf8')).get('original_baseline','C:/Users/pe/AppData/Local/Temp/iu-fragment-access-baseline-10fee8sh') if previous.exists() else str(base)
report={'path':str(output),'entries':len(entries),'sha256':hashlib.sha256(output.read_bytes()).hexdigest(),'baseline':str(base),'original_baseline':original,'staged_copy':str(stage),'all_checks':True}
(root/'docs/fragment-cache-access-zip.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf8')
print(json.dumps(report,indent=2))
