<?php

/**
 * @file
 * REDIS-ACL-001: Valida configuracion Redis 8.0 para Jaraba Impact Platform.
 *
 * 14 checks:
 *  1. redis.conf NO contiene rename-command (legacy, deprecated)
 *  2. redis.conf referencia aclfile
 *  3. users.acl existe y define >= 3 usuarios (default + admin + sentinel)
 *  4. users.acl bloquea @dangerous para default user
 *  5. users.acl restringe key-pattern para default user (jaraba_*)
 *  6. redis.conf tiene io-threads >= 2
 *  7. redis.conf tiene lazyfree-lazy-eviction yes
 *  8. redis.conf tiene maxmemory-policy allkeys-lru
 *  9. redis.conf NO contiene requirepass (ACL replaces it)
 * 10. docker-compose.yml usa redis:8-alpine (no redis:7)
 * 11. .lando/redis.conf tiene io-threads
 * 12. settings.php cache_prefix = 'jaraba_' (matches ACL key-pattern)
 * 13. sentinel.conf uses auth-user (dedicated sentinel user for Redis 8 ACL)
 * 14. users.acl sentinel user has CONFIG permission (required for failover)
 *
 * Usage:
 *   php scripts/validation/validate-redis-config.php
 */

$errors = [];
$warnings = [];
$passed = 0;
$total = 14;

$base = dirname(__DIR__, 2);

// File paths.
$prod_redis_conf   = $base . '/infrastructure/ha/redis/redis.conf';
$prod_users_acl    = $base . '/infrastructure/ha/redis/users.acl';
$ha_docker_compose = $base . '/infrastructure/ha/docker-compose.yml';
$lando_redis_conf  = $base . '/.lando/redis.conf';
$settings_php      = $base . '/web/sites/default/settings.php';

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------
function check(string $label, bool $condition, string $error_msg, array &$errors, array &$warnings, int &$passed, bool $is_warning = FALSE): void {
  if ($condition) {
    echo "  \033[32m[PASS]\033[0m $label\n";
    $passed++;
  }
  elseif ($is_warning) {
    echo "  \033[33m[WARN]\033[0m $label — $error_msg\n";
    $warnings[] = "$label: $error_msg";
    $passed++; // Warnings still count as passed.
  }
  else {
    echo "  \033[31m[FAIL]\033[0m $label — $error_msg\n";
    $errors[] = "$label: $error_msg";
  }
}

echo "\n=== REDIS-ACL-001: Redis 8.0 Configuration Validator ===\n\n";

