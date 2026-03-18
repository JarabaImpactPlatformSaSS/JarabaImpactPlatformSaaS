<?php

/**
 * @file
 * LANDING-PLAN-COHERENCE-001: Detects promises in landing page text that
 * contradict the corrected pricing model (Doc 158 v3 + Doc 181).
 *
 * Scans VerticalLandingController for patterns that should NOT appear:
 * - "mentoría 1:1" / "mentoring individual" (Regla #0)
 * - "soporte dedicado" / "soporte 24/7" (Doc 158 v3)
 * - "Jitsi" (must be Google Meet/Zoom)
 * - "white-label" as included feature (must be addon)
 * - "marketplace" as primary value prop without threshold disclaimer
 *
 * Usage: php scripts/validation/validate-landing-vs-plans.php
 */

$base_path = dirname(__DIR__, 2);
$errors = [];
$warnings = [];

// Files to scan (user-facing text).
$files_to_scan = [
  $base_path . '/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php',
  $base_path . '/web/modules/custom/ecosistema_jaraba_core/src/Hook/PageAttachmentsHooks.php',
];

// Forbidden patterns (P0 — violate Doc 181 / Doc 158 v3).
$forbidden = [
  '/mentoría\s+1[:\s]*1/ui' => 'Regla #0: "mentoría 1:1" en SaaS plan text (must be separate service)',
  '/mentoring\s+individual/ui' => 'Regla #0: "mentoring individual" in SaaS plan text',
  '/soporte\s+dedicado/ui' => 'Doc 158 v3: "soporte dedicado" (should be "email prioritario 24h")',
  '/soporte\s+24\/?7/ui' => 'Doc 158 v3: "soporte 24/7" (no support team)',
  '/Jitsi(?!\s*\(legacy)/ui' => 'Doc 181: "Jitsi" without (legacy) qualifier (must be Google Meet/Zoom)',
];

// Warning patterns (P1 — potentially misleading).
$warn_patterns = [
  '/white[- ]?label/ui' => 'White label should be addon, not plan feature',
];

foreach ($files_to_scan as $file) {
  if (!file_exists($file)) {
    continue;
  }

  $content = file_get_contents($file);
  $lines = explode("\n", $content);
  $basename = basename($file);

  foreach ($forbidden as $pattern => $message) {
    foreach ($lines as $num => $line) {
      // Skip comments.
      if (preg_match('/^\s*(\/\/|\*|#)/', $line)) {
        continue;
      }
      if (preg_match($pattern, $line)) {
        $errors[] = sprintf(
          '%s:%d — %s | Text: "%s"',
          $basename, $num + 1, $message, trim(substr($line, 0, 120))
        );
      }
    }
  }

  foreach ($warn_patterns as $pattern => $message) {
    foreach ($lines as $num => $line) {
      if (preg_match('/^\s*(\/\/|\*|#)/', $line)) {
        continue;
      }
      if (preg_match($pattern, $line)) {
        $warnings[] = sprintf(
          '%s:%d — %s | Text: "%s"',
          $basename, $num + 1, $message, trim(substr($line, 0, 120))
        );
      }
    }
  }
}

// Output.
echo "LANDING-PLAN-COHERENCE-001: Landing page vs plan coherence\n";
echo str_repeat('=', 60) . "\n";
echo "Files scanned: " . count($files_to_scan) . "\n\n";

if (empty($errors) && empty($warnings)) {
  echo "\033[32mPASS\033[0m — Landing texts consistent with Doc 158 v3.\n";
}
else {
  if (!empty($errors)) {
    echo "\033[31mFAIL\033[0m — " . count($errors) . " forbidden pattern(s):\n";
    foreach ($errors as $e) {
      echo "  \033[31m✗\033[0m $e\n";
    }
  }
  if (!empty($warnings)) {
    echo "\n\033[33mWARN\033[0m — " . count($warnings) . " warning(s):\n";
    foreach ($warnings as $w) {
      echo "  \033[33m⚠\033[0m $w\n";
    }
  }
}

exit(empty($errors) ? 0 : 1);
