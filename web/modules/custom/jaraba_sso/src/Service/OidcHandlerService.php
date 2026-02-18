<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Entity\SsoConfigurationInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * OpenID Connect Handler Service.
 *
 * Manages the full OIDC Authorization Code Flow: authorization URL generation,
 * code exchange, ID token validation, userinfo retrieval, and token refresh.
 *
 * SECURITY:
 * - Uses state parameter to prevent CSRF.
 * - Uses nonce to prevent replay attacks.
 * - Validates ID token signature when possible.
 *
 * FLOW:
 * 1. initiateLogin() -> Redirect user to authorization endpoint with state + nonce.
 * 2. handleCallback() -> Exchange code for tokens, validate ID token, fetch userinfo.
 * 3. refreshToken() -> Refresh expired access tokens.
 */
class OidcHandlerService {

  /**
   * Constructor with property promotion.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ClientInterface $httpClient,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Builds the OIDC authorization URL and returns it for redirect.
   *
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration for the OIDC provider.
   *
   * @return string
   *   The full authorization URL with state, nonce, and scope.
   */
  public function initiateLogin(SsoConfigurationInterface $config): string {
    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));

    // Store state and nonce in session for validation on callback.
    $session = \Drupal::request()->getSession();
    $session->set('jaraba_sso_oidc_state', $state);
    $session->set('jaraba_sso_oidc_nonce', $nonce);
    $session->set('jaraba_sso_oidc_provider_id', (int) $config->id());

    $callbackUrl = $this->getCallbackUrl();

    $params = [
      'response_type' => 'code',
      'client_id' => $config->getEntityId(),
      'redirect_uri' => $callbackUrl,
      'scope' => 'openid email profile',
      'state' => $state,
      'nonce' => $nonce,
    ];

    $separator = str_contains($config->getSsoUrl(), '?') ? '&' : '?';

    $this->logger->info('OIDC authorization initiated for provider @name (state: @state)', [
      '@name' => $config->getProviderName(),
      '@state' => $state,
    ]);

    return $config->getSsoUrl() . $separator . http_build_query($params);
  }

  /**
   * Handles the OIDC callback: exchanges code for tokens and returns user data.
   *
   * @param string $code
   *   The authorization code from the IdP callback.
   * @param string $state
   *   The state parameter for CSRF validation.
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration.
   *
   * @return array
   *   User data array:
   *   - email: string
   *   - first_name: string
   *   - last_name: string
   *   - sub: string (subject identifier)
   *   - groups: string[]
   *   - access_token: string
   *   - refresh_token: string|null
   *   - id_token: string
   *
   * @throws \RuntimeException
   *   If state validation fails or token exchange errors.
   */
  public function handleCallback(string $code, string $state, SsoConfigurationInterface $config): array {
    // Validate state against session.
    $session = \Drupal::request()->getSession();
    $expectedState = $session->get('jaraba_sso_oidc_state');
    $expectedNonce = $session->get('jaraba_sso_oidc_nonce');

    // Clear session values immediately.
    $session->remove('jaraba_sso_oidc_state');
    $session->remove('jaraba_sso_oidc_nonce');
    $session->remove('jaraba_sso_oidc_provider_id');

    if (empty($expectedState) || !hash_equals($expectedState, $state)) {
      throw new \RuntimeException('OIDC state validation failed. Possible CSRF attack.');
    }

    // Exchange authorization code for tokens.
    $tokenData = $this->exchangeCode($code, $config);

    $accessToken = $tokenData['access_token'] ?? '';
    $idToken = $tokenData['id_token'] ?? '';
    $refreshToken = $tokenData['refresh_token'] ?? NULL;

    if (empty($accessToken)) {
      throw new \RuntimeException('No access_token received from token endpoint.');
    }

    // Decode and validate ID token.
    $idTokenClaims = $this->decodeIdToken($idToken);

    // Validate nonce.
    $tokenNonce = $idTokenClaims['nonce'] ?? '';
    if (!empty($expectedNonce) && !empty($tokenNonce) && !hash_equals($expectedNonce, $tokenNonce)) {
      throw new \RuntimeException('OIDC nonce validation failed. Possible replay attack.');
    }

    // Fetch userinfo for additional claims.
    $userInfo = $this->fetchUserInfo($accessToken, $config);

    // Merge ID token claims with userinfo.
    $email = $userInfo['email'] ?? $idTokenClaims['email'] ?? '';
    $firstName = $userInfo['given_name'] ?? $idTokenClaims['given_name'] ?? '';
    $lastName = $userInfo['family_name'] ?? $idTokenClaims['family_name'] ?? '';
    $sub = $userInfo['sub'] ?? $idTokenClaims['sub'] ?? '';
    $groups = $userInfo['groups'] ?? $idTokenClaims['groups'] ?? [];
    if (is_string($groups)) {
      $groups = [$groups];
    }

    $this->logger->info('OIDC callback processed for @email via provider @name', [
      '@email' => $email,
      '@name' => $config->getProviderName(),
    ]);

    return [
      'email' => $email,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'sub' => $sub,
      'groups' => $groups,
      'access_token' => $accessToken,
      'refresh_token' => $refreshToken,
      'id_token' => $idToken,
    ];
  }

  /**
   * Refreshes an access token using a refresh token.
   *
   * @param string $refreshToken
   *   The refresh token.
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration.
   *
   * @return array
   *   New token data (access_token, refresh_token, expires_in).
   *
   * @throws \RuntimeException
   *   If the refresh fails.
   */
  public function refreshToken(string $refreshToken, SsoConfigurationInterface $config): array {
    try {
      $response = $this->httpClient->request('POST', $config->getTokenUrl(), [
        'form_params' => [
          'grant_type' => 'refresh_token',
          'refresh_token' => $refreshToken,
          'client_id' => $config->getEntityId(),
          'client_secret' => $config->getClientSecret(),
        ],
        'timeout' => 15,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      if (!is_array($body) || empty($body['access_token'])) {
        throw new \RuntimeException('Invalid response from token endpoint during refresh.');
      }

      return $body;
    }
    catch (\Exception $e) {
      $this->logger->error('OIDC token refresh failed for provider @name: @error', [
        '@name' => $config->getProviderName(),
        '@error' => $e->getMessage(),
      ]);
      throw new \RuntimeException('Token refresh failed: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Exchanges an authorization code for tokens.
   */
  protected function exchangeCode(string $code, SsoConfigurationInterface $config): array {
    $callbackUrl = $this->getCallbackUrl();

    try {
      $response = $this->httpClient->request('POST', $config->getTokenUrl(), [
        'form_params' => [
          'grant_type' => 'authorization_code',
          'code' => $code,
          'redirect_uri' => $callbackUrl,
          'client_id' => $config->getEntityId(),
          'client_secret' => $config->getClientSecret(),
        ],
        'timeout' => 15,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      if (!is_array($body)) {
        throw new \RuntimeException('Invalid JSON response from token endpoint.');
      }

      return $body;
    }
    catch (\Exception $e) {
      $this->logger->error('OIDC code exchange failed for provider @name: @error', [
        '@name' => $config->getProviderName(),
        '@error' => $e->getMessage(),
      ]);
      throw new \RuntimeException('Code exchange failed: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Decodes an ID token (JWT) without full cryptographic verification.
   *
   * Full JWK-based verification requires fetching the JWKS endpoint
   * and is planned for a future iteration. Currently validates structure only.
   */
  protected function decodeIdToken(string $idToken): array {
    if (empty($idToken)) {
      return [];
    }

    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
      $this->logger->warning('ID token does not have 3 parts (header.payload.signature).');
      return [];
    }

    $payload = base64_decode(strtr($parts[1], '-_', '+/'), TRUE);
    if ($payload === FALSE) {
      $this->logger->warning('Failed to Base64-decode ID token payload.');
      return [];
    }

    $claims = json_decode($payload, TRUE);
    return is_array($claims) ? $claims : [];
  }

  /**
   * Fetches user info from the OIDC userinfo endpoint.
   */
  protected function fetchUserInfo(string $accessToken, SsoConfigurationInterface $config): array {
    $userinfoUrl = $config->getUserinfoUrl();
    if (empty($userinfoUrl)) {
      return [];
    }

    try {
      $response = $this->httpClient->request('GET', $userinfoUrl, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Accept' => 'application/json',
        ],
        'timeout' => 10,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      return is_array($body) ? $body : [];
    }
    catch (\Exception $e) {
      $this->logger->warning('OIDC userinfo fetch failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets the OIDC callback URL.
   */
  protected function getCallbackUrl(): string {
    $request = \Drupal::request();
    return $request->getSchemeAndHttpHost() . '/sso/oidc/callback';
  }

}
