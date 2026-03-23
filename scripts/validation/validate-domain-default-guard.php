#!/usr/bin/env php
<?php

/**
 * @file
 * DOMAIN-DEFAULT-GUARD-001: Verifica que los domain records con is_default=true
 * en config/sync NO tienen un Tenant entity asociado via domain_id.
 *
 * El dominio default es el sitio SaaS principal. Si un Tenant lo referencia,
 * MetaSiteResolverService lo resuelve como meta-sitio, desactivando mega menú,
 * CTA y footer powered_by.
 *
 * Verificación estática contra config/sync/ (no requiere DB).
 *
 * Exit codes: 0 = OK, 1 = default domain has tenant reference
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$configDir = $root . '/config/sync';

// Step 1: Find default domain records.
$defaultDomains = [];
$domainFiles = glob($configDir . '/domain.record.*.yml');

foreach ($domainFiles as $file) {
  $content = file_get_contents($file);
  if (preg_match('/^is_default:\s*true/m', $content)) {
    if (preg_match('/^id:\s*(.+)$/m', $content, $idMatch)) {
      $domainId = trim($idMatch[1]);
      if (preg_match('/^hostname:\s*(.+)$/m', $content, $hostMatch)) {
        $hostname = trim($hostMatch[1]);
        $defaultDomains[$domainId] = $hostname;
        echo "Default domain: $domainId ($hostname)\n";
      }
    }
  }
}

if (empty($defaultDomains)) {
  echo "WARN: No default domain found in config/sync/\n";
  echo "PASS: DOMAIN-DEFAULT-GUARD-001 (no default domain to check)\n";
  exit(0);
}

// Step 2: Check if any Tenant entity has domain_id referencing a default domain.
// We check via seed scripts and update hooks since Tenant entities are in DB.
// Also check config entities that might reference domain_id.
$errors = 0;

// Check seed scripts for hardcoded tenant-domain assignments.
$seedFiles = glob($root . '/scripts/migration/*.php');
$seedFiles = array_merge($seedFiles, glob($root . '/scripts/*.php'));

foreach ($seedFiles as $file) {
  $content = file_get_contents($file);
  foreach ($defaultDomains as $domainId => $hostname) {
    if (strpos($content, "'domain_id' => '$domainId'") !== false ||
        strpos($content, "\"domain_id\" => \"$domainId\"") !== false) {
      $relPath = str_replace($root . '/', '', $file);
      echo "ERROR: $relPath assigns default domain '$domainId' to a tenant\n";
      $errors++;
    }
  }
}

// Step 3: Check that MetaSiteResolverService has isDefault guard.
$resolverFile = $root . '/web/modules/custom/jaraba_site_builder/src/Service/MetaSiteResolverService.php';
if (file_exists($resolverFile)) {
  $resolverContent = file_get_contents($resolverFile);
  if (strpos($resolverContent, 'isDefault()') !== false) {
    echo "OK: MetaSiteResolverService has isDefault() guard\n";
  } else {
    echo "ERROR: MetaSiteResolverService missing isDefault() guard in Strategy 1\n";
    $errors++;
  }
}

echo "\n";
if ($errors > 0) {
  echo "FAIL: $errors domain-default-guard violations\n";
  exit(1);
}

echo "PASS: DOMAIN-DEFAULT-GUARD-001 — default domain is protected\n";
exit(0);
