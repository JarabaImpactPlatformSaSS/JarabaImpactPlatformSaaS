<?php

declare(strict_types=1);

/**
 * @file
 * HOOK-DUPLICATE-001: Detects duplicate function declarations in .module files.
 *
 * PHP 8.4 throws a fatal error if the same function is declared twice.
 * This is easy to introduce when merging code or adding hooks in different
 * sections of a large .module file.
 *
 * EXIT CODES:
 *   0 = No duplicates found
 *   1 = Duplicate functions detected
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checked = 0;

$moduleFiles = glob($root . '/web/modules/custom/*/*.module');

foreach ($moduleFiles as $moduleFile) {
    $content = file_get_contents($moduleFile);
    if ($content === false) {
        continue;
    }

    $checked++;
    $functions = [];

    // Find all function declarations.
    if (preg_match_all('/^function\s+(\w+)\s*\(/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $match) {
            $funcName = $match[0];
            $offset = $match[1];
            $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;

            if (isset($functions[$funcName])) {
                $relPath = str_replace($root . '/', '', $moduleFile);
                $violations[] = [
                    'file' => $relPath,
                    'function' => $funcName,
                    'line1' => $functions[$funcName],
                    'line2' => $lineNum,
                ];
            }
            else {
                $functions[$funcName] = $lineNum;
            }
        }
    }
}

echo "HOOK-DUPLICATE-001: Duplicate Function Detection\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checked} .module files\n\n";

if (empty($violations)) {
    echo "✅ PASS — No duplicate functions found.\n";
    exit(0);
}

echo "❌ FAIL — " . count($violations) . " duplicate(s) found:\n\n";
foreach ($violations as $v) {
    echo "  {$v['file']}\n";
    echo "    function {$v['function']}() declared at:\n";
    echo "      Line {$v['line1']} (first)\n";
    echo "      Line {$v['line2']} (duplicate) ← PHP 8.4 FATAL ERROR\n\n";
}
exit(1);
