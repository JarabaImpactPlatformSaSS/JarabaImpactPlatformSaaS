<?php

/**
 * @file
 * Validator: API-ENDPOINT-JS-PARITY-001 — JS fetch URLs vs routing.yml routes.
 *
 * For each fetch()/XMLHttpRequest/Drupal.url() call in JS files of custom
 * modules, verifies that a matching route path exists in some routing.yml.
 *
 * Usage: php scripts/validation/validate-api-endpoint-js-parity.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$modulesDir = __DIR__ . '/../../web/modules/custom';

// Step 1: Collect ALL route paths from routing.yml files.
$allRoutePaths = [];
$routingFiles = glob($modulesDir . '/*/*.routing.yml');
foreach ($routingFiles as $routingFile) {
  $content = file_get_contents($routingFile);
  if (empty($content)) {
    continue;
  }
  // Extract path: values.
  if (preg_match_all("/^\s+path:\s*['\"]?([^'\"#\n]+)/m", $content, $matches)) {
    foreach ($matches[1] as $path) {
      $allRoutePaths[] = trim($path);
    }
  }
}

// Normalize route paths: replace {placeholder} with regex pattern.
$routePatterns = [];
foreach ($allRoutePaths as $path) {
  // Convert /api/v1/something/{id}/action to regex.
  // Use '#' as delimiter to avoid issues with '/' in paths.
  $pattern = preg_quote($path, '#');
  // Replace escaped braces \{...\} with a wildcard segment.
  $pattern = preg_replace('/\\\{[^}]+\\\}/', '[^/]+', $pattern);
  $routePatterns[] = [
    'path' => $path,
    'regex' => '#^' . $pattern . '$#',
  ];
}

// Step 2: Scan all JS files in custom modules for API calls.
$jsFiles = [];
$moduleDirs = glob($modulesDir . '/*/js');
foreach ($moduleDirs as $jsDir) {
  $jsFiles = array_merge($jsFiles, glob($jsDir . '/*.js'));
  // Also check subdirectories.
  $subDirs = glob($jsDir . '/*/');
  foreach ($subDirs as $subDir) {
    $jsFiles = array_merge($jsFiles, glob($subDir . '*.js'));
  }
}

// Also check theme JS.
$themeJsDir = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/js';
if (is_dir($themeJsDir)) {
  $jsFiles = array_merge($jsFiles, glob($themeJsDir . '/*.js'));
  $subDirs = glob($themeJsDir . '/*/');
  foreach ($subDirs as $subDir) {
    $jsFiles = array_merge($jsFiles, glob($subDir . '*.js'));
  }
}

$endpointResults = []; // Deduplicate by file+path.

foreach ($jsFiles as $jsFile) {
  $content = file_get_contents($jsFile);
  if (empty($content)) {
    continue;
  }
  $fileName = basename($jsFile);

  $apiPaths = [];

  // Pattern 1: fetch('...') or fetch("...") or fetch(`...`) with API-like paths.
  if (preg_match_all("/fetch\s*\(\s*['\"`]([^'\"`\$]+)['\"`]/", $content, $fetchMatches)) {
    foreach ($fetchMatches[1] as $url) {
      if (str_starts_with($url, '/')) {
        $apiPaths[] = $url;
      }
    }
  }

  // Pattern 2: fetch(baseUrl + '...') or fetch(url) where url is built from drupalSettings.
  // We look for string concatenation patterns building /api/ paths.
  if (preg_match_all("/['\"`](\/api\/[^'\"`\$]+)['\"`]/", $content, $apiMatches)) {
    foreach ($apiMatches[1] as $url) {
      $apiPaths[] = $url;
    }
  }

  // Pattern 3: Drupal.url('...') calls.
  if (preg_match_all("/Drupal\.url\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $drupalUrlMatches)) {
    foreach ($drupalUrlMatches[1] as $url) {
      // Drupal.url() paths don't start with / (Drupal adds base path).
      if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
      }
      $apiPaths[] = $url;
    }
  }

  // Pattern 4: XMLHttpRequest open('GET/POST', '...').
  if (preg_match_all("/\.open\s*\(\s*['\"][A-Z]+['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $xhrMatches)) {
    foreach ($xhrMatches[1] as $url) {
      if (str_starts_with($url, '/')) {
        $apiPaths[] = $url;
      }
    }
  }

  // Filter: only keep API-like paths (contain /api/ or module-specific endpoints).
  // Skip Drupal core paths, external URLs, anchors, etc.
  foreach ($apiPaths as $apiPath) {
    // Remove query strings.
    $cleanPath = preg_replace('/\?.*$/', '', $apiPath);
    // Remove trailing slashes.
    $cleanPath = rtrim($cleanPath, '/');

    // Skip non-API paths (Drupal core, session, etc.).
    $skipPatterns = [
      '/session/token',
      '/contextual/render',
      '/batch',
      '/history/',
      '/quickedit/',
      '/editor/',
      '/index.php',
      '/jsonapi/',
    ];
    $skip = FALSE;
    foreach ($skipPatterns as $skipPattern) {
      if (str_starts_with($cleanPath, $skipPattern)) {
        $skip = TRUE;
        break;
      }
    }
    if ($skip || empty($cleanPath) || $cleanPath === '/') {
      continue;
    }

    // Replace numeric segments with placeholder for matching (e.g., /api/v1/thing/123 → /api/v1/thing/{id}).
    $dedupeKey = $fileName . '::' . $cleanPath;
    if (isset($endpointResults[$dedupeKey])) {
      continue;
    }

    // Check if this path matches any routing.yml route.
    $matched = FALSE;
    $matchedRoute = '';
    foreach ($routePatterns as $rp) {
      if (preg_match($rp['regex'], $cleanPath)) {
        $matched = TRUE;
        $matchedRoute = $rp['path'];
        break;
      }
    }

    // Also try with numeric segments replaced by placeholders.
    if (!$matched) {
      $normalizedPath = preg_replace('/\/\d+/', '/{id}', $cleanPath);
      foreach ($routePatterns as $rp) {
        if (preg_match($rp['regex'], $normalizedPath)) {
          $matched = TRUE;
          $matchedRoute = $rp['path'];
          break;
        }
      }
    }

    // Try partial path matching (JS might build paths dynamically).
    if (!$matched) {
      foreach ($allRoutePaths as $routePath) {
        // Remove placeholders from route for base comparison.
        $routeBase = preg_replace('/\/\{[^}]+\}/', '', $routePath);
        $pathBase = preg_replace('/\/\d+/', '', $cleanPath);
        if (!empty($routeBase) && $routeBase === $pathBase) {
          $matched = TRUE;
          $matchedRoute = $routePath;
          break;
        }
      }
    }

    $endpointResults[$dedupeKey] = [
      'file' => $fileName,
      'path' => $cleanPath,
      'matched' => $matched,
      'route' => $matchedRoute,
    ];
  }
}

// Generate results.
foreach ($endpointResults as $result) {
  if ($result['matched']) {
    $passes[] = "{$result['file']} → {$result['path']} — route exists ({$result['route']})";
  }
  else {
    $errors[] = "{$result['file']} → {$result['path']} — NO route found";
  }
}

// RESULTS.
$total = count($errors) + count($passes);
echo "\n=== API-ENDPOINT-JS-PARITY-001 ===\n\n";
foreach ($passes as $msg) {
  echo "  ✅ $msg\n";
}
foreach ($errors as $msg) {
  echo "  ❌ $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total endpoints matched ---\n\n";
exit(empty($errors) ? 0 : 1);
