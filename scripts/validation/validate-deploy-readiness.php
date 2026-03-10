<?php

/**
 * @file
 * DEPLOY-READY-001: Validate production deploy readiness.
 *
 * Checks that all required production configuration exists:
 * 1. Domain entities for every production hostname in config/sync/
 * 2. trusted_host_patterns covers all Domain entity hostnames
 * 3. settings.production.php contains jaraba_base_domain
 * 4. Nginx config files exist and reference all production domains
 * 5. SaaS base domain (plataformadeecosistemas.com) has Domain entity
 *
 * Usage: php scripts/validation/validate-deploy-readiness.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$configSyncDir = $projectRoot . '/config/sync';
$deployDir = $projectRoot . '/config/deploy';

$errors = 0;
$warnings = 0;

// ============================================================================
// REQUIRED PRODUCTION HOSTNAMES
// ============================================================================
// Source of truth: these are the hostnames that MUST work in production.
// Update this list when adding new meta-sites or base domains.
$requiredHostnames = [
  'plataformadeecosistemas.com' => 'SaaS base domain (tenant subdomains)',
  'plataformadeecosistemas.es'  => 'Meta-sitio corporativo PED S.L.',
  'pepejaraba.com'              => 'Marca personal Pepe Jaraba',
  'jarabaimpact.com'            => 'Jaraba Impact B2B',
];

// ============================================================================
// CHECK 1: Domain entities in config/sync for every required hostname
// ============================================================================
echo "=== CHECK 1: Domain entities in config/sync ===\n";

$domainConfigs = glob($configSyncDir . '/domain.record.*.yml');
$existingHostnames = [];

foreach ($domainConfigs as $file) {
  $content = file_get_contents($file);
  if (preg_match('/^hostname:\s*[\'"]?([^\s\'"]+)/m', $content, $m)) {
    $existingHostnames[$m[1]] = basename($file);
  }
}

foreach ($requiredHostnames as $hostname => $description) {
  if (isset($existingHostnames[$hostname])) {
    echo "  [OK] $hostname ($description) -> {$existingHostnames[$hostname]}\n";
  } else {
    echo "  [ERROR] MISSING Domain entity for: $hostname ($description)\n";
    echo "          DOMAIN-ROUTE-CACHE-001: Without a Domain entity, RouteProvider\n";
    echo "          caches routes with wrong key. Create via drush or config export.\n";
    $errors++;
  }
}

// ============================================================================
// CHECK 2: settings.production.php exists and has jaraba_base_domain
// ============================================================================
echo "\n=== CHECK 2: settings.production.php ===\n";

$settingsProd = $deployDir . '/settings.production.php';
if (!file_exists($settingsProd)) {
  echo "  [ERROR] Missing: config/deploy/settings.production.php\n";
  echo "          Must set \$settings['jaraba_base_domain'] for production.\n";
  $errors++;
} else {
  $settingsContent = file_get_contents($settingsProd);

  // Check jaraba_base_domain.
  if (preg_match("/\\\$settings\['jaraba_base_domain'\]\s*=\s*'([^']+)'/", $settingsContent, $m)) {
    echo "  [OK] jaraba_base_domain = '{$m[1]}'\n";
  } else {
    echo "  [ERROR] jaraba_base_domain NOT set in settings.production.php\n";
    echo "          Tenants will use Lando fallback domain in production.\n";
    $errors++;
  }

  // Check trusted_host_patterns covers all required hostnames.
  foreach ($requiredHostnames as $hostname => $description) {
    $escapedHost = preg_quote($hostname, '/');
    // Match the escaped version in the patterns (dots as \.).
    $patternHost = str_replace('.', '\\.', $hostname);
    if (strpos($settingsContent, $patternHost) !== false) {
      echo "  [OK] trusted_host_patterns includes $hostname\n";
    } else {
      echo "  [ERROR] trusted_host_patterns MISSING: $hostname ($description)\n";
      echo "          Drupal will return 400 Bad Request for this hostname.\n";
      $errors++;
    }
  }
}

// ============================================================================
// CHECK 3: Nginx config files exist
// ============================================================================
echo "\n=== CHECK 3: Nginx configuration files ===\n";

$nginxFiles = [
  'nginx-metasites.conf'    => 'Server blocks for all domains',
  'nginx-jaraba-common.conf' => 'Shared snippet (security headers, gzip, PHP-FPM)',
];

foreach ($nginxFiles as $file => $description) {
  $path = $deployDir . '/' . $file;
  if (file_exists($path)) {
    echo "  [OK] $file ($description)\n";

    // Verify all required hostnames appear in the metasites config.
    if ($file === 'nginx-metasites.conf') {
      $nginxContent = file_get_contents($path);
      foreach ($requiredHostnames as $hostname => $desc) {
        if (strpos($nginxContent, $hostname) !== false) {
          echo "       [OK] server_name includes $hostname\n";
        } else {
          echo "       [ERROR] server_name MISSING: $hostname\n";
          $errors++;
        }
      }
    }
  } else {
    echo "  [ERROR] Missing: config/deploy/$file ($description)\n";
    $errors++;
  }
}

// ============================================================================
// CHECK 4: Wildcard SSL note for SaaS subdomain
// ============================================================================
echo "\n=== CHECK 4: Wildcard SSL readiness ===\n";

$metasitesConf = $deployDir . '/nginx-metasites.conf';
if (file_exists($metasitesConf)) {
  $nginxContent = file_get_contents($metasitesConf);
  if (strpos($nginxContent, '*.plataformadeecosistemas.com') !== false) {
    echo "  [OK] Nginx wildcard server_name for *.plataformadeecosistemas.com\n";
  } else {
    echo "  [WARN] No wildcard *.plataformadeecosistemas.com in Nginx config\n";
    echo "         Tenant subdomains will not be served without this.\n";
    $warnings++;
  }
} else {
  echo "  [SKIP] Nginx metasites config not found\n";
}

// ============================================================================
// CHECK 5: Deploy checklist exists
// ============================================================================
echo "\n=== CHECK 5: Deploy documentation ===\n";

$checklist = $projectRoot . '/docs/operaciones/20260226-deploy_checklist_ionos.md';
if (file_exists($checklist)) {
  $checklistContent = file_get_contents($checklist);
  $requiredSections = [
    'DNS'             => 'DNS record configuration',
    'Nginx'           => 'Nginx setup instructions',
    'SSL'             => 'SSL/TLS certificate setup',
    'Domain Entities' => 'Domain entity verification (DOMAIN-ROUTE-CACHE-001)',
    'jaraba_base_domain' => 'SaaS base domain setting',
  ];
  foreach ($requiredSections as $keyword => $description) {
    if (stripos($checklistContent, $keyword) !== false) {
      echo "  [OK] Checklist covers: $description\n";
    } else {
      echo "  [WARN] Checklist missing section: $description\n";
      $warnings++;
    }
  }
} else {
  echo "  [WARN] Deploy checklist not found at expected path\n";
  $warnings++;
}

// ============================================================================
// CHECK 6: Domain entity vs trusted_host_patterns cross-validation
// ============================================================================
echo "\n=== CHECK 6: Domain ↔ trusted_host cross-validation ===\n";

// Extract production hostnames from Domain config entities (non-Lando).
$productionDomains = [];
foreach ($domainConfigs as $file) {
  $content = file_get_contents($file);
  if (preg_match('/^hostname:\s*[\'"]?([^\s\'"]+)/m', $content, $m)) {
    $hostname = $m[1];
    // Skip Lando development domains.
    if (str_contains($hostname, 'lndo.site') || str_contains($hostname, 'localhost')) {
      continue;
    }
    // Skip internal-only hostnames.
    if (str_contains($hostname, 'jaraba.io')) {
      continue;
    }
    $productionDomains[$hostname] = basename($file);
  }
}

if (file_exists($settingsProd)) {
  $settingsContent = file_get_contents($settingsProd);
  foreach ($productionDomains as $hostname => $configFile) {
    $patternHost = str_replace('.', '\\.', $hostname);
    if (strpos($settingsContent, $patternHost) !== false) {
      echo "  [OK] Domain '$hostname' covered by trusted_host_patterns\n";
    } else {
      echo "  [ERROR] Domain '$hostname' ($configFile) NOT in trusted_host_patterns\n";
      echo "          Drupal will reject requests to this hostname.\n";
      $errors++;
    }
  }
} else {
  echo "  [SKIP] settings.production.php not found\n";
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n============================================================\n";
echo "  DEPLOY-READY-001: $errors error(s), $warnings warning(s)\n";
echo "============================================================\n";

if ($errors > 0) {
  echo "  [FAIL] Fix $errors error(s) before deploying to production.\n\n";
  exit(1);
}

if ($warnings > 0) {
  echo "  [WARN] $warnings warning(s) — review before deploy.\n\n";
  exit(0);
}

echo "  [PASS] Production deploy readiness verified.\n\n";
exit(0);
