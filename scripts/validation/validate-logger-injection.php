<?php

/**
 * @file
 * LOGGER-INJECT-001: Detect logger injection mismatches.
 *
 * Finds services that inject @logger.channel.X (which gives a LoggerChannel)
 * but whose PHP constructor calls ->get() (which is a LoggerChannelFactory method).
 * This causes "Call to undefined method LoggerChannel::get()" at runtime.
 *
 * Two valid patterns:
 *   1. services.yml: @logger.channel.X     PHP: LoggerInterface $logger     (assign directly)
 *   2. services.yml: @logger.factory        PHP: $factory->get('channel')   (factory pattern)
 *
 * Invalid pattern:
 *   services.yml: @logger.channel.X         PHP: $factory->get('channel')   (CRASH!)
 *
 * Usage: php scripts/validation/validate-logger-injection.php
 * Exit:  0 = clean, 1 = mismatches found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

// ─────────────────────────────────────────────────────────────
// Step 1: Parse services.yml to find services using @logger.channel.X
// ─────────────────────────────────────────────────────────────
$serviceFiles = array_merge(
  glob("$modulesDir/*/*.services.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.services.yml") ?: []
);

$servicesWithLoggerChannel = []; // service_id => ['file', 'class', 'arg_index']

foreach ($serviceFiles as $file) {
  $content = file_get_contents($file);
  if ($content === FALSE) {
    continue;
  }

  $lines = explode("\n", $content);
  $currentService = NULL;
  $currentClass = NULL;
  $inServices = FALSE;
  $argIndex = 0;
  $inArguments = FALSE;

  foreach ($lines as $line) {
    if (preg_match('/^services:\s*$/', $line)) {
      $inServices = TRUE;
      continue;
    }
    if (!$inServices) {
      continue;
    }

    // Service definition.
    if (preg_match('/^  ([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/', $line, $m)) {
      $currentService = $m[1];
      $currentClass = NULL;
      $argIndex = 0;
      $inArguments = FALSE;
      if (str_starts_with($currentService, '_')) {
        $currentService = NULL;
      }
      continue;
    }

    if ($currentService === NULL) {
      continue;
    }

    // End of services section.
    if ($line !== '' && !str_starts_with($line, ' ') && !str_starts_with($line, '#')) {
      $inServices = FALSE;
      $currentService = NULL;
      continue;
    }

    // Class declaration.
    if (preg_match('/^\s+class:\s+[\'"]?([^\s\'"]+)/', $line, $m)) {
      $currentClass = $m[1];
    }

    // Arguments section.
    if (preg_match('/^\s+arguments:\s*$/', $line)) {
      $inArguments = TRUE;
      $argIndex = 0;
      continue;
    }

    // End of arguments (new property at service level).
    if ($inArguments && preg_match('/^    [a-z]/', $line) && !str_starts_with(trim($line), '-')) {
      $inArguments = FALSE;
    }

    // Argument line with @logger.channel.
    if ($inArguments && preg_match('/^\s+- [\'"]?@\??logger\.channel\.([^\s\'"]+)/', $line, $m)) {
      $relFile = str_replace($projectRoot . '/', '', $file);
      $servicesWithLoggerChannel[$currentService] = [
        'file' => $relFile,
        'class' => $currentClass,
        'channel' => $m[1],
        'arg_index' => $argIndex,
      ];
      $argIndex++;
      continue;
    }

    if ($inArguments && preg_match('/^\s+-\s/', $line)) {
      $argIndex++;
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Step 2: Check PHP constructors for ->get() calls on logger args.
// ─────────────────────────────────────────────────────────────
$violations = [];

foreach ($servicesWithLoggerChannel as $serviceId => $info) {
  $className = $info['class'];
  if (empty($className)) {
    continue;
  }

  // Convert FQCN to file path.
  // Drupal\module_name\... => web/modules/custom/module_name/src/...
  $classParts = explode('\\', $className);
  if (count($classParts) < 3 || $classParts[0] !== 'Drupal') {
    continue;
  }

  $moduleName = $classParts[1];
  $relPath = implode('/', array_slice($classParts, 2));
  $phpFile = "$modulesDir/$moduleName/src/$relPath.php";

  // Also check submodules.
  if (!file_exists($phpFile)) {
    $phpFiles = glob("$modulesDir/*/modules/$moduleName/src/$relPath.php");
    if (!empty($phpFiles)) {
      $phpFile = $phpFiles[0];
    }
  }

  if (!file_exists($phpFile)) {
    continue;
  }

  $phpContent = file_get_contents($phpFile);
  if ($phpContent === FALSE) {
    continue;
  }

  // Check if constructor assigns $this->logger via ->get() pattern.
  // This is the SPECIFIC pattern that crashes:
  //   $this->logger = $logger_factory->get('channel');
  // We must NOT flag other ->get() calls (entity field access, config, etc.).
  if (preg_match('/function\s+__construct\s*\([^)]*\)\s*\{(.*?)(?:\n  \}|\n\})/s', $phpContent, $ctorMatch)) {
    $ctorBody = $ctorMatch[1];
    // Match: $this->logger = $variable->get('channel_name')
    if (preg_match('/\$this->logger\s*=\s*\$(\w+)->get\s*\(\s*[\'"]([^"\']+)[\'"]\s*\)/', $ctorBody, $getMatch)) {
      $relPhpFile = str_replace($projectRoot . '/', '', $phpFile);
      $violations[] = [
        'service' => $serviceId,
        'services_file' => $info['file'],
        'php_file' => $relPhpFile,
        'channel_in_yml' => $info['channel'],
        'channel_in_php' => $getMatch[2],
        'variable' => '$' . $getMatch[1],
        'class' => $className,
      ];
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== LOGGER-INJECT-001: Logger injection mismatch detection ===\n";
echo "  Services with @logger.channel.X: " . count($servicesWithLoggerChannel) . "\n";
echo "\n";

if (!empty($violations)) {
  echo "  [FAIL] Logger injection mismatches found:\n";
  foreach ($violations as $v) {
    echo "    Service: {$v['service']}\n";
    echo "      services.yml: @logger.channel.{$v['channel_in_yml']} (injects LoggerChannel)\n";
    echo "      PHP class: {$v['class']}\n";
    echo "      Constructor calls: ->get('{$v['channel_in_php']}') (needs LoggerChannelFactory)\n";
    echo "      Fix: Accept LoggerInterface \$logger and assign directly (no ->get())\n";
    echo "\n";
  }
  echo "  " . count($violations) . " logger injection mismatch(es) found.\n";
  echo "  @logger.channel.X injects a LoggerChannel which has NO ->get() method.\n";
  echo "  Either:\n";
  echo "    a) Change PHP to accept LoggerInterface and remove ->get(), OR\n";
  echo "    b) Change services.yml to use @logger.factory instead.\n";
  echo "\n";
  exit(1);
}

echo "  OK: All logger injections are consistent.\n";
echo "\n";
exit(0);
