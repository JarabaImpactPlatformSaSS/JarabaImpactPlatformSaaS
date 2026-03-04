<?php

/**
 * @file
 * DI-TYPE-001: Validate service DI type consistency.
 *
 * Cross-references services.yml argument references against PHP constructor
 * type hints to detect mismatches (e.g., injecting @logger.factory where
 * the constructor expects LoggerInterface).
 *
 * Usage: php scripts/validation/validate-services-di.php
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

// Known service-to-interface mappings for Drupal core + contrib.
// Only the most common ones that cause real type mismatches.
$serviceTypeMap = [
  // Logger: the #1 source of DI mismatches.
  'logger.factory' => 'Drupal\Core\Logger\LoggerChannelFactoryInterface',
  'logger.channel_base' => 'Drupal\Core\Logger\LoggerChannelInterface',
  // Common core services.
  'entity_type.manager' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
  'current_user' => ['Drupal\Core\Session\AccountProxyInterface', 'Drupal\Core\Session\AccountInterface'],
  'database' => 'Drupal\Core\Database\Connection',
  'config.factory' => 'Drupal\Core\Config\ConfigFactoryInterface',
  'event_dispatcher' => ['Symfony\Contracts\EventDispatcher\EventDispatcherInterface', 'Symfony\Component\EventDispatcher\EventDispatcherInterface', 'Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher'],
  'module_handler' => 'Drupal\Core\Extension\ModuleHandlerInterface',
  'cache.default' => 'Drupal\Core\Cache\CacheBackendInterface',
  'datetime.time' => 'Drupal\Component\Datetime\TimeInterface',
  'messenger' => 'Drupal\Core\Messenger\MessengerInterface',
  'string_translation' => 'Drupal\Core\StringTranslation\TranslationInterface',
  'path_alias.manager' => 'Drupal\path_alias\AliasManagerInterface',
  'language_manager' => 'Drupal\Core\Language\LanguageManagerInterface',
  'request_stack' => 'Symfony\Component\HttpFoundation\RequestStack',
  'renderer' => 'Drupal\Core\Render\RendererInterface',
  'file_system' => 'Drupal\Core\File\FileSystemInterface',
  'token' => 'Drupal\Core\Utility\Token',
  'state' => 'Drupal\Core\State\StateInterface',
  'queue' => 'Drupal\Core\Queue\QueueFactory',
  'http_client' => 'GuzzleHttp\ClientInterface',
  'stream_wrapper_manager' => 'Drupal\Core\StreamWrapper\StreamWrapperManagerInterface',
  'entity_field.manager' => 'Drupal\Core\Entity\EntityFieldManagerInterface',
  'entity_display.repository' => 'Drupal\Core\Entity\EntityDisplayRepositoryInterface',
  'typed_data_manager' => 'Drupal\Core\TypedData\TypedDataManagerInterface',
  'path.current' => 'Drupal\Core\Path\CurrentPathStack',
  'route_match' => 'Drupal\Core\Routing\RouteMatchInterface',
  'url_generator' => 'Drupal\Core\Routing\UrlGeneratorInterface',
  'theme.manager' => 'Drupal\Core\Theme\ThemeManagerInterface',
  'plugin.manager.mail' => 'Drupal\Core\Mail\MailManagerInterface',
  'lock' => 'Drupal\Core\Lock\LockBackendInterface',
  'email.validator' => 'Drupal\Component\Utility\EmailValidatorInterface',
  'transliteration' => 'Drupal\Component\Transliteration\TransliterationInterface',
];

// Types that are compatible with LoggerInterface (both should accept it).
$loggerCompatible = [
  'Psr\Log\LoggerInterface',
  'Drupal\Core\Logger\LoggerChannelInterface',
  'Drupal\Core\Logger\LoggerChannel',
];

// Types that match @logger.factory.
$loggerFactoryCompatible = [
  'Drupal\Core\Logger\LoggerChannelFactoryInterface',
  'Drupal\Core\Logger\LoggerChannelFactory',
];

/**
 * Resolve a Drupal class FQCN to its file path.
 */
