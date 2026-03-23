<?php

/**
 * @file
 * API-CONTRACT-001: Verify API endpoint response structure consistency.
 *
 * Checks:
 * 1. Controllers returning JsonResponse should declare ALLOWED_FIELDS or have
 *    structured responses (explicit array keys, not raw pass-through).
 * 2. API routes with POST/PATCH/PUT/DELETE methods have CSRF protection
 *    (_csrf_request_header_token or _csrf_token in requirements).
 * 3. Detects controllers that return raw data without explicit field whitelisting.
 *
 * Usage: php scripts/validation/validate-api-contract.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$errors = [];
$warnings = [];
$checkedRoutes = 0;
$checkedControllers = 0;

// ─────────────────────────────────────────────────────────────
// Step 1: Collect all routing.yml files and extract _controller routes.
// ─────────────────────────────────────────────────────────────

$routingFiles = array_merge(
  glob("$modulesDir/*/*.routing.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.routing.yml") ?: []
);

/**
 * Parse routing.yml to extract route definitions with _controller.
 *
 * Returns an array of routes with keys:
 *   - route_name: string
 *   - controller: string (class::method)
 *   - methods: array of HTTP methods (empty = all)
 *   - has_csrf: bool
 *   - file: string (source routing file)
 *
 * @param string $content
 *   Raw YAML content.
 * @param string $file
 *   File path for error reporting.
 *
 * @return array<int, array<string, mixed>>
 */
function parseRoutingYaml(string $content, string $file): array {
  $routes = [];
  $lines = explode("\n", $content);
  $currentRoute = NULL;
  $currentController = NULL;
  $currentMethods = [];
  $hasCsrf = FALSE;
  $inRequirements = FALSE;
  $inDefaults = FALSE;

  foreach ($lines as $line) {
    // Top-level route definition (no leading whitespace, ends with colon).
    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_.]*):/', $line, $m)) {
      // Save previous route.
      if ($currentRoute !== NULL && $currentController !== NULL) {
        $routes[] = [
          'route_name' => $currentRoute,
          'controller' => $currentController,
          'methods' => $currentMethods,
          'has_csrf' => $hasCsrf,
          'file' => $file,
        ];
      }
      $currentRoute = $m[1];
      $currentController = NULL;
      $currentMethods = [];
      $hasCsrf = FALSE;
      $inRequirements = FALSE;
      $inDefaults = FALSE;
      continue;
    }

    $trimmed = ltrim($line);

    // Detect section headers.
    if (preg_match('/^\s{2}defaults:\s*$/', $line)) {
      $inDefaults = TRUE;
      $inRequirements = FALSE;
      continue;
    }
    if (preg_match('/^\s{2}requirements:\s*$/', $line)) {
      $inRequirements = TRUE;
      $inDefaults = FALSE;
      continue;
    }
    if (preg_match('/^\s{2}(options|methods):\s*/', $line)) {
      $inDefaults = FALSE;
      $inRequirements = FALSE;
    }

    // Extract _controller from defaults section.
    if ($inDefaults && preg_match("/_controller:\s*['\"]?(.+?)['\"]?\s*$/", $trimmed, $m)) {
      $currentController = trim($m[1], "'\" ");
    }

    // Extract methods.
    if (preg_match('/^\s{2}methods:\s*\[(.+)\]/', $line, $m)) {
      $currentMethods = array_map('trim', explode(',', $m[1]));
    }

    // Detect CSRF protection in requirements.
    if ($inRequirements && preg_match('/_csrf_request_header_token|_csrf_token/', $trimmed)) {
      $hasCsrf = TRUE;
    }
  }

  // Don't forget the last route.
  if ($currentRoute !== NULL && $currentController !== NULL) {
    $routes[] = [
      'route_name' => $currentRoute,
      'controller' => $currentController,
      'methods' => $currentMethods,
      'has_csrf' => $hasCsrf,
      'file' => $file,
    ];
  }

  return $routes;
}

/**
 * Resolve a Drupal namespaced class to a file path.
 *
 * @param string $class
 *   Fully qualified class name (e.g., \Drupal\module\Controller\FooController).
 * @param string $modulesDir
 *   Path to web/modules/custom.
 *
 * @return string|null
 *   File path or NULL if not resolvable.
 */
