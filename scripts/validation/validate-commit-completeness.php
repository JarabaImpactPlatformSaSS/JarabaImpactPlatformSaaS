<?php

/**
 * @file
 * COMMIT-COMPLETENESS-001: Detects staged files calling methods defined
 * in unstaged files — the "caller without callee" problem.
 *
 * Root cause of the 2026-03-27 production 500: ecosistema_jaraba_theme.theme
 * was committed calling $bridge->getJarabaImpactColumns(), but
 * MegaMenuBridgeService.php (which defines that method) was left unstaged.
 *
 * This validator scans all staged .php and .theme files for method calls
 * like $var->methodName() and checks if methodName is a NEW method defined
 * only in unstaged files. If so, it flags a potential deploy coherence gap.
 *
 * Best run as pre-push hook or manual check before deploy.
 *
 * Usage: php scripts/validation/validate-commit-completeness.php
 * Exit code: 0 = no gaps, 1 = potential gaps found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$repoRoot = realpath(__DIR__ . '/../..');

// Get staged PHP files (added or modified).
$stagedFiles = [];
exec('git diff --cached --name-only --diff-filter=AM -- "*.php" "*.theme" "*.module" 2>/dev/null', $stagedFiles);

// Get unstaged PHP files (modified but not staged).
$unstagedFiles = [];
exec('git diff --name-only --diff-filter=M -- "*.php" "*.theme" "*.module" 2>/dev/null', $unstagedFiles);

if (empty($stagedFiles) && empty($unstagedFiles)) {
    // Nothing staged — check HEAD vs working tree for general analysis.
    exec('git diff HEAD --name-only --diff-filter=M -- "*.php" "*.theme" "*.module" 2>/dev/null', $unstagedFiles);
}

// ─── CHECK 1: Extract new method definitions from unstaged files ───
$unstagedMethods = [];
foreach ($unstagedFiles as $file) {
    $fullPath = $repoRoot . '/' . $file;
    if (!file_exists($fullPath)) {
        continue;
    }

    // Get the diff of the unstaged file to find NEW method definitions.
    $diffOutput = [];
    exec('git diff HEAD -- ' . escapeshellarg($file) . ' 2>/dev/null', $diffOutput);
    $diffText = implode("\n", $diffOutput);

    // Find new public/protected function definitions (lines starting with +).
    if (preg_match_all('/^\+\s*(?:public|protected)\s+function\s+(\w+)\s*\(/m', $diffText, $matches)) {
        foreach ($matches[1] as $method) {
            $unstagedMethods[$method] = $file;
        }
    }
}

if (empty($unstagedMethods)) {
    $passed++;
    // No new methods in unstaged files — nothing to check.
} else {
    // ─── CHECK 2: Scan staged files for calls to unstaged methods ───
    $gapsFound = [];
    foreach ($stagedFiles as $file) {
        $fullPath = $repoRoot . '/' . $file;
        if (!file_exists($fullPath)) {
            continue;
        }
        $content = file_get_contents($fullPath);
        if ($content === false) {
            continue;
        }

        foreach ($unstagedMethods as $method => $sourceFile) {
            // Skip if the staged file IS the source file (same file).
            if ($file === $sourceFile) {
                continue;
            }
            // Look for ->methodName( or ::methodName( calls.
            if (preg_match('/(?:->|::)' . preg_quote($method, '/') . '\s*\(/', $content)) {
                $gapsFound[] = [
                    'caller' => $file,
                    'method' => $method,
                    'callee' => $sourceFile,
                ];
            }
        }
    }

    if (empty($gapsFound)) {
        $passed++;
    } else {
        foreach ($gapsFound as $gap) {
            $errors[] = "DEPLOY-COHERENCE-001: {$gap['caller']} calls ->{$gap['method']}() " .
                "but {$gap['callee']} (which defines it) is NOT staged. " .
                "Both files must be in the same commit.";
        }
    }
}

// ─── CHECK 3: Verify no staged .theme/.module calls brand-new services ───
// This is a lighter check: if a staged file adds a new ->service_name pattern
// that references a service not in the container (new service not committed).
$newServiceCalls = 0;
foreach ($stagedFiles as $file) {
    if (!str_ends_with($file, '.theme') && !str_ends_with($file, '.module')) {
        continue;
    }
    $diffOutput = [];
    exec('git diff --cached -- ' . escapeshellarg($file) . ' 2>/dev/null', $diffOutput);
    $diffText = implode("\n", $diffOutput);

    // Find new lines adding Drupal::service() or $container->get() calls.
    if (preg_match_all('/^\+.*\\\\Drupal::service\([\'"]([^\'"]+)[\'"]\)/m', $diffText, $matches)) {
        foreach ($matches[1] as $serviceName) {
            // Check if the service definition file exists.
            $serviceModule = explode('.', $serviceName)[0] ?? '';
            $servicesYml = $repoRoot . '/web/modules/custom/' . $serviceModule . '/' . $serviceModule . '.services.yml';
            if (file_exists($servicesYml) && str_contains(file_get_contents($servicesYml), $serviceName . ':')) {
                // Service exists — OK.
            } else {
                $warnings[] = "CHECK 3: {$file} adds \\Drupal::service('{$serviceName}') — verify service definition is committed";
                $newServiceCalls++;
            }
        }
    }
}
if ($newServiceCalls === 0) {
    $passed++;
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " COMMIT-COMPLETENESS-001: Caller/Callee Coherence\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

if (!empty($unstagedMethods)) {
    echo "  New methods in unstaged files: " . count($unstagedMethods) . "\n";
    foreach ($unstagedMethods as $method => $file) {
        echo "    → {$method}() in {$file}\n";
    }
    echo "\n";
}

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
