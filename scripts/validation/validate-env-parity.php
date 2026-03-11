<?php

/**
 * @file
 * ENV-PARITY-001: Validate dev/prod environment parity.
 *
 * Detects configuration drift between Lando (local dev), CI pipeline,
 * and IONOS production that could cause bugs only visible in production.
 *
 * Checks:
 *  1. PHP version parity across .lando.yml, ci.yml, settings, runbook
 *  2. PHP extensions required by codebase vs documented/available
 *  3. MariaDB version parity across .lando.yml, ci.yml, docker-compose
 *  4. Redis version parity across .lando.yml, docker-compose
 *  5. PHP config parity (memory_limit, max_execution_time, upload sizes)
 *  6. MariaDB config parity (max_connections, max_allowed_packet, buffer pool)
 *  7. OPcache configuration and invalidation strategy
 *  8. Supervisor workers defined but absent in dev
 *  9. Filesystem paths consistency (private files, tmp)
 * 10. Trusted host patterns vs Domain entities vs Nginx config
 * 11. Environment-specific code paths (getenv, LANDO checks)
 * 12. Composer.lock freshness and platform requirements
 * 13. Reverse proxy configuration coherence (Nginx vs Traefik)
 * 14. Wildcard SSL strategy for multi-tenant subdomains
 *
 * Usage: php scripts/validation/validate-env-parity.php
 * Exit:  0 = clean, 1 = violations found
 *
 * @see RUNTIME-VERIFY-001
 * @see DEPLOY-READY-001
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);

$errors = 0;
$warnings = 0;

echo "\n============================================================\n";
echo "  ENV-PARITY-001: Dev/Prod Environment Parity Check\n";
echo "  Date: " . date('Y-m-d H:i:s') . "\n";
echo "============================================================\n\n";

// ============================================================================
// HELPER: Extract version from YAML-like content.
// ============================================================================
function extractVersion(string $content, string $pattern): ?string {
  if (preg_match($pattern, $content, $m)) {
    return trim($m[1], " \t\n\r\"'");
  }
  return null;
}

// ============================================================================
// HELPER: Report result.
// ============================================================================
function reportOk(string $message): void {
  echo "  [OK] $message\n";
}

function reportError(string $message, string $detail = ''): void {
  echo "  [ERROR] $message\n";
  if ($detail !== '') {
    echo "          $detail\n";
  }
}

function reportWarn(string $message, string $detail = ''): void {
  echo "  [WARN] $message\n";
  if ($detail !== '') {
    echo "         $detail\n";
  }
}

function convertToBytes(string $value): int {
  $value = trim($value);
  $num = (int) $value;
  $suffix = strtoupper(substr($value, -1));
  return match ($suffix) {
    'G' => $num * 1024 * 1024 * 1024,
    'M' => $num * 1024 * 1024,
    'K' => $num * 1024,
    default => $num,
  };
}

// ============================================================================
// Load source files.
// ============================================================================
$landoFile = $projectRoot . '/.lando.yml';
$ciFile = $projectRoot . '/.github/workflows/ci.yml';
$deployFile = $projectRoot . '/.github/workflows/deploy.yml';
$settingsProd = $projectRoot . '/config/deploy/settings.production.php';
$supervisorConf = $projectRoot . '/config/deploy/supervisor-ai-workers.conf';
$nginxConf = $projectRoot . '/config/deploy/nginx-metasites.conf';
$composerJson = $projectRoot . '/composer.json';
$composerLock = $projectRoot . '/composer.lock';
$phpIni = $projectRoot . '/php.ini';

$landoContent = file_exists($landoFile) ? file_get_contents($landoFile) : '';
$ciContent = file_exists($ciFile) ? file_get_contents($ciFile) : '';
$deployContent = file_exists($deployFile) ? file_get_contents($deployFile) : '';
$settingsProdContent = file_exists($settingsProd) ? file_get_contents($settingsProd) : '';
$supervisorContent = file_exists($supervisorConf) ? file_get_contents($supervisorConf) : '';
$nginxContent = file_exists($nginxConf) ? file_get_contents($nginxConf) : '';
$composerJsonContent = file_exists($composerJson) ? file_get_contents($composerJson) : '';
$composerLockContent = file_exists($composerLock) ? file_get_contents($composerLock) : '';
$phpIniContent = file_exists($phpIni) ? file_get_contents($phpIni) : '';
$composer = ($composerJsonContent !== '') ? json_decode($composerJsonContent, true) : [];

// ============================================================================
// CHECK 1: PHP version parity.
// ============================================================================
echo "=== CHECK 1: PHP version parity ===\n";

$phpVersions = [];

// Lando.
$v = extractVersion($landoContent, '/^\s*php:\s*["\']?([\d.]+)/m');
if ($v !== null) {
  $phpVersions['Lando (.lando.yml)'] = $v;
}

// CI pipeline.
$v = extractVersion($ciContent, '/PHP_VERSION:\s*["\']?([\d.]+)/');
if ($v !== null) {
  $phpVersions['CI (ci.yml)'] = $v;
}

// Deploy pipeline.
$v = extractVersion($deployContent, '/PHP_VERSION:\s*["\']?([\d.]+)/');
if ($v === null) {
  // Try alternate patterns.
  $v = extractVersion($deployContent, '/php[- ]?([\d.]+)/i');
}
if ($v !== null) {
  $phpVersions['Deploy (deploy.yml)'] = $v;
}

// Composer.json platform requirement.
if (!empty($composer)) {
  $platformPhp = $composer['config']['platform']['php'] ?? null;
  $requirePhp = $composer['require']['php'] ?? null;
  if ($platformPhp !== null) {
    $phpVersions['Composer platform.php'] = $platformPhp;
  }
  if ($requirePhp !== null) {
    $phpVersions['Composer require.php'] = $requirePhp;
  }
}

// Current runtime.
$phpVersions['Current runtime'] = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

$uniqueMinorVersions = [];
foreach ($phpVersions as $source => $version) {
  // Normalize to major.minor for comparison (ignore constraint prefixes like ^, >=).
  if (preg_match('/([\d]+\.[\d]+)/', $version, $m)) {
    $uniqueMinorVersions[$m[1]][] = $source;
  }
  echo "  $source: $version\n";
}

if (count($uniqueMinorVersions) > 1) {
  reportError(
    'PHP version MISMATCH across environments',
    'All environments must use the same PHP major.minor version.'
  );
  $errors++;
} elseif (count($phpVersions) < 2) {
  reportWarn('Could not extract PHP version from enough sources for comparison');
  $warnings++;
} else {
  reportOk('PHP version consistent across ' . count($phpVersions) . ' sources');
}

echo "\n";

// ============================================================================
// CHECK 2: PHP extensions required by codebase.
// ============================================================================
echo "=== CHECK 2: PHP extensions required by codebase ===\n";

// Extensions required for this project based on composer.json and codebase usage.
$requiredExtensions = [
  'pdo_mysql'  => 'MariaDB database driver',
  'mbstring'   => 'Multi-byte string handling (i18n)',
  'gd'         => 'Image processing (image styles, canvas)',
  'curl'       => 'HTTP client (AI APIs, Qdrant, Stripe)',
  'zip'        => 'Composer, file exports',
  'xml'        => 'Drupal core, sitemap, feeds',
  'json'       => 'API responses, config, canvas_data',
  'opcache'    => 'Production performance (JIT)',
  'intl'       => 'Internationalization (i18n, ICU)',
  'bcmath'     => 'Stripe payment calculations',
  'dom'        => 'HTML parsing (Tika, MJML)',
  'fileinfo'   => 'MIME type detection (file uploads)',
  'redis'      => 'Cache backend (PhpRedis)',
  'sodium'     => 'Encryption (GDPR, secrets)',
];

// Check composer.json ext-* requirements.
$composerRequiredExts = [];
if (isset($composer['require'])) {
  foreach ($composer['require'] as $pkg => $ver) {
    if (str_starts_with($pkg, 'ext-')) {
      $composerRequiredExts[] = substr($pkg, 4);
    }
  }
}

$currentExtensions = get_loaded_extensions();
$missingInRuntime = [];
$missingInComposer = [];

// Extensions that may only load in FPM/web context, not CLI.
// - opcache: typically disabled in CLI (opcache.enable_cli=0)
// - redis: phpredis often loaded only in FPM; Lando PHP container has it,
//   but host CLI (WSL/Mac) does not. Not a parity issue — prod FPM has it.
$fpmOnlyExtensions = ['opcache', 'redis'];

foreach ($requiredExtensions as $ext => $reason) {
  $loaded = in_array($ext, $currentExtensions, true);
  $inComposer = in_array($ext, $composerRequiredExts, true);

  if (!$loaded && !in_array($ext, $fpmOnlyExtensions, true)) {
    $missingInRuntime[] = "$ext ($reason)";
  }
  // FPM-only extensions not loaded in CLI is expected behavior.
  // In Lando, these run inside the container; on host CLI (WSL/Mac), they're absent.
  // This is NOT a parity issue — production FPM will have them.
  if (!$inComposer && !in_array($ext, $fpmOnlyExtensions, true)) {
    $missingInComposer[] = $ext;
  }
}

if (count($missingInRuntime) > 0) {
  foreach ($missingInRuntime as $ext) {
    reportError("Extension NOT loaded in current runtime: $ext");
    $errors++;
  }
} else {
  reportOk('All ' . count($requiredExtensions) . ' required extensions loaded in current runtime');
}

if (count($missingInComposer) > 0) {
  reportWarn(
    count($missingInComposer) . ' extension(s) used but not declared in composer.json require',
    'Missing: ' . implode(', ', $missingInComposer)
  );
  echo "         Adding ext-* to composer.json ensures CI and deploy fail early if missing.\n";
  $warnings++;
}

echo "\n";

// ============================================================================
// CHECK 3: MariaDB version parity.
// ============================================================================
echo "=== CHECK 3: MariaDB version parity ===\n";

$dbVersions = [];

// Lando.
$v = extractVersion($landoContent, '/type:\s*mariadb:([\d.]+)/');
if ($v !== null) {
  $dbVersions['Lando (.lando.yml)'] = $v;
}

// CI pipeline.
$v = extractVersion($ciContent, '/mariadb:([\d.]+)/');
if ($v !== null) {
  $dbVersions['CI (ci.yml)'] = $v;
}

// Deploy compose (if exists).
$dockerComposeProd = $projectRoot . '/docker-compose.prod.yml';
if (file_exists($dockerComposeProd)) {
  $dcContent = file_get_contents($dockerComposeProd);
  $v = extractVersion($dcContent, '/mariadb:([\d.]+)/');
  if ($v !== null) {
    $dbVersions['Docker Compose prod'] = $v;
  }
}

foreach ($dbVersions as $source => $version) {
  echo "  $source: $version\n";
}

$uniqueDbVersions = [];
foreach ($dbVersions as $source => $version) {
  if (preg_match('/([\d]+\.[\d]+)/', $version, $m)) {
    $uniqueDbVersions[$m[1]][] = $source;
  }
}

if (count($uniqueDbVersions) > 1) {
  reportError(
    'MariaDB version MISMATCH across environments',
    'Lando, CI, and production must all use the same major.minor MariaDB version.'
  );
  $errors++;
} elseif (count($uniqueDbVersions) === 0) {
  reportWarn('Could not extract MariaDB version from any source');
  $warnings++;
} else {
  reportOk('MariaDB version consistent across ' . count($dbVersions) . ' sources');
}

echo "\n";

// ============================================================================
// CHECK 4: Redis version parity.
// ============================================================================
echo "=== CHECK 4: Redis version parity ===\n";

$redisVersions = [];

// Lando.
$v = extractVersion($landoContent, '/type:\s*redis:([\d.]+)/');
if ($v !== null) {
  $redisVersions['Lando (.lando.yml)'] = $v;
}

// Docker Compose prod.
if (file_exists($dockerComposeProd)) {
  $dcContent = file_get_contents($dockerComposeProd);
  $v = extractVersion($dcContent, '/redis:([\d.]+)/');
  if ($v !== null) {
    $redisVersions['Docker Compose prod'] = $v;
  }
}

foreach ($redisVersions as $source => $version) {
  echo "  $source: $version\n";
}

$uniqueRedis = [];
foreach ($redisVersions as $source => $version) {
  if (preg_match('/([\d]+)/', $version, $m)) {
    $uniqueRedis[$m[1]][] = $source;
  }
}

if (count($uniqueRedis) > 1) {
  reportError('Redis major version MISMATCH across environments');
  $errors++;
} elseif (count($redisVersions) < 1) {
  reportWarn('Could not detect Redis version from config files');
  $warnings++;
} else {
  reportOk('Redis version consistent across ' . count($redisVersions) . ' sources');
}

echo "\n";

// ============================================================================
// CHECK 5: PHP configuration parity.
// ============================================================================
echo "=== CHECK 5: PHP configuration parity (runtime vs php.ini) ===\n";

// Critical PHP settings for Drupal SaaS.
$criticalSettings = [
  'memory_limit'        => ['min' => '256M', 'recommended' => '512M'],
  'max_execution_time'  => ['min' => '120',  'recommended' => '300'],
  'upload_max_filesize' => ['min' => '32M',  'recommended' => '64M'],
  'post_max_size'       => ['min' => '32M',  'recommended' => '64M'],
  'max_input_vars'      => ['min' => '3000', 'recommended' => '5000'],
];

// Parse php.ini if exists.
$iniValues = [];
if ($phpIniContent !== '') {
  foreach (explode("\n", $phpIniContent) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, ';') || str_starts_with($line, '[')) {
      continue;
    }
    if (preg_match('/^([\w.]+)\s*=\s*(.+)$/', $line, $m)) {
      $iniValues[trim($m[1])] = trim($m[2]);
    }
  }
}

foreach ($criticalSettings as $setting => $thresholds) {
  $runtimeValue = ini_get($setting);
  $iniValue = $iniValues[$setting] ?? null;

  $display = "runtime=$runtimeValue";
  if ($iniValue !== null && $iniValue !== $runtimeValue) {
    $display .= ", php.ini=$iniValue";
    reportWarn(
      "$setting: values differ between php.ini ($iniValue) and runtime ($runtimeValue)",
      "Ensure production php.ini/FPM pool matches. Min: {$thresholds['min']}, Recommended: {$thresholds['recommended']}"
    );
    $warnings++;
  } else {
    // Check minimum threshold.
    $numericRuntime = (int) $runtimeValue;
    if ($setting === 'memory_limit' || $setting === 'upload_max_filesize' || $setting === 'post_max_size') {
      $numericRuntime = convertToBytes($runtimeValue);
      $numericMin = convertToBytes($thresholds['min']);
    } else {
      $numericMin = (int) $thresholds['min'];
    }

    if ($numericRuntime > 0 && $numericRuntime < $numericMin) {
      reportWarn("$setting=$runtimeValue is below minimum ({$thresholds['min']})");
      $warnings++;
    } else {
      reportOk("$setting=$runtimeValue (min: {$thresholds['min']})");
    }
  }
}

echo "\n";

// ============================================================================
// CHECK 6: MariaDB config parity (runbook vs reality).
// ============================================================================
echo "=== CHECK 6: MariaDB critical config expectations ===\n";

$mariadbConfFile = $projectRoot . '/config/deploy/mariadb/my.cnf';
$mariadbConf = file_exists($mariadbConfFile) ? file_get_contents($mariadbConfFile) : '';

// Runbook specifies these values — verify they're documented somewhere.
$expectedDbSettings = [
  'max_connections'       => ['value' => '300',  'reason' => 'Multi-tenant concurrent access'],
  'max_allowed_packet'    => ['value' => '256M', 'reason' => 'GrapesJS canvas_data blobs (2-10 MB)'],
  'innodb_flush_method'   => ['value' => 'O_DIRECT', 'reason' => 'NVMe direct I/O performance'],
  'character_set_server'  => ['value' => 'utf8mb4', 'reason' => 'Full Unicode support (emojis, i18n)'],
  'slow_query_log'        => ['value' => '1', 'reason' => 'Performance debugging in production'],
];

// Check if a my.cnf or equivalent exists anywhere in config/deploy/.
$mariadbConfSources = array_merge(
  glob($projectRoot . '/config/deploy/*/my.cnf') ?: [],
  glob($projectRoot . '/config/deploy/mariadb/*') ?: [],
);

