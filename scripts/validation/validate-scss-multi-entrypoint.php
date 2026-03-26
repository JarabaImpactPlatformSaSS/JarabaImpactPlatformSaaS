<?php

/**
 * @file
 * SCSS-MULTI-ENTRYPOINT-001: Detecta SCSS partials importados por múltiples
 * entry points y verifica que TODOS los CSS compilados están frescos.
 *
 * Previene el bug de compilar solo main.scss cuando un partial también es
 * importado por routes/landing.scss (bug 2026-03-26: landing.css 28 min stale).
 *
 * Checks:
 * 1. Detectar SCSS partials con múltiples consumidores
 * 2. Para cada partial multi-entry, verificar que TODOS los CSS son más recientes
 * 3. Alertar si algún CSS compilado es anterior al SCSS fuente
 *
 * Uso: php scripts/validation/validate-scss-multi-entrypoint.php
 */

$errors = 0;
$warnings = 0;
$pass = 0;

$theme_dir = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';
$scss_dir = $theme_dir . '/scss';
$css_dir = $theme_dir . '/css';

if (!is_dir($scss_dir)) {
  echo "SKIP: SCSS directory not found.\n";
  exit(0);
}

// Step 1: Build a map of entry points → imported partials.
$entry_points = [
  'main' => ['file' => $scss_dir . '/main.scss', 'css' => $css_dir . '/ecosistema-jaraba-theme.css'],
];

// Discover route entry points.
$routes_dir = $scss_dir . '/routes';
if (is_dir($routes_dir)) {
  foreach (glob($routes_dir . '/*.scss') as $route_scss) {
    $name = basename($route_scss, '.scss');
    $entry_points["route-{$name}"] = [
      'file' => $route_scss,
      'css' => $css_dir . '/routes/' . $name . '.css',
    ];
  }
}

// Discover bundle entry points.
$bundles_dir = $scss_dir . '/bundles';
if (is_dir($bundles_dir)) {
  foreach (glob($bundles_dir . '/*.scss') as $bundle_scss) {
    $name = basename($bundle_scss, '.scss');
    $entry_points["bundle-{$name}"] = [
      'file' => $bundle_scss,
      'css' => $css_dir . '/bundles/' . $name . '.css',
    ];
  }
}

// Step 2: For each entry point, extract @use imports (1 level deep).
$partial_consumers = []; // partial_name => [entry_point_names]

foreach ($entry_points as $ep_name => $ep_data) {
  if (!file_exists($ep_data['file'])) {
    continue;
  }
  $content = file_get_contents($ep_data['file']);

  // Match @use 'components/xxx' or @use '../components/xxx'.
  if (preg_match_all("/@use\s+'(?:\.\.\/)?(components\/[^']+)'/", $content, $matches)) {
    foreach ($matches[1] as $import) {
      // Normalize to partial filename.
      $partial = basename($import);
      $partial_consumers[$partial][] = $ep_name;
    }
  }
}

// Step 3: Identify multi-consumer partials.
$multi_consumer = array_filter($partial_consumers, fn($consumers) => count($consumers) > 1);

if (empty($multi_consumer)) {
  echo "PASS: No SCSS partials with multiple entry point consumers found.\n";
  $pass++;
} else {
  echo "INFO: " . count($multi_consumer) . " SCSS partials imported by multiple entry points:\n";
  foreach ($multi_consumer as $partial => $consumers) {
    echo "  _{$partial}.scss → " . implode(', ', $consumers) . "\n";
  }
  echo "\n";
}

// Step 4: For each multi-consumer partial, check ALL CSS outputs are fresh.
$components_dir = $scss_dir . '/components';
$stale_count = 0;

foreach ($multi_consumer as $partial => $consumers) {
  $scss_file = $components_dir . '/_{$partial}.scss';

  // Try to find the actual SCSS file.
  $candidates = [
    $components_dir . "/_${partial}.scss",
    $components_dir . "/${partial}.scss",
  ];

  $actual_scss = null;
  foreach ($candidates as $c) {
    if (file_exists($c)) {
      $actual_scss = $c;
      break;
    }
  }

  if ($actual_scss === null) {
    continue;
  }

  $scss_mtime = filemtime($actual_scss);

  foreach ($consumers as $ep_name) {
    $css_file = $entry_points[$ep_name]['css'] ?? null;
    if ($css_file === null || !file_exists($css_file)) {
      echo "WARN: CSS output for entry point '{$ep_name}' not found: " . ($css_file ?? 'unknown') . "\n";
      $warnings++;
      continue;
    }

    $css_mtime = filemtime($css_file);

    if ($css_mtime < $scss_mtime) {
      $scss_time = date('H:i:s', $scss_mtime);
      $css_time = date('H:i:s', $css_mtime);
      echo "FAIL: _{$partial}.scss ({$scss_time}) is NEWER than {$ep_name} CSS ({$css_time}). Run 'npm run build'.\n";
      $errors++;
      $stale_count++;
    }
  }
}

if ($stale_count === 0 && !empty($multi_consumer)) {
  echo "PASS: All CSS outputs for multi-consumer partials are fresh.\n";
  $pass++;
}

// Step 5: Special check for known high-risk partials.
$high_risk = ['ped-metasite', 'landing-page', 'glass-utilities'];
foreach ($high_risk as $hr_partial) {
  if (!isset($partial_consumers[$hr_partial])) {
    // Check if it exists but wasn't detected.
    $hr_file = $components_dir . "/_{$hr_partial}.scss";
    if (file_exists($hr_file)) {
      // Scan ALL entry points for this partial.
      $found_in = [];
      foreach ($entry_points as $ep_name => $ep_data) {
        if (!file_exists($ep_data['file'])) {
          continue;
        }
        $content = file_get_contents($ep_data['file']);
        if (strpos($content, $hr_partial) !== false) {
          $found_in[] = $ep_name;
        }
      }
      if (count($found_in) > 1) {
        echo "INFO: High-risk partial _{$hr_partial}.scss found in: " . implode(', ', $found_in) . "\n";

        // Verify freshness.
        $scss_mtime = filemtime($hr_file);
        foreach ($found_in as $ep_name) {
          $css_file = $entry_points[$ep_name]['css'] ?? null;
          if ($css_file && file_exists($css_file) && filemtime($css_file) < $scss_mtime) {
            echo "FAIL: _{$hr_partial}.scss is NEWER than {$ep_name} CSS. Run 'npm run build'.\n";
            $errors++;
          }
        }
      }
    }
  }
}

// Summary.
$total = $pass + $warnings + $errors;
echo "\n=== SCSS-MULTI-ENTRYPOINT-001: {$pass} PASS, {$warnings} WARN, {$errors} FAIL";
if (!empty($multi_consumer)) {
  echo " ({" . count($multi_consumer) . "} multi-consumer partials)";
}
echo " ===\n";

if ($errors > 0) {
  echo "\nACTION: Run 'cd web/themes/custom/ecosistema_jaraba_theme && npm run build' to recompile ALL entry points.\n";
}

exit($errors > 0 ? 1 : 0);