function resolveClassPath(string $fqcn, string $projectRoot): ?string {
  if (!str_starts_with($fqcn, 'Drupal\\')) {
    return NULL;
  }

  $parts = explode('\\', $fqcn);
  if (count($parts) < 3) {
    return NULL;
  }

  $moduleName = $parts[1];
  $classPath = implode('/', array_slice($parts, 2)) . '.php';

  $directPath = "$projectRoot/web/modules/custom/$moduleName/src/$classPath";
  if (file_exists($directPath)) {
    return $directPath;
  }

  // Try as submodule.
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
 * Extract constructor parameter type hints from a PHP file.
 *
 * Returns array of [position => type_hint_string] for each constructor param.
 */
function getConstructorParams(string $filePath): ?array {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return NULL;
  }

  // Find __construct method signature (may span multiple lines).
  // Match from "function __construct(" to the closing ")".
  if (!preg_match('/function\s+__construct\s*\(([^)]*(?:\([^)]*\)[^)]*)*)\)/s', $content, $match)) {
    return NULL;
  }

  $paramsStr = $match[1];
  $params = [];
  $position = 0;

  // Split parameters by comma, but respect nested parentheses/generics.
  $current = '';
  $depth = 0;
  for ($i = 0; $i < strlen($paramsStr); $i++) {
    $ch = $paramsStr[$i];
    if ($ch === '(' || $ch === '<') {
      $depth++;
    }
    if ($ch === ')' || $ch === '>') {
      $depth--;
    }
    if ($ch === ',' && $depth === 0) {
      $params[] = trim($current);
      $current = '';
      continue;
    }
    $current .= $ch;
  }
  if (trim($current) !== '') {
    $params[] = trim($current);
  }

  $result = [];
  foreach ($params as $idx => $param) {
    // Remove attributes like #[...].
    $param = preg_replace('/#\[[^\]]*\]/', '', $param);
    // Remove access modifiers (public, protected, private, readonly).
    $param = preg_replace('/\b(public|protected|private|readonly)\s+/', '', $param);
    $param = trim($param);

    // Extract type hint: everything before the $.
    if (preg_match('/^([^$]+)\$/', $param, $m)) {
      $typeHint = trim($m[1]);
      // Handle nullable: ?Type.
      $typeHint = ltrim($typeHint, '?');
      // Handle union types: take first one.
      if (str_contains($typeHint, '|')) {
        $typeHint = explode('|', $typeHint)[0];
      }
      $typeHint = trim($typeHint);
      $result[$idx] = $typeHint;
    }
    else {
      $result[$idx] = NULL;
    }
  }

  return $result;
}

/**
 * Resolve short class names to FQCN using use statements in a file.
 */
function resolveUseStatements(string $filePath): array {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return [];
  }

  $uses = [];
  if (preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
      $fqcn = $m[1];
      $alias = $m[2] ?? basename(str_replace('\\', '/', $fqcn));
      $uses[$alias] = $fqcn;
    }
  }

  return $uses;
}

/**
 * Parse a services.yml file and extract service definitions.
 */
