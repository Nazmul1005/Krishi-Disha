const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
process.chdir(path.join(__dirname, '..'));
if (process.argv.includes('--check')) {
  const manifest = JSON.parse(fs.readFileSync('assets/vendor/manifest.json', 'utf8'));
  for (const [file, expected] of Object.entries(manifest)) {
    const actual = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
    if (actual !== expected) throw new Error(`Asset checksum mismatch: ${file}`);
    if (file.endsWith('.woff2') && fs.readFileSync(file).toString('ascii', 0, 4) !== 'wOF2') throw new Error(`Invalid WOFF2: ${file}`);
  }
  console.log(`Verified ${Object.keys(manifest).length} local vendor/font assets.`);
  process.exit(0);
}
const files = {
  'node_modules/bootstrap/dist/css/bootstrap.min.css': 'assets/vendor/bootstrap/bootstrap.min.css',
  'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js': 'assets/vendor/bootstrap/bootstrap.bundle.min.js',
  'node_modules/bootstrap/LICENSE': 'assets/vendor/bootstrap/LICENSE',
  'node_modules/@fortawesome/fontawesome-free/css/all.min.css': 'assets/vendor/fontawesome/css/all.min.css',
  'node_modules/@fortawesome/fontawesome-free/LICENSE.txt': 'assets/vendor/fontawesome/LICENSE.txt'
};
for (const font of fs.readdirSync('node_modules/@fortawesome/fontawesome-free/webfonts').filter(file => file.endsWith('.woff2'))) files['node_modules/@fortawesome/fontawesome-free/webfonts/' + font] = 'assets/vendor/fontawesome/webfonts/' + font;
const manifest = {};
for (const [source, target] of Object.entries(files)) {
  fs.mkdirSync(path.dirname(target), { recursive: true }); fs.copyFileSync(source, target);
  manifest[target] = crypto.createHash('sha256').update(fs.readFileSync(target)).digest('hex');
}
for (const file of fs.readdirSync('assets/fonts').sort()) {
  const target = path.join('assets/fonts', file);
  if (fs.statSync(target).isFile()) manifest[target] = crypto.createHash('sha256').update(fs.readFileSync(target)).digest('hex');
}
fs.writeFileSync('assets/vendor/manifest.json', JSON.stringify(manifest, null, 2) + '\n');
