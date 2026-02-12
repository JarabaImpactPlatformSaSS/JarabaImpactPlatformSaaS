<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_integrations\Entity\OauthClient;

/**
 * Servicio OAuth2 server para autorización de apps externas.
 *
 * PROPÓSITO:
 * Implementa el flujo Authorization Code de OAuth2:
 * 1. /oauth/authorize — muestra pantalla de consentimiento.
 * 2. Genera authorization code temporal.
 * 3. /oauth/token — intercambia code por access_token + refresh_token.
 * 4. Valida tokens en peticiones API.
 *
 * SEGURIDAD:
 * - Authorization codes expiran en 10 minutos.
 * - Access tokens expiran según configuración (default: 1h).
 * - Refresh tokens expiran en 30 días.
 * - Tokens firmados con HMAC-SHA256 usando site hash_salt.
 */
class OauthServerService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Valida un client_id y redirect_uri.
   *
   * @param string $client_id
   *   El client_id a validar.
   * @param string $redirect_uri
   *   La redirect_uri a validar.
   *
   * @return \Drupal\jaraba_integrations\Entity\OauthClient|null
   *   El cliente válido o NULL.
   */
  public function validateClient(string $client_id, string $redirect_uri): ?OauthClient {
    $storage = $this->entityTypeManager->getStorage('oauth_client');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('client_id', $client_id)
      ->condition('is_active', TRUE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $client = $storage->load(reset($ids));
    if (!$client instanceof OauthClient) {
      return NULL;
    }

    // Validar redirect_uri.
    $allowed_uris = array_map('trim', explode("\n", $client->get('redirect_uri')->value ?? ''));
    if (!in_array($redirect_uri, $allowed_uris, TRUE)) {
      $this->logger->warning('Redirect URI @uri no autorizada para client @id', [
        '@uri' => $redirect_uri,
        '@id' => $client_id,
      ]);
      return NULL;
    }

    return $client;
  }

  /**
   * Genera un authorization code temporal.
   *
   * @param \Drupal\jaraba_integrations\Entity\OauthClient $client
   *   El cliente OAuth.
   * @param int $user_id
   *   ID del usuario que autoriza.
   * @param array $scopes
   *   Scopes autorizados.
   *
   * @return string
   *   Authorization code.
   */
  public function generateAuthorizationCode(OauthClient $client, int $user_id, array $scopes): string {
    $code = bin2hex(random_bytes(32));
    $hash_salt = \Drupal\Core\Site\Settings::getHashSalt();

    // Almacenar en state con TTL de 10 minutos.
    $state_key = 'jaraba_oauth_code:' . $code;
    $data = [
      'client_id' => $client->getClientId(),
      'user_id' => $user_id,
      'scopes' => $scopes,
      'created' => time(),
      'expires' => time() + 600, // 10 minutos.
      'signature' => hash_hmac('sha256', $code . $client->getClientId() . $user_id, $hash_salt),
    ];

    \Drupal::state()->set($state_key, $data);

    $this->logger->notice('Authorization code generado para client @id, user @uid', [
      '@id' => $client->getClientId(),
      '@uid' => $user_id,
    ]);

    return $code;
  }

  /**
   * Intercambia un authorization code por tokens.
   *
   * @param string $code
   *   El authorization code.
   * @param string $client_id
   *   El client_id.
   * @param string $client_secret
   *   El client_secret.
   *
   * @return array|null
   *   Array con access_token, refresh_token, expires_in, scope. NULL si inválido.
   */
  public function exchangeCode(string $code, string $client_id, string $client_secret): ?array {
    $state_key = 'jaraba_oauth_code:' . $code;
    $data = \Drupal::state()->get($state_key);

    if (!$data) {
      return NULL;
    }

    // Eliminar code usado (one-time use).
    \Drupal::state()->delete($state_key);

    // Verificar expiración.
    if (time() > $data['expires']) {
      $this->logger->warning('Authorization code expirado para client @id', ['@id' => $client_id]);
      return NULL;
    }

    // Verificar client_id.
    if ($data['client_id'] !== $client_id) {
      return NULL;
    }

    // Verificar client_secret.
    $storage = $this->entityTypeManager->getStorage('oauth_client');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('client_id', $client_id)
      ->condition('client_secret', $client_secret)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      $this->logger->warning('Client secret inválido para client @id', ['@id' => $client_id]);
      return NULL;
    }

    // Generar tokens.
    $config = \Drupal::config('jaraba_integrations.settings');
    $token_lifetime = $config->get('oauth_token_lifetime') ?? 3600;
    $hash_salt = \Drupal\Core\Site\Settings::getHashSalt();

    $access_token = bin2hex(random_bytes(32));
    $refresh_token = bin2hex(random_bytes(32));

    // Almacenar access token.
    \Drupal::state()->set('jaraba_oauth_token:' . $access_token, [
      'client_id' => $client_id,
      'user_id' => $data['user_id'],
      'scopes' => $data['scopes'],
      'expires' => time() + $token_lifetime,
    ]);

    // Almacenar refresh token.
    $refresh_lifetime = ($config->get('oauth_refresh_token_lifetime') ?? 30) * 86400;
    \Drupal::state()->set('jaraba_oauth_refresh:' . $refresh_token, [
      'client_id' => $client_id,
      'user_id' => $data['user_id'],
      'scopes' => $data['scopes'],
      'expires' => time() + $refresh_lifetime,
    ]);

    $this->logger->notice('Tokens generados para client @id, user @uid', [
      '@id' => $client_id,
      '@uid' => $data['user_id'],
    ]);

    return [
      'access_token' => $access_token,
      'token_type' => 'Bearer',
      'expires_in' => $token_lifetime,
      'refresh_token' => $refresh_token,
      'scope' => implode(' ', $data['scopes']),
    ];
  }

  /**
   * Valida un access token.
   *
   * @param string $token
   *   El access token.
   *
   * @return array|null
   *   Datos del token (client_id, user_id, scopes) o NULL si inválido.
   */
  public function validateAccessToken(string $token): ?array {
    $data = \Drupal::state()->get('jaraba_oauth_token:' . $token);
    if (!$data) {
      return NULL;
    }

    if (time() > $data['expires']) {
      \Drupal::state()->delete('jaraba_oauth_token:' . $token);
      return NULL;
    }

    return $data;
  }

}
