<?php

/**
 * @file
 * EMAIL-TEMPLATE-RENDER-001: Verifica sintaxis Twig de templates de email.
 *
 * Extiende TWIG-SYNTAX-LINT-001 para cubrir templates en templates/email/
 * y templates con "email" en su nombre. Detecta errores de sintaxis que
 * causarían emails rotos en producción.
 *
 * Usage: php scripts/validation/validate-email-template-render.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

$violations = [];
$filesChecked = 0;

// Patrones de detección de templates de email.
$emailPatterns = [
    '/templates/email/',
    'email-base',
    'email-template',
    'email-unsubscribe',
    'digest-email',
    'notification.html.twig',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'twig') {
        continue;
    }

    $path = $file->getPathname();
    $isEmail = false;

    foreach ($emailPatterns as $pattern) {
        if (str_contains($path, $pattern) || str_contains(basename($path), 'email')) {
            $isEmail = true;
            break;
        }
    }

    if (!$isEmail) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $filesChecked++;
    $relativePath = str_replace($projectRoot . '/', '', $path);

    // Check 1: Doble coma en mappings (TWIG-SYNTAX-LINT-001).
    if (preg_match('/,,/', $content)) {
        $violations[] = "[ERROR] EMAIL-TEMPLATE-RENDER-001: Doble coma en {$relativePath}";
    }

    // Check 2: Tags Twig no cerrados.
    $openTags = preg_match_all('/\{%/', $content);
    $closeTags = preg_match_all('/%\}/', $content);
    if ($openTags !== $closeTags) {
        $violations[] = "[ERROR] EMAIL-TEMPLATE-RENDER-001: Tags Twig desbalanceados ({$openTags} open vs {$closeTags} close) en {$relativePath}";
    }

    // Check 3: Variables Twig no cerradas.
    $openVars = preg_match_all('/\{\{/', $content);
    $closeVars = preg_match_all('/\}\}/', $content);
    if ($openVars !== $closeVars) {
        $violations[] = "[ERROR] EMAIL-TEMPLATE-RENDER-001: Variables Twig desbalanceadas ({$openVars} open vs {$closeVars} close) en {$relativePath}";
    }

    // Check 4: Comentarios Twig anidados (cierre prematuro).
    if (preg_match('/\{#.*\{#/s', $content)) {
        $violations[] = "[ERROR] EMAIL-TEMPLATE-RENDER-001: Comentario Twig anidado en {$relativePath}";
    }

    // Check 5: |raw sin sanitización previa (AUDIT-SEC-003 para emails).
    if (preg_match('/\{\{.*\|raw.*\}\}/', $content)) {
        // Verificar si hay |e o Markup antes de |raw.
        if (!preg_match('/\|\s*e\s*\|.*\|raw|\bMarkup\b/', $content)) {
            $violations[] = "[ERROR] EMAIL-TEMPLATE-RENDER-001: |raw sin sanitización previa en {$relativePath}";
        }
    }

    // Check 6: URLs hardcoded en email templates (ROUTE-LANGPREFIX-001).
    if (preg_match('/href\s*=\s*["\'](\/[a-z])/', $content)) {
        $violations[] = "[ERROR] EMAIL-TEMPLATE-RENDER-001: URL hardcoded en href (usar url() o variable) en {$relativePath}";
    }
}

if (!empty($violations)) {
    foreach ($violations as $msg) {
        fwrite(STDERR, "$msg\n");
    }
    fwrite(STDERR, sprintf("\n[FAIL] %d error(es) en %d templates de email revisados\n", count($violations), $filesChecked));
    exit(1);
}

echo sprintf("[OK] EMAIL-TEMPLATE-RENDER-001: %d templates de email verificados, sintaxis correcta\n", $filesChecked);
exit(0);