if ($mariadbConf !== '') {
  foreach ($expectedDbSettings as $setting => $info) {
    if (preg_match("/$setting\s*=\s*(.+)/i", $mariadbConf, $m)) {
      $actual = trim($m[1]);
      if (stripos($actual, $info['value']) !== false || $actual === $info['value']) {
        reportOk("$setting = {$info['value']} ({$info['reason']})");
      } else {
        reportWarn("$setting = $actual (expected {$info['value']}): {$info['reason']}");
        $warnings++;
      }
    } else {
      reportWarn("$setting not found in my.cnf — expected {$info['value']} for: {$info['reason']}");
      $warnings++;
    }
  }
} else {
  reportWarn(
    'No MariaDB config (my.cnf) found in config/deploy/',
    'Production MariaDB needs explicit tuning. Default max_allowed_packet (16M) will'
  );
  echo "         truncate GrapesJS canvas_data saves. Create config/deploy/mariadb/my.cnf\n";
  echo "         with the settings from the runbook (section 7.2).\n";
  $warnings++;
}

echo "\n";

// ============================================================================
// CHECK 7: OPcache configuration and invalidation strategy.
// ============================================================================
echo "=== CHECK 7: OPcache configuration ===\n";

$opcacheEnabled = (bool) ini_get('opcache.enable');
$opcacheValidateTimestamps = (bool) ini_get('opcache.validate_timestamps');
$opcacheJit = ini_get('opcache.jit') ?: 'disabled';
$opcacheJitBuffer = ini_get('opcache.jit_buffer_size') ?: '0';

