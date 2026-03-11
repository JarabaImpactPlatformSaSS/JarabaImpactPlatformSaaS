<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * P2-05: Google OAuth 2.0 service for social login.
 *
 * Lightweight OAuth2 client — no contrib module dependency.
 * Credentials via settings.secrets.php (SECRET-MGMT-001):
 *   SOCIAL_AUTH_GOOGLE_CLIENT_ID → social_auth_google.settings.client_id
 *   SOCIAL_AUTH_GOOGLE_CLIENT_SECRET → social_auth_google.settings.client_secret
 */
class GoogleOAuthService {

  /**
   * Google OAuth2 endpoints.
   */
  private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
  private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
  private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

  /**
   * Required scopes.
   */
  private const SCOPES = 'openid email profile';

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Returns the Google OAuth authorization URL.
   *
   * @param string $state
   *   CSRF state token.
   *
   * @return string|null
   *   The authorization URL, or NULL if not configured.
   */
  public function getAuthorizationUrl(string $state): ?string {
    $clientId = $this->getClientId();
    if (!$clientId) {
      return NULL;
    }

    $redirectUri = $this->getRedirectUri();

    $params = [
      'client_id' => $clientId,
      'redirect_uri' => $redirectUri,
      'response_type' => 'code',
      'scope' => self::SCOPES,
      'state' => $state,
      'access_type' => 'online',
      'prompt' => 'select_account',
    ];

    return self::AUTH_URL . '?' . http_build_query($params);
  }

  /**
   * Exchanges authorization code for tokens and user info.
   *
   * @param string $code
   *   The authorization code from Google.
   *
   * @return array|null
   *   User info array {email, name, picture, sub} or NULL on failure.
   */
  public function exchangeCodeForUserInfo(string $code): ?array {
    $clientId = $this->getClientId();
    $clientSecret = $this->getClientSecret();

    if (!$clientId || !$clientSecret) {
      $this->logger->error('Google OAuth: client_id or client_secret not configured.');
      return NULL;
    }

    try {
      // Exchange code for access token.
      $tokenResponse = $this->httpClient->request('POST', self::TOKEN_URL, [
        'form_params' => [
          'code' => $code,
          'client_id' => $clientId,
          'client_secret' => $clientSecret,
          'redirect_uri' => $this->getRedirectUri(),
          'grant_type' => 'authorization_code',
        ],
      ]);

      $tokenData = json_decode((string) $tokenResponse->getBody(), TRUE);
      $accessToken = $tokenData['access_token'] ?? NULL;

      if (!$accessToken) {
        $this->logger->error('Google OAuth: no access_token in response.');
        return NULL;
      }

      // Fetch user info.
      $userResponse = $this->httpClient->request('GET', self::USERINFO_URL, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
        ],
      ]);

      $userInfo = json_decode((string) $userResponse->getBody(), TRUE);

      if (empty($userInfo['email'])) {
        $this->logger->error('Google OAuth: no email in user info.');
        return NULL;
      }

      return [
        'email' => $userInfo['email'],
        'name' => $userInfo['name'] ?? '',
        'picture' => $userInfo['picture'] ?? '',
        'sub' => $userInfo['sub'] ?? '',
        'email_verified' => $userInfo['email_verified'] ?? FALSE,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Google OAuth error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Checks if Google OAuth is configured.
   *
   * @return bool
   *   TRUE if both client_id and client_secret are set.
   */
  public function isConfigured(): bool {
    return !empty($this->getClientId()) && !empty($this->getClientSecret());
  }

  /**
   * Gets the callback redirect URI.
   *
   * OAuth redirect_uri MUST be a fixed, deterministic string matching
   * exactly what is registered in the provider console.
   *
   * Language normalization is handled by PathProcessorOAuthCallback
   * (OAUTH-REDIRECT-URI-001) which forces the default language prefix
   * on all OAuth callback paths. This ensures the URI is always the
   * same regardless of the user's current browsing language.
   *
   * @return string
   *   Absolute URL for the OAuth callback.
   */
  protected function getRedirectUri(): string {
    return Url::fromRoute(
      'ecosistema_jaraba_core.google_oauth_callback',
      [],
      ['absolute' => TRUE]
    )->toString();
  }

  /**
   * Gets the client ID from config (injected via settings.secrets.php).
   */
  protected function getClientId(): string {
    return (string) $this->configFactory
      ->get('social_auth_google.settings')
      ->get('client_id');
  }

  /**
   * Gets the client secret from config (injected via settings.secrets.php).
   */
  protected function getClientSecret(): string {
    return (string) $this->configFactory
      ->get('social_auth_google.settings')
      ->get('client_secret');
  }

}
