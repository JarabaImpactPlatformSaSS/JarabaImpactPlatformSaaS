<?php

declare(strict_types=1);

/**
 * @file
 * JS-CACHE-BUST-001: Detects when JS source files changed but library version didn't.
 *
 * Compares the modification timestamp of JS files against their library version.
 * If the JS was modified after the last cache clear but the version is unchanged,
 * the browser will serve stale cached JS → invisible bugs.
 *
 * EXIT CODES:
 *   0 = All JS libraries have current versions
 *   1 = Stale library versions detected
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checked = 0;

// Parse all libraries.yml files in the theme.
$libFiles = glob($root . '/web/themes/custom/*//*.libraries.yml');
foreach ($libFiles as $libFile) {
    $content = file_get_contents($libFile);
    if ($content === false) {
        continue;
    }

    $themeDir = dirname($libFile);
    $lines = explode("\n", $content);
    $currentLib = '';
    $currentVersion = '';

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // Detect library name (no indentation, ends with :).
        if (preg_match('/^([a-z][a-z0-9_-]*):$/i', $line, $m)) {
            $currentLib = $m[1];
            $currentVersion = '';
            continue;
        }

        // Detect version.
        if (preg_match('/^\s+version:\s*(.+)$/', $line, $m)) {
            $currentVersion = trim($m[1]);
            continue;
        }

        // Detect JS file.
        if (preg_match('/^\s{4,}(js\/[^:]+\.js):/', $line, $m)) {
            $jsRelPath = trim($m[1]);
            $jsFullPath = $themeDir . '/' . $jsRelPath;

            if (!file_exists($jsFullPath)) {
                continue;
            }

            $checked++;
            $jsMtime = filemtime($jsFullPath);
            $libMtime = filemtime($libFile);

            // If JS was modified AFTER the libraries.yml, the version might be stale.
            if ($jsMtime > $libMtime + 60) {
                $violations[] = [
                    'library' => $currentLib,
                    'version' => $currentVersion,
                    'js_file' => $jsRelPath,
                    'js_modified' => date('Y-m-d H:i:s', $jsMtime),
                    'lib_modified' => date('Y-m-d H:i:s', $libMtime),
                ];
            }
        }
    }
}

echo "JS-CACHE-BUST-001: JS Library Version Freshness\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checked} JS files in theme libraries\n\n";

if (empty($violations)) {
    echo "✅ PASS — All JS library versions are current.\n";
    exit(0);
}

echo "⚠️  WARNING — " . count($violations) . " JS file(s) modified after library version:\n\n";
foreach ($violations as $v) {
    echo "  Library: {$v['library']} (version: {$v['version']})\n";
    echo "    JS: {$v['js_file']} (modified: {$v['js_modified']})\n";
    echo "    libraries.yml last modified: {$v['lib_modified']}\n";
    echo "    → Update the version in libraries.yml to force browser cache bust.\n\n";
}
exit(1);
