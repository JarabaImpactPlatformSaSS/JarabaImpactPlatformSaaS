<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de cache para datos de subvenciones.
 *
 * Proporciona cache con TTLs diferenciados segun el tipo de dato:
 * - Convocatorias: 30 minutos (datos que cambian con menos frecuencia).
 * - Matches: 5 minutos (datos dependientes del perfil del usuario).
 * - Estadisticas: 15 minutos (datos agregados).
 *
 * ARQUITECTURA:
 * - Cache key prefixada con 'jaraba_funding:'.
 * - TTLs configurables por tipo de dato.
 * - Invalidacion selectiva o total.
 * - Callback pattern para cache-aside.
 *
 * RELACIONES:
 * - FundingCacheService -> CacheBackendInterface (almacen)
 * - FundingCacheService <- Controllers / Services (consumido por)
 */
class FundingCacheService {

  /**
   * TTL por defecto para convocatorias (30 minutos).
   */
  protected const TTL_CALLS = 1800;

  /**
   * TTL por defecto para matches (5 minutos).
   */
  protected const TTL_MATCHES = 300;

  /**
   * TTL por defecto para estadisticas (15 minutos).
   */
  protected const TTL_STATS = 900;

  /**
   * Prefijo de todas las claves de cache.
   */
  protected const CACHE_PREFIX = 'jaraba_funding:';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Backend de cache.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected CacheBackendInterface $cache,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene convocatorias con cache (TTL: 30 minutos).
   *
   * @param callable $callback
   *   Callback que retorna los datos si no estan en cache.
   * @param array $filters
   *   Filtros aplicados (para construir la clave de cache).
   * @param int $ttl
   *   Tiempo de vida en segundos (default 1800).
   *
   * @return array
   *   Datos de convocatorias (desde cache o callback).
   */
  public function getCallsCached(callable $callback, array $filters = [], int $ttl = self::TTL_CALLS): array {
    $cacheKey = $this->buildCacheKey('calls', $filters);

    $cached = $this->cache->get($cacheKey);
    if ($cached !== FALSE) {
      return $cached->data;
    }

    try {
      $data = $callback();
      $this->cache->set($cacheKey, $data, \Drupal::time()->getRequestTime() + $ttl);
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo datos de convocatorias para cache: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene matches con cache (TTL: 5 minutos).
   *
   * @param callable $callback
   *   Callback que retorna los datos si no estan en cache.
   * @param int $subscriptionId
   *   ID de la suscripcion (para construir la clave de cache).
   * @param int $ttl
   *   Tiempo de vida en segundos (default 300).
   *
   * @return array
   *   Datos de matches (desde cache o callback).
   */
  public function getMatchesCached(callable $callback, int $subscriptionId, int $ttl = self::TTL_MATCHES): array {
    $cacheKey = $this->buildCacheKey('matches', ['subscription_id' => $subscriptionId]);

    $cached = $this->cache->get($cacheKey);
    if ($cached !== FALSE) {
      return $cached->data;
    }

    try {
      $data = $callback();
      $this->cache->set($cacheKey, $data, \Drupal::time()->getRequestTime() + $ttl);
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo datos de matches para cache: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene estadisticas con cache (TTL: 15 minutos).
   *
   * @param callable $callback
   *   Callback que retorna los datos si no estan en cache.
   * @param int $tenantId
   *   ID del tenant (para construir la clave de cache).
   * @param int $ttl
   *   Tiempo de vida en segundos (default 900).
   *
   * @return array
   *   Datos de estadisticas (desde cache o callback).
   */
  public function getStatsCached(callable $callback, int $tenantId, int $ttl = self::TTL_STATS): array {
    $cacheKey = $this->buildCacheKey('stats', ['tenant_id' => $tenantId]);

    $cached = $this->cache->get($cacheKey);
    if ($cached !== FALSE) {
      return $cached->data;
    }

    try {
      $data = $callback();
      $this->cache->set($cacheKey, $data, \Drupal::time()->getRequestTime() + $ttl);
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas para cache: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Invalida cache de convocatorias.
   */
  public function invalidateCallsCache(): void {
    $this->cache->invalidate(self::CACHE_PREFIX . 'calls');
    // Invalidar tambien con patron prefix si es posible.
    $this->cache->removeBin();
    $this->logger->info('Cache de convocatorias invalidada.');
  }

  /**
   * Invalida cache de matches para una suscripcion.
   *
   * @param int $subscriptionId
   *   ID de la suscripcion.
   */
  public function invalidateMatchesCache(int $subscriptionId): void {
    $cacheKey = $this->buildCacheKey('matches', ['subscription_id' => $subscriptionId]);
    $this->cache->delete($cacheKey);
    $this->logger->info('Cache de matches invalidada para suscripcion @id.', [
      '@id' => $subscriptionId,
    ]);
  }

  /**
   * Invalida toda la cache del modulo de funding.
   */
  public function invalidateAllCache(): void {
    $this->cache->removeBin();
    $this->logger->info('Toda la cache de funding invalidada.');
  }

  /**
   * Construye una clave de cache a partir de prefijo y parametros.
   *
   * @param string $prefix
   *   Prefijo del tipo de datos (e.g., 'calls', 'matches', 'stats').
   * @param array $params
   *   Parametros para hacer la clave unica.
   *
   * @return string
   *   Clave de cache completa.
   */
  public function buildCacheKey(string $prefix, array $params): string {
    $key = self::CACHE_PREFIX . $prefix;

    if (!empty($params)) {
      ksort($params);
      $key .= ':' . md5(serialize($params));
    }

    return $key;
  }

}
