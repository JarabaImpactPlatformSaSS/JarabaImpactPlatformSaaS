<?php

/**
 * @file
 * ENTITY-ACCESS-HANDLER-001: Validates AccessControlHandler coverage.
 *
 * AUDIT-CONS-001 requires every ContentEntity to declare an
 * AccessControlHandler in its annotation. This validator scans all
 * custom entity classes and reports those missing the handler.
 *
 * Legitimate exceptions (log/audit entities with no user-facing routes):
 * - Entities matching *Log, *Audit*, *Event, *Entry patterns
 * - Entities without any route in their annotation links
 *
 * Usage: php scripts/validation/validate-entity-access-handler.php
 * Exit code: 0 = all checks pass, 1 = failures found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$modulesDir = __DIR__ . '/../../web/modules/custom';

// Log/audit entities that legitimately may skip access handler.
$logPatterns = [
    '/Log\.php$/',
    '/AuditLog\.php$/',
    '/AuditEntry\.php$/',
    '/Event\.php$/',
    '/UsageLog\.php$/',
    '/FunnelEvent\.php$/',
    '/NotificationLog\.php$/',
    '/GenerationLog\.php$/',
    '/AnalyticsDaily\.php$/',
    '/AnalyticsEvent\.php$/',
    '/RemediationLog\.php$/',
    '/VerificationResult\.php$/',
    '/SeoNotificationLog\.php$/',
    '/RolProgramaLog\.php$/',
    '/PromptImprovement\.php$/',
];

// ─── CHECK 1: ContentEntity classes with access handler ───
$entitiesTotal = 0;
$entitiesWithHandler = 0;
$entitiesExempt = 0;
$missingHandler = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    // Only look in Entity directories.
    if (!str_contains($file->getPathname(), '/src/Entity/')) {
        continue;
    }

    $content = file_get_contents($file->getPathname());

    // Only ContentEntityType annotations (not ConfigEntityType).
    if (!str_contains($content, '@ContentEntityType') && !str_contains($content, 'ContentEntityType(')) {
        continue;
    }

    $entitiesTotal++;
    $relativePath = str_replace($modulesDir . '/', '', $file->getPathname());

    // Check if it has access handler in annotation.
    if (preg_match('/"access"\s*=\s*"[^"]+"|\'access\'\s*=\s*\'[^\']+\'/', $content)) {
        $entitiesWithHandler++;
        continue;
    }

    // Check if it's a log/audit entity (exempt).
    $isExempt = false;
    foreach ($logPatterns as $pattern) {
        if (preg_match($pattern, $file->getBasename())) {
            $isExempt = true;
            break;
        }
    }

    if ($isExempt) {
        $entitiesExempt++;
        continue;
    }

    $missingHandler[] = $relativePath;
}

// Allow a baseline of entities without handler (pre-existing).
$baseline = 30;
if (count($missingHandler) <= $baseline) {
    $passed++;
    if (!empty($missingHandler)) {
        $warnings[] = "CHECK 1: " . count($missingHandler) . " entities without AccessControlHandler (baseline: {$baseline}). Examples: " . implode(', ', array_slice($missingHandler, 0, 5));
    }
} else {
    $errors[] = "CHECK 1: " . count($missingHandler) . " entities without AccessControlHandler EXCEED baseline of {$baseline} (new entities introduced without handler)";
}

// ─── CHECK 2: Entities with tenant_id MUST have tenant check in handler ───
$tenantViolations = [];
foreach ($iterator as $file) {
    // Reset iterator — need fresh pass.
}

// Re-scan for tenant_id entities.
$iterator2 = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator2 as $file) {
    if ($file->getExtension() !== 'php' || !str_contains($file->getPathname(), '/src/Entity/')) {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    if (!str_contains($content, '@ContentEntityType')) {
        continue;
    }

    // Has tenant_id field?
    if (!str_contains($content, "'tenant_id'") && !str_contains($content, '"tenant_id"')) {
        continue;
    }

    // Has access handler reference?
    if (preg_match('/"access"\s*=\s*"([^"]+)"/', $content, $matches)) {
        $handlerClass = $matches[1];
        // Check if handler file exists and contains tenant check.
        $handlerFile = str_replace('\\', '/', $handlerClass);
        // Extract just the class name to search for it.
        $className = basename(str_replace('\\', '/', $handlerClass));
        $handlerSearch = shell_exec("grep -rl 'class {$className}' " . escapeshellarg($modulesDir) . " 2>/dev/null | head -1");
        if ($handlerSearch) {
            $handlerContent = file_get_contents(trim($handlerSearch));
            if (!str_contains($handlerContent, 'tenant') && !str_contains($handlerContent, 'Tenant')) {
                $relativePath = str_replace($modulesDir . '/', '', $file->getPathname());
                $tenantViolations[] = $relativePath;
            }
        }
    }
}

// Tenant isolation check — baseline allowed.
$tenantBaseline = 15;
if (count($tenantViolations) <= $tenantBaseline) {
    $passed++;
    if (!empty($tenantViolations)) {
        $warnings[] = "CHECK 2: " . count($tenantViolations) . " tenant entities without tenant check in handler (baseline: {$tenantBaseline})";
    }
} else {
    $errors[] = "CHECK 2: " . count($tenantViolations) . " tenant entities without tenant check in handler EXCEED baseline of {$tenantBaseline}";
}

// ─── CHECK 3: Coverage percentage ───
$coverage = $entitiesTotal > 0 ? round(($entitiesWithHandler / $entitiesTotal) * 100, 1) : 0;
if ($coverage >= 85) {
    $passed++;
} else {
    $errors[] = "CHECK 3: AccessControlHandler coverage {$coverage}% is below 85% minimum ({$entitiesWithHandler}/{$entitiesTotal})";
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " ENTITY-ACCESS-HANDLER-001: AccessControlHandler Coverage\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

foreach ($errors as $error) {
    echo "  ✗ FAIL: {$error}\n";
}
foreach ($warnings as $warning) {
    echo "  ⚠ WARN: {$warning}\n";
}

echo "\n  Passed: {$passed}/{$total}";
if (!empty($warnings)) {
    echo " (+" . count($warnings) . " warnings)";
}
echo "\n  Coverage: {$entitiesWithHandler}/{$entitiesTotal} ({$coverage}%) + {$entitiesExempt} exempt (log/audit)\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