echo "  Runtime: opcache.enable=$opcacheEnabled, validate_timestamps=$opcacheValidateTimestamps\n";
echo "  Runtime: jit=$opcacheJit, jit_buffer_size=$opcacheJitBuffer\n";

// Check if deploy pipeline includes OPcache invalidation.
$deployHasOpcacheReset = false;
if ($deployContent !== '') {
  $deployHasOpcacheReset = (
    str_contains($deployContent, 'opcache_reset')
    || str_contains($deployContent, 'OPcache')
    || str_contains($deployContent, 'php-fpm reload')
    || str_contains($deployContent, 'restart php')
    || str_contains($deployContent, 'docker restart')
  );
}

// Check if any production OPcache setting disables timestamps.
$prodDisablesTimestamps = false;
$phpIniFiles = glob($projectRoot . '/config/deploy/php*')
  + glob($projectRoot . '/config/deploy/*/php*');
foreach ($phpIniFiles as $file) {
  $content = file_get_contents($file);
  if (preg_match('/opcache\.validate_timestamps\s*=\s*0/', $content)) {
    $prodDisablesTimestamps = true;
  }
}

// Also check runbook and docker-compose for opcache settings.
$runbookFiles = glob($projectRoot . '/docs/tecnicos/*Runbook*.md')
  + glob($projectRoot . '/docs/tecnicos/*runbook*.md');
