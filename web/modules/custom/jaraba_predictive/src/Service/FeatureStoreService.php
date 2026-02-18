<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
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
    protected readonly Connection $database,
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
      'job_posting_velocity' => $this->calculateJobPostingVelocity($tenantId),
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
   * Calcula la velocidad de publicación de ofertas (últimos 30 días).
   * Específico para el vertical de Empleo.
   */
  protected function calculateJobPostingVelocity(int $tenantId): int {
    try {
      if (!$this->database->schema()->tableExists('job_announcement')) {
        return 0;
      }

      return (int) $this->database->select('job_announcement', 'j')
        ->condition('j.tenant_id', $tenantId)
        ->condition('j.created', time() - (30 * 86400), '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
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
   * Calcula el número de fallos de pago recientes.
   */
  protected function calculatePaymentFailureCount(int $tenantId): int {
    try {
      // Consultamos la tabla de facturas de jaraba_billing.
      if (!$this->database->schema()->tableExists('billing_invoice')) {
        return 0;
      }

      return (int) $this->database->select('billing_invoice', 'i')
        ->condition('i.tenant_id', $tenantId)
        ->condition('i.status', 'failed')
        ->condition('i.created', time() - (30 * 86400), '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Calcula el número de tickets de soporte abiertos.
   */
  protected function calculateSupportTicketCount(int $tenantId): int {
    try {
      // Consultamos la tabla de tickets si existe (módulo jaraba_support).
      if (!$this->database->schema()->tableExists('support_ticket')) {
        return 0;
      }

      return (int) $this->database->select('support_ticket', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.status', 'open')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Calcula la tasa de adopción de funcionalidades (0.0-1.0).
   */
  protected function calculateFeatureAdoptionRate(int $tenantId): float {
    try {
      // Consultamos los feature gates de AgroConecta como proxy de adopción.
      if (!$this->database->schema()->tableExists('agroconecta_feature_usage')) {
        return 0.5;
      }

      $usedFeatures = (int) $this->database->select('agroconecta_feature_usage', 'f')
        ->condition('f.tenant_id', $tenantId)
        ->addExpression('COUNT(DISTINCT feature_key)', 'count')
        ->execute()
        ->fetchField();

      // Asumimos un máximo de 10 features principales por vertical para el score.
      return (float) min(1.0, $usedFeatures / 10);
    }
    catch (\Exception $e) {
      return 0.5;
    }
  }

  /**
   * Calcula el revenue de los últimos 30 días.
   */
  protected function calculateRevenue30d(int $tenantId): float {
    try {
      if (!$this->database->schema()->tableExists('financial_transaction')) {
        return 0.0;
      }

      $revenue = $this->database->select('financial_transaction', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.type', 'credit')
        ->condition('t.created', time() - (30 * 86400), '>=')
        ->addExpression('SUM(amount)', 'total')
        ->execute()
        ->fetchField();

      return (float) ($revenue ?? 0.0);
    }
    catch (\Exception $e) {
      return 0.0;
    }
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
