<?php

declare(strict_types=1);

/**
 * @file
 * ROUTE-PERMISSION-AUDIT-001: Detect routes lacking access control in routing.yml.
 *
 * Every route in web/modules/custom/ MUST have a requirements: section with at
 * least one access check (_permission, _access, _custom_access, _role,
 * _entity_access, _csrf_request_header_token).
 *
 * FLAGS:
 * - ERROR:   Route with NO requirements section at all.
 * - WARNING: Route with only _user_is_logged_in that handles tenant data
 *            (path contains /api/ or /admin/).
 * - WARNING: Route with _access: 'TRUE' and path suggesting private data
 *            (/api/, /admin/, /tenant/).
 *
 * WHITELIST: Known intentionally public routes are skipped.
 *
 * Usage: php scripts/validation/validate-route-permissions.php
 * Exit:  0 = PASS (no errors, warnings OK), 1 = FAIL (errors found)
 *
 * @see AUDIT-SEC-002
 */

$root = dirname(__DIR__, 2);
$modulesDir = $root . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

// ── Header ──────────────────────────────────────────────────────────────

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  ROUTE-PERMISSION-AUDIT-001                             ║\033[0m\n";
echo "\033[36m║  Route Access Control Validator                          ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── Configuration ───────────────────────────────────────────────────────

// Public path prefixes — routes starting with these are intentionally public.
$publicPathPrefixes = [
  '/test-vertical',
  '/planes',
  '/registro',
  '/verificar',
  '/contacto',
  '/es/test-vertical',
  '/es/planes',
  '/es/registro',
  '/es/verificar',
  '/es/contacto',
];

// Path suffixes that indicate intentionally public routes.
$publicPathSuffixes = [
  '/callback',
];

// Route name prefixes to skip entirely.
$skipRouteNamePrefixes = [
  'system.',
];

// Route name patterns to skip.
$skipRouteNamePatterns = [
  '/^entity\.[a-z_]+\.canonical$/',
];

// Known valid access keys in requirements section.
$accessKeys = [
  '_permission',
  '_access',
  '_custom_access',
  '_role',
  '_entity_access',
  '_csrf_request_header_token',
  '_user_is_logged_in',
  '_entity_create_access',
  '_entity_create_any_access',
  '_format',
  '_module_dependencies',
];

// Path segments that suggest private/tenant data.
$privatePathSegments = ['/api/', '/admin/', '/tenant/'];

// ── Collect routing files ───────────────────────────────────────────────

$routingFiles = array_merge(
  glob("$modulesDir/*/*.routing.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.routing.yml") ?: []
);

$errors = [];
$warnings = [];
$passes = [];
$totalRoutes = 0;
$totalFiles = count($routingFiles);

// ── Parse routing YAML (line-based, no Symfony dependency) ──────────────

/**
 * Parse a routing.yml file into an array of routes.
 *
 * Returns array of [name => string, path => string, requirements => array, line => int].
 */
function parseRoutingFile(string $filePath): array {
  $content = file_get_contents($filePath);
  if ($content === false) {
    return [];
  }

  // Normalize line endings (handle CRLF from Windows/mixed files).
  $content = str_replace("\r\n", "\n", $content);
  $content = str_replace("\r", "\n", $content);
  $lines = explode("\n", $content);
  $routes = [];
  $currentRoute = null;
  $currentPath = null;
  $currentLine = 0;
  $inRequirements = false;
  $requirements = [];
  $indent = 0;

  foreach ($lines as $lineNum => $line) {
    // Skip empty lines and comments.
    if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
      continue;
    }

    // Route name: top-level key (no leading whitespace, ends with :).
    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_.-]+):\s*$/', $line, $m)) {
      // Save previous route if exists.
      if ($currentRoute !== null) {
        $routes[] = [
          'name' => $currentRoute,
          'path' => $currentPath ?? '',
          'requirements' => $requirements,
          'line' => $currentLine,
        ];
      }

      $currentRoute = $m[1];
      $currentPath = null;
      $currentLine = $lineNum + 1;
      $inRequirements = false;
      $requirements = [];
      continue;
    }

    if ($currentRoute === null) {
      continue;
    }

    $trimmed = ltrim($line);

    // Detect path: key.
    if (preg_match('/^\s+path:\s*[\'"]?([^\'"#]+)[\'"]?\s*$/', $line, $m)) {
      $currentPath = trim($m[1]);
      $inRequirements = false;
      continue;
    }

    // Detect requirements: section start.
    if (preg_match('/^(\s+)requirements:\s*$/', $line, $m)) {
      $inRequirements = true;
      $indent = strlen($m[1]);
      continue;
    }

    // If we're inside requirements, collect keys.
    if ($inRequirements) {
      // Check if we've left the requirements section (less or equal indent, non-blank).
      $currentIndent = strlen($line) - strlen(ltrim($line));
      if ($currentIndent <= $indent && !str_starts_with($trimmed, '#')) {
        $inRequirements = false;
        // Fall through to process this line normally.
      }
      else {
        // Parse requirement key-value.
        if (preg_match('/^\s+(_[a-zA-Z_]+)\s*:\s*(.*)$/', $line, $m)) {
          $requirements[$m[1]] = trim($m[2], " '\"");
        }
        continue;
      }
    }

    // Detect other top-level route keys (options:, defaults:, methods:) — skip.
    // If a new top-level route starts, it would match the first regex above.
  }

  // Save last route.
  if ($currentRoute !== null) {
    $routes[] = [
      'name' => $currentRoute,
      'path' => $currentPath ?? '',
      'requirements' => $requirements,
      'line' => $currentLine,
    ];
  }

  return $routes;
}

