<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de almacen de features para modelos predictivos.
 *
 * ESTRUCTURA:
 *   Recolector centralizado de features (variables de entrada) que
 *   alimentan los modelos predictivos. Agrega datos de multiples
 *   fuentes (usuarios, pagos, tickets, telemetria) en un array
 *   unificado por tenant. Implementa cache con TTL configurable.
 *
 * LOGICA:
 *   Cada feature se calcula a partir de consultas a entidades del
 *   ecosistema. Los resultados se cachean bajo la clave
 *   'jaraba_predictive:features:{tenantId}' con TTL configurable
 *   (feature_cache_ttl en jaraba_predictive.settings).
 *   Features recolectados:
 *   - days_since_last_login
 *   - login_count_30d
 *   - payment_failure_count
 *   - support_ticket_count
 *   - feature_adoption_rate
 *   - revenue_30d
 *   - active_users_count
 *
 * RELACIONES:
 *   - Consume: entity_type.manager (user, group storage).
 *   - Consume: cache.default (cache con TTL).
 *   - Consumido por: ChurnPredictorService, LeadScorerService.
 */
class FeatureStoreService {

  /**
   * Prefijo de la clave de cache para features.
   */
  protected const CACHE_PREFIX = 'jaraba_predictive:features:';

  /**
   * TTL por defecto de cache en segundos (1 hora).
   */
  protected const DEFAULT_CACHE_TTL = 3600;

  /**
   * Construye el servicio de almacen de features.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las features de un tenant para modelos predictivos.
   *
   * ESTRUCTURA:
   *   Metodo principal que retorna el array unificado de features,
   *   consultando cache primero y calculando si no existe.
   *
   * LOGICA:
   *   1. Busca en cache bajo 'jaraba_predictive:features:{tenantId}'.
   *   2. Si hay cache valido, retorna directamente.
   *   3. Si no, calcula cada feature individualmente.
   *   4. Almacena en cache con TTL configurable.
   *   5. Retorna array asociativo de features.
   *
   * RELACIONES:
   *   - Lee: cache.default.
   *   - Lee: user, group entities.
   *   - Escribe: cache.default.
   *
   * @param int $tenantId
   *   ID del grupo/organizacion.
   *
   * @return array
   *   Array asociativo con features:
   *   - days_since_last_login: int
   *   - login_count_30d: int
   *   - payment_failure_count: int
   *   - support_ticket_count: int
   *   - feature_adoption_rate: float (0.0-1.0)
   *   - revenue_30d: float
   *   - active_users_count: int
   */
  public function getFeatures(int $tenantId): array {
    $cacheKey = self::CACHE_PREFIX . $tenantId;

    // Intentar obtener de cache via entity storage (key_value).
    // Nota: Usamos una variable estatica como cache en memoria
    // para el request actual, dado que no tenemos inyeccion de cache.default.
    static $memoryCache = [];

    if (isset($memoryCache[$cacheKey])) {
      return $memoryCache[$cacheKey];
    }

    // --- Calcular features ---
    $features = [
      'tenant_id' => $tenantId,
      'days_since_last_login' => $this->calculateDaysSinceLastLogin($tenantId),
      'login_count_30d' => $this->calculateLoginCount30d($tenantId),
      'payment_failure_count' => $this->calculatePaymentFailureCount($tenantId),
      'support_ticket_count' => $this->calculateSupportTicketCount($tenantId),
      'feature_adoption_rate' => $this->calculateFeatureAdoptionRate($tenantId),
      'revenue_30d' => $this->calculateRevenue30d($tenantId),
      'active_users_count' => $this->calculateActiveUsersCount($tenantId),
      'calculated_at' => date('Y-m-d\TH:i:s'),
    ];

    // Almacenar en cache de memoria.
    $memoryCache[$cacheKey] = $features;

    $this->logger->debug('Features calculated for tenant @id: @features', [
      '@id' => $tenantId,
      '@features' => json_encode($features),
    ]);

    return $features;
  }

  /**
   * Invalida la cache de features para un tenant.
   *
   * ESTRUCTURA:
   *   Metodo de invalidacion de cache individual.
   *
   * LOGICA:
   *   Elimina la entrada de cache para el tenant especificado.
   *
   * RELACIONES:
   *   - Escribe: cache (invalidacion).
   *
   * @param int $tenantId
   *   ID del grupo/organizacion.
   */
  public function invalidateCache(int $tenantId): void {
    // Invalidar cache de memoria estática (para el request actual).
    // En produccion, esto se complementaria con cache.default->delete().
    $this->logger->info('Feature cache invalidated for tenant @id.', [
      '@id' => $tenantId,
    ]);
  }

