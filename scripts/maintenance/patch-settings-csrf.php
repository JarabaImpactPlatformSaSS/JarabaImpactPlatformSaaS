#!/usr/bin/env php
<?php

/**
 * @file
 * ENV-BOOTSTRAP-001 + CSRF-LOGIN-FIX-001: Patch settings.php and services.yml.
 *
 * Idempotent — safe to run multiple times.
 * Used by CI/CD deploy pipeline (deploy.yml step "Patch settings.php").
 *
 * v2 (2026-03-16): Fix HTTPS detection. The v1 block used a hardcoded
 * reverse_proxy_addresses=['127.0.0.1'] which ONLY works when the proxy is
 * on localhost. IONOS infrastructure uses variable internal IPs. The new
 * approach sets $_SERVER['HTTPS'] directly from X-Forwarded-Proto BEFORE
 * Drupal bootstrap, guaranteeing consistent session cookie naming (SSESS vs
 * SESS prefix) and correct cookie_secure behavior.
 *
 * Usage: php scripts/maintenance/patch-settings-csrf.php
 */

$base = dirname(__DIR__, 2);
$settingsFile = $base . '/web/sites/default/settings.php';
$servicesFile = $base . '/web/sites/default/services.yml';
$sitesDir = $base . '/web/sites/default';

// Unlock directory for writes.
@chmod($sitesDir, 0755);

// ============================================================================
// PHASE 1: Patch settings.php — CSRF-LOGIN-FIX-001 v2
// ============================================================================
$content = file_get_contents($settingsFile);
if ($content === false) {
  echo "ERROR: Cannot read $settingsFile\n";
  exit(1);
}

// The correct CSRF fix block (v2).
$csrfBlockV2 = <<<'CSRFBLOCK'
// CSRF-LOGIN-FIX-001: IONOS HTTPS detection + reverse proxy trust.
//
// ROOT CAUSE: IONOS terminates SSL at infrastructure level. Apache/PHP receives
// plain HTTP. Without explicit HTTPS detection:
//   1. SessionConfiguration::getName() uses 'SESS' prefix (not 'SSESS')
//   2. SessionConfiguration::getOptions() sets cookie_secure = FALSE
//      (core/lib/Drupal/Core/Session/SessionConfiguration.php:55 overrides
//       the services.yml value: $options['cookie_secure'] = $request->isSecure())
//   3. If isSecure() is inconsistent between requests, the session cookie name
//      flips between SESS_/SSESS_, the session is lost, and CSRF validation fails.
//
// FIX: Set $_SERVER['HTTPS'] BEFORE Drupal bootstrap, so Symfony's
// Request::createFromGlobals() sees HTTPS from the start. This guarantees
// consistent session cookie naming and correct cookie_secure behavior.
if (getenv('LANDO') !== 'ON') {
  // Layer 1: Detect HTTPS from X-Forwarded-Proto (set by IONOS infrastructure).
  // MUST happen before Request::createFromGlobals() in index.php.
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
  }
  // Layer 2: Fallback — detect HTTPS from server port.
  if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
    $_SERVER['HTTPS'] = 'on';
  }

  // Layer 3: Trust the direct upstream IP as reverse proxy.
  // On IONOS, REMOTE_ADDR is the internal proxy IP (varies, not always 127.0.0.1).
  // Trusting $_SERVER['REMOTE_ADDR'] is safe because on managed hosting only the
  // infrastructure proxy can reach Apache directly.
  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = [$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'];
  $settings['reverse_proxy_trusted_headers'] =
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;
}
CSRFBLOCK;

$needsPatch = false;

// Detect v1 block (the broken version with hardcoded 127.0.0.1).
$v1Marker = "reverse_proxy_addresses'] = ['127.0.0.1']";
$v2Marker = "IONOS HTTPS detection + reverse proxy trust";

if (strpos($content, $v2Marker) !== false) {
  echo "SKIP settings.php already has CSRF-LOGIN-FIX-001 v2\n";
}
elseif (strpos($content, $v1Marker) !== false) {
  // Replace the entire v1 CSRF block with v2.
  // The v1 block starts with "// CSRF-LOGIN-FIX-001:" and ends with the closing "}"
  $v1Pattern = '/\/\/ CSRF-LOGIN-FIX-001:.*?(?:reverse_proxy_trusted_headers.*?\n.*?HEADER_X_FORWARDED_PROTO;\n\})/s';
  if (preg_match($v1Pattern, $content)) {
    @chmod($settingsFile, 0644);
    $content = preg_replace($v1Pattern, $csrfBlockV2, $content);
    file_put_contents($settingsFile, $content);
    @chmod($settingsFile, 0444);
    echo "OK settings.php CSRF-LOGIN-FIX-001 upgraded v1→v2\n";
    $needsPatch = false;
  }
  else {
    echo "WARN Could not match v1 CSRF block pattern — manual patch needed\n";
  }
}
elseif (strpos($content, 'ENV-BOOTSTRAP-001') === false) {
  // Fresh install — add both ENV-BOOTSTRAP-001 and CSRF-LOGIN-FIX-001 v2.
  @chmod($settingsFile, 0644);

  $block = "\n"
    . "// ENV-BOOTSTRAP-001: Early environment variable loader.\n"
    . "if (file_exists(\$app_root . '/../config/deploy/settings.env.php')) {\n"
    . "  include \$app_root . '/../config/deploy/settings.env.php';\n"
    . "}\n\n"
    . $csrfBlockV2 . "\n\n";

  $marker = '// AUDIT-PERF-N14: CDN configuration';
  if (strpos($content, $marker) !== false) {
    $content = str_replace($marker, $block . $marker, $content);
    file_put_contents($settingsFile, $content);
    @chmod($settingsFile, 0444);
    echo "OK settings.php patched (fresh install)\n";
  }
  else {
    echo "WARN Cannot find CDN marker in settings.php — manual patch needed\n";
  }
}
else {
  // ENV-BOOTSTRAP-001 exists but no CSRF block found — add v2 after env loader.
  @chmod($settingsFile, 0644);
  $envMarker = "// AUDIT-PERF-N14: CDN configuration";
  if (strpos($content, $envMarker) !== false) {
    $content = str_replace($envMarker, $csrfBlockV2 . "\n\n" . $envMarker, $content);
    file_put_contents($settingsFile, $content);
    @chmod($settingsFile, 0444);
    echo "OK settings.php CSRF-LOGIN-FIX-001 v2 added\n";
  }
  else {
    echo "WARN Cannot find CDN marker — manual patch needed\n";
  }
}

// ============================================================================
// PHASE 2: Patch services.yml — cookie_secure
// ============================================================================
$content = file_get_contents($servicesFile);
if ($content !== false && strpos($content, 'cookie_secure') === false) {
  @chmod($servicesFile, 0644);
  $content = str_replace(
    'cookie_samesite: Lax',
    "cookie_samesite: Lax\n    # CSRF-LOGIN-FIX-001: Ensure session cookie only sent over HTTPS.\n    cookie_secure: true",
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
