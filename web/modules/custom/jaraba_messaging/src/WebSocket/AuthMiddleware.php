<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\WebSocket;

use Psr\Log\LoggerInterface;

/**
 * Middleware de autenticación para conexiones WebSocket.
 *
 * PROPÓSITO:
 * Valida tokens JWT o de sesión durante el handshake WebSocket.
 * Extrae user_id y tenant_id del token para asociar la conexión
 * al usuario autenticado.
 *
 * FLUJO:
 * 1. Cliente conecta con ?token=<jwt> en la URL del WebSocket.
 * 2. Este middleware decodifica y valida el token.
 * 3. Si es válido, retorna ['user_id' => int, 'tenant_id' => int].
 * 4. Si no, retorna NULL y la conexión se rechaza.
 */
class AuthMiddleware {

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Authenticates a WebSocket connection using a JWT or session token.
   *
   * Attempts JWT validation first. Falls back to Drupal session/token
   * validation if JWT modules are not available.
   *
   * @param string $token
   *   The authentication token from the WebSocket query parameter.
   *
   * @return array|null
   *   An array with 'user_id' (int) and 'tenant_id' (int) on success,
   *   or NULL if authentication fails.
   */
  public function authenticate(string $token): ?array {
    if (empty($token)) {
      $this->logger->warning('WebSocket auth: empty token provided.');
      return NULL;
    }

    // Attempt JWT validation via jwt_auth module.
    $result = $this->validateJwt($token);
    if ($result !== NULL) {
      return $result;
    }

    // Fallback: validate as Drupal session token.
    $result = $this->validateSessionToken($token);
    if ($result !== NULL) {
      return $result;
    }

    $this->logger->warning('WebSocket auth: all validation methods failed for token.');
    return NULL;
  }

  /**
   * Validates a JWT token using the jwt_auth service.
   *
   * @param string $token
   *   The JWT string.
   *
   * @return array|null
   *   Authentication data or NULL on failure.
   */
  protected function validateJwt(string $token): ?array {
    try {
      if (!\Drupal::hasService('jwt.authentication.jwt')) {
        return NULL;
      }

      $jwtAuth = \Drupal::service('jwt.authentication.jwt');

      // Decode the JWT payload.
      $parts = explode('.', $token);
      if (count($parts) !== 3) {
        return NULL;
      }

      $payload = json_decode(
        base64_decode(strtr($parts[1], '-_', '+/')),
        TRUE,
      );

      if (empty($payload) || !isset($payload['drupal']['uid'])) {
        return NULL;
      }

      $userId = (int) $payload['drupal']['uid'];
      $tenantId = (int) ($payload['drupal']['tenant_id'] ?? 0);

      // Validate the full token via Drupal's JWT service.
      $user = $jwtAuth->loadUserFromToken($token);
      if ($user === NULL || (int) $user->id() !== $userId) {
        $this->logger->warning('WebSocket auth: JWT validation failed for uid @uid.', [
          '@uid' => $userId,
        ]);
        return NULL;
      }

      $this->logger->debug('WebSocket auth: JWT validated for user @uid (tenant @tenant).', [
        '@uid' => $userId,
        '@tenant' => $tenantId,
      ]);

      return [
        'user_id' => $userId,
        'tenant_id' => $tenantId,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->debug('WebSocket auth: JWT validation exception: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Validates a Drupal session-based token as fallback.
   *
   * This supports scenarios where the client passes a CSRF/session token
   * instead of a JWT (e.g. same-origin browser connections).
   *
   * @param string $token
   *   The session token string.
   *
   * @return array|null
   *   Authentication data or NULL on failure.
   */
  protected function validateSessionToken(string $token): ?array {
    try {
      if (!\Drupal::hasService('session_manager')) {
        return NULL;
      }

      // Attempt to validate using Drupal's CSRF token mechanism.
      $csrfGenerator = \Drupal::service('csrf_token');
      if (!$csrfGenerator->validate($token, 'jaraba_messaging_ws')) {
        return NULL;
      }

      // If CSRF is valid, use the current user context.
      $currentUser = \Drupal::currentUser();
      if ($currentUser->isAnonymous()) {
        return NULL;
      }

      $userId = (int) $currentUser->id();
      $tenantId = 0;

      // Resolve tenant context if available.
      if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $tenantId = (int) ($tenantContext->getCurrentTenantId() ?? 0);
      }

      $this->logger->debug('WebSocket auth: session token validated for user @uid (tenant @tenant).', [
        '@uid' => $userId,
        '@tenant' => $tenantId,
      ]);

      return [
        'user_id' => $userId,
        'tenant_id' => $tenantId,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->debug('WebSocket auth: session validation exception: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