foreach ($runbookFiles as $file) {
  $content = file_get_contents($file);
  if (preg_match('/opcache\.validate_timestamps\s*=\s*0/', $content)) {
    $prodDisablesTimestamps = true;
  }
}

if ($prodDisablesTimestamps && !$deployHasOpcacheReset) {
  reportError(
    'Production uses validate_timestamps=0 but deploy pipeline has NO OPcache invalidation',
    'After deploy, PHP will serve STALE CODE until FPM is restarted.'
  );
  echo "          Add php-fpm reload or opcache_reset() to deploy.yml after code update.\n";
  $errors++;
} elseif ($prodDisablesTimestamps && $deployHasOpcacheReset) {
  reportOk('Production validate_timestamps=0 with OPcache invalidation in deploy pipeline');
} else {
  reportOk('OPcache validate_timestamps enabled (auto-reload on file change)');
}

echo "\n";

// ============================================================================
// CHECK 8: Supervisor workers defined but absent in dev.
// ============================================================================
echo "=== CHECK 8: Supervisor AI workers (dev/prod gap analysis) ===\n";

$supervisorQueues = [];
if ($supervisorContent !== '') {
  preg_match_all('/queue:run\s+([\w]+)/', $supervisorContent, $matches);
  $supervisorQueues = $matches[1] ?? [];
}

