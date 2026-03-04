<?php

/**
 * @file
 * ROUTE-CTRL-001: Validate routing.yml controller/form references.
 *
 * For every route with _controller or _form, verifies:
 * 1. The referenced PHP class file exists (PSR-4 resolution)
 * 2. The referenced method exists in the class (for _controller)
 * 3. The referenced class exists (for _form)
 *
 * Usage: php scripts/validation/validate-routing.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$deprecatedModules = ['jaraba_blog'];

// Custom module namespace prefixes — only validate these.
// Core/contrib classes (Drupal\Core\*, Drupal\system\*, etc.) are trusted.
$customNamespaces = ['jaraba_', 'ecosistema_'];

/**
 * Check if a FQCN belongs to a custom module (not core/contrib).
 */
function isCustomClass(string $fqcn, array $customNamespaces): bool {
  if (!str_starts_with($fqcn, 'Drupal\\')) {
    return FALSE;
  }
  $parts = explode('\\', $fqcn);
  if (count($parts) < 3) {
    return FALSE;
  }
  $moduleName = $parts[1];
  foreach ($customNamespaces as $prefix) {
    if (str_starts_with($moduleName, $prefix)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Resolve a Drupal namespace to a file path.
 *
 * Drupal\module_name\Sub\Class -> web/modules/custom/module_name/src/Sub/Class.php
 * Also checks submodule paths: module_name/modules/sub_module/src/...
 */
function resolveClassPath(string $fqcn, string $projectRoot): ?string {
  if (!str_starts_with($fqcn, 'Drupal\\')) {
    return NULL;
  }

  $parts = explode('\\', $fqcn);
  // $parts[0] = 'Drupal', $parts[1] = module_name, rest = path
  if (count($parts) < 3) {
    return NULL;
  }

  $moduleName = $parts[1];
  $classPath = implode('/', array_slice($parts, 2)) . '.php';

  // Try direct path first.
  $directPath = "$projectRoot/web/modules/custom/$moduleName/src/$classPath";
  if (file_exists($directPath)) {
    return $directPath;
  }

  // Try as a submodule: scan parent modules.
  $customDir = "$projectRoot/web/modules/custom";
  $parentDirs = glob("$customDir/*/modules/$moduleName", GLOB_ONLYDIR);
  foreach ($parentDirs as $parentDir) {
    $subPath = "$parentDir/src/$classPath";
    if (file_exists($subPath)) {
      return $subPath;
    }
  }

  return NULL;
}

/**
 * Check if a method exists in a PHP file (via regex, no autoload needed).
 */
function methodExistsInFile(string $filePath, string $methodName): bool {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return FALSE;
  }

  // Match public/protected/private [static] function methodName(
  $pattern = '/\b(?:public|protected|private)\s+(?:static\s+)?function\s+' . preg_quote($methodName, '/') . '\s*\(/';
  return (bool) preg_match($pattern, $content);
}

/**
 * Parse a simple YAML file for routing definitions.
 *
 * Returns array of route definitions with _controller, _form, _title_callback.
 */
function parseRoutingYml(string $filePath): array {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return [];
  }

  $routes = [];
  $currentRoute = NULL;
  $inDefaults = FALSE;

  foreach (explode("\n", $content) as $lineNum => $line) {
    // Skip comments and empty lines.
    if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
      continue;
    }

    // Top-level route name (no leading whitespace, ends with :).
    if (!str_starts_with($line, ' ') && !str_starts_with($line, "\t") && str_contains($line, ':')) {
      $routeName = trim(explode(':', $line, 2)[0]);
      if ($routeName !== '' && !str_starts_with($routeName, '#')) {
        $currentRoute = $routeName;
        $routes[$currentRoute] = ['line' => $lineNum + 1, 'file' => $filePath];
        $inDefaults = FALSE;
      }
      continue;
    }

    if ($currentRoute === NULL) {
      continue;
    }

    $trimmed = trim($line);

    if ($trimmed === 'defaults:') {
      $inDefaults = TRUE;
      continue;
    }

    // Detect leaving defaults section.
    if (preg_match('/^  [a-z]/', $line) && $trimmed !== '') {
      $inDefaults = FALSE;
    }

    if (!$inDefaults) {
      continue;
    }

    // Parse _controller, _form, _title_callback.
    if (preg_match('/^\s+_controller:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $m)) {
      $routes[$currentRoute]['_controller'] = trim($m[1], "'\" ");
    }
    if (preg_match('/^\s+_form:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $m)) {
      $routes[$currentRoute]['_form'] = trim($m[1], "'\" ");
    }
    if (preg_match('/^\s+_title_callback:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $m)) {
      $routes[$currentRoute]['_title_callback'] = trim($m[1], "'\" ");
    }
  }

  return $routes;
}

// Find all routing.yml files.
$routingFiles = array_merge(
  glob("$modulesDir/*/*.routing.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.routing.yml") ?: []
);

$errors = [];
$checkedRoutes = 0;

foreach ($routingFiles as $routingFile) {
  // Extract module name to check deprecated.
  $relativePath = str_replace($projectRoot . '/', '', $routingFile);
  $pathParts = explode('/', $relativePath);
  // web/modules/custom/MODULE_NAME/...
  $moduleName = $pathParts[3] ?? '';
  if (in_array($moduleName, $deprecatedModules, TRUE)) {
    continue;
  }
  // Also check submodule name.
  if (count($pathParts) >= 6 && $pathParts[4] === 'modules') {
    $subModuleName = $pathParts[5] ?? '';
    if (in_array($subModuleName, $deprecatedModules, TRUE)) {
      continue;
    }
  }

  $routes = parseRoutingYml($routingFile);

  foreach ($routes as $routeName => $routeInfo) {
    // Check _controller: '\Namespace\Class::method'
    if (!empty($routeInfo['_controller'])) {
      $controller = $routeInfo['_controller'];

      if (str_contains($controller, '::')) {
        [$fqcn, $method] = explode('::', $controller, 2);
        $fqcn = ltrim($fqcn, '\\');

        // Only validate custom module classes (skip core/contrib).
        if (isCustomClass($fqcn, $customNamespaces)) {
          $checkedRoutes++;
          $classFile = resolveClassPath($fqcn, $projectRoot);
          if ($classFile === NULL) {
            $errors[] = "$relativePath:{$routeInfo['line']} — route '$routeName': class '$fqcn' not found";
          }
          elseif (!methodExistsInFile($classFile, $method)) {
            $errors[] = "$relativePath:{$routeInfo['line']} — route '$routeName': method '$method' not found in $fqcn";
          }
        }
      }
    }

    // Check _form: '\Namespace\Class'
    if (!empty($routeInfo['_form'])) {
      $formClass = ltrim($routeInfo['_form'], '\\');

      // Only validate custom module classes.
      if (isCustomClass($formClass, $customNamespaces)) {
        $checkedRoutes++;
        $classFile = resolveClassPath($formClass, $projectRoot);
        if ($classFile === NULL) {
          $errors[] = "$relativePath:{$routeInfo['line']} — route '$routeName': form class '$formClass' not found";
        }
      }
    }

    // Check _title_callback: '\Namespace\Class::method'
    if (!empty($routeInfo['_title_callback'])) {
      $callback = $routeInfo['_title_callback'];
      if (str_contains($callback, '::')) {
        [$fqcn, $method] = explode('::', $callback, 2);
        $fqcn = ltrim($fqcn, '\\');

        // Only validate custom module classes.
        if (isCustomClass($fqcn, $customNamespaces)) {
          $checkedRoutes++;
          $classFile = resolveClassPath($fqcn, $projectRoot);
          if ($classFile === NULL) {
            $errors[] = "$relativePath:{$routeInfo['line']} — route '$routeName': title callback class '$fqcn' not found";
          }
          elseif (!methodExistsInFile($classFile, $method)) {
            $errors[] = "$relativePath:{$routeInfo['line']} — route '$routeName': title callback method '$method' not found in $fqcn";
          }
        }
      }
    }
  }
}

// Output results.
echo "\n";
echo "=== ROUTE-CTRL-001: Route-Controller method validation ===\n";
echo "  Checked: $checkedRoutes route references\n";
echo "\n";

if (empty($errors)) {
  echo "  OK: All route references resolve correctly.\n";
  echo "\n";
  exit(0);
}

echo "  VIOLATIONS:\n";
foreach ($errors as $error) {
  echo "  [ERROR] $error\n";
}
echo "\n";
echo "  " . count($errors) . " violation(s) found.\n";
echo "  Every _controller, _form, and _title_callback must reference existing classes/methods.\n";
echo "\n";
exit(1);
