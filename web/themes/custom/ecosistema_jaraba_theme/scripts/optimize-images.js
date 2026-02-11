/**
 * @file
 * PERF-04: Image optimization script using Sharp.
 *
 * Optimizes PNG/JPEG images in images/ directory in-place and generates
 * WebP variants alongside originals for use with <picture> or
 * Drupal's responsive image module.
 *
 * Run: npm run build:images
 */

const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const IMAGES_DIR = path.resolve(__dirname, '../images');
const EXTENSIONS = ['.png', '.jpg', '.jpeg'];

async function optimizeImages() {
  console.log('PERF-04: Image Optimization');
  console.log('===========================\n');

  const files = getAllImages(IMAGES_DIR);
  let optimized = 0;
  let webpGenerated = 0;
  let totalSaved = 0;

  for (const filePath of files) {
    const ext = path.extname(filePath).toLowerCase();
    const originalSize = fs.statSync(filePath).size;

    try {
      const image = sharp(filePath);
      const metadata = await image.metadata();
      let buffer;

      if (ext === '.png') {
        buffer = await sharp(filePath)
          .png({ quality: 85, compressionLevel: 9, adaptiveFiltering: true })
          .toBuffer();
      } else {
        buffer = await sharp(filePath)
          .jpeg({ quality: 85, mozjpeg: true })
          .toBuffer();
      }

      // Only write if we actually saved bytes.
      if (buffer.length < originalSize) {
        fs.writeFileSync(filePath, buffer);
        const saved = originalSize - buffer.length;
        totalSaved += saved;

        const origKb = (originalSize / 1024).toFixed(1);
        const newKb = (buffer.length / 1024).toFixed(1);
        console.log(`  ${path.relative(IMAGES_DIR, filePath)}  ${origKb}KB -> ${newKb}KB`);
        optimized++;
      }

      // Generate WebP variant.
      const webpPath = filePath.replace(/\.(png|jpe?g)$/i, '.webp');
      if (!fs.existsSync(webpPath)) {
        await sharp(filePath)
          .webp({ quality: 80 })
          .toFile(webpPath);
        webpGenerated++;
      }
    } catch (err) {
      console.error(`  ERROR: ${path.relative(IMAGES_DIR, filePath)} - ${err.message}`);
    }
  }

  const totalSavedKb = (totalSaved / 1024).toFixed(1);
  console.log(`\n${optimized} images optimized (saved ${totalSavedKb}KB), ${webpGenerated} WebP files generated.`);
}

function getAllImages(dir) {
  const results = [];
  if (!fs.existsSync(dir)) return results;

  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      results.push(...getAllImages(fullPath));
    } else if (EXTENSIONS.includes(path.extname(entry.name).toLowerCase())) {
      results.push(fullPath);
    }
  }
  return results;
}

optimizeImages().catch(err => {
  console.error('Fatal:', err.message);
  process.exit(1);
});
