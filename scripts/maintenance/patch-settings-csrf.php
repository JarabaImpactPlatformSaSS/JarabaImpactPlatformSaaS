#!/usr/bin/env php
<?php

/**
 * @file
 * ENV-BOOTSTRAP-001 + CSRF-LOGIN-FIX-001: Patch settings.php and services.yml.
 *
 * Idempotent — safe to run multiple times. Only patches if not already applied.
 * Used by CI/CD deploy pipeline (deploy.yml).
 *
 * Usage: php scripts/maintenance/patch-settings-csrf.php
 */

$base = dirname(__DIR__, 2);
$settingsFile = $base . '/web/sites/default/settings.php';
$servicesFile = $base . '/web/sites/default/services.yml';
$sitesDir = $base . '/web/sites/default';

// Unlock directory for writes.
@chmod($sitesDir, 0755);

// --- Patch settings.php ---
$content = file_get_contents($settingsFile);
if ($content === false) {
  echo "ERROR: Cannot read $settingsFile\n";
  exit(1);
}

if (strpos($content, 'ENV-BOOTSTRAP-001') === false) {
  @chmod($settingsFile, 0644);

  $block = "\n"
    . "// ENV-BOOTSTRAP-001: Early environment variable loader.\n"
    . "if (file_exists(\$app_root . '/../config/deploy/settings.env.php')) {\n"
    . "  include \$app_root . '/../config/deploy/settings.env.php';\n"
    . "}\n\n"
    . "// CSRF-LOGIN-FIX-001: Production reverse proxy.\n"
    . "if (getenv('LANDO') !== 'ON') {\n"
    . "  \$settings['reverse_proxy'] = TRUE;\n"
    . "  \$settings['reverse_proxy_addresses'] = ['127.0.0.1'];\n"
    . "  \$settings['reverse_proxy_trusted_headers'] =\n"
    . "    \\Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_FOR |\n"
    . "    \\Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_HOST |\n"
    . "    \\Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_PORT |\n"
    . "    \\Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_PROTO;\n"
    . "}\n\n";

  $marker = '// AUDIT-PERF-N14: CDN configuration';
  if (strpos($content, $marker) !== false) {
    $content = str_replace($marker, $block . $marker, $content);
    file_put_contents($settingsFile, $content);
    @chmod($settingsFile, 0444);
    echo "OK settings.php patched\n";
  }
  else {
    echo "WARN Cannot find CDN marker in settings.php — manual patch needed\n";
  }
}
else {
  echo "SKIP settings.php already patched\n";
}

// --- Patch services.yml ---
$content = file_get_contents($servicesFile);
if ($content !== false && strpos($content, 'cookie_secure') === false) {
  @chmod($servicesFile, 0644);
  $content = str_replace(
    'cookie_samesite: Lax',
    "cookie_samesite: Lax\n    cookie_secure: true",
    $content
  );
  file_put_contents($servicesFile, $content);
  @chmod($servicesFile, 0444);
  echo "OK services.yml patched\n";
}
else {
  echo "SKIP services.yml already patched\n";
}

// Lock directory.
@chmod($sitesDir, 0555);
echo "DONE\n";
