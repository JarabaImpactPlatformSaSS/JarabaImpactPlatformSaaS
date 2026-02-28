#!/usr/bin/env node
/**
 * @file check-frontend-coherence.js
 *
 * Validates frontend-backend coherence:
 * 1. Twig partials referenced via {% include %} have matching files on disk
 * 2. Twig conditional variables ({% if variable %}) are populated in .theme
 * 3. libraries.yml entries reference files that exist on disk
 *
 * Prevents "code exists but user doesn't see it" scenarios.
 *
 * @see docs/tecnicos/aprendizajes/2026-02-28_error_pages_scss_orphan.md
 */

const fs = require('fs');
const path = require('path');

const THEME_DIR = path.resolve(__dirname, '..');
const TEMPLATES_DIR = path.join(THEME_DIR, 'templates');
const THEME_FILE = path.join(THEME_DIR, 'ecosistema_jaraba_theme.theme');
const LIBRARIES_FILE = path.join(THEME_DIR, 'ecosistema_jaraba_theme.libraries.yml');

let warnings = [];
let errors = [];

// ─── CHECK 1: Twig partials with conditional variables ───
function checkTwigConditionals() {
    const twigFiles = findFiles(TEMPLATES_DIR, '.html.twig');
    const themeContent = fs.existsSync(THEME_FILE) ? fs.readFileSync(THEME_FILE, 'utf8') : '';

    // Variables that MUST be set in preprocess for features to render.
    // Add new critical variables here as features are developed.
    const criticalVars = [
        { variable: 'available_languages', description: 'Language switcher visibility' },
        { variable: 'proactive_insights_count', description: 'Proactive insights bell' },
        { variable: 'avatar_nav', description: 'Avatar navigation bar' },
        { variable: 'detected_vertical', description: 'Vertical selector detection' },
    ];

    for (const { variable, description } of criticalVars) {
        const usedInTwig = twigFiles.some(f => {
            const content = fs.readFileSync(f, 'utf8');
            return content.includes(`{% if ${variable}`) || content.includes(`${variable}|default`);
        });

        if (usedInTwig) {
            const setInTheme = themeContent.includes(`$variables['${variable}']`);
            if (!setInTheme) {
                errors.push(`Variable '${variable}' (${description}) used in Twig but NEVER set in .theme preprocess`);
            }
        }
    }
}

// ─── CHECK 2: libraries.yml file references exist ───
function checkLibrariesYml() {
    if (!fs.existsSync(LIBRARIES_FILE)) return;
    const content = fs.readFileSync(LIBRARIES_FILE, 'utf8');
    // Match lines like "  js/something.js: {}" or "  css/something.css: {}"
    const fileRefRegex = /^\s+([\w\-\/\.]+\.(?:js|css|min\.js|min\.css))\s*:/gm;
    let match;
    while ((match = fileRefRegex.exec(content)) !== null) {
        const refPath = match[1].trim();
        const fullPath = path.join(THEME_DIR, refPath);
        if (!fs.existsSync(fullPath)) {
            errors.push(`libraries.yml references '${refPath}' but file does not exist`);
        }
    }
}

// ─── CHECK 3: Twig includes reference existing partials ───
function checkTwigIncludes() {
    const twigFiles = findFiles(TEMPLATES_DIR, '.html.twig');
    // Only match STATIC includes (full string literal path, no Twig concatenation)
    const includeRegex = /\{%\s*include\s+'@ecosistema_jaraba_theme\/([^'{}~]+)'\s/g;

    for (const file of twigFiles) {
        const content = fs.readFileSync(file, 'utf8');
        let match;
        while ((match = includeRegex.exec(content)) !== null) {
            const includedPath = match[1];
            const fullPath = path.join(TEMPLATES_DIR, includedPath);
            if (!fs.existsSync(fullPath)) {
                const relFile = path.relative(THEME_DIR, file);
                warnings.push(`${relFile} includes '${includedPath}' but template does not exist`);
            }
        }
    }
}

// ─── Helpers ───
function findFiles(dir, ext) {
    let results = [];
    if (!fs.existsSync(dir)) return results;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            results = results.concat(findFiles(fullPath, ext));
        } else if (entry.name.endsWith(ext)) {
            results.push(fullPath);
        }
    }
    return results;
}

// ─── Main ───
function main() {
    console.log('Frontend Coherence Audit');
    console.log('========================\n');

    checkTwigConditionals();
    checkLibrariesYml();
    checkTwigIncludes();

    if (warnings.length > 0) {
        console.log(`⚠️  ${warnings.length} Warning(s):`);
        for (const w of warnings) console.log(`  ⚠️  ${w}`);
        console.log();
    }

    if (errors.length > 0) {
        console.error(`❌ ${errors.length} Error(s):\n`);
        for (const e of errors) console.error(`  ❌ ${e}`);
        console.error();
        process.exit(1);
    }

    console.log('✅ All coherence checks passed.');
    process.exit(0);
}

main();
