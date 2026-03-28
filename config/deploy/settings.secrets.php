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
// OAUTH-REDIRECT-URI-001: Canonical base URL for OAuth callbacks.
// MUST match exactly what is registered in provider consoles.
// ============================================================================
if ($oauth_base = getenv('OAUTH_CALLBACK_BASE_URL')) {
  $settings['oauth_callback_base_url'] = $oauth_base;
}


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
// EMAIL — AWS SES SMTP Transport (EMAIL-DEDICATED-IP-001)
// SMTP credentials from IAM user jaraba-ses-smtp-prod (eu-central-1).
// STRIPE-ENV-UNIFY-001 pattern: secrets via getenv(), NEVER in config/sync/.
// ============================================================================

if ($ses_user = getenv('SES_SMTP_USER')) {
  $config['symfony_mailer.mailer_transport.smtp_ses']['configuration']['user'] = $ses_user;
}
if ($ses_pass = getenv('SES_SMTP_PASS')) {
  $config['symfony_mailer.mailer_transport.smtp_ses']['configuration']['pass'] = $ses_pass;
}
if ($ses_host = getenv('SES_SMTP_HOST')) {
  $config['symfony_mailer.mailer_transport.smtp_ses']['configuration']['host'] = $ses_host;
}
if ($ses_port = getenv('SES_SMTP_PORT')) {
  $config['symfony_mailer.mailer_transport.smtp_ses']['configuration']['port'] = (int) $ses_port;
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
  // Unify: FOC (Connect/marketplace) and Legal Billing use the same secret key.
  $config['jaraba_foc.settings']['stripe_secret_key'] = $stripe_secret;
  $config['jaraba_legal_billing.settings']['stripe_secret_key'] = $stripe_secret;
}
if ($stripe_webhook = getenv('STRIPE_WEBHOOK_SECRET')) {
  $config['ecosistema_jaraba_core.stripe']['webhook_secret'] = $stripe_webhook;
}
if ($stripe_foc_webhook = getenv('STRIPE_FOC_WEBHOOK_SECRET')) {
  $config['jaraba_foc.settings']['stripe_webhook_secret'] = $stripe_foc_webhook;
}

// ============================================================================
// AI — Claude API Key (SEC-C01: migrated from Key module config to env)
// ============================================================================

if ($claude_api_key = getenv('CLAUDE_API_KEY')) {
  $config['jaraba_copilot_v2.settings']['claude_api_key_value'] = $claude_api_key;
}

// ============================================================================
// SUPPORT — Attachment HMAC Secret (SEC-C02: removed hardcoded fallback)
// ============================================================================

if ($attachment_hmac = getenv('SUPPORT_ATTACHMENT_HMAC_SECRET')) {
  $config['jaraba_support.settings']['attachment_hmac_secret'] = $attachment_hmac;
}

// ============================================================================
// SEO-DEPLOY-NOTIFY-001: Google Search Console OAuth credentials.
// Uses same Google Cloud project as Social Auth Google.
// Required for sitemap submission and Indexing API notifications.
// Redirect URI: https://plataformadeecosistemas.com/es/admin/config/services/insights-hub/connect
// ============================================================================

if ($gsc_client_id = getenv('GOOGLE_SEARCH_CONSOLE_CLIENT_ID')) {
  $config['jaraba_insights_hub.settings']['search_console_client_id'] = $gsc_client_id;
}
if ($gsc_client_secret = getenv('GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET')) {
  $config['jaraba_insights_hub.settings']['search_console_client_secret'] = $gsc_client_secret;
}