if (count($supervisorQueues) > 0) {
  echo "  Production Supervisor queues: " . implode(', ', $supervisorQueues) . "\n";

  // Check if Lando has any equivalent.
  $landoHasWorkers = (
    str_contains($landoContent, 'queue:run')
    || str_contains($landoContent, 'supervisor')
  );

  if (!$landoHasWorkers) {
    reportWarn(
      count($supervisorQueues) . ' AI queue workers run in production but NOT in Lando dev',
      'Queue items will accumulate in dev and never process. This means:'
    );
    echo "         - AI async operations (A2A tasks, insights, scheduled agents) won't execute\n";
    echo "         - Bugs in queue handler code won't surface until production\n";
    echo "         Mitigation: Run manually in dev with: drush queue:run <queue_name>\n";
    $warnings++;
  } else {
    reportOk('Lando includes queue worker configuration');
  }

  // Check settings.ai-queues.php is includable.
  $aiQueuesFile = $projectRoot . '/config/deploy/settings.ai-queues.php';
  if (file_exists($aiQueuesFile)) {
    reportOk('settings.ai-queues.php exists (Redis queue routing)');
  } else {
    reportError(
      'settings.ai-queues.php NOT FOUND — queue workers will use database backend',
      'Workers expect Redis reliable queue. Copy from config/deploy/ and include in settings.php.'
    );
    $errors++;
  }
} else {
  reportWarn('No Supervisor worker configuration found in config/deploy/');
  $warnings++;
}

echo "\n";

// ============================================================================
// CHECK 9: Filesystem paths consistency.
// ============================================================================
echo "=== CHECK 9: Filesystem paths consistency ===\n";

$pathIssues = 0;

