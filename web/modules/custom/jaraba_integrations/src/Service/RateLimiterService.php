<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de rate limiting con ventana deslizante para APIs de integracion.
 *
 * PROPOSITO:
 * Protege los endpoints de la API de integraciones contra abuso,
 * aplicando limites por tenant, conector e IP. Usa sliding window
 * para distribucion uniforme de requests.
 *
 * USO:
 * @code
 * if (!$this->rateLimiter->isAllowed('connector_api', $tenantId, 100, 3600)) {
 *   return new JsonResponse(['error' => 'Rate limit exceeded'], 429);
 * }
 * @endcode
 */
class RateLimiterService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Backend de cache para almacenar contadores.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del modulo.
   */
  public function __construct(
    protected CacheBackendInterface $cache,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Verifica si una request esta dentro del limite permitido.
   *
   * @param string $key
   *   Identificador del recurso (e.g. 'connector_api', 'webhook_dispatch').
   * @param int $identifier
   *   Identificador del actor (tenant_id, user_id).
   * @param int $maxRequests
   *   Numero maximo de requests permitidas en la ventana.
   * @param int $windowSeconds
   *   Tamano de la ventana en segundos.
   *
   * @return bool
   *   TRUE si la request esta permitida, FALSE si excede el limite.
   */
  public function isAllowed(string $key, int $identifier, int $maxRequests = 100, int $windowSeconds = 3600): bool {
    try {
      $cacheKey = sprintf('rate_limit:%s:%d', $key, $identifier);
      $now = time();
      $windowStart = $now - $windowSeconds;

      $cached = $this->cache->get($cacheKey);
      $timestamps = ($cached && is_array($cached->data)) ? $cached->data : [];

      // Filtrar timestamps fuera de la ventana.
      $timestamps = array_filter($timestamps, fn(int $ts) => $ts >= $windowStart);

      if (count($timestamps) >= $maxRequests) {
        $this->logger->notice('Rate limit alcanzado para @key (identifier: @id, requests: @count/@max)', [
          '@key' => $key,
          '@id' => $identifier,
          '@count' => count($timestamps),
          '@max' => $maxRequests,
        ]);
        return FALSE;
      }

      // Registrar la request actual.
      $timestamps[] = $now;
      $this->cache->set($cacheKey, $timestamps, $now + $windowSeconds);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en rate limiter: @message', [
        '@message' => $e->getMessage(),
      ]);
      // En caso de error, permitir la request (fail-open).
      return TRUE;
    }
  }

  /**
   * Obtiene el estado actual del rate limit para un recurso.
   *
   * @param string $key
   *   Identificador del recurso.
   * @param int $identifier
   *   Identificador del actor.
   * @param int $maxRequests
   *   Numero maximo de requests.
   * @param int $windowSeconds
   *   Tamano de la ventana.
   *
   * @return array
   *   Array con 'remaining', 'limit', 'reset_at'.
   */
  public function getStatus(string $key, int $identifier, int $maxRequests = 100, int $windowSeconds = 3600): array {
    $cacheKey = sprintf('rate_limit:%s:%d', $key, $identifier);
    $now = time();
    $windowStart = $now - $windowSeconds;

    $cached = $this->cache->get($cacheKey);
    $timestamps = ($cached && is_array($cached->data)) ? $cached->data : [];
    $timestamps = array_filter($timestamps, fn(int $ts) => $ts >= $windowStart);

    $used = count($timestamps);

    return [
      'remaining' => max(0, $maxRequests - $used),
      'limit' => $maxRequests,
      'used' => $used,
      'reset_at' => $now + $windowSeconds,
    ];
  }

  /**
   * Resetea el contador de rate limit para un recurso.
   *
   * @param string $key
   *   Identificador del recurso.
   * @param int $identifier
   *   Identificador del actor.
   */
  public function reset(string $key, int $identifier): void {
    $cacheKey = sprintf('rate_limit:%s:%d', $key, $identifier);
    $this->cache->delete($cacheKey);
  }

}
