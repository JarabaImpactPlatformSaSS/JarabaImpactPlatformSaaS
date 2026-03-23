<?php

/**
 * @file
 * CACHE-KEY-TENANT-001: Verifica que cache keys en servicios incluyen tenant scope.
 *
 * Busca usos de cache()->set() o \Drupal::cache() en servicios custom
 * que no incluyen referencia a tenant_id o tenant context en la key.
 * Sin tenant scope, datos de un tenant pueden servirse a otro.
 *
 * Usage: php scripts/validation/validate-cache-key-tenant.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

$violations = [];
$filesChecked = 0;

// Solo verificar Service classes (donde tiene sentido el cache custom).
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

// Patrones que indican tenant awareness en cache key.
$tenantPatterns = [
    'tenant',
    'getTenantId',
    'getCurrentTenant',
    'group_id',
    'getGroupId',
    'tenant_context',
    'tenantId',
    'tenant_id',
];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    // Solo analizar Service classes.
    if (!str_contains($path, '/Service/') && !str_contains($path, '/src/Service')) {
        continue;
    }

    // Excluir tests.
    if (str_contains($path, '/tests/') || str_contains($path, '/Test/')) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    // Buscar usos de cache set/get.
    if (!preg_match_all('/->set\s*\(\s*[\'"]([^"\']+)[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    $filesChecked++;

    foreach ($matches[0] as $idx => $match) {
        $cacheKey = $matches[1][$idx][0];
        $offset = (int) $match[1];

        // Verificar contexto: ¿es una llamada a cache?
        $before = substr($content, max(0, $offset - 100), 100);
        if (!preg_match('/cache|Cache/', $before)) {
            continue;
        }

        // Verificar si la key incluye tenant scope.
        $hasTenant = false;
        foreach ($tenantPatterns as $pattern) {
            if (stripos($cacheKey, $pattern) !== false) {
                $hasTenant = true;
                break;
            }
        }

        // Si no tiene tenant en la key, verificar si se construye dinámicamente.
        if (!$hasTenant) {
            // Buscar en las 5 líneas anteriores si hay tenant variable concatenada.
            $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
            $lines = explode("\n", $content);
            $contextStart = max(0, $lineNum - 6);
            $context = implode("\n", array_slice($lines, $contextStart, 6));

            foreach ($tenantPatterns as $pattern) {
                if (stripos($context, $pattern) !== false) {
                    $hasTenant = true;
                    break;
                }
            }
        }

        if (!$hasTenant) {
            $relativePath = str_replace($projectRoot . '/', '', $path);
            $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
            $violations[] = "[ERROR] CACHE-KEY-TENANT-001: Cache key '{$cacheKey}' sin tenant scope en {$relativePath}:{$lineNum}";
        }
    }
}

if (!empty($violations)) {
    foreach ($violations as $msg) {
        fwrite(STDERR, "$msg\n");
    }
    fwrite(STDERR, sprintf("\n[FAIL] %d cache key(s) sin tenant scope en %d servicios revisados\n", count($violations), $filesChecked));
    exit(1);
}

echo sprintf("[OK] CACHE-KEY-TENANT-001: %d servicios verificados, cache keys con tenant scope\n", $filesChecked);
exit(0);