// Check private file path in settings.production.php.
if (str_contains($settingsProdContent, 'file_private_path')) {
  $prodPrivatePath = '/var/www/jaraba/private';
  if (str_contains($settingsProdContent, $prodPrivatePath)) {
    reportOk("Production private files: $prodPrivatePath");
  } else {
    reportWarn('Production file_private_path is set but differs from expected /var/www/jaraba/private');
    $warnings++;
  }
} else {
  reportError('file_private_path NOT configured in settings.production.php');
  $errors++;
}

// Check that Supervisor, Nginx, and deploy scripts use consistent paths.
$expectedDocroot = '/var/www/jaraba/web';
$pathSources = [
  'Nginx config' => [$nginxContent, '/root\s+([^;]+)/'],
  'Supervisor config' => [$supervisorContent, '/directory=(.+)/'],
];

foreach ($pathSources as $source => [$content, $pattern]) {
  if ($content === '') {
    continue;
  }
  preg_match_all($pattern, $content, $matches);
  $paths = array_unique($matches[1] ?? []);
  foreach ($paths as $path) {
    $path = trim($path);
    if ($source === 'Nginx config' && $path === $expectedDocroot) {
      reportOk("$source docroot: $path");
    } elseif ($source === 'Supervisor config' && $path === '/var/www/jaraba') {
      reportOk("$source directory: $path");
    } elseif ($path !== '' && !str_contains($path, '/var/www/jaraba')) {
      reportWarn("$source uses unexpected path: $path (expected /var/www/jaraba/*)");
      $warnings++;
    }
  }
}

echo "\n";

// ============================================================================
// CHECK 10: Trusted host patterns vs Domain entities vs Nginx.
// ============================================================================
echo "=== CHECK 10: Multi-domain consistency (settings vs Nginx vs Domains) ===\n";

// Extract trusted_host_patterns from settings.production.php.
$trustedPatterns = [];
if (preg_match_all("/'(\^[^']+)'/", $settingsProdContent, $m)) {
  $trustedPatterns = $m[1];
}

// Extract Nginx server_name directives.
$nginxHosts = [];
if (preg_match_all('/server_name\s+([^;]+);/', $nginxContent, $m)) {
  foreach ($m[1] as $serverNames) {
    foreach (preg_split('/\s+/', trim($serverNames)) as $host) {
      if ($host !== '' && $host !== '_') {
        $nginxHosts[] = $host;
      }
    }
  }
}
$nginxHosts = array_unique($nginxHosts);

// Extract Domain entities from config/sync.
$configSyncDir = $projectRoot . '/config/sync';
$domainHosts = [];
foreach (glob($configSyncDir . '/domain.record.*.yml') as $file) {
  $content = file_get_contents($file);
  if (preg_match('/^hostname:\s*[\'"]?([^\s\'"]+)/m', $content, $dm)) {
    $domainHosts[] = $dm[1];
  }
}

echo "  Trusted host patterns: " . count($trustedPatterns) . "\n";
echo "  Nginx server_names: " . count($nginxHosts) . "\n";
echo "  Domain entities: " . count($domainHosts) . "\n";

// Check each Nginx host is covered by trusted_host_patterns.
$uncoveredByTrusted = [];
foreach ($nginxHosts as $host) {
  if (str_starts_with($host, '*.')) {
    continue; // Wildcards handled by pattern.
  }
  $covered = false;
  foreach ($trustedPatterns as $pattern) {
    if (@preg_match('/' . $pattern . '/', $host)) {
      $covered = true;
      break;
    }
  }
  if (!$covered) {
    $uncoveredByTrusted[] = $host;
  }
}

if (count($uncoveredByTrusted) > 0) {
  reportError(
    'Nginx hosts NOT covered by trusted_host_patterns: ' . implode(', ', $uncoveredByTrusted),
    'Drupal will reject requests for these hosts with 400 Bad Request.'
  );
  $errors++;
} else {
  reportOk('All Nginx hosts covered by trusted_host_patterns');
}

// Check each non-wildcard Nginx host has a Domain entity (DOMAIN-ROUTE-CACHE-001).
$nonWildcardNginx = array_filter($nginxHosts, fn($h) => !str_starts_with($h, '*.'));
$missingDomainEntities = array_diff($nonWildcardNginx, $domainHosts);
// Exclude www. variants — they redirect.
$missingDomainEntities = array_filter($missingDomainEntities, fn($h) => !str_starts_with($h, 'www.'));

if (count($missingDomainEntities) > 0) {
  reportWarn(
    'Hosts in Nginx without Domain entity: ' . implode(', ', $missingDomainEntities),
    'DOMAIN-ROUTE-CACHE-001: RouteProvider caches by domain. Missing entities cause cache collisions.'
  );
  $warnings++;
} else {
  reportOk('All production hosts have Domain entities (DOMAIN-ROUTE-CACHE-001)');
}