function parseServicesYml(string $filePath): array {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return [];
  }

  $services = [];
  $currentService = NULL;
  $inArguments = FALSE;
  $currentClass = NULL;
  $args = [];

  foreach (explode("\n", $content) as $lineNum => $line) {
    // Skip comments.
    if (str_starts_with(trim($line), '#')) {
      continue;
    }

    // Top-level 'services:' key.
    if (trim($line) === 'services:') {
      continue;
    }

    // Service name (2-space indent, no further nesting).
    if (preg_match('/^  ([a-zA-Z_][a-zA-Z0-9_.:]+):\s*$/', $line, $m)) {
      // Save previous service.
      if ($currentService !== NULL && $currentClass !== NULL) {
        $services[$currentService] = [
          'class' => $currentClass,
          'arguments' => $args,
          'line' => $serviceLineNum ?? 0,
        ];
      }
      $currentService = $m[1];
      $serviceLineNum = $lineNum + 1;
      $currentClass = NULL;
      $args = [];
      $inArguments = FALSE;
      continue;
    }

    if ($currentService === NULL) {
      continue;
    }

    $trimmed = trim($line);

    // Class declaration.
    if (preg_match('/^\s+class:\s*(.+)$/', $line, $m)) {
      $currentClass = trim($m[1], "'\" ");
      continue;
    }

    // Arguments section (inline or block).
    if (preg_match('/^\s+arguments:\s*\[(.+)\]\s*$/', $line, $m)) {
      // Inline arguments.
      $inlineArgs = $m[1];
      $args = array_map('trim', str_getcsv($inlineArgs));
      $args = array_map(fn($a) => trim($a, "'\" "), $args);
      $inArguments = FALSE;
      continue;
    }

    if (preg_match('/^\s+arguments:\s*$/', $line)) {
      $inArguments = TRUE;
      $args = [];
      continue;
    }

    // Block argument item.
    if ($inArguments && preg_match('/^\s+- (.+)$/', $line, $m)) {
      $args[] = trim($m[1], "'\" ");
      continue;
    }

    // Any other property at service level ends arguments.
    if ($inArguments && preg_match('/^\s+[a-z]/', $line) && !str_starts_with($trimmed, '-')) {
      $inArguments = FALSE;
    }

    // Another service definition at same level ends current.
    if (preg_match('/^  [a-zA-Z_]/', $line) && $trimmed !== '') {
      if ($currentService !== NULL && $currentClass !== NULL) {
        $services[$currentService] = [
          'class' => $currentClass,
          'arguments' => $args,
          'line' => $serviceLineNum ?? 0,
        ];
      }
      $currentService = NULL;
      $currentClass = NULL;
      $args = [];
      $inArguments = FALSE;
    }
  }

  // Save last service.
  if ($currentService !== NULL && $currentClass !== NULL) {
    $services[$currentService] = [
      'class' => $currentClass,
      'arguments' => $args,
      'line' => $serviceLineNum ?? 0,
    ];
  }

  return $services;
}

// Collect all custom service definitions (for resolving @custom_service references).
$allServiceClasses = [];
$servicesFiles = array_merge(
  glob("$modulesDir/*/*.services.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.services.yml") ?: []
);

// First pass: collect all service->class mappings.
foreach ($servicesFiles as $sFile) {
  $parsed = parseServicesYml($sFile);
  foreach ($parsed as $svcName => $svcDef) {
    $allServiceClasses[$svcName] = $svcDef['class'];
  }
}

// logger.channel.* services always provide LoggerInterface.
// Register a wildcard pattern.

$errors = [];
$warnings = [];
$checkedServices = 0;

