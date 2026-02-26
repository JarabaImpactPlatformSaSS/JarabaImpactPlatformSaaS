<?php

/**
 * @file
 * Secret configuration overrides via environment variables.
 *
 * This file maps environment variables to Drupal $config overrides.
 * $config overrides are runtime-only — they are NOT stored in the database
 * and NOT exported by `drush config:export`, keeping secrets out of git.
 *
 * Usage: Include from settings.php:
 *   if (file_exists($app_root . '/../config/deploy/settings.secrets.php')) {
 *     include $app_root . '/../config/deploy/settings.secrets.php';
 *   }
 *
 * Environment variables should be set in:
 *   - Production: hosting provider environment variables panel
 *   - Lando: .lando.local.yml overrides → services.appserver.overrides.environment
 *   - CI: pipeline secret variables
 *
 * @see .env.example for the complete list of required variables.
 */

// ============================================================================
// SOCIAL AUTH — OAuth Providers
// ============================================================================

// Google OAuth 2.0.
if ($google_client_id = getenv('SOCIAL_AUTH_GOOGLE_CLIENT_ID')) {
  $config['social_auth_google.settings']['client_id'] = $google_client_id;
}
if ($google_client_secret = getenv('SOCIAL_AUTH_GOOGLE_CLIENT_SECRET')) {
  $config['social_auth_google.settings']['client_secret'] = $google_client_secret;
}

// LinkedIn OAuth 2.0.
if ($linkedin_client_id = getenv('SOCIAL_AUTH_LINKEDIN_CLIENT_ID')) {
  $config['social_auth_linkedin.settings']['client_id'] = $linkedin_client_id;
}
if ($linkedin_client_secret = getenv('SOCIAL_AUTH_LINKEDIN_CLIENT_SECRET')) {
  $config['social_auth_linkedin.settings']['client_secret'] = $linkedin_client_secret;
}

// Microsoft / Azure AD OAuth 2.0.
if ($microsoft_client_id = getenv('SOCIAL_AUTH_MICROSOFT_CLIENT_ID')) {
  $config['social_auth_microsoft.settings']['client_id'] = $microsoft_client_id;
}
if ($microsoft_client_secret = getenv('SOCIAL_AUTH_MICROSOFT_CLIENT_SECRET')) {
  $config['social_auth_microsoft.settings']['client_secret'] = $microsoft_client_secret;
}

// ============================================================================
// EMAIL — SMTP Transport
// ============================================================================

if ($smtp_user = getenv('SMTP_USER')) {
  $config['symfony_mailer.mailer_transport.smtp_ionos']['configuration']['user'] = $smtp_user;
}
if ($smtp_pass = getenv('SMTP_PASS')) {
  $config['symfony_mailer.mailer_transport.smtp_ionos']['configuration']['pass'] = $smtp_pass;
}
if ($smtp_host = getenv('SMTP_HOST')) {
  $config['symfony_mailer.mailer_transport.smtp_ionos']['configuration']['host'] = $smtp_host;
}

// ============================================================================
// RECAPTCHA v3
// ============================================================================

if ($recaptcha_site_key = getenv('RECAPTCHA_SITE_KEY')) {
  $config['recaptcha_v3.settings']['site_key'] = $recaptcha_site_key;
}
if ($recaptcha_secret_key = getenv('RECAPTCHA_SECRET_KEY')) {
  $config['recaptcha_v3.settings']['secret_key'] = $recaptcha_secret_key;
}

// ============================================================================
// STRIPE — Payment Gateway
// ============================================================================

if ($stripe_public = getenv('STRIPE_PUBLIC_KEY')) {
  $config['ecosistema_jaraba_core.stripe']['public_key'] = $stripe_public;
}
if ($stripe_secret = getenv('STRIPE_SECRET_KEY')) {
  $config['ecosistema_jaraba_core.stripe']['secret_key'] = $stripe_secret;
}
if ($stripe_webhook = getenv('STRIPE_WEBHOOK_SECRET')) {
  $config['ecosistema_jaraba_core.stripe']['webhook_secret'] = $stripe_webhook;
}