/**
 * Check if a route should be whitelisted (intentionally public).
 */
function isWhitelisted(
  string $routeName,
  string $path,
  array $requirements,
  array $publicPathPrefixes,
  array $publicPathSuffixes,
  array $skipRouteNamePrefixes,
  array $skipRouteNamePatterns,
): bool {
  // Skip by route name prefix.
  foreach ($skipRouteNamePrefixes as $prefix) {
    if (str_starts_with($routeName, $prefix)) {
      return true;
    }
  }

  // Skip by route name pattern.
  foreach ($skipRouteNamePatterns as $pattern) {
    if (preg_match($pattern, $routeName)) {
      return true;
    }
  }

  // Skip by public path prefix.
  foreach ($publicPathPrefixes as $prefix) {
    if (str_starts_with($path, $prefix)) {
      return true;
    }
  }

  // Skip by public path suffix.
  foreach ($publicPathSuffixes as $suffix) {
    if (str_ends_with($path, $suffix)) {
      return true;
    }
  }

  // Routes with CSRF token protection are OK (API routes).
  if (isset($requirements['_csrf_request_header_token'])) {
    return true;
  }

  return false;
}

/**
 * Check if a path suggests private/tenant data.
 */
function pathSuggestsPrivateData(string $path, array $privatePathSegments): bool {
  foreach ($privatePathSegments as $segment) {
    if (str_contains($path, $segment)) {
      return true;
    }
  }
  return false;
}

// ── Main scan ───────────────────────────────────────────────────────────

