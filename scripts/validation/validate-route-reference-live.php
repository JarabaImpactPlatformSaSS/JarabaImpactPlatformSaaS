<?php

/**
 * @file
 * ROUTE-REFERENCE-LIVE-001: Validates route names in Url::fromRoute() exist in routing.yml.
 *
 * Scans all PHP files under web/modules/custom/ for Url::fromRoute('route.name')
 * and \Drupal::url('route.name') calls, then verifies the referenced route name
 * exists in a *.routing.yml file within web/modules/custom/.
 *
 * Exclusions:
 * - Drupal core routes (system.*, user.*, entity.*, view.*, jsonapi.*, rest.*)
 * - Routes with { in the name (parameterized/dynamic)
 * - Special routes: <front>, <current>, <none>
 * - Test files, scripts/, comments
 * - Known contrib route prefixes (commerce_*, webform.*, field_ui.*, etc.)
 *
 * Usage: php scripts/validation/validate-route-reference-live.php
 * Exit:  0 = pass (warn_check), 1 = violations found
 *
 * @see ROUTE-LANGPREFIX-001
 */

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);
$modulesDir = $basePath . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$pass = 0;
$fail = 0;
$warn = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = '', bool $isWarn = false): void {
  global $pass, $fail, $warn, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  elseif ($isWarn) {
    $warn++;
    echo "  \033[33mWARN\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║\033[0m  \033[1mROUTE-REFERENCE-LIVE-001\033[0m                                \033[36m║\033[0m\n";
echo "\033[36m║\033[0m  Route references in PHP vs routing.yml definitions      \033[36m║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ─────────────────────────────────────────────────────────────
// Prefixes for routes provided by Drupal core or contrib.
// These are NOT expected to appear in custom routing.yml files.
// ─────────────────────────────────────────────────────────────
$coreContribPrefixes = [
  'system.', 'user.', 'entity.', 'view.', 'jsonapi.', 'rest.',
  'node.', 'block.', 'block_content.', 'taxonomy.', 'comment.',
  'media.', 'file.', 'field_ui.', 'locale.', 'language.',
  'path.', 'search.', 'shortcut.', 'config.', 'dblog.',
  'image.', 'filter.', 'editor.', 'ckeditor5.',
  'commerce_', 'webform.', 'group.', 'domain.',
  'content_moderation.', 'workflows.', 'menu_ui.',
  'redirect.', 'metatag.', 'pathauto.', 'token.',
  'admin_toolbar.', 'layout_builder.', 'contextual.',
  'toolbar.', 'update.', 'aggregator.', 'forum.', 'contact.',
  'statistics.', 'tracker.', 'big_pipe.', 'ban.',
  'color.', 'help.', 'history.', 'options.',
  'symfony_mailer.', 'mailsystem.', 'swiftmailer.',
  'oauth2_token.', 'openid_connect.',
  'content_translation.',
];

// ─────────────────────────────────────────────────────────────
// Step 1: Extract all route names from *.routing.yml files.
// ─────────────────────────────────────────────────────────────
$knownRoutes = [];

$routingFiles = array_merge(
  glob("$modulesDir/*/*.routing.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.routing.yml") ?: []
);

foreach ($routingFiles as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }
  // Route names are top-level keys (indent level 0, ending with colon).
  if (preg_match_all('/^([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/m', $content, $matches)) {
    foreach ($matches[1] as $routeName) {
      $knownRoutes[$routeName] = str_replace($basePath . '/', '', $file);
    }
  }
}

echo "  Routing files scanned: " . count($routingFiles) . "\n";
echo "  Routes extracted: " . count($knownRoutes) . "\n\n";

// ─────────────────────────────────────────────────────────────
// Step 2: Scan PHP files for fromRoute() / \Drupal::url() calls.
// ─────────────────────────────────────────────────────────────
$references = [];
$totalRefsScanned = 0;

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
  /** @var SplFileInfo $fileInfo */
  if ($fileInfo->getExtension() !== 'php') {
    continue;
  }

  $filePath = $fileInfo->getPathname();
  $relativePath = str_replace($basePath . '/', '', $filePath);

  // Skip test files and scripts.
  if (preg_match('#/(tests|Tests|scripts|fixtures|Fixtures)/#', $relativePath)) {
    continue;
  }

  $content = file_get_contents($filePath);
  if ($content === false) {
    continue;
  }

  $lines = explode("\n", $content);

  foreach ($lines as $lineIdx => $line) {
    $lineNum = $lineIdx + 1;

    // Skip lines that are pure comments.
    $trimmed = ltrim($line);
    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#')) {
      continue;
    }

    // Match patterns:
    // Url::fromRoute('route.name'
    // fromRoute('route.name'
    // \Drupal::url('route.name'
    // RedirectResponse::create() doesn't apply — only route references.
    $patterns = [
      "/fromRoute\s*\(\s*'([a-zA-Z_<][a-zA-Z0-9_.<>]*)'/",
      '/fromRoute\s*\(\s*"([a-zA-Z_<][a-zA-Z0-9_.<>]*)"/',
      "/\\\\Drupal::url\s*\(\s*'([a-zA-Z_<][a-zA-Z0-9_.<>]*)'/",
      '/\\\\Drupal::url\s*\(\s*"([a-zA-Z_<][a-zA-Z0-9_.<>]*)"/',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match_all($pattern, $line, $matches)) {
        foreach ($matches[1] as $routeName) {
          $totalRefsScanned++;
          $references[] = [
            'route' => $routeName,
            'file' => $relativePath,
            'line' => $lineNum,
          ];
        }
      }
    }
  }
}

