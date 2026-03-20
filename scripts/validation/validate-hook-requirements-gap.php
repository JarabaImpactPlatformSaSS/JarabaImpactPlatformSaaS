<?php

/**
 * @file validate-hook-requirements-gap.php
 * HOOK-REQUIREMENTS-GAP-001: Audits custom modules missing hook_requirements().
 *
 * Every non-trivial module should implement hook_requirements() to report
 * status on /admin/reports/status. Target: 95%+ coverage.
 */

$modulesPath = 'web/modules/custom';
$errors = [];
$withRequirements = 0;
$totalNonTrivial = 0;

// Modules considered trivial (no runtime logic, just config/glue)
$trivialModules = [
    'ecosistema_jaraba_core_test',
];

$dirs = glob("$modulesPath/*/", GLOB_ONLYDIR);

foreach ($dirs as $dir) {
    $moduleName = basename($dir);

    // Skip test modules and explicitly trivial ones
    if (str_ends_with($moduleName, '_test') || in_array($moduleName, $trivialModules, true)) {
        continue;
    }

    // Check if module has any PHP code (not just info.yml)
    $hasPHP = !empty(glob("$dir/src/**/*.php")) || !empty(glob("$dir/src/*.php")) || file_exists("$dir/$moduleName.module");
    if (!$hasPHP) {
        continue;
    }

    $totalNonTrivial++;

    // Check for hook_requirements in .install file
    $installFile = "$dir/$moduleName.install";
    if (file_exists($installFile)) {
        $content = file_get_contents($installFile);
        if (preg_match('/function\s+' . preg_quote($moduleName) . '_requirements\b/', $content)) {
            $withRequirements++;
            continue;
        }
    }

    $errors[] = $moduleName;
}

$coverage = $totalNonTrivial > 0 ? round(($withRequirements / $totalNonTrivial) * 100, 1) : 0;

if ($coverage >= 95) {
    echo "HOOK-REQUIREMENTS-GAP-001: PASS — $withRequirements/$totalNonTrivial modules ($coverage%) have hook_requirements\n";
    exit(0);
}

if ($coverage >= 85) {
    echo "HOOK-REQUIREMENTS-GAP-001: WARN — $withRequirements/$totalNonTrivial modules ($coverage%) — target 95%\n";
    echo "  Missing hook_requirements:\n";
    foreach ($errors as $mod) {
        echo "    - $mod\n";
    }
    exit(0);
}

echo "HOOK-REQUIREMENTS-GAP-001: FAIL — $withRequirements/$totalNonTrivial modules ($coverage%) — target 95%\n";
echo "  Missing hook_requirements:\n";
foreach ($errors as $mod) {
    echo "    - $mod\n";
}
exit(1);