function resolveControllerFile(string $class, string $modulesDir): ?string {
  $class = ltrim($class, '\\');

  // Must be a Drupal\ namespace.
  if (!str_starts_with($class, 'Drupal\\')) {
    return NULL;
  }

  $parts = explode('\\', $class);
  // Drupal\module_name\rest\of\path => module_name, rest/of/path.
  if (count($parts) < 3) {
    return NULL;
  }

  $moduleName = $parts[1];
  $classPath = implode('/', array_slice($parts, 2)) . '.php';

  // Try direct module path.
  $file = "$modulesDir/$moduleName/src/$classPath";
  if (file_exists($file)) {
    return $file;
  }

  // Try as submodule: look for parent/modules/submodule pattern.
  $parentDirs = glob("$modulesDir/*/modules/$moduleName") ?: [];
  foreach ($parentDirs as $parentDir) {
    $file = "$parentDir/src/$classPath";
    if (file_exists($file)) {
      return $file;
    }
  }

  return NULL;
}

/**
 * Check if a controller method returns JsonResponse.
 *
 * Scans the PHP file for:
 * - use statement importing JsonResponse
 * - The specific method returning new JsonResponse
 *
 * @param string $filePath
 *   Path to the PHP file.
 * @param string $method
 *   Method name to check.
 *
 * @return bool
 *   TRUE if the method likely returns JsonResponse.
 */
function methodReturnsJsonResponse(string $filePath, string $method): bool {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return FALSE;
  }

  // Must import or reference JsonResponse.
  if (
    strpos($content, 'JsonResponse') === FALSE
  ) {
    return FALSE;
  }

  // Look for the method and check if it contains JsonResponse usage.
  // We use a simple heuristic: find the method, then scan until the next
  // method or class closing brace for JsonResponse references.
  $pattern = '/function\s+' . preg_quote($method, '/') . '\s*\(/';
  if (!preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
    return FALSE;
  }

  $methodStart = $m[0][1];

  // Find the end of this method (next public/protected/private function or closing brace).
  $rest = substr($content, $methodStart);

  // Extract roughly the method body (until next function declaration at same indent).
  $nextMethod = preg_match('/\n\s{2}(?:public|protected|private)\s+function\s/', $rest, $nm, PREG_OFFSET_CAPTURE, 50);
  if ($nextMethod) {
    $methodBody = substr($rest, 0, $nm[0][1]);
  }
  else {
    $methodBody = $rest;
  }

  return strpos($methodBody, 'JsonResponse') !== FALSE;
}

/**
 * Check if a controller class has ALLOWED_FIELDS or structured response pattern.
 *
 * Returns one of:
 * - 'allowed_fields' — has ALLOWED_FIELDS or ALLOWED_*_FIELDS constant
 * - 'structured'     — method uses explicit array keys in JsonResponse
 * - 'docblock'       — method has @return with structure documentation
 * - 'unstructured'   — none of the above (potential violation)
 *
 * @param string $filePath
 *   Path to the PHP file.
 * @param string $method
 *   Method name to check.
 *
 * @return string
 *   Category of the response pattern.
 */
function classifyResponsePattern(string $filePath, string $method): string {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return 'unstructured';
  }

  // Check 1: Class has ALLOWED_FIELDS or ALLOWED_*_FIELDS constants.
  if (preg_match('/\bconst\s+ALLOWED_\w*FIELDS\b/', $content)) {
    return 'allowed_fields';
  }

  // Find the method body for more specific checks.
  $pattern = '/function\s+' . preg_quote($method, '/') . '\s*\(/';
  if (!preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
    return 'unstructured';
  }

  $methodStart = $m[0][1];
  $rest = substr($content, $methodStart);

  // Extract the method body.
  $nextMethod = preg_match('/\n\s{2}(?:public|protected|private)\s+function\s/', $rest, $nm, PREG_OFFSET_CAPTURE, 50);
  if ($nextMethod) {
    $methodBody = substr($rest, 0, $nm[0][1]);
  }
  else {
    $methodBody = $rest;
  }

  // Check 2: JsonResponse with explicit array keys (structured response).
  // Look for patterns like: new JsonResponse(['key' => ..., 'key2' => ...])
  // or $data = ['key' => ...]; ... new JsonResponse($data)
  if (preg_match('/new\s+JsonResponse\s*\(\s*\[/', $methodBody)) {
    return 'structured';
  }

  // Check for variable assignment with array then passed to JsonResponse.
  if (
    preg_match('/\$\w+\s*=\s*\[\s*[\'"]/', $methodBody) &&
    preg_match('/new\s+JsonResponse\s*\(\s*\$/', $methodBody)
  ) {
    return 'structured';
  }

  // Check 3: Docblock with @return annotation mentioning JsonResponse and structure.
  // Look backwards from method for docblock.
  $beforeMethod = substr($content, max(0, $methodStart - 500), min(500, $methodStart));
  if (preg_match('/\/\*\*.*?@return\s+.*JsonResponse.*?\*\//s', $beforeMethod)) {
    return 'docblock';
  }

  return 'unstructured';
}

