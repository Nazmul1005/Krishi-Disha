/** Preserve downloaded originals and create credited WebP derivatives locally. */
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');

process.chdir(path.join(__dirname, '..'));
const catalogPath = 'assets/images/reference/catalog.json';
const photos = JSON.parse(fs.readFileSync(catalogPath, 'utf8'));
const checkOnly = process.argv.includes('--check');
const hash = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
let originalBytes = 0;
let optimizedBytes = 0;
for (const photo of photos) {
  if (!['crop', 'disease'].includes(photo.type) || !/^[a-z_]+$/.test(photo.slug) ||
      !new RegExp(`^assets/images/reference/${photo.type}/${photo.slug}\\.(jpg|png)$`).test(photo.image)) {
    throw new Error(`Unsafe image path for ${photo.slug}`);
  }
  if (hash(photo.image) !== photo.sha256) throw new Error(`Original checksum mismatch: ${photo.image}`);
  const target = `assets/images/reference/${photo.type}/${photo.slug}.webp`;
  if (!checkOnly) {
    const encode = resize => {
      const result = spawnSync('cwebp', ['-q', '80', '-m', '6', '-metadata', 'none', ...resize, photo.image, '-o', target], { encoding: 'utf8' });
      if (result.error) throw result.error;
      if (result.status !== 0) throw new Error(result.stderr);
      const dimensions = /Dimension:\s+(\d+) x (\d+)/.exec(result.stderr);
      if (!dimensions) throw new Error(`Missing dimensions: ${photo.image}`);
      return dimensions.slice(1).map(Number);
    };
    let [width, height] = encode([]);
    if (Math.max(width, height) > 960) {
      const scale = 960 / Math.max(width, height);
      [width, height] = encode(['-resize', String(Math.round(width * scale)), String(Math.round(height * scale))]);
    }
    photo.optimized_image = target;
    photo.optimized_sha256 = hash(target);
    photo.optimized_bytes = fs.statSync(target).size;
    photo.optimized_width = width;
    photo.optimized_height = height;
  }
  if (photo.optimized_image !== target || hash(target) !== photo.optimized_sha256) {
    throw new Error(`Derivative checksum mismatch: ${target}`);
  }
  const bytes = fs.readFileSync(target);
  if (bytes.toString('ascii', 0, 4) !== 'RIFF' || bytes.toString('ascii', 8, 12) !== 'WEBP' || bytes.length !== photo.optimized_bytes) {
    throw new Error(`Invalid WebP derivative: ${target}`);
  }
  for (const field of ['name', 'caption', 'credit', 'license', 'license_url', 'source']) {
    if (typeof photo[field] !== 'string' || !photo[field].trim()) throw new Error(`Missing ${field}: ${photo.slug}`);
  }
  originalBytes += fs.statSync(photo.image).size;
  optimizedBytes += bytes.length;
}
if (!checkOnly) fs.writeFileSync(catalogPath, JSON.stringify(photos, null, 2) + '\n');
console.log(`${checkOnly ? 'Verified' : 'Generated'} ${photos.length} credited WebP derivatives; originals unchanged. ${(originalBytes / 1024 / 1024).toFixed(2)} MiB → ${(optimizedBytes / 1024 / 1024).toFixed(2)} MiB (${Math.round((1 - optimizedBytes / originalBytes) * 100)}% smaller).`);
