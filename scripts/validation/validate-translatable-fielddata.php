<?php

/**
 * @file
 * TRANSLATABLE-FIELDDATA-001: Detects incorrect table usage for translatable entities.
 *
 * Translatable entities store fields in {type}_field_data, NOT in the base table.
 * Direct SQL using the base table for translatable entities silently returns
 * wrong or empty data.
 *
 * Scans custom modules for raw SQL (db_query, ->query(), Database::getConnection)
 * referencing base tables of known translatable entities without _field_data suffix.
 *
 * Usage: php scripts/validation/validate-translatable-fielddata.php
 * Exit code: 0 = pass, 1 = violations exceed baseline.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$modulesDir = __DIR__ . '/../../web/modules/custom';

// Known translatable entity base tables that MUST use _field_data for field access.
$translatableTables = [
    'node',
    'page_content',
    'content_article',
    'taxonomy_term',
    'menu_link_content',
    'block_content',
];

// Patterns that indicate raw SQL table usage.
$sqlPatterns = [
    'db_query',
    '->query(',
    'Database::getConnection',
    'db_select',
    '$connection->',
    "FROM {",
    "JOIN {",
    "from {",
    "join {",
];

// ─── CHECK 1: Scan for raw SQL using base table instead of _field_data ───
$violations = [];
$filesScanned = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    // Skip test files.
    if (str_contains($file->getPathname(), '/tests/')) {
        continue;
    }

    // Skip .install files (raw SQL is allowed there per project rules).
    if (str_ends_with($file->getBasename(), '.install')) {
        continue;
    }

    $content = file_get_contents($file->getPathname());

    // Only check files with SQL patterns.
    $hasSql = false;
    foreach ($sqlPatterns as $pattern) {
        if (str_contains($content, $pattern)) {
            $hasSql = true;
            break;
        }
    }
    if (!$hasSql) {
        continue;
    }

    $filesScanned++;
    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);

    foreach ($lines as $lineNum => $line) {
        $lineNo = $lineNum + 1;

        foreach ($translatableTables as $table) {
            // Match {table_name} without _field_data suffix in SQL context.
            // e.g., FROM {page_content} but NOT FROM {page_content_field_data}.
            if (preg_match('/\{' . preg_quote($table, '/') . '\}/', $line) &&
                !str_contains($line, $table . '_field_data') &&
                !str_contains($line, $table . '_revision')) {
                // Verify it's in a SQL context, not just a comment or string.
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) {
                    continue;
                }
                $relativePath = str_replace($modulesDir . '/', '', $file->getPathname());
                $violations[] = "{$relativePath}:{$lineNo} — uses {{$table}} instead of {{$table}_field_data}";
            }
        }
    }
}

$baseline = 10;
if (count($violations) <= $baseline) {
    $passed++;
    if (!empty($violations)) {
        $warnings[] = "CHECK 1: " . count($violations) . " potential base table usages (baseline: {$baseline})";
    }
} else {
    $errors[] = "CHECK 1: " . count($violations) . " base table usages EXCEED baseline of {$baseline}";
}

// ─── CHECK 2: Rule documented in CLAUDE.md ───
$claudeMd = __DIR__ . '/../../CLAUDE.md';
if (file_exists($claudeMd)) {
    $content = file_get_contents($claudeMd);
    if (str_contains($content, 'TRANSLATABLE-FIELDDATA-001')) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 2: TRANSLATABLE-FIELDDATA-001 not found in CLAUDE.md';
        $passed++;
    }
} else {
    $passed++;
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " TRANSLATABLE-FIELDDATA-001: Translatable Entity Table Usage\n";
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
echo " [{$filesScanned} PHP files with SQL scanned]\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
