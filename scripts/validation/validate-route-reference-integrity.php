#!/usr/bin/env php
<?php

/**
 * @file
 * ROUTE-REFERENCE-INTEGRITY-001: Verifica que Url::fromRoute() en codigo PHP
 * referencia rutas que existen en routing.yml.
 *
 * Excluye:
 * - Rutas de Drupal core (entity.*, system.*, user.*, etc.)
 * - Rutas en strings de try-catch (ya protegidas)
 * - Rutas en comentarios
 *
 * Exit codes: 0 = OK, 1 = broken route references found
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$modulesDir = $root . '/web/modules/custom';

// Step 1: Collect all defined routes from routing.yml files.
$definedRoutes = [];
$routingFiles = glob($modulesDir . '/*/*.routing.yml');
foreach ($routingFiles as $file) {
  $content = file_get_contents($file);
  // Parse route names (lines starting with non-space, ending with :)
  if (preg_match_all('/^([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/m', $content, $matches)) {
    foreach ($matches[1] as $route) {
      $definedRoutes[$route] = basename(dirname($file));
    }
  }
}

echo "Found " . count($definedRoutes) . " custom routes in " . count($routingFiles) . " routing files\n";

// Step 2: Find Url::fromRoute() calls in PHP files.
$errors = 0;
$checked = 0;
$phpFiles = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

// Patterns for core/contrib routes to skip.
$corePatterns = [
  '/^entity\./',
  '/^system\./',
  '/^user\./',
  '/^</',  // <front>, <current>, <none>
  '/^view\./',
  '/^node\./',
  '/^commerce_/',
  '/^domain\./',
  '/^group\./',
  '/^content_translation\./',
  '/^field_ui\./',
  '/^jsonapi\./',
  '/^rest\./',
  '/^locale\./',
  '/^language\./',
  '/^config\./',
  '/^block\./',
  '/^taxonomy\./',
  '/^media\./',
  '/^file\./',
  '/^search_api\./',
  '/^drupal\./',
];

foreach ($phpFiles as $file) {
  if ($file->getExtension() !== 'php') {
    continue;
  }
  // Skip test files.
  if (strpos($file->getPathname(), '/tests/') !== false) {
    continue;
  }
  // Skip vendor.
  if (strpos($file->getPathname(), '/vendor/') !== false) {
    continue;
  }

  $content = file_get_contents($file->getPathname());

  // Find Url::fromRoute('route.name') patterns.
  if (preg_match_all("/Url::fromRoute\(\s*['\"]([^'\"]+)['\"]/", $content, $matches, PREG_OFFSET_CAPTURE)) {
    foreach ($matches[1] as [$routeName, $offset]) {
      $checked++;

      // Skip core/contrib routes.
      $isCore = false;
      foreach ($corePatterns as $pattern) {
        if (preg_match($pattern, $routeName)) {
          $isCore = true;
          break;
        }
      }
      if ($isCore) {
        continue;
      }

      // Check if route is in try-catch block (already protected).
      $linesBefore = substr($content, max(0, $offset - 500), 500);
      if (preg_match('/try\s*\{[^}]*$/s', $linesBefore)) {
        continue;
      }

      // Check if route exists.
      if (!isset($definedRoutes[$routeName])) {
        $relPath = str_replace($root . '/', '', $file->getPathname());
        $line = substr_count(substr($content, 0, $offset), "\n") + 1;
        echo "ERROR: $relPath:$line — Url::fromRoute('$routeName') not found in any routing.yml\n";
        $errors++;
      }
    }
  }
}

echo "Checked $checked Url::fromRoute() calls\n";

if ($errors > 0) {
  echo "FAIL: $errors broken route references found\n";
  exit(1);
}

echo "PASS: ROUTE-REFERENCE-INTEGRITY-001 — all route references valid\n";
exit(0);
