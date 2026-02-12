<?php

/**
 * @file
 * Script para detectar iconos faltantes en el sistema Jaraba.
 *
 * Ejecutar con: lando ssh -c 'drush scr detect_missing_icons.php'
 */

$module_path = \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core');
$icons_base = DRUPAL_ROOT . '/' . $module_path . '/images/icons/';

// Categorías disponibles
$categories = ['ui', 'business', 'analytics', 'ai', 'actions', 'verticals', 'social'];

// Obtener todos los iconos existentes
$existing = [];
foreach ($categories as $cat) {
    $dir = $icons_base . $cat;
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            // Solo archivos SVG, sin variante duotone
            if (preg_match('/^([a-z0-9-]+)\.svg$/', $file, $m)) {
                $existing[$cat][$m[1]] = true;
            }
        }
    }
}

// Escanear templates para encontrar usos de jaraba_icon
$templates_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/templates';
$theme_dir = DRUPAL_ROOT . '/themes/custom/ecosistema_jaraba_theme/templates';

$used = [];
$scan_dirs = [$templates_dir, $theme_dir];

foreach ($scan_dirs as $base_dir) {
    if (!is_dir($base_dir))
        continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'twig')
            continue;

        $content = file_get_contents($file->getPathname());

        // Buscar patrones jaraba_icon('category', 'name', ...)
        preg_match_all(
            "/jaraba_icon\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]/",
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $cat = $match[1];
            $name = $match[2];
            $used[$cat][$name] = $file->getBasename();
        }
    }
}

// Encontrar iconos faltantes
$missing = [];
foreach ($used as $cat => $icons) {
    foreach ($icons as $name => $source) {
        if (!isset($existing[$cat][$name])) {
            $missing[] = [
                'category' => $cat,
                'name' => $name,
                'source' => $source,
                // Verificar si tiene duotone
                'has_duotone' => file_exists($icons_base . $cat . '/' . $name . '-duotone.svg'),
            ];
        }
    }
}

echo "=== ICONOS FALTANTES EN EL SISTEMA ===\n\n";
echo "Total iconos usados en templates: " . array_sum(array_map('count', $used)) . "\n";
echo "Total iconos faltantes: " . count($missing) . "\n\n";

if (empty($missing)) {
    echo "✓ ¡Todos los iconos existen!\n";
} else {
    echo "ICONOS FALTANTES:\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($missing as $m) {
        echo sprintf(
            "  • [%s] %s (usado en %s)%s\n",
            $m['category'],
            $m['name'],
            $m['source'],
            $m['has_duotone'] ? ' [tiene duotone pero no outline]' : ''
        );
    }
}

// Mostrar variantes de uso (filled, duotone, etc.)
echo "\n\n=== VARIANTES DE ICONOS USADAS ===\n";
$variants_used = [];

foreach ($scan_dirs as $base_dir) {
    if (!is_dir($base_dir))
        continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'twig')
            continue;

        $content = file_get_contents($file->getPathname());

        // Buscar variant:
        preg_match_all(
            "/variant:\s*['\"]([^'\"]+)['\"]/",
            $content,
            $matches
        );

        foreach ($matches[1] as $variant) {
            $variants_used[$variant] = ($variants_used[$variant] ?? 0) + 1;
        }
    }
}

foreach ($variants_used as $variant => $count) {
    echo "  • $variant: $count usos\n";
}

echo "\n¡Listo!\n";
