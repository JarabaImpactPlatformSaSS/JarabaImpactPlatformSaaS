#!/usr/bin/env node
/**
 * @file check-scss-orphans.js
 *
 * Detects SCSS partial files in scss/components/ that are NOT imported
 * in main.scss (or any bundle/route entry point).
 *
 * Runs as part of `npm run lint:scss` to prevent regressions where a
 * component SCSS file exists but is never compiled into CSS.
 *
 * Exit code 0 = all OK, exit code 1 = orphans found.
 *
 * @see docs/tecnicos/aprendizajes/2026-02-28_error_pages_scss_orphan.md
 */

const fs = require('fs');
const path = require('path');

const THEME_DIR = path.resolve(__dirname, '..');
const SCSS_DIR = path.join(THEME_DIR, 'scss');
const COMPONENTS_DIR = path.join(SCSS_DIR, 'components');

// Entry points that may import component partials.
const ENTRY_POINTS = [
    path.join(SCSS_DIR, 'main.scss'),
    ...glob(path.join(SCSS_DIR, 'bundles', '*.scss')),
    ...glob(path.join(SCSS_DIR, 'routes', '*.scss')),
];

// Known intentional exclusions — files with pre-existing compilation issues
// or loaded via Drupal libraries.yml instead of main.scss.
// TODO: Fix these files and remove from allowlist.
const ALLOWLIST = [
    '_autofirma.scss',              // Uses color.adjust() with CSS vars — needs refactor
    '_commerce-product-geo.scss',   // Pending review
];

function glob(pattern) {
    const dir = path.dirname(pattern);
    if (!fs.existsSync(dir)) return [];
    return fs.readdirSync(dir)
        .filter(f => f.endsWith('.scss'))
        .map(f => path.join(dir, f));
}

function getImportedNames(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const imports = new Set();
    // Match: @use 'components/foo'  or  @use '../components/foo' as bar
    // Match: @import 'components/foo'  or  @import '../components/foo'
    const regex = /@(?:use|import)\s+['"](?:\.\.\/|\.\/)?components\/([^'"]+)['"]/g;
    let match;
    while ((match = regex.exec(content)) !== null) {
        imports.add(`_${match[1]}.scss`);
    }
    return imports;
}

function main() {
    if (!fs.existsSync(COMPONENTS_DIR)) {
        console.log('✅ No components directory found, nothing to check.');
        process.exit(0);
    }
    const partials = fs.readdirSync(COMPONENTS_DIR)
        .filter(f => f.startsWith('_') && f.endsWith('.scss'));

    const allImported = new Set();
    for (const entry of ENTRY_POINTS) {
        if (!fs.existsSync(entry)) continue;
        for (const name of getImportedNames(entry)) {
            allImported.add(name);
        }
    }

    const orphans = partials.filter(p =>
        !allImported.has(p) && !ALLOWLIST.includes(p)
    );

    if (orphans.length === 0) {
        const allowed = partials.filter(p => ALLOWLIST.includes(p));
        console.log(`✅ All ${partials.length - allowed.length} component partials are imported (${allowed.length} allowlisted).`);
        process.exit(0);
    }

    console.error(`\n❌ SCSS ORPHAN${orphans.length > 1 ? 'S' : ''} DETECTED!`);
    console.error('The following component partials exist but are NOT imported in any entry point:\n');
    for (const orphan of orphans) {
        console.error(`  • scss/components/${orphan}`);
    }
    console.error('\nFix: Add a @use directive in main.scss (or the appropriate bundle).');
    console.error('If intentionally excluded, add to ALLOWLIST in scripts/check-scss-orphans.js.\n');
    process.exit(1);
}

main();
