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
// REVERSE PROXY / CDN
// ============================================================================
// Descomentar si se usa Cloudflare u otro proxy delante de IONOS:
// $settings['reverse_proxy'] = TRUE;
// $settings['reverse_proxy_addresses'] = ['IP_DEL_PROXY'];
// $settings['reverse_proxy_header'] = 'X-Forwarded-For';
