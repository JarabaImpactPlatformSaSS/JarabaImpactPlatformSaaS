<?php

/**
 * @file
 * MJML-BINARY-AVAILABILITY-001: Informational check for MJML binary availability.
 *
 * This is a warn_check — always exits 0.
 * Reports whether proper MJML compilation is available or if the fallback
 * regex converter is being used.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$mjmlDir = $projectRoot . '/web/modules/custom/jaraba_email/templates/mjml';

// Count templates that benefit from proper MJML compilation.
$templateCount = 0;
if (is_dir($mjmlDir)) {
  $files = glob($mjmlDir . '/{,*/,*/*/}*.mjml', GLOB_BRACE);
  $files = array_filter($files, fn($f) => basename($f) !== 'base.mjml');
  $templateCount = count($files);
}

// Check 1: Local binary.
$localBinary = '/usr/local/bin/mjml';
$hasLocal = file_exists($localBinary);

// Check 2: npx mjml.
$hasNpx = false;
$npxVersion = '';
exec('npx mjml --version 2>/dev/null', $output, $returnCode);
if ($returnCode === 0 && !empty($output)) {
  $hasNpx = true;
  $npxVersion = trim(implode(' ', $output));
}

// Report.
if ($hasLocal) {
  echo "MJML-BINARY-AVAILABILITY-001: OK — Local binary found at $localBinary ($templateCount templates)\n";
}
elseif ($hasNpx) {
  echo "MJML-BINARY-AVAILABILITY-001: OK — npx mjml available ($npxVersion) ($templateCount templates)\n";
}
else {
  echo "MJML-BINARY-AVAILABILITY-001: WARNING — No MJML binary available. Fallback regex converter is being used for $templateCount templates. Install MJML for proper compilation: npm install -g mjml\n";
}

// Always exit 0 — this is informational.
exit(0);
