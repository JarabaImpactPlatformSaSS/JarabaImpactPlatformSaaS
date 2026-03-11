<?php

/**
 * @file
 * I18N-NAVPREFIX-001: Validates that navigation links use language_prefix.
 *
 * Scans header and footer Twig partials for hardcoded href="/path" patterns
 * that don't use {{ lp }} or {{ language_prefix }} — these break multi-language
 * navigation because they lack the /en/ or /pt-br/ prefix.
 *
 * Allowed patterns:
 * - href="{{ lp }}/path"      (correct: uses language prefix)
 * - href="{{ language_prefix }}/path"  (correct: full variable name)
 * - href="{{ path('<front>') }}"       (correct: Drupal route function)
 * - href="{{ link.url }}"              (dynamic: external or already prefixed)
 * - href="#"                           (anchor)
 * - href="https://..."                 (absolute external URL)
 * - href="{{ ts.footer_*"             (theme settings: external URLs)
 *
 * Violations:
 * - href="/equipo"                     (hardcoded internal path, missing prefix)
 * - href="/user/login"                 (hardcoded auth path, missing prefix)
 *
 * Usage:
 *   php scripts/validation/validate-nav-i18n.php
 *
 * Part of Safeguard Layer 1 (scripts/validation/).
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$templateDir = $projectRoot . '/web/themes/custom/ecosistema_jaraba_theme/templates/partials';

$filesToCheck = [
  '_header.html.twig',
  '_header-classic.html.twig',
  '_header-hero.html.twig',
  '_header-minimal.html.twig',
  '_header-split.html.twig',
  '_header-centered.html.twig',
  '_footer.html.twig',
  '_bottom-nav.html.twig',
];

$violations = [];
$checked = 0;

foreach ($filesToCheck as $filename) {
  $filepath = "$templateDir/$filename";
  if (!file_exists($filepath)) {
    continue;
  }

  $lines = file($filepath, FILE_IGNORE_NEW_LINES);
  $checked++;

  foreach ($lines as $lineNum => $line) {
    // Match href="/something" (hardcoded internal path without language prefix).
    if (preg_match('/href="\/[a-z]/', $line)) {
      // Skip allowed patterns.
      if (preg_match('/href="\{\{/', $line)) {
        continue; // Dynamic (Twig variable).
      }
      if (preg_match('/href="https?:/', $line)) {
        continue; // Absolute external URL.
      }

      $violations[] = sprintf(
        '[FAIL] %s:%d — Hardcoded path without language_prefix: %s',
        $filename,
        $lineNum + 1,
        trim($line)
      );
    }
  }
}

if (empty($violations)) {
  echo "I18N-NAVPREFIX-001: All $checked templates use language_prefix correctly.\n";
  exit(0);
}

echo "I18N-NAVPREFIX-001: " . count($violations) . " violation(s) found:\n\n";
foreach ($violations as $v) {
  echo "  $v\n";
}
echo "\nFix: Replace href=\"/path\" with href=\"{{ lp }}/path\" (where lp = language_prefix|default(''))\n";
exit(count($violations));
