#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-infra-health.php
 *
 * INFRA-HEALTH-001: Verifica salud de servicios de infraestructura del servidor dedicado.
 *
 * Checks:
 *   1. PHP-FPM activo y respondiendo
 *   2. MariaDB conectado y jaraba DB accesible
 *   3. Redis conectado y respondiendo PONG
 *   4. Nginx activo y config válida
 *   5. Supervisor activo
 *   6. Tika respondiendo en :9998
 *   7. SSL certificados no expirados (>30 días)
 *   8. Disco libre >10%
 *   9. settings.env.php tiene API keys críticas
 *  10. settings.production.php incluido (jaraba_base_domain definido)
 *
 * USO:
 *   php scripts/validation/validate-infra-health.php
 *
 * NOTA: Solo ejecutable en el servidor de producción (no en CI).
 *
 * EXIT CODES:
 *   0 = PASS
 *   1 = FAIL (servicios caídos)
 *   2 = WARN (degradación)
 */

// Solo ejecutar en producción.
if (php_sapi_name() !== 'cli') {
  exit(0);
}

// Detectar si estamos en servidor dedicado.
if (!file_exists('/var/www/jaraba/web/index.php')) {
  echo "INFRA-HEALTH-001: Skipped (not on production server)\n";
  exit(0);
}

$errors = [];
$warnings = [];
$passed = 0;

// 1. PHP-FPM.
$fpm_status = trim(shell_exec('systemctl is-active php8.4-fpm 2>/dev/null') ?? '');
if ($fpm_status === 'active') {
  $passed++;
}
else {
  $errors[] = 'PHP-FPM is not active: ' . $fpm_status;
}

// 2. MariaDB.
$db_status = trim(shell_exec('systemctl is-active mariadb 2>/dev/null') ?? '');
if ($db_status === 'active') {
  $db_test = trim(shell_exec('mariadb jaraba -e "SELECT 1;" -sN 2>/dev/null') ?? '');
  if ($db_test === '1') {
    $passed++;
  }
  else {
    $errors[] = 'MariaDB active but jaraba DB query failed';
  }
}
else {
  $errors[] = 'MariaDB is not active: ' . $db_status;
}

// 3. Redis.
$redis_ping = trim(shell_exec('redis-cli ping 2>/dev/null') ?? '');
if ($redis_ping === 'PONG') {
  $passed++;
}
else {
  $errors[] = 'Redis not responding: ' . $redis_ping;
}

// 4. Nginx.
$nginx_status = trim(shell_exec('systemctl is-active nginx 2>/dev/null') ?? '');
if ($nginx_status === 'active') {
  $nginx_test = shell_exec('nginx -t 2>&1');
  if (strpos($nginx_test, 'syntax is ok') !== FALSE) {
    $passed++;
  }
  else {
    $warnings[] = 'Nginx active but config test failed';
  }
}
else {
  $errors[] = 'Nginx is not active: ' . $nginx_status;
}

// 5. Supervisor.
$sup_status = trim(shell_exec('systemctl is-active supervisor 2>/dev/null') ?? '');
if ($sup_status === 'active') {
  $passed++;
}
else {
  $warnings[] = 'Supervisor is not active: ' . $sup_status;
}

// 6. Tika.
$tika_code = trim(shell_exec('curl -s -o /dev/null -w "%{http_code}" http://localhost:9998/ 2>/dev/null') ?? '');
if ($tika_code === '200') {
  $passed++;
}
else {
  $warnings[] = 'Tika not responding: HTTP ' . $tika_code;
}

// 7. SSL expiry (>30 days).
$ssl_domains = ['plataformadeecosistemas.com', 'pepejaraba.com', 'jarabaimpact.com'];
$ssl_ok = TRUE;
foreach ($ssl_domains as $domain) {
  $expiry = shell_exec("echo | openssl s_client -servername {$domain} -connect {$domain}:443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null");
  if ($expiry) {
    $expiry_date = str_replace('notAfter=', '', trim($expiry));
    $days_left = (int) ((strtotime($expiry_date) - time()) / 86400);
    if ($days_left < 30) {
      $warnings[] = "SSL {$domain}: expires in {$days_left} days";
      $ssl_ok = FALSE;
    }
  }
}
if ($ssl_ok) {
  $passed++;
}

// 8. Disk free >10%.
$disk_free_pct = (int) trim(shell_exec("df / | awk 'NR==2{print 100-\$5}'") ?? '0');
if ($disk_free_pct > 10) {
  $passed++;
}
else {
  $errors[] = "Disk free: {$disk_free_pct}% (<10%)";
}

// 9. settings.env.php has critical keys.
$env_file = '/var/www/jaraba/config/deploy/settings.env.php';
if (file_exists($env_file)) {
  $env_content = file_get_contents($env_file);
  $critical_keys = ['OPENAI_API_KEY', 'ANTHROPIC_API_KEY', 'RECAPTCHA_SITE_KEY'];
  $missing_keys = [];
  foreach ($critical_keys as $key) {
    if (strpos($env_content, $key) === FALSE) {
      $missing_keys[] = $key;
    }
  }
  if (empty($missing_keys)) {
    $passed++;
  }
  else {
    $warnings[] = 'settings.env.php missing keys: ' . implode(', ', $missing_keys);
  }
}
else {
  $errors[] = 'settings.env.php not found';
}

// 10. settings.production.php included (jaraba_base_domain set).
$base_domain = trim(shell_exec('cd /var/www/jaraba && vendor/bin/drush eval "echo \\Drupal\\Core\\Site\\Settings::get(\'jaraba_base_domain\') ?: \'NOT_SET\';" 2>/dev/null') ?? '');
if ($base_domain !== 'NOT_SET' && !empty($base_domain)) {
  $passed++;
}
else {
  $errors[] = 'jaraba_base_domain not set — settings.production.php not loaded';
}

// Report.
$total = $passed + count($errors) + count($warnings);
echo "INFRA-HEALTH-001: Infrastructure health check\n";
echo str_repeat('=', 60) . "\n";
echo "Checks: {$total} total, {$passed} passed, " . count($errors) . " errors, " . count($warnings) . " warnings\n\n";

foreach ($errors as $err) {
  echo "  \033[0;31mFAIL\033[0m  {$err}\n";
}
foreach ($warnings as $warn) {
  echo "  \033[1;33mWARN\033[0m  {$warn}\n";
}

if (empty($errors) && empty($warnings)) {
  echo "  \033[0;32mPASS\033[0m  All infrastructure checks passed.\n";
}

echo "\n";
exit(empty($errors) ? (empty($warnings) ? 0 : 2) : 1);
