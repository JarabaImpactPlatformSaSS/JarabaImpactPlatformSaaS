<?php

/**
 * @file
 * GIT-OBJECTS-OWNER-MONITOR: Detects git objects owned by root.
 *
 * Root-owned objects in .git/objects/ prevent git fetch/pull, blocking
 * deploys and leaving production stuck on an old commit. This happened
 * on 2026-03-27 when MegaMenuBridgeService couldn't reach production.
 *
 * This validator runs locally (CI) and can also run on production
 * via SSH as a deploy pre-check.
 *
 * Usage: php scripts/validation/validate-git-objects-owner.php
 * Exit code: 0 = no root-owned objects, 1 = root objects found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$gitDir = __DIR__ . '/../../.git/objects';

if (!is_dir($gitDir)) {
    echo "\n  SKIP: .git/objects not found (not a git repo or bare checkout)\n\n";
    exit(0);
}

// ─── CHECK 1: No root-owned objects ───
// On CI (GitHub Actions) everything runs as the runner user, so this
// check always passes. On production, it detects the actual problem.
$output = [];
exec("find " . escapeshellarg($gitDir) . " -user root -type f 2>/dev/null | head -20", $output, $exitCode);

$rootCount = count($output);
if ($rootCount === 0) {
    $passed++;
} else {
    $errors[] = "CHECK 1: Found {$rootCount}+ git objects owned by root. " .
        "This will block git fetch/pull during deploy. " .
        "Fix: sudo chown -R $(whoami):$(whoami) .git/";
    // Show first 5 for diagnosis.
    foreach (array_slice($output, 0, 5) as $file) {
        $errors[] = "  → " . $file;
    }
}

// ─── CHECK 2: .git directory writable by current user ───
if (is_writable($gitDir)) {
    $passed++;
} else {
    $warnings[] = "CHECK 2: .git/objects is not writable by current user (" . get_current_user() . ")";
}

// ─── CHECK 3: refs directory writable ───
$refsDir = __DIR__ . '/../../.git/refs';
if (is_dir($refsDir) && is_writable($refsDir)) {
    $passed++;
} else {
    $warnings[] = "CHECK 3: .git/refs is not writable by current user";
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " GIT-OBJECTS-OWNER-MONITOR: Git Object Ownership\n";
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
echo "\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