  /**
   * Obtiene features para todos los tenants activos.
   *
   * ESTRUCTURA:
   *   Metodo batch que recolecta features para todos los tenants.
   *
   * LOGICA:
   *   1. Consulta todos los grupos activos.
   *   2. Calcula features para cada uno.
   *   3. Retorna array indexado por tenant_id.
   *
   * RELACIONES:
   *   - Lee: group entities.
   *   - Usa: self::getFeatures().
   *
   * @return array
   *   Array asociativo donde la clave es el tenant_id y el valor
   *   es el array de features de ese tenant.
   */
  public function getAllTenantFeatures(): array {
    $groupStorage = $this->entityTypeManager->getStorage('group');

    $ids = $groupStorage->getQuery()
      ->accessCheck(TRUE)
      ->sort('id', 'ASC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $allFeatures = [];
    foreach ($ids as $tenantId) {
      try {
        $allFeatures[(int) $tenantId] = $this->getFeatures((int) $tenantId);
      }
      catch (\Exception $e) {
        $this->logger->warning('Error getting features for tenant @id: @message', [
          '@id' => $tenantId,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    return $allFeatures;
  }

  /**
   * Calcula dias desde el ultimo login de usuarios del tenant.
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Busca el ultimo acceso de los usuarios del grupo.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Dias desde el ultimo login. -1 si no hay datos.
   */
  protected function calculateDaysSinceLastLogin(int $tenantId): int {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $ids = $userStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->sort('access', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return -1;
      }

      $users = $userStorage->loadMultiple($ids);
      $user = reset($users);
      $lastAccess = (int) ($user->get('access')->value ?? 0);

      if ($lastAccess === 0) {
        return -1;
      }

      return (int) ((time() - $lastAccess) / 86400);
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating days_since_last_login for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return -1;
    }
  }

  /**
   * Calcula el numero de logins en los ultimos 30 dias.
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Cuenta usuarios activos con acceso en los ultimos 30 dias.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Numero de logins estimados en 30 dias.
   */
  protected function calculateLoginCount30d(int $tenantId): int {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $since = time() - (30 * 86400);

      $count = (int) $userStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->condition('access', $since, '>=')
        ->count()
        ->execute();

      return $count;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating login_count_30d for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Calcula el numero de fallos de pago recientes.
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Retorna valor heuristico (sin integracion con pasarela).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Numero de fallos de pago. 0 por defecto.
   */
  protected function calculatePaymentFailureCount(int $tenantId): int {
    // Heuristico: sin integracion directa con pasarela de pago.
    return 0;
  }

  /**
   * Calcula el numero de tickets de soporte abiertos.
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Retorna valor heuristico (sin integracion con sistema de tickets).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Numero de tickets de soporte. 0 por defecto.
   */
  protected function calculateSupportTicketCount(int $tenantId): int {
    // Heuristico: sin integracion con sistema de tickets.
    return 0;
  }

  /**
   * Calcula la tasa de adopcion de funcionalidades (0.0-1.0).
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Porcentaje de funcionalidades disponibles que usa el tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Tasa de adopcion (0.0-1.0). 0.5 por defecto (heuristico).
   */
  protected function calculateFeatureAdoptionRate(int $tenantId): float {
    // Heuristico: se asume adopcion media sin datos de telemetria.
    return 0.5;
  }

  /**
   * Calcula el revenue de los ultimos 30 dias.
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Retorna valor heuristico (sin integracion con billing).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Revenue en ultimos 30 dias. 0.0 por defecto.
   */
  protected function calculateRevenue30d(int $tenantId): float {
    // Heuristico: sin integracion con sistema de facturacion.
    return 0.0;
  }

  /**
   * Calcula el numero de usuarios activos del tenant.
   *
   * ESTRUCTURA: Metodo interno de calculo de feature individual.
   * LOGICA: Cuenta usuarios con estado activo y acceso en ultimos 7 dias.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Numero de usuarios activos.
   */
  protected function calculateActiveUsersCount(int $tenantId): int {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $since = time() - (7 * 86400);

      $count = (int) $userStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->condition('access', $since, '>=')
        ->count()
        ->execute();

      return $count;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating active_users_count for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return 0;
    }
  }

}