foreach ($routingFiles as $file) {
  $routes = parseRoutingFile($file);
  $relFile = str_replace($root . '/', '', $file);

  foreach ($routes as $route) {
    $totalRoutes++;

    $routeName = $route['name'];
    $path = $route['path'];
    $reqs = $route['requirements'];
    $line = $route['line'];

    // Skip whitelisted routes.
    if (isWhitelisted($routeName, $path, $reqs, $publicPathPrefixes, $publicPathSuffixes, $skipRouteNamePrefixes, $skipRouteNamePatterns)) {
      continue;
    }

    // CHECK 1: No requirements section at all.
    if (empty($reqs)) {
      $errors[] = "$relFile:$line — Route '$routeName' (path: $path) has NO requirements section. Add _permission, _access, or _custom_access.";
      continue;
    }

    // Determine if any real access check is present.
    $hasRealAccessCheck = false;
    $hasOnlyLoggedIn = false;
    $hasPublicAccess = false;

    // Known Drupal requirement keys that are NOT access checks.
    $nonAccessKeys = ['_format', '_method', '_maintenance_access', '_module_dependencies'];

    foreach ($reqs as $key => $value) {
      // Standard Drupal access checks.
      if (in_array($key, ['_permission', '_custom_access', '_role', '_entity_access', '_entity_create_access', '_entity_create_any_access'], true)) {
        $hasRealAccessCheck = true;
      }
      // Custom access checkers (e.g., _avatar_access) — any _*_access key
      // registered via tagged services is a valid access check in Drupal.
      if (str_starts_with($key, '_') && str_ends_with($key, '_access')
        && $key !== '_access' && $key !== '_entity_access'
        && !in_array($key, $nonAccessKeys, true)) {
        $hasRealAccessCheck = true;
      }
      if ($key === '_access' && strtolower(trim($value)) === 'true') {
        $hasPublicAccess = true;
      }
      if ($key === '_user_is_logged_in' && strtolower(trim($value)) === 'true') {
        $hasOnlyLoggedIn = true;
      }
    }

    // If there's a real access check, the route is properly protected.
    if ($hasRealAccessCheck) {
      continue;
    }

    // CHECK 2: _access: 'TRUE' on paths suggesting private data.
    if ($hasPublicAccess && pathSuggestsPrivateData($path, $privatePathSegments)) {
      $warnings[] = "$relFile:$line — Route '$routeName' (path: $path) uses _access: 'TRUE' but path suggests private data. Consider using _permission instead.";
      continue;
    }

    // CHECK 3: Only _user_is_logged_in on paths suggesting private/admin data.
    if ($hasOnlyLoggedIn && !$hasRealAccessCheck && pathSuggestsPrivateData($path, $privatePathSegments)) {
      $warnings[] = "$relFile:$line — Route '$routeName' (path: $path) uses only _user_is_logged_in for a path with sensitive data. Consider adding _permission for tenant isolation (AUDIT-SEC-002).";
      continue;
    }

    // CHECK 4: Only _user_is_logged_in with no real access check (not on private path) — acceptable but note it.
    if ($hasOnlyLoggedIn && !$hasRealAccessCheck && !$hasPublicAccess) {
      // This is acceptable — user must be logged in.
      continue;
    }

    // CHECK 5: _access: 'TRUE' on non-private path — intentionally public, OK.
    if ($hasPublicAccess) {
      continue;
    }

    // If we reach here, the requirements section exists but has no recognized access check.
    // This could be routes with only _format or _method requirements.
    $reqKeys = implode(', ', array_keys($reqs));
    $errors[] = "$relFile:$line — Route '$routeName' (path: $path) has requirements ($reqKeys) but NO access check. Add _permission, _access, or _custom_access.";
  }
}

// ── Summary passes ──────────────────────────────────────────────────────

$cleanRoutes = $totalRoutes - count($errors) - count($warnings);
if (empty($errors) && empty($warnings)) {
  $passes[] = "All $totalRoutes routes across $totalFiles files have proper access control";
}
else {
  $passes[] = "$cleanRoutes/$totalRoutes routes are properly protected";
}

// ── Report ──────────────────────────────────────────────────────────────

echo "Scanned: $totalFiles routing files, $totalRoutes routes\n\n";

foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}
foreach ($warnings as $w) {
  echo "  \033[33m⚠\033[0m $w\n";
}
foreach ($errors as $e) {
  echo "  \033[31m✗\033[0m $e\n";
}

$totalChecks = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$totalChecks PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

// Errors block, warnings do not.
exit(empty($errors) ? 0 : 1);
