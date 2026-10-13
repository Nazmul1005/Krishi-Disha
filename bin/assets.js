const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
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
fs.writeFileSync('assets/vendor/manifest.json', JSON.stringify(manifest, null, 2) + '\n');