echo "\n";

// ============================================================================
// CHECK 11: Environment-specific code paths.
// ============================================================================
echo "=== CHECK 11: Environment-specific code paths ===\n";

// Scan for LANDO-specific conditionals that might hide prod-only bugs.
$landoChecks = [];
$modulesDir = $projectRoot . '/web/modules/custom';
$themeDir = $projectRoot . '/web/themes/custom';

$phpFiles = [];
foreach ([$modulesDir, $themeDir] as $searchDir) {
  if (!is_dir($searchDir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($searchDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );
  foreach ($iterator as $file) {
    $ext = $file->getExtension();
    if ($ext === 'php' || $ext === 'theme' || $ext === 'module') {
      $phpFiles[] = $file->getPathname();
    }
  }
}

// Use a more targeted search for LANDO env checks in PHP.
$envSpecificPatterns = [
  'getenv(\'LANDO\')' => 'LANDO environment detection',
  'getenv("LANDO")' => 'LANDO environment detection',
  '$_ENV[\'LANDO\']' => 'LANDO environment variable access',
  'LANDO_INFO' => 'Lando service info',
];

$envCodePaths = 0;
foreach ($phpFiles as $file) {
  $content = file_get_contents($file);
  foreach ($envSpecificPatterns as $pattern => $desc) {
    if (str_contains($content, $pattern)) {
      $relativePath = str_replace($projectRoot . '/', '', $file);
      $landoChecks[] = "$relativePath: $desc";
      $envCodePaths++;
    }
  }
}

if ($envCodePaths > 0) {
  reportWarn("$envCodePaths file(s) contain LANDO-specific code paths:");
  foreach ($landoChecks as $check) {
    echo "         $check\n";
  }
  echo "         These code branches only execute in dev — production follows a different path.\n";
  echo "         Ensure both paths have test coverage.\n";
  $warnings++;
} else {
  reportOk('No LANDO-specific code paths found in custom modules/themes');
}

echo "\n";

// ============================================================================
// CHECK 12: Composer.lock freshness and platform consistency.
// ============================================================================
echo "=== CHECK 12: Composer.lock freshness ===\n";

if (!file_exists($composerLock)) {
  reportError('composer.lock NOT FOUND — dependency versions are not locked');
  $errors++;
} elseif (!file_exists($composerJson)) {
  reportWarn('composer.json not found — cannot verify lock freshness');
  $warnings++;
} else {
  $lockMtime = filemtime($composerLock);
  $jsonMtime = filemtime($composerJson);

  if ($jsonMtime > $lockMtime) {
    reportError(
      'composer.json is NEWER than composer.lock',
      'Run "composer update" to regenerate lock file before deploying.'
    );
    $errors++;
  } else {
    reportOk('composer.lock is up-to-date with composer.json');
  }

  // Check content hash.
  if ($composerLockContent !== '') {
    $lockData = json_decode($composerLockContent, true);
    $lockContentHash = $lockData['content-hash'] ?? null;

    if ($lockContentHash !== null) {
      // content-hash confirms the lock was generated by Composer (not hand-edited).
      reportOk("composer.lock has content-hash: " . substr($lockContentHash, 0, 12) . '...');
    }
  }

  // Check platform overrides.
  if (isset($composer['config']['platform'])) {
    $platform = $composer['config']['platform'];
    echo "  Platform overrides:\n";
    foreach ($platform as $key => $value) {
      echo "    $key: $value\n";
    }
    reportOk('Platform overrides documented in composer.json');
  } else {
    reportWarn(
      'No platform config in composer.json',
      'Consider adding config.platform.php to lock dependency resolution to production PHP version.'
    );
    $warnings++;
  }
}

echo "\n";

// ============================================================================
// CHECK 13: Reverse proxy coherence (Nginx vs Traefik decision).
// ============================================================================
echo "=== CHECK 13: Reverse proxy configuration coherence ===\n";

$hasNginxConfig = file_exists($nginxConf)
  || file_exists($projectRoot . '/config/deploy/nginx-jaraba-common.conf');
$hasTraefikConfig = false;
$hasTraefikInDocs = false;

// Check for Traefik references in executable config (docker-compose, toml, yaml).
$traefikSources = glob($projectRoot . '/docker-compose*.yml') ?: [];
$traefikSources = array_merge($traefikSources, glob($projectRoot . '/config/deploy/traefik*') ?: []);
foreach ($traefikSources as $file) {
  if (str_contains(file_get_contents($file), 'traefik')) {
    $hasTraefikConfig = true;
  }
}

// Check runbook/docs for Traefik mentions (advisory only, not blocking).
foreach ($runbookFiles as $file) {
  $content = file_get_contents($file);
  if (stripos($content, 'traefik') !== false) {
    $hasTraefikInDocs = true;
  }
}

if ($hasNginxConfig && $hasTraefikConfig) {
  reportError(
    'BOTH Nginx AND Traefik executable configs found — ambiguous reverse proxy strategy',
    'config/deploy/ has Nginx configs AND docker-compose references Traefik.'
  );
  echo "          Decision required: choose ONE reverse proxy. Nginx is already fully configured.\n";
  $errors++;
} elseif ($hasNginxConfig && $hasTraefikInDocs) {
  reportWarn(
    'Nginx configs active but runbook/docs mention Traefik — potential documentation drift',
    'Consider updating docs to reflect Nginx as the chosen reverse proxy.'
  );
  $warnings++;
} elseif ($hasNginxConfig) {
  reportOk('Nginx is the sole reverse proxy (configs in config/deploy/)');
} elseif ($hasTraefikConfig) {
  reportOk('Traefik is the sole reverse proxy (docker-compose labels)');
} else {
  reportWarn('No reverse proxy configuration found');
  $warnings++;
}

echo "\n";

// ============================================================================
// CHECK 14: Wildcard SSL strategy for multi-tenancy.
// ============================================================================
echo "=== CHECK 14: Wildcard SSL strategy ===\n";

$wildcardDomains = [];
foreach ($trustedPatterns as $pattern) {
  if (str_contains($pattern, '.+\\.') || str_contains($pattern, '.*\\.')) {
    // Extract base domain.
    $cleaned = preg_replace('/[\^\$]/', '', $pattern);
    $cleaned = str_replace(['.+\\.', '.*\\.', '\\.'], ['*.', '*.', '.'], $cleaned);
    $wildcardDomains[] = $cleaned;
  }
}

if (count($wildcardDomains) > 0) {
  echo "  Wildcard patterns detected: " . implode(', ', $wildcardDomains) . "\n";

  // Check if DNS challenge is documented.
  $dnsChallengeDocs = false;
  $sslPatterns = ['dns-01', 'dns_challenge', 'DNS challenge', 'dns challenge', 'certbot.*dns', 'cloudflare.*api'];

  $docsToCheck = array_merge(
    glob($projectRoot . '/docs/operaciones/*.md') ?: [],
    glob($projectRoot . '/docs/tecnicos/*deploy*.md') ?: [],
    glob($projectRoot . '/docs/tecnicos/*runbook*.md') ?: [],
    glob($projectRoot . '/docs/tecnicos/*Runbook*.md') ?: [],
    [$nginxConf],
  );

  foreach ($docsToCheck as $file) {
    if (!file_exists($file)) {
      continue;
    }
    $content = file_get_contents($file);
    foreach ($sslPatterns as $pattern) {
      if (preg_match("/$pattern/i", $content)) {
        $dnsChallengeDocs = true;
        break 2;
      }
    }
  }

  if (!$dnsChallengeDocs) {
    reportError(
      'Wildcard SSL required for tenant subdomains but NO DNS challenge strategy documented',
      'Let\'s Encrypt wildcard certs require DNS-01 challenge (not HTTP-01).'
    );
    echo "          Options: (1) Cloudflare DNS API + certbot-dns-cloudflare plugin,\n";
    echo "          (2) IONOS DNS API + certbot-dns-ionos, (3) Commercial wildcard cert.\n";
    echo "          This is BLOCKING for multi-tenant subdomain deployment.\n";
    $errors++;
  } else {
    reportOk('DNS challenge strategy documented for wildcard SSL');
  }
} else {
  reportOk('No wildcard domains detected (multi-tenancy via path-based routing)');
}

echo "\n";

// ============================================================================
// SUMMARY.
// ============================================================================
echo "============================================================\n";
echo "  ENV-PARITY-001: $errors error(s), $warnings warning(s)\n";
echo "============================================================\n";

if ($errors > 0) {
  echo "  [FAIL] $errors parity violation(s) detected.\n";
  echo "         Fix errors before deploying to production.\n\n";
  exit(1);
}

if ($warnings > 0) {
  echo "  [WARN] $warnings advisory warning(s) — review before deploy.\n\n";
  exit(0);
}

echo "  [PASS] Dev/Prod environment parity verified.\n\n";
exit(0);