// ─────────────────────────────────────────────────────────────
// Step 2: Process all routing files.
// ─────────────────────────────────────────────────────────────

$allRoutes = [];
foreach ($routingFiles as $routingFile) {
  $content = file_get_contents($routingFile);
  if ($content === FALSE) {
    continue;
  }
  $routes = parseRoutingYaml($content, $routingFile);
  $allRoutes = array_merge($allRoutes, $routes);
}

// ─────────────────────────────────────────────────────────────
// Step 3: Analyze each route's controller.
// ─────────────────────────────────────────────────────────────

// Track which controller files we've already classified at class level.
$classifiedFiles = [];

// HTTP methods that modify state and need CSRF protection.
$stateMutatingMethods = ['POST', 'PATCH', 'PUT', 'DELETE'];

foreach ($allRoutes as $route) {
  $checkedRoutes++;

  $controllerSpec = $route['controller'];

  // Split class::method.
  if (!str_contains($controllerSpec, '::')) {
    continue;
  }

  [$className, $methodName] = explode('::', $controllerSpec, 2);
  $filePath = resolveControllerFile($className, $modulesDir);

  if ($filePath === NULL || !file_exists($filePath)) {
    continue;
  }

  // Check if this method returns JsonResponse.
  if (!methodReturnsJsonResponse($filePath, $methodName)) {
    continue;
  }

  $checkedControllers++;
  $shortFile = str_replace($projectRoot . '/', '', $filePath);

  // Check A: CSRF protection for state-mutating methods.
  $routeMethods = array_map('strtoupper', $route['methods']);
  $isMutating = !empty(array_intersect($routeMethods, $stateMutatingMethods));

  if ($isMutating && !$route['has_csrf']) {
    $methodsStr = implode('/', $routeMethods);
    $errors[] = sprintf(
      '[CSRF] Route "%s" (%s) returns JsonResponse without CSRF protection — %s',
      $route['route_name'],
      $methodsStr,
      str_replace($projectRoot . '/', '', $route['file'])
    );
  }

  // Check B: Response structure (ALLOWED_FIELDS or structured response).
  $pattern = classifyResponsePattern($filePath, $methodName);

  if ($pattern === 'unstructured') {
    $errors[] = sprintf(
      '[WHITELIST] %s::%s returns JsonResponse without ALLOWED_FIELDS, explicit array keys, or @return docblock — %s',
      basename($filePath, '.php'),
      $methodName,
      $shortFile
    );
  }
}

// ─────────────────────────────────────────────────────────────
// Step 4: Output results.
// ─────────────────────────────────────────────────────────────

if (count($errors) > 0) {
  fwrite(STDERR, "\n");
  fwrite(STDERR, "API-CONTRACT-001: " . count($errors) . " violation(s) found\n");
  fwrite(STDERR, str_repeat('─', 60) . "\n");
  foreach ($errors as $error) {
    fwrite(STDERR, "  [ERROR] $error\n");
  }
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Checked: $checkedRoutes routes, $checkedControllers returning JsonResponse\n");
  exit(1);
}

echo "[OK] API-CONTRACT-001: $checkedControllers API endpoints verified ($checkedRoutes routes scanned). All have structured responses and CSRF where needed.\n";
exit(0);
