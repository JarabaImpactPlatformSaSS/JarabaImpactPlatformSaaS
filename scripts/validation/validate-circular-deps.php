<?php

/**
 * @file
 * CONTAINER-DEPS-002: Detect circular references in service dependencies.
 *
 * Parses all *.services.yml and builds a dependency graph, then uses DFS
 * to detect cycles. Circular references cause Symfony's container compiler
 * to fail, which cascades into ALL kernel tests failing.
 *
 * Usage: php scripts/validation/validate-circular-deps.php
 * Exit:  0 = no cycles, 1 = cycles found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

// ─────────────────────────────────────────────────────────────
// Step 1: Parse all *.services.yml and build adjacency list.
// ─────────────────────────────────────────────────────────────
$serviceFiles = array_merge(
  glob("$modulesDir/*/*.services.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.services.yml") ?: []
);

// adjacency: service_id => [list of service_ids it depends on]
$adjacency = [];
$allServices = [];

foreach ($serviceFiles as $file) {
  $content = file_get_contents($file);
  if ($content === FALSE) {
    continue;
  }

  $lines = explode("\n", $content);
  $currentService = NULL;
  $inServices = FALSE;

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
      if (str_starts_with($currentService, '_')) {
        $currentService = NULL;
        continue;
      }
      $allServices[$currentService] = str_replace($projectRoot . '/', '', $file);
      if (!isset($adjacency[$currentService])) {
        $adjacency[$currentService] = [];
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

    // New service at same level.
    if (preg_match('/^  [a-zA-Z_]/', $line) && !str_starts_with($line, '    ')) {
      $currentService = NULL;
      continue;
    }

    // Extract hard service references (@ without ?).
    // Optional deps (@?) are fine — they don't create real dependency chains.
    if (preg_match_all('/@([a-zA-Z_][a-zA-Z0-9_.]+)/', $line, $matches)) {
      foreach ($matches[1] as $ref) {
        // Skip optional references (preceded by @?).
        if (str_contains($line, '@?' . $ref)) {
          continue;
        }
        // Only track references to our custom services (ignore core/contrib).
        if (isset($allServices[$ref]) || str_starts_with($ref, 'ecosistema_jaraba_core.') ||
            str_starts_with($ref, 'jaraba_')) {
          $adjacency[$currentService][] = $ref;
        }
      }
    }

    // Extract parent references.
    if (preg_match('/^\s+parent:\s+[\'"]?([a-zA-Z_][a-zA-Z0-9_.]+)/', $line, $m)) {
      $ref = $m[1];
      if (isset($allServices[$ref]) || str_starts_with($ref, 'ecosistema_jaraba_core.') ||
          str_starts_with($ref, 'jaraba_')) {
        $adjacency[$currentService][] = $ref;
      }
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Step 2: DFS cycle detection.
// ─────────────────────────────────────────────────────────────
$WHITE = 0; // Unvisited.
$GRAY = 1;  // In current path (potential cycle).
$BLACK = 2; // Fully explored.

$color = [];
foreach (array_keys($adjacency) as $node) {
  $color[$node] = $WHITE;
}

$cycles = [];
$path = [];

/**
 * DFS with cycle detection.
 */
function dfs(string $node, array &$adjacency, array &$color, array &$path, array &$cycles): void {
  $color[$node] = $GLOBALS['GRAY'];
  $path[] = $node;

  foreach ($adjacency[$node] ?? [] as $neighbor) {
    if (!isset($color[$neighbor])) {
      // Unknown service — skip (it's external).
      continue;
    }

    if ($color[$neighbor] === $GLOBALS['GRAY']) {
      // Found a cycle! Extract it from path.
      $cycleStart = array_search($neighbor, $path, TRUE);
      if ($cycleStart !== FALSE) {
        $cycle = array_slice($path, $cycleStart);
        $cycle[] = $neighbor; // Close the cycle.
        $cycles[] = $cycle;
      }
    }
    elseif ($color[$neighbor] === $GLOBALS['WHITE']) {
      dfs($neighbor, $adjacency, $color, $path, $cycles);
    }
  }

  array_pop($path);
  $color[$node] = $GLOBALS['BLACK'];
}

foreach (array_keys($adjacency) as $node) {
  if ($color[$node] === $WHITE) {
    dfs($node, $adjacency, $color, $path, $cycles);
  }
}

// ─────────────────────────────────────────────────────────────
// Step 3: Deduplicate cycles (same cycle can be found from different start nodes).
// ─────────────────────────────────────────────────────────────
$uniqueCycles = [];
foreach ($cycles as $cycle) {
  // Normalize: rotate to start with smallest service ID.
  $min = min(array_slice($cycle, 0, -1));
  $minIdx = array_search($min, $cycle, TRUE);
  $rotated = array_merge(
    array_slice($cycle, $minIdx, -1),
    array_slice($cycle, 0, $minIdx),
    [$min]
  );
  $key = implode(' -> ', $rotated);
  $uniqueCycles[$key] = $rotated;
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== CONTAINER-DEPS-002: Circular reference detection ===\n";
echo "  Services scanned: " . count($allServices) . "\n";
echo "  Dependency edges: " . array_sum(array_map('count', $adjacency)) . "\n";
echo "\n";

if (!empty($uniqueCycles)) {
  echo "  [FAIL] Circular references detected:\n";
  foreach ($uniqueCycles as $key => $cycle) {
    echo "    " . implode(' -> ', $cycle) . "\n";
    // Show which files define the services in the cycle.
    foreach (array_slice($cycle, 0, -1) as $svc) {
      if (isset($allServices[$svc])) {
        echo "      $svc: {$allServices[$svc]}\n";
      }
    }
    echo "\n";
  }
  echo "  " . count($uniqueCycles) . " circular reference(s) found.\n";
  echo "  Fix: Make one direction of the dependency optional (@?) or\n";
  echo "  lazy-load via \\Drupal::service() inside the method body.\n";
  echo "\n";
  exit(1);
}

echo "  OK: No circular references detected.\n";
echo "\n";
exit(0);
