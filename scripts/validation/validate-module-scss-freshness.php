<?php

/**
 * @file
 * SCSS-MODULE-COMPILE-001: Verifica que CSS compilados en módulos custom
 * sean más recientes que los SCSS parciales que los generan.
 *
 * Cubre módulos con SCSS propio (ej: jaraba_andalucia_ei).
 * Usa timestamps de filesystem (fiable en entorno local).
 *
 * Uso: php scripts/validation/validate-module-scss-freshness.php
 * Exit: 0 = OK, 1 = CSS stale detectado
 */

$base = dirname(__DIR__, 2);

// Módulos con SCSS propio: {module_dir} => {css_file_relative_to_module}.
$modules = [
    'web/modules/custom/jaraba_andalucia_ei' => [
        'scss_dir' => 'scss',
        'css_file' => 'css/andalucia-ei.css',
    ],
];

echo "\n=== SCSS-MODULE-COMPILE-001: Module CSS freshness vs SCSS ===\n\n";

$errors = 0;
$checked = 0;

foreach ($modules as $moduleRel => $config) {
    $moduleDir = $base . '/' . $moduleRel;
    $scssDir = $moduleDir . '/' . $config['scss_dir'];
    $cssFile = $moduleDir . '/' . $config['css_file'];

    if (!is_dir($scssDir)) {
        echo "  ⚠ SKIP: SCSS dir not found: {$moduleRel}/{$config['scss_dir']}\n";
        continue;
    }

    if (!file_exists($cssFile)) {
        echo "  ✗ FAIL: CSS file not found: {$moduleRel}/{$config['css_file']}\n";
        $errors++;
        continue;
    }

    // Find newest SCSS file.
    $scssFiles = glob($scssDir . '/*.scss') ?: [];
    $newestScss = 0;
    $newestScssName = '';
    foreach ($scssFiles as $f) {
        $ts = filemtime($f);
        if ($ts > $newestScss) {
            $newestScss = $ts;
            $newestScssName = basename($f);
        }
    }

    $cssTs = filemtime($cssFile);
    $checked++;

    if ($cssTs >= $newestScss) {
        echo "  ✔ PASS: {$moduleRel}/{$config['css_file']} (CSS newer than SCSS)\n";
    }
    else {
        $diff = $newestScss - $cssTs;
        echo "  ✗ FAIL: {$moduleRel}/{$config['css_file']} is {$diff}s OLDER than {$newestScssName}\n";
        echo "         Run: cd {$moduleRel} && npx sass {$config['scss_dir']}/main.scss {$config['css_file']} --no-source-map --style=compressed\n";
        $errors++;
    }
}

echo "\n";
if ($errors > 0) {
    echo "✗ FAIL — {$errors} module(s) with stale CSS. Recompile SCSS.\n\n";
    exit(1);
}

echo "✔ PASS — {$checked} module(s) checked, all CSS fresh.\n\n";
exit(0);
