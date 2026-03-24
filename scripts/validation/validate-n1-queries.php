<?php

/**
 * @file
 * PERF-N1-QUERY-001: Detecta patrones N+1 en loops PHP.
 *
 * Busca ::load() dentro de foreach/for/while sin ::loadMultiple() previo.
 * Los patrones N+1 causan degradación de rendimiento proporcional al dataset.
 *
 * Usage: php scripts/validation/validate-n1-queries.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

$violations = [];
$filesChecked = 0;

// Patrones de exclusión: tests, migrations, install hooks (cargas puntuales aceptables).
$excludePatterns = ['/tests/', '/Test/', '.install', '/scripts/'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    // Excluir paths no relevantes.
    foreach ($excludePatterns as $exclude) {
        if (str_contains($path, $exclude)) {
            continue 2;
        }
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $filesChecked++;
    $lines = explode("\n", $content);
    $inLoop = false;
    $loopDepth = 0;
    $loopStartLine = 0;
    $hasLoadMultipleBefore = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);

        // Detectar inicio de loop.
        // FIX: No incrementar $loopDepth manualmente — la llave '{' de la
        // misma linea se cuenta en el tracking de braces. Sin este fix,
        // se producía double-counting y loops "no cerraban" (off-by-one).
        if (preg_match('/\b(foreach|for|while)\s*\(/', $trimmed)) {
            if (!$inLoop) {
                $loopStartLine = $i + 1;
                // Verificar si hay loadMultiple en las 10 líneas anteriores.
                $hasLoadMultipleBefore = false;
                $lookback = max(0, $i - 10);
                for ($j = $lookback; $j < $i; $j++) {
                    if (preg_match('/::loadMultiple\s*\(/', $lines[$j])) {
                        $hasLoadMultipleBefore = true;
                        break;
                    }
                }
            }
            $inLoop = true;
        }

        // Rastrear profundidad de llaves dentro del loop.
        if ($inLoop) {
            $loopDepth += substr_count($trimmed, '{') - substr_count($trimmed, '}');
            if ($loopDepth <= 0) {
                $inLoop = false;
                $loopDepth = 0;
            }
        }

        // Detectar ::load() dentro de loop sin loadMultiple previo.
        if ($inLoop && !$hasLoadMultipleBefore) {
            if (preg_match('/\w+::load\s*\(/', $trimmed) && !str_contains($trimmed, 'loadMultiple')) {
                $relativePath = str_replace($projectRoot . '/', '', $path);
                $lineNum = $i + 1;
                $violations[] = "[ERROR] PERF-N1-QUERY-001: ::load() dentro de loop sin loadMultiple previo en {$relativePath}:{$lineNum}";
                // Solo reportar una vez por loop.
                $hasLoadMultipleBefore = true;
            }
        }
    }
}

if (!empty($violations)) {
    foreach ($violations as $msg) {
        fwrite(STDERR, "$msg\n");
    }
    fwrite(STDERR, sprintf("\n[FAIL] %d patrón(es) N+1 detectados en %d archivos revisados\n", count($violations), $filesChecked));
    exit(1);
}

echo sprintf("[OK] PERF-N1-QUERY-001: %d archivos verificados, sin patrones N+1\n", $filesChecked);
exit(0);
