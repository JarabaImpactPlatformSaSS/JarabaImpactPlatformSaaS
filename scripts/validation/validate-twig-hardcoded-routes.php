<?php

/**
 * @file
 * TWIG-HARDCODED-ROUTES-001: Detect hardcoded route paths in Twig templates.
 *
 * Catches href attributes that build URLs from raw paths instead of using
 * resolved variables from PHP (Url::fromRoute()). In a multilingual site
 * with language prefix (/es/), hardcoded paths cause 404 errors.
 *
 * Detects patterns like:
 * - href="/caso-de-exito/slug" (missing language prefix)
 * - href="{{ prefix }}/caso-de-exito/slug" (built in Twig, not PHP)
 * - href="/es/caso-de-exito/slug" (hardcoded /es/)
 * - href="/planes/vertical" (should use Url::fromRoute)
 * - href="/registro/vertical" (should use Url::fromRoute)
 *
 * Allowed patterns:
 * - href="{{ variable }}" (resolved URL from PHP)
 * - href="{{ path('route.name', {}) }}" (Twig path() function)
 * - href="{{ url('route.name', {}) }}" (Twig url() function)
 * - href="#anchor" (page anchors)
 * - href="https://..." (external URLs)
 * - href="mailto:..." (email links)
 * - href="tel:..." (phone links)
 * - href="javascript:void(0)" (JS triggers — separate XSS check)
 *
 * Usage: php scripts/validation/validate-twig-hardcoded-routes.php [dir]
 * Exit: 0 = pass, 1 = violations found
 */

declare(strict_types=1);

// Directory to scan.
$baseDir = $argv[1] ?? 'web';

if (!is_dir($baseDir)) {
  echo "Directory not found: $baseDir\n";
  exit(1);
}

// Route path segments that indicate internal Drupal routes.
// These should ALWAYS be resolved in PHP, not built in Twig.
$routePathPatterns = [
  'caso-de-exito',
  'casos-de-exito',
  '/planes/',
  '/registro/',
  '/diagnostico',
  '/checkout/',
  '/mi-panel',
  '/mi-analytics',
  '/mi-certificacion',
];

// Build regex: href with a literal path containing route segments.
// This catches both direct paths and Twig concatenation patterns.
$routeSegmentRegex = implode('|', array_map(fn($p) => preg_quote($p, '/'), $routePathPatterns));

// Patterns to detect.
$patterns = [
  // Pattern 1: href="/path" — literal path without any Twig variable.
  'literal_path' => '/href\s*=\s*"\/(' . $routeSegmentRegex . ')/',

  // Pattern 2: href="/es/path" — hardcoded language prefix.
  'hardcoded_es' => '/href\s*=\s*"\/es\/(' . $routeSegmentRegex . ')/',

  // Pattern 3: href="{{ var }}/path/{{ slug }}" — Twig var + path + dynamic slug.
  // This is the most dangerous: {{ language_prefix }}/caso-de-exito/{{ slug }}
  // Static nav links like {{ lp }}/casos-de-exito are covered by I18N-NAVPREFIX-001.
  'twig_concat_slug' => '/href\s*=\s*"\{\{[^}]+\}\}\s*\/(' . $routeSegmentRegex . ')\/\{\{/',
];

// Allowed patterns — these are proper URL resolution methods.
$allowedPatterns = [
  '/href\s*=\s*"\{\{\s*[\w\[\]\'".]+\s*\}\}"/',  // {{ variable }} alone (resolved URL)
  '/href\s*=\s*"\{\{\s*path\(/',                     // {{ path('route') }}
  '/href\s*=\s*"\{\{\s*url\(/',                       // {{ url('route') }}
];

// Directories to skip.
$skipDirs = ['node_modules', 'vendor', '.git', 'tests', 'docs'];

$violations = [];
$filesChecked = 0;

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'twig') {
    continue;
  }

  $path = $file->getPathname();

  // Skip excluded directories.
  foreach ($skipDirs as $skipDir) {
    if (str_contains($path, '/' . $skipDir . '/')) {
      continue 2;
    }
  }

  $filesChecked++;
  $content = file_get_contents($path);
  $lines = explode("\n", $content);

  foreach ($lines as $lineNum => $line) {
    // Skip Twig comments.
    if (preg_match('/^\s*\{#/', $line)) {
      continue;
    }
    // Skip HTML comments.
    if (preg_match('/^\s*<!--/', $line)) {
      continue;
    }
    // Skip lines that are doc comments (starting with * ).
    if (preg_match('/^\s*\*/', $line)) {
      continue;
    }

    foreach ($patterns as $patternName => $regex) {
      if (preg_match($regex, $line, $matches)) {
        // Check if the full href is actually an allowed pattern.
        $isAllowed = false;
        foreach ($allowedPatterns as $allowedRegex) {
          if (preg_match($allowedRegex, $line)) {
            $isAllowed = true;
            break;
          }
        }

        if (!$isAllowed) {
          $relPath = str_replace($baseDir . '/', '', $path);
          $violations[] = [
            'file' => $relPath,
            'line' => $lineNum + 1,
            'pattern' => $patternName,
            'content' => trim($line),
          ];
        }
      }
    }
  }
}

// Output results.
echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  TWIG-HARDCODED-ROUTES-001                              ║\033[0m\n";
echo "\033[36m║  Detect hardcoded route paths in Twig templates         ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

echo "Archivos analizados: $filesChecked\n";

if (empty($violations)) {
  echo "\n\033[32m✔ PASS — Sin rutas hardcodeadas en templates Twig\033[0m\n";
  exit(0);
}

echo "\n\033[31m✘ FAIL — " . count($violations) . " violaciones encontradas:\033[0m\n\n";

foreach ($violations as $v) {
  $patternLabel = match ($v['pattern']) {
    'literal_path' => 'Ruta literal sin prefijo idioma',
    'hardcoded_es' => 'Prefijo /es/ hardcodeado',
    'twig_concat_slug' => 'URL con slug dinamico construida en Twig (deberia resolverse en PHP)',
    default => $v['pattern'],
  };

  echo "  \033[33m{$v['file']}:{$v['line']}\033[0m\n";
  echo "    Patron: $patternLabel\n";
  echo "    Linea: {$v['content']}\n\n";
}

echo "Fix: Resolver URLs en PHP con Url::fromRoute() y pasar como strings al template.\n";
echo "Referencia: TWIG-URL-RESOLVE-PHP-001 en DIRECTRICES + ROUTE-LANGPREFIX-001 en CLAUDE.md\n";

exit(1);