// Second pass: validate DI.
foreach ($servicesFiles as $servicesFile) {
  $relativePath = str_replace($projectRoot . '/', '', $servicesFile);

  // Check deprecated module.
  $pathParts = explode('/', $relativePath);
  $moduleName = $pathParts[3] ?? '';
  if (in_array($moduleName, $deprecatedModules, TRUE)) {
    continue;
  }

  $services = parseServicesYml($servicesFile);

  foreach ($services as $serviceName => $serviceInfo) {
    $className = $serviceInfo['class'];
    $arguments = $serviceInfo['arguments'];

    if (empty($arguments)) {
      continue;
    }

    // Resolve class file.
    $classFile = resolveClassPath($className, $projectRoot);
    if ($classFile === NULL) {
      // Class might be in contrib/core, skip.
      continue;
    }

    $constructorParams = getConstructorParams($classFile);
    if ($constructorParams === NULL) {
      // No constructor or can't parse, skip.
      continue;
    }

    $useStatements = resolveUseStatements($classFile);

    $checkedServices++;

    // Match arguments to constructor parameters.
    $paramIdx = 0;
    foreach ($arguments as $arg) {
      if (!isset($constructorParams[$paramIdx])) {
        $paramIdx++;
        continue;
      }

      $typeHint = $constructorParams[$paramIdx];
      $paramIdx++;

      if ($typeHint === NULL || $typeHint === '') {
        continue;
      }

      // Skip non-service arguments.
      if (!str_starts_with($arg, '@')) {
        continue;
      }

      // Handle optional services.
      $isOptional = str_starts_with($arg, '@?');
      $serviceRef = ltrim($arg, '@?');

      // Resolve type hint to FQCN.
      $resolvedTypeHint = $typeHint;
      if (!str_contains($typeHint, '\\')) {
        // Short name — try use statements.
        $resolvedTypeHint = $useStatements[$typeHint] ?? $typeHint;
      }

      // === KEY CHECK: @logger.factory vs LoggerInterface ===
      if ($serviceRef === 'logger.factory') {
        $isFactoryCompatible = FALSE;
        foreach ($loggerFactoryCompatible as $factoryType) {
          if ($resolvedTypeHint === $factoryType || str_ends_with($resolvedTypeHint, '\\' . basename(str_replace('\\', '/', $factoryType)))) {
            $isFactoryCompatible = TRUE;
            break;
          }
        }

        if (!$isFactoryCompatible) {
          // Check if it expects LoggerInterface (the most common mismatch).
          $isLoggerInterface = FALSE;
          foreach ($loggerCompatible as $loggerType) {
            if ($resolvedTypeHint === $loggerType || str_ends_with($resolvedTypeHint, '\\' . basename(str_replace('\\', '/', $loggerType)))) {
              $isLoggerInterface = TRUE;
              break;
            }
          }

          if ($isLoggerInterface) {
            $errors[] = "$relativePath:{$serviceInfo['line']} — service '$serviceName': injects @logger.factory (LoggerChannelFactory) but constructor expects LoggerInterface. Use @logger.channel.$moduleName instead.";
          }
        }
        continue;
      }

      // === Check logger.channel.* ===
      if (str_starts_with($serviceRef, 'logger.channel.')) {
        // logger.channel.* provides LoggerInterface — check constructor expects it.
        $isLoggerCompatibleType = FALSE;
        foreach ($loggerCompatible as $loggerType) {
          if ($resolvedTypeHint === $loggerType || str_ends_with($resolvedTypeHint, '\\' . basename(str_replace('\\', '/', $loggerType)))) {
            $isLoggerCompatibleType = TRUE;
            break;
          }
        }

        // Also accept LoggerChannelFactoryInterface (some services accept factory).
        foreach ($loggerFactoryCompatible as $factoryType) {
          if ($resolvedTypeHint === $factoryType || str_ends_with($resolvedTypeHint, '\\' . basename(str_replace('\\', '/', $factoryType)))) {
            $isLoggerCompatibleType = TRUE;
            break;
          }
        }

        if (!$isLoggerCompatibleType && $resolvedTypeHint !== 'mixed') {
          $warnings[] = "$relativePath:{$serviceInfo['line']} — service '$serviceName': injects @$serviceRef but constructor type-hint is '$resolvedTypeHint' (expected LoggerInterface).";
        }
        continue;
      }

      // === General core service type checks ===
      if (isset($serviceTypeMap[$serviceRef])) {
        $expectedTypes = (array) $serviceTypeMap[$serviceRef];
        $isCompatible = FALSE;

        foreach ($expectedTypes as $expected) {
          $expectedShort = basename(str_replace('\\', '/', $expected));
          if ($resolvedTypeHint === $expected ||
              $resolvedTypeHint === $expectedShort ||
              str_ends_with($resolvedTypeHint, '\\' . $expectedShort)) {
            $isCompatible = TRUE;
            break;
          }
        }

        // Allow 'mixed' and '' type hints.
        if ($resolvedTypeHint === 'mixed' || $resolvedTypeHint === '') {
          $isCompatible = TRUE;
        }

        if (!$isCompatible) {
          // Only warn — not all core service types can be fully resolved.
          $warnings[] = "$relativePath:{$serviceInfo['line']} — service '$serviceName': injects @$serviceRef (provides " . implode('|', $expectedTypes) . ") but constructor expects '$resolvedTypeHint'.";
        }
      }
    }
  }
}

// Output results.
echo "\n";
echo "=== DI-TYPE-001: Service DI type consistency ===\n";
echo "  Checked: $checkedServices services with arguments\n";
echo "\n";

if (!empty($errors)) {
  echo "  ERRORS:\n";
  foreach ($errors as $error) {
    echo "  [ERROR] $error\n";
  }
  echo "\n";
}

if (!empty($warnings)) {
  echo "  WARNINGS:\n";
  foreach ($warnings as $warning) {
    echo "  [WARN]  $warning\n";
  }
  echo "\n";
}

if (empty($errors)) {
  echo "  OK: No DI type mismatches found.\n";
  echo "\n";
  exit(0);
}

echo "  " . count($errors) . " error(s), " . count($warnings) . " warning(s) found.\n";
echo "\n";
exit(1);
