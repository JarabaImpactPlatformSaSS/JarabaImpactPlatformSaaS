<?php

/**
 * @file
 * DEPRECATED-TEMPLATE-USAGE-001: Detects active usage of deprecated Twig templates.
 *
 * Scans all {% include %} directives across templates looking for references
 * to files marked with @deprecated. New code MUST use the replacement partial.
 *
 * Known deprecated templates:
 *   - _trust-bar.html.twig → _trust-strip.html.twig (TRUST-STRIP-001)
 *   - _landing-partner-logos.html.twig → _trust-strip.html.twig (TRUST-STRIP-001)
 *
 * Usage: php scripts/validation/validate-deprecated-template-usage.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$themeDir = "$root/web/themes/custom/ecosistema_jaraba_theme/templates";
$modulesDir = "$root/web/modules/custom";

$passes = 0;
$failures = [];
$warnings = [];

echo "=== DEPRECATED-TEMPLATE-USAGE-001: Deprecated Template Scanner ===\n\n";

// Step 1: Discover deprecated templates by scanning for @deprecated annotation.
$deprecatedTemplates = [];
$templateFiles = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($themeDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($templateFiles as $file) {
  if ($file->getExtension() !== 'twig') {
    continue;
  }
  $content = file_get_contents($file->getPathname());
  if (str_contains($content, '@deprecated')) {
    // Extract the replacement suggestion.
    $replacement = 'unknown';
    if (preg_match('/Use\s+(\S+\.html\.twig)\s+instead/i', $content, $m)) {
      $replacement = $m[1];
    }
    $basename = $file->getBasename();
    $deprecatedTemplates[$basename] = [
      'replacement' => $replacement,
      'path' => str_replace("$root/", '', $file->getPathname()),
    ];
  }
}

if (empty($deprecatedTemplates)) {
  echo "✅ No deprecated templates found — nothing to check.\n";
  exit(0);
}

echo "📋 Deprecated templates discovered:\n";
foreach ($deprecatedTemplates as $name => $info) {
  echo "  - $name → {$info['replacement']}\n";
}
echo "\n";

// Step 2: Scan ALL Twig files for {% include %} referencing deprecated templates.
$scanDirs = [$themeDir, $modulesDir];
$violations = [];

foreach ($scanDirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->getExtension() !== 'twig') {
      continue;
    }

    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    $relativePath = str_replace("$root/", '', $filePath);

    // Strip Twig comment blocks {# ... #} (multiline) before scanning.
    $contentNoComments = preg_replace('/\{#.*?#\}/s', '', $content);
    $lines = explode("\n", $contentNoComments);

    foreach ($lines as $lineNum => $line) {
      // Skip lines that are clearly documentation (PHP/Twig docblocks).
      if (preg_match('/^\s*\*/', $line)) {
        continue;
      }

      // Check for {% include %} or {% embed %} referencing deprecated templates.
      foreach ($deprecatedTemplates as $deprecated => $info) {
        // Skip self-references (the deprecated file itself).
        if (str_contains($relativePath, $deprecated)) {
          continue;
        }

        if (str_contains($line, $deprecated)) {
          $violations[] = [
            'file' => $relativePath,
            'line' => $lineNum + 1,
            'deprecated' => $deprecated,
            'replacement' => $info['replacement'],
            'context' => trim($line),
          ];
        }
      }
    }
  }
}

// Step 3: Report.
if (empty($violations)) {
  echo "✅ No active usage of deprecated templates found.\n";
  $passes++;
} else {
  echo "❌ " . count($violations) . " references to deprecated templates found:\n\n";
  foreach ($violations as $v) {
    echo "  {$v['file']}:{$v['line']}\n";
    echo "    Uses: {$v['deprecated']} → Replace with: {$v['replacement']}\n";
    echo "    Line: {$v['context']}\n\n";
    $failures[] = "{$v['file']}:{$v['line']} uses deprecated {$v['deprecated']}";
  }
}

echo "\n=== RESULT ===\n";

if (!empty($failures)) {
  echo "❌ DEPRECATED-TEMPLATE-USAGE-001: " . count($failures) . " violations. Migrate to replacement templates.\n";
  exit(1);
}

echo "✅ DEPRECATED-TEMPLATE-USAGE-001: All templates use current (non-deprecated) partials.\n";
exit(0);
