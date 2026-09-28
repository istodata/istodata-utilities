// Local licensed vendor fixtures only; no download, no production runtime dependency.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const manifest = require('./fixtures/atomic-interactions.json');
const directory = process.argv[2];
if (!directory) throw new Error('Usage: node tests/prepare-atomic-fixtures.cjs <directory containing the reference vendor files>');
const files = manifest.files.map(file => {
  const data = fs.readFileSync(path.join(directory, file.sourceName));
  if (crypto.createHash('sha256').update(data).digest('hex') !== file.sha256) {
    throw new Error('Unverified vendor fixture: ' + file.sourceName);
  }
  return { file, data };
});
const target = path.join(__dirname, 'fixtures', 'vendor');
fs.mkdirSync(target, { recursive: true });
files.forEach(({ file, data }) => fs.writeFileSync(path.join(target, file.name), data));
console.log('Prepared checksum-verified Elementor 4.3.2 / Pro 4.3.0 local fixtures.');
