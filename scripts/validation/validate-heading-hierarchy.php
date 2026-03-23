<?php

/**
 * @file
 * A11Y-HEADING-HIERARCHY-001: Verifica jerarquía de headings en templates Twig.
 *
 * Detecta saltos en la jerarquía de encabezados (ej: h1 seguido de h3 sin h2).
 * Esto viola WCAG 2.1 SC 1.3.1 (Info and Relationships).
 *
 * Usage: php scripts/validation/validate-heading-hierarchy.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$dirs = [
    $projectRoot . '/web/modules/custom',
    $projectRoot . '/web/themes/custom',
];

$violations = [];
$filesChecked = 0;

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'twig') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            continue;
        }

        $filesChecked++;

        // Extraer todos los headings del template (h1-h6).
        if (!preg_match_all('/<h([1-6])[\s>]/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }

        $levels = array_map(fn($m) => (int) $m[0], $matches[1]);

        // Verificar que no hay saltos > 1 nivel.
        for ($i = 1; $i < count($levels); $i++) {
            $current = $levels[$i];
            $previous = $levels[$i - 1];

            // Solo verificar saltos descendentes (h1→h3 es error, h3→h1 es válido).
            if ($current > $previous + 1) {
                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                // Calcular línea aproximada.
                $offset = (int) $matches[1][$i][1];
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                $violations[] = "[ERROR] A11Y-HEADING-HIERARCHY-001: Salto h{$previous}→h{$current} en {$relativePath}:{$line}";
                break; // Solo reportar el primer salto por archivo.
            }
        }
    }
}

if (!empty($violations)) {
    foreach ($violations as $msg) {
        fwrite(STDERR, "$msg\n");
    }
    fwrite(STDERR, sprintf("\n[FAIL] %d archivo(s) con saltos de heading de %d revisados\n", count($violations), $filesChecked));
    exit(1);
}

echo sprintf("[OK] A11Y-HEADING-HIERARCHY-001: %d templates verificados, jerarquía correcta\n", $filesChecked);
exit(0);
