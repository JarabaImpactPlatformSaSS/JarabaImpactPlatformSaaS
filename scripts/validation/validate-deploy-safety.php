<?php

/**
 * @file
 * DEPLOY-MAINTENANCE-SAFETY-001: Deploy must exit maintenance mode on failure.
 *
 * Validates that .github/workflows/deploy.yml has `if: always()` on the
 * step that disables maintenance mode, ensuring the site never stays in
 * 503 state after a failed deploy.
 *
 * Usage: php scripts/validation/validate-deploy-safety.php
 * Exit: 0 = pass, 1 = fail
 */

$deploy_yml = __DIR__ . '/../../.github/workflows/deploy.yml';
$errors = [];

if (!file_exists($deploy_yml)) {
  echo "⚠️ DEPLOY-MAINTENANCE-SAFETY-001: deploy.yml not found — skipped\n";
  exit(0);
}

$content = file_get_contents($deploy_yml);
$lines = explode("\n", $content);

// Find the "Disable maintenance mode" step.
$found_disable_step = FALSE;
$has_always_condition = FALSE;

for ($i = 0; $i < count($lines); $i++) {
  if (str_contains($lines[$i], 'Disable maintenance mode')) {
    $found_disable_step = TRUE;
    // Check surrounding lines for if: always().
    $context_start = max(0, $i - 2);
    $context_end = min(count($lines) - 1, $i + 2);
    for ($j = $context_start; $j <= $context_end; $j++) {
      if (preg_match('/if:\s*always\(\)/', $lines[$j])) {
        $has_always_condition = TRUE;
        break;
      }
    }
    break;
  }
}

if (!$found_disable_step) {
  $errors[] = "No 'Disable maintenance mode' step found in deploy.yml";
}
elseif (!$has_always_condition) {
  $errors[] = "Step 'Disable maintenance mode' lacks 'if: always()' — site may stay in 503 after failed deploy";
}

// Report.
if (empty($errors)) {
  echo "✅ DEPLOY-MAINTENANCE-SAFETY-001: Maintenance mode safety gate present\n";
  exit(0);
}

echo "❌ DEPLOY-MAINTENANCE-SAFETY-001:\n";
foreach ($errors as $e) {
  echo "  - $e\n";
}
exit(1);
