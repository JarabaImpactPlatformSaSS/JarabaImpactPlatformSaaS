/**
 * @file
 * PERF-03: JS minification script using Terser.
 *
 * Minifies all JS files in js/ directory to js/dist/ with source maps.
 * Run: npm run build:js
 */

const { minify } = require('terser');
const fs = require('fs');
const path = require('path');

const JS_DIR = path.resolve(__dirname, '../js');
const DIST_DIR = path.resolve(__dirname, '../js/dist');

async function minifyAll() {
  console.log('PERF-03: JS Minification');
  console.log('========================\n');

  if (!fs.existsSync(DIST_DIR)) {
    fs.mkdirSync(DIST_DIR, { recursive: true });
  }

  const jsFiles = fs.readdirSync(JS_DIR).filter(f => f.endsWith('.js'));
  let success = 0;
  let totalSaved = 0;

  for (const file of jsFiles) {
    const srcPath = path.join(JS_DIR, file);
    const stat = fs.statSync(srcPath);

    if (!stat.isFile()) continue;

    const code = fs.readFileSync(srcPath, 'utf8');
    const minName = file.replace('.js', '.min.js');

    try {
      const result = await minify(code, {
        sourceMap: {
          filename: minName,
          url: `${minName}.map`,
        },
        compress: {
          drop_console: false,
          passes: 2,
        },
        format: {
          comments: /^!\s|@license|@preserve/,
        },
      });

      fs.writeFileSync(path.join(DIST_DIR, minName), result.code);
      if (result.map) {
        fs.writeFileSync(path.join(DIST_DIR, `${minName}.map`), result.map);
      }

      const originalKb = (code.length / 1024).toFixed(1);
      const minifiedKb = (result.code.length / 1024).toFixed(1);
      const saved = ((1 - result.code.length / code.length) * 100).toFixed(0);
      totalSaved += code.length - result.code.length;

      console.log(`  ${file} -> ${minName}  (${originalKb}KB -> ${minifiedKb}KB, -${saved}%)`);
      success++;
    } catch (err) {
      console.error(`  ERROR: ${file} - ${err.message}`);
    }
  }

  const totalSavedKb = (totalSaved / 1024).toFixed(1);
  console.log(`\n${success}/${jsFiles.length} files minified. Total saved: ${totalSavedKb}KB`);
  console.log(`Output: ${DIST_DIR}/`);
}

minifyAll().catch(err => {
  console.error('Fatal:', err.message);
  process.exit(1);
});