// ---------------------------------------------------------------------------
// Check 1: No rename-command in production redis.conf
// ---------------------------------------------------------------------------
$redis_conf = file_exists($prod_redis_conf) ? file_get_contents($prod_redis_conf) : '';
$has_active_rename = !empty($redis_conf) && preg_match('/^\s*rename-command\s+/m', $redis_conf);
check(
  '1/14 No rename-command (legacy deprecated)',
  !empty($redis_conf) && !$has_active_rename,
  'redis.conf still contains active rename-command directives. Migrate to ACLs (REDIS-ACL-001)',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 2: aclfile reference in redis.conf
// ---------------------------------------------------------------------------
check(
  '2/14 aclfile reference in redis.conf',
  !empty($redis_conf) && preg_match('/^aclfile\s+/m', $redis_conf),
  'redis.conf does not reference an aclfile. Add: aclfile /usr/local/etc/redis/users.acl',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 3: users.acl exists with >= 2 users
// ---------------------------------------------------------------------------
$acl_content = file_exists($prod_users_acl) ? file_get_contents($prod_users_acl) : '';
$acl_user_count = 0;
if (!empty($acl_content)) {
  preg_match_all('/^user\s+\S+/m', $acl_content, $matches);
  $acl_user_count = count($matches[0]);
}
check(
  '3/14 users.acl defines >= 3 users',
  $acl_user_count >= 3,
  "users.acl defines $acl_user_count users (need >= 3: default + admin + sentinel)",
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 4: default user blocks @dangerous
// ---------------------------------------------------------------------------
$default_line = '';
if (!empty($acl_content)) {
  preg_match('/^user\s+default\s+.+$/m', $acl_content, $match);
  $default_line = $match[0] ?? '';
}
check(
  '4/14 default user blocks @dangerous',
  !empty($default_line) && strpos($default_line, '-@dangerous') !== FALSE,
  'default user in users.acl does not block @dangerous commands',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 5: default user restricts key-pattern to jaraba_*
// ---------------------------------------------------------------------------
check(
  '5/14 default user key-pattern includes jaraba_*',
  !empty($default_line) && strpos($default_line, '~jaraba_') !== FALSE,
  'default user should restrict keys to ~jaraba_* pattern for defense in depth',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 6: io-threads >= 2 in production
// ---------------------------------------------------------------------------
$io_threads = 0;
if (preg_match('/^io-threads\s+(\d+)/m', $redis_conf, $m)) {
  $io_threads = (int) $m[1];
}
check(
  '6/14 io-threads >= 2 (production)',
  $io_threads >= 2,
  "io-threads = $io_threads (should be >= 2 for multi-core EPYC)",
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 7: lazyfree-lazy-eviction enabled
// ---------------------------------------------------------------------------
check(
  '7/14 lazyfree-lazy-eviction enabled',
  !empty($redis_conf) && preg_match('/^lazyfree-lazy-eviction\s+yes/m', $redis_conf),
  'lazyfree-lazy-eviction not enabled. Required for SaaS with large cache evictions',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 8: maxmemory-policy allkeys-lru
// ---------------------------------------------------------------------------
check(
  '8/14 maxmemory-policy allkeys-lru',
  !empty($redis_conf) && preg_match('/^maxmemory-policy\s+allkeys-lru/m', $redis_conf),
  'maxmemory-policy should be allkeys-lru for cache backend',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 9: No requirepass (ACL replaces it)
// ---------------------------------------------------------------------------
check(
  '9/14 No requirepass in redis.conf (ACL replaces it)',
  !empty($redis_conf) && !preg_match('/^requirepass\s+/m', $redis_conf),
  'redis.conf still uses requirepass. With ACL file, passwords are in users.acl',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 10: docker-compose uses redis:8-alpine
// ---------------------------------------------------------------------------
$dc_content = file_exists($ha_docker_compose) ? file_get_contents($ha_docker_compose) : '';
$has_redis7 = !empty($dc_content) && preg_match('/redis:\s*7/', $dc_content);
$has_redis8 = !empty($dc_content) && preg_match('/redis:\s*8/', $dc_content);
check(
  '10/14 docker-compose uses redis:8-alpine',
  $has_redis8 && !$has_redis7,
  $has_redis7 ? 'docker-compose.yml still references redis:7' : 'docker-compose.yml not found or no redis image',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 11: Lando redis.conf has io-threads
// ---------------------------------------------------------------------------
$lando_conf = file_exists($lando_redis_conf) ? file_get_contents($lando_redis_conf) : '';
check(
  '11/14 Lando redis.conf has io-threads (dev parity)',
  !empty($lando_conf) && preg_match('/^io-threads\s+\d+/m', $lando_conf),
  'Lando redis.conf lacks io-threads. Add for dev/prod parity',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 12: settings.php cache_prefix matches ACL key-pattern
// ---------------------------------------------------------------------------
$settings_content = file_exists($settings_php) ? file_get_contents($settings_php) : '';
$has_jaraba_prefix = !empty($settings_content) && preg_match("/cache_prefix.*['\"]jaraba_['\"]/", $settings_content);
check(
  '12/14 cache_prefix = jaraba_ (matches ACL key-pattern)',
  $has_jaraba_prefix,
  'settings.php cache_prefix must be "jaraba_" to match ACL ~jaraba_* restriction',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 13: sentinel.conf uses auth-user (dedicated sentinel user)
// ---------------------------------------------------------------------------
$sentinel_conf_path = $base . '/infrastructure/ha/redis/sentinel.conf';
$sentinel_conf = file_exists($sentinel_conf_path) ? file_get_contents($sentinel_conf_path) : '';
check(
  '13/14 sentinel.conf uses auth-user (dedicated sentinel user)',
  !empty($sentinel_conf) && preg_match('/^sentinel\s+auth-user\s+/m', $sentinel_conf),
  'sentinel.conf should use "sentinel auth-user" for dedicated ACL user (not default)',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 14: sentinel user in ACL has CONFIG permission for failover
// ---------------------------------------------------------------------------
$sentinel_acl_line = '';
if (!empty($acl_content)) {
  preg_match('/^user\s+sentinel\s+.+$/m', $acl_content, $match);
  $sentinel_acl_line = $match[0] ?? '';
}
check(
  '14/14 sentinel user has CONFIG permission (required for failover)',
  !empty($sentinel_acl_line) && strpos($sentinel_acl_line, '+CONFIG') !== FALSE,
  'sentinel user in users.acl needs +CONFIG|SET +CONFIG|REWRITE for failover',
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\n";
$error_count = count($errors);
$warn_count = count($warnings);

if ($error_count === 0) {
  echo "\033[32m=== $passed/$total checks PASSED";
  if ($warn_count > 0) {
    echo " ($warn_count warnings)";
  }
  echo " ===\033[0m\n\n";
  exit(0);
}
else {
  echo "\033[31m=== $passed/$total passed, $error_count FAILED ===\033[0m\n";
  foreach ($errors as $err) {
    echo "  - $err\n";
  }
  echo "\n";
  exit(1);
}