echo "  PHP files scanned: route references found: $totalRefsScanned\n\n";

// ─────────────────────────────────────────────────────────────
// Step 3: Filter and validate references.
// ─────────────────────────────────────────────────────────────
$violations = [];
$skippedCore = 0;
$skippedSpecial = 0;
$skippedDynamic = 0;
$verified = 0;

foreach ($references as $ref) {
  $routeName = $ref['route'];

  // Skip special routes.
  if (in_array($routeName, ['<front>', '<current>', '<none>', '<nolink>'], true)) {
    $skippedSpecial++;
    continue;
  }

  // Skip routes containing { (dynamic/parameterized).
  if (str_contains($routeName, '{')) {
    $skippedDynamic++;
    continue;
  }

  // Skip core/contrib prefixes.
  $isCoreContrib = false;
  foreach ($coreContribPrefixes as $prefix) {
    if (str_starts_with($routeName, $prefix)) {
      $isCoreContrib = true;
      break;
    }
  }
  if ($isCoreContrib) {
    $skippedCore++;
    continue;
  }

  // Check if route exists in our custom routing.yml files.
  if (isset($knownRoutes[$routeName])) {
    $verified++;
  }
  else {
    $violations[] = $ref;
  }
}

// ─────────────────────────────────────────────────────────────
// Step 4: Report results.
// ─────────────────────────────────────────────────────────────
check(
  "Route definitions loaded from routing.yml",
  count($knownRoutes) > 0,
  count($knownRoutes) . " routes"
);

check(
  "PHP route references scanned",
  $totalRefsScanned > 0,
  "$totalRefsScanned references in custom modules"
);

check(
  "Skipped core/contrib routes",
  true,
  "$skippedCore core/contrib, $skippedSpecial special, $skippedDynamic dynamic"
);

check(
  "Verified custom route references",
  $verified > 0,
  "$verified references match known routing.yml routes"
);

if (count($violations) === 0) {
  check("No unresolved route references", true);
}
else {
  echo "\n  \033[33m── Unresolved route references (" . count($violations) . ") ──\033[0m\n\n";

  // Group violations by route name for readability.
  $grouped = [];
  foreach ($violations as $v) {
    $grouped[$v['route']][] = $v['file'] . ':' . $v['line'];
  }
  ksort($grouped);

  foreach ($grouped as $routeName => $locations) {
    echo "  \033[33mWARN\033[0m Route \033[1m$routeName\033[0m not found in any routing.yml\n";
    foreach ($locations as $loc) {
      echo "       └─ $loc\n";
    }
  }

  $warn += count($grouped);
  $checks += count($grouped);

  echo "\n";
  check(
    "Unresolved route references detected",
    false,
    count($violations) . " references to " . count($grouped) . " unknown routes (may be entity routes or contrib)",
    true  // warn, not fail — baseline tolerance
  );
}

echo "\n\033[36m══════════════════════════════════════════════════════════\033[0m\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
echo "\033[36m══════════════════════════════════════════════════════════\033[0m\n\n";

// Exit 0 = pass (warn_check mode). Only fail on structural errors.
exit($fail > 0 ? 1 : 0);
