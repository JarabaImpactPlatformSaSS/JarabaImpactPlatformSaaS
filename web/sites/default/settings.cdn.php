<?php

/**
 * @file
 * AUDIT-PERF-N14: CDN configuration for static assets.
 *
 * This file configures CDN integration for serving public files (images,
 * CSS aggregates, JS aggregates, user-uploaded content) from an external
 * CDN origin. It is safe to track in version control because it reads
 * all sensitive values from environment variables.
 *
 * SETUP:
 *   1. Set the CDN_BASE_URL environment variable on your hosting provider.
 *      Example: CDN_BASE_URL=https://cdn.jarabaimpact.com
 *
 *   2. Ensure this file is included from settings.php:
 *      @code
 *      if (file_exists($app_root . '/' . $site_path . '/settings.cdn.php')) {
 *        include $app_root . '/' . $site_path . '/settings.cdn.php';
 *      }
 *      @endcode
 *
 *   3. Configure your CDN to pull from the origin domain with the path
 *      /sites/default/files as the origin path.
 *
 * Without CDN_BASE_URL set, this file has no effect and the site
 * continues to serve files from the local domain.
 */

// AUDIT-PERF-N14: CDN base URL for public files.
// When CDN_BASE_URL is defined, Drupal rewrites all public file URLs
// (e.g. /sites/default/files/image.jpg) to use the CDN domain instead.
if ($cdn_url = getenv('CDN_BASE_URL')) {
  // Remove trailing slash if present to avoid double slashes.
  $cdn_url = rtrim($cdn_url, '/');

  // AUDIT-PERF-N14: Rewrite public file base URL to CDN.
  // This affects all files served via the public:// stream wrapper,
  // including uploaded images, aggregated CSS/JS, and managed files.
  $settings['file_public_base_url'] = $cdn_url . '/sites/default/files';
}

// AUDIT-PERF-N14: CSS/JS aggregation and performance defaults for production.
// These ensure that assets served (via CDN or origin) are optimally compressed
// and aggregated. These can be overridden in settings.local.php for development.
if (getenv('LANDO') !== 'ON') {
  $config['system.performance']['css']['preprocess'] = TRUE;
  $config['system.performance']['js']['preprocess'] = TRUE;
  $config['system.performance']['css']['gzip'] = TRUE;
  $config['system.performance']['js']['gzip'] = TRUE;

  // AUDIT-PERF-N14: Set page cache max-age to 15 minutes for anonymous traffic.
  // CDN edge nodes respect this header for HTML responses.
  // Static assets use far-future expires set via .htaccess.
  if (!isset($config['system.performance']['cache']['page']['max_age'])) {
    $config['system.performance']['cache']['page']['max_age'] = 900;
  }
}
