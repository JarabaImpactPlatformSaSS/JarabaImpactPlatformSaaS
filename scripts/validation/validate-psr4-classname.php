<?php

declare(strict_types=1);

/**
 * @file
 * PSR4-CLASSNAME-001: Verifies PHP class names match their filenames (PSR-4).
 *
 * A mismatch causes "Class not found" fatal errors at runtime because
 * Composer's PSR-4 autoloader maps filename → class name.
 *
 * EXIT CODES:
 *   0 = All class names match filenames
 *   1 = Mismatches found
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checked = 0;

$dirs = glob($root . '/web/modules/custom/*/src/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE);

foreach ($dirs as $phpFile) {
    $content = file_get_contents($phpFile);
    if ($content === false) {
        continue;
    }

    // Extract class/interface/trait/enum name.
    if (!preg_match('/^(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+(\w+)/m', $content, $m)) {
        continue;
    }

    $checked++;
    $className = $m[1];
    $fileName = pathinfo($phpFile, PATHINFO_FILENAME);

    if ($className !== $fileName) {
        $relPath = str_replace($root . '/', '', $phpFile);
        $violations[] = [
            'file' => $relPath,
            'filename' => $fileName,
            'classname' => $className,
        ];
    }
}

echo "PSR4-CLASSNAME-001: PSR-4 Class/Filename Consistency\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checked} PHP class files\n\n";

if (empty($violations)) {
    echo "✅ PASS — All {$checked} class names match their filenames.\n";
    exit(0);
}

echo "❌ FAIL — " . count($violations) . " PSR-4 mismatch(es):\n\n";
foreach ($violations as $v) {
    echo "  {$v['file']}\n";
    echo "    Filename: {$v['filename']}.php\n";
    echo "    Class:    {$v['classname']}\n";
    echo "    → Rename file to {$v['classname']}.php or rename class to {$v['filename']}\n\n";
}
exit(1);
