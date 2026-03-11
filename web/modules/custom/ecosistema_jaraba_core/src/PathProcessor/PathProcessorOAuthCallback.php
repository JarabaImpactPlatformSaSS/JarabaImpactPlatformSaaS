<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\PathProcessor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * OAUTH-REDIRECT-URI-001: Normalize OAuth callback URLs.
 *
 * OAuth redirect_uri MUST be a deterministic, fixed string matching EXACTLY
 * what is registered in the provider console (Google, LinkedIn, Microsoft).
 *
 * Two problems in our multi-tenant, multi-language SaaS:
 *
 * 1. LANGUAGE PREFIX: Drupal adds /es/, /en/ based on current language.
 *    The registered callback uses the DEFAULT language prefix (/es/).
 *
 * 2. MULTI-TENANT HOSTNAME: On subdomain tenants (e.g., tenant.domain.com),
 *    Url::fromRoute(['absolute' => TRUE]) uses the REQUEST hostname.
 *    Provider consoles only have the BASE domain registered.
 *
 * Solution: Read OAUTH_CALLBACK_BASE_URL from $settings (injected via .env).
 * This gives a single, explicit, deterministic base URL for all OAuth
 * callbacks. No guessing, no magic, no derivation from the request.
 *
 * Configuration chain:
 *   .env: OAUTH_CALLBACK_BASE_URL=https://jaraba-saas.lndo.site
 *   settings.env.php: putenv('OAUTH_CALLBACK_BASE_URL=...')
 *   settings.php: $settings['oauth_callback_base_url'] = getenv(...)
 *
 * @see \Drupal\social_auth\Plugin\Network\NetworkBase::initSdk()
 * @see \Drupal\ecosistema_jaraba_core\Service\GoogleOAuthService::getRedirectUri()
 */
class PathProcessorOAuthCallback implements OutboundPathProcessorInterface {

  /**
   * OAuth callback path patterns to normalize.
   */
  private const OAUTH_CALLBACK_PATHS = [
    '/user/login/google/callback',
    '/user/login/linkedin/callback',
    '/user/login/microsoft/callback',
  ];

  public function __construct(
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL): string {
    if (!in_array($path, self::OAUTH_CALLBACK_PATHS, TRUE)) {
      return $path;
    }

    // 1. Force DEFAULT language prefix on OAuth callbacks.
    // Regardless of user's browsing language, callbacks always use /es/.
    // This ensures deterministic URIs matching provider console registration.
    $options['language'] = $this->languageManager->getDefaultLanguage();

    // 2. Force canonical base URL from explicit configuration.
    $baseUrl = Settings::get('oauth_callback_base_url', '');
    if ($baseUrl) {
      $options['base_url'] = rtrim($baseUrl, '/');
      return $path;
    }

    // 3. Fallback: no explicit config → strip tenant subdomain heuristically.
    // This handles the case before OAUTH_CALLBACK_BASE_URL is configured.
    if ($request) {
      $host = $request->getHost();
      $parts = explode('.', $host);
      // 4+ segments = subdomain tenant (e.g., tenant.jaraba-saas.lndo.site).
      if (count($parts) > 3) {
        array_shift($parts);
        $options['base_url'] = $request->getScheme() . '://' . implode('.', $parts);
      }
    }

    return $path;
  }

}
