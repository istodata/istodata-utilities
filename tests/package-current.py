"""Build and inspect the current local manual-test ZIP from a clean copy."""
from pathlib import Path
import hashlib, json, re, shutil, tempfile, zipfile

root = Path(__file__).resolve().parents[1]
output = root.parent / 'istodata-utilities.zip'
stage_base = Path(tempfile.mkdtemp(prefix='iu-kit-test-package-'))
stage = stage_base / 'istodata-utilities'
stage.mkdir()
excluded = {'tests', 'docs', '.git', '.github', '.claude', '.agents', '.codex', '__pycache__', 'node_modules'}
sources = [root / name for name in ('istodata-utilities.php', 'wpml-config.xml', 'CHANGELOG.md')]
for folder in ('assets', 'includes', 'templates'):
    for source in (root / folder).rglob('*'):
        if not source.is_file():
            continue
        relative = source.relative_to(root)
        if any(part in excluded or part.startswith('.') for part in relative.parts):
            continue
        if source.suffix.lower() in {'.zip', '.tmp', '.bak', '.log', '.pyc', '.patch', '.ps1', '.py'}:
            continue
        if 'Zone.Identifier' in source.name or source.name in {'AGENTS.md', 'Thumbs.db', 'desktop.ini'}:
            continue
        sources.append(source)
for source in sources:
    destination = stage / source.relative_to(root)
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(source, destination)
main = (stage / 'istodata-utilities.php').read_text(encoding='utf-8-sig')
version = re.search(r'^Version:\s*(\S+)', main, re.M).group(1)
assert re.search(r"define\('IU_PLUGIN_VERSION', '" + re.escape(version) + r"'\)", main)
assert (stage / 'includes/elementor-fragment-cache-atomic.php').is_file()
with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for source in sorted(stage.rglob('*')):
        if source.is_file():
            archive.write(source, source.relative_to(stage_base).as_posix())
with zipfile.ZipFile(output) as archive:
    entries = archive.namelist()
    assert archive.testzip() is None
    assert {name.split('/')[0] for name in entries} == {'istodata-utilities'}
    assert 'istodata-utilities/istodata-utilities.php' in entries
    assert 'istodata-utilities.php' not in entries
    assert 'istodata-utilities/istodata-utilities/istodata-utilities.php' not in entries
    for name in entries:
        assert '\\' not in name
        assert not any(part in excluded or part in {'AGENTS.md', '.gitignore', '.gitattributes'} for part in Path(name).parts)
        assert not name.lower().endswith('.zip') and 'Zone.Identifier' not in name
        relative = Path(name).relative_to('istodata-utilities')
        assert archive.read(name) == (root / relative).read_bytes(), name
    assert len(entries) == len(sources)
report = dict(path=str(output), version=version, build='Current clean distribution ZIP',
              files=len(entries), bytes=output.stat().st_size,
              sha256=hashlib.sha256(output.read_bytes()).hexdigest(), single_plugin_folder=True,
              main_file_correct=True, forward_slashes=True, development_files_excluded=True,
              atomic_adapter_included=True, archive_matches_local_sources=True, crc_check='PASS')
(root / 'docs/kit-current-test-zip.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print(json.dumps(report, ensure_ascii=False, indent=2))
