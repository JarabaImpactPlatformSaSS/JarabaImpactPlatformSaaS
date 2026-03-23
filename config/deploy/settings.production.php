<?php

/**
 * @file
 * Production-specific settings for IONOS Dedicated Server.
 *
 * This file MUST be included from settings.local.php on the production server:
 *
 *   // In web/sites/default/settings.local.php on IONOS:
 *   include $app_root . '/../config/deploy/settings.production.php';
 *
 * It sets platform-level $settings that are NOT secrets (those go in
 * settings.secrets.php via environment variables) but ARE environment-specific.
 *
 * SECRET-MGMT-001: No secrets here. Only structural configuration.
 */

// ============================================================================
// PLATFORM BASE DOMAIN
// ============================================================================
// Source of truth for tenant CNAME targets and subdomain provisioning.
// Used by: Tenant::provisionDomainIfNeeded(), TenantOnboardingService,
//          TenantDomainSettingsForm (DNS guide CNAME target).
// Default in code: 'jaraba-saas.lndo.site' (development).
$settings['jaraba_base_domain'] = 'plataformadeecosistemas.com';

// ============================================================================
// TRUSTED HOST PATTERNS — Production
// ============================================================================
// Override Lando patterns with production-only domains.
// DOMAIN-ROUTE-CACHE-001: each hostname needs a matching Domain entity.
$settings['trusted_host_patterns'] = [
  // SaaS base domain + all tenant subdomains (*.plataformadeecosistemas.com).
  '^plataformadeecosistemas\.com$',
  '^.+\.plataformadeecosistemas\.com$',
  // Meta-sitio corporativo PED S.L.
  '^plataformadeecosistemas\.es$',
  '^www\.plataformadeecosistemas\.es$',
  // Marca personal Pepe Jaraba.
  '^pepejaraba\.com$',
  '^www\.pepejaraba\.com$',
  // Jaraba Impact B2B.
  '^jarabaimpact\.com$',
  '^www\.jarabaimpact\.com$',
  // jaraba.es (reservado, futuro).
  '^jaraba\.es$',
  '^.+\.jaraba\.es$',
];

// ============================================================================
// BASE URL — CLI & Cron
// ============================================================================
// Drupal uses $base_url to generate absolute URLs. In CLI context (drush, cron,
// Supervisor), there is no HTTP request, so $GLOBALS['base_url'] defaults to
// "http://default". This breaks all generated URLs in emails, tokens, etc.
// Setting $base_url here ensures correct URLs in ALL contexts.
$base_url = 'https://plataformadeecosistemas.com';

// ============================================================================
// PERFORMANCE — Production Tuning
// ============================================================================
$settings['cache']['default'] = 'cache.backend.redis';

// Aggregate CSS/JS in production.
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;
$config['system.performance']['cache']['page']['max_age'] = 3600;

// Disable render cache debug.
$settings['cache']['bins']['render'] = 'cache.backend.redis';
$settings['cache']['bins']['discovery'] = 'cache.backend.redis';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.redis';
$settings['cache']['bins']['page'] = 'cache.backend.redis';

// ============================================================================
// FILE SYSTEM
// ============================================================================
$settings['file_private_path'] = '/var/www/jaraba/private';
$settings['file_temp_path'] = '/tmp';

// ============================================================================
// ERROR HANDLING — Production
// ============================================================================
$config['system.logging']['error_level'] = 'hide';

// ============================================================================
// REVERSE PROXY / HTTPS DETECTION
// ============================================================================
// CSRF-LOGIN-FIX-001 v2: IONOS infrastructure terminates SSL before Apache.
// The canonical fix is in settings.php (applied by patch-settings-csrf.php).
// This block is a defense-in-depth fallback for manual deployments.
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
  }
  if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
    $_SERVER['HTTPS'] = 'on';
  }
}
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = [$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'];
$settings['reverse_proxy_trusted_headers'] =
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;
