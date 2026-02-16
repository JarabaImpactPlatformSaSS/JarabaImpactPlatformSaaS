<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Entity\AupViolation;
use Drupal\jaraba_legal\Entity\UsageLimitRecord;
use Psr\Log\LoggerInterface;

/**
 * Servicio de enforcement de la Acceptable Use Policy.
 *
 * ESTRUCTURA:
 * Monitoriza el uso de recursos por tenant, detecta violaciones de la AUP
 * y aplica las acciones correctivas graduales correspondientes.
 *
 * LOGICA DE NEGOCIO:
 * - Monitorizar rate limits, storage, bandwidth y API calls por tenant.
 * - Detectar violaciones de los limites del plan contratado.
 * - Aplicar acciones graduales: warning -> throttle -> suspend -> terminate.
 * - Registrar AupViolation con tipo, severidad y accion tomada.
 * - Actualizar UsageLimitRecord con el consumo actual.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera AupViolation y UsageLimitRecord entities.
 *
 * Spec: Doc 184 §3.3. Plan: FASE 5, Stack Compliance Legal N1.
 */
class AupEnforcerService {

  /**
   * Nombre de la configuracion del modulo.
   */
  const CONFIG_NAME = 'jaraba_legal.settings';

  /**
   * Acciones de enforcement graduales ordenadas por severidad.
   */
  const ENFORCEMENT_ACTIONS = ['warning', 'throttle', 'suspend', 'terminate'];

  /**
   * Mapa de severidad segun el numero de violaciones acumuladas.
   */
  const SEVERITY_ESCALATION = [
    1 => 'low',
    2 => 'medium',
    3 => 'high',
    5 => 'critical',
  ];

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Verifica los limites de uso actuales de un tenant vs su plan.
   *
   * Compara el consumo actual de cada recurso con los limites
   * definidos en el plan del tenant. Devuelve un array con el
   * estado de cada recurso.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   *
   * @return array
   *   Array de registros de uso con porcentaje, excedido, etc.
   */
  public function checkUsageLimits(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('usage_limit_record');

    // Obtener todos los registros de uso del tenant.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->sort('limit_type', 'ASC')
      ->execute();

    $records = $storage->loadMultiple($ids);
    $limits = [];

    /** @var \Drupal\jaraba_legal\Entity\UsageLimitRecord $record */
    foreach ($records as $record) {
      $limitType = $record->get('limit_type')->value;
      $limitValue = (int) $record->get('limit_value')->value;
      $currentUsage = (int) $record->get('current_usage')->value;
      $percentage = $record->getUsagePercentage();

      $limits[] = [
        'id' => (int) $record->id(),
        'limit_type' => $limitType,
        'limit_value' => $limitValue,
        'current_usage' => $currentUsage,
        'percentage' => round($percentage, 1),
        'exceeded' => $record->isExceeded(),
        'near_limit' => $record->isNearLimit(),
        'period' => $record->get('period')->value,
        'action_taken' => $record->get('action_taken')->value,
      ];
    }

    // Si no hay registros, crear los registros por defecto del plan.
    if (empty($limits)) {
      $limits = $this->initializeDefaultLimits($tenant_id);
    }

    return $limits;
  }

  /**
   * Detecta y registra una violacion de la AUP.
   *
   * Crea un registro AupViolation con la informacion del tipo de
   * violacion, calcula la severidad y determina la accion a tomar
   * segun el historial de violaciones del tenant.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param string $violation_type
   *   Tipo de violacion (rate_limit, storage, bandwidth, api_abuse, content, other).
   * @param string $description
   *   Descripcion detallada de la violacion detectada.
   *
   * @return \Drupal\jaraba_legal\Entity\AupViolation
   *   Entidad AupViolation creada.
   */
  public function detectViolation(int $tenant_id, string $violation_type, string $description): AupViolation {
    // Determinar severidad basada en el historial.
    $violationCount = $this->countRecentViolations($tenant_id, $violation_type);
    $severity = $this->determineSeverity($violationCount + 1);

    // Determinar accion a tomar.
    $action = $this->determineAction($violationCount + 1);

    $storage = $this->entityTypeManager->getStorage('aup_violation');

    /** @var \Drupal\jaraba_legal\Entity\AupViolation $violation */
    $violation = $storage->create([
      'tenant_id' => $tenant_id,
      'violation_type' => $violation_type,
      'severity' => $severity,
      'description' => [
        'value' => $description,
        'format' => 'plain_text',
      ],
      'action_taken' => $action,
      'detected_at' => time(),
    ]);

    $violation->save();

    // Aplicar la accion de enforcement.
    if ($action !== 'warning') {
      $this->enforceAction($tenant_id, $action);
    }

    $this->logger->warning('Violacion AUP detectada para tenant @tenant: tipo @type, severidad @severity, accion @action.', [
      '@tenant' => $tenant_id,
      '@type' => $violation_type,
      '@severity' => $severity,
      '@action' => $action,
    ]);

    return $violation;
  }

  /**
   * Obtiene el historial de violaciones AUP de un tenant.
   *
   * Devuelve todas las violaciones registradas para el tenant,
   * ordenadas por fecha de deteccion (mas reciente primero).
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   *
   * @return array
   *   Array de violaciones con datos serializados.
   */
  public function getViolationHistory(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('aup_violation');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->sort('detected_at', 'DESC')
      ->execute();

    $violations = [];

    /** @var \Drupal\jaraba_legal\Entity\AupViolation $violation */
    foreach ($storage->loadMultiple($ids) as $violation) {
      $violations[] = [
        'id' => (int) $violation->id(),
        'violation_type' => $violation->get('violation_type')->value,
        'severity' => $violation->get('severity')->value,
        'description' => $violation->get('description')->value,
        'action_taken' => $violation->get('action_taken')->value,
        'detected_at' => (int) $violation->get('detected_at')->value,
        'resolved_at' => $violation->get('resolved_at')->value ? (int) $violation->get('resolved_at')->value : NULL,
        'is_resolved' => $violation->isResolved(),
      ];
    }

    return $violations;
  }

  /**
   * Actualiza el registro de uso de un recurso para un tenant.
   *
   * Busca o crea un UsageLimitRecord para el tipo de recurso
   * especificado y actualiza el valor de uso actual.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param string $resource_type
   *   Tipo de recurso (api_calls, storage_mb, bandwidth_mb, users, pages, products).
   * @param int $current_usage
   *   Valor de uso actual del recurso.
   *
   * @return \Drupal\jaraba_legal\Entity\UsageLimitRecord
   *   Entidad UsageLimitRecord actualizada.
   */
  public function updateUsageRecord(int $tenant_id, string $resource_type, int $current_usage): UsageLimitRecord {
    $storage = $this->entityTypeManager->getStorage('usage_limit_record');

    // Buscar registro existente.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('limit_type', $resource_type)
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      /** @var \Drupal\jaraba_legal\Entity\UsageLimitRecord $record */
      $record = $storage->load(reset($ids));
    }
    else {
      // Crear registro nuevo con limites por defecto.
      $defaultLimits = $this->getDefaultLimits();
      $limitValue = $defaultLimits[$resource_type] ?? 1000;

      /** @var \Drupal\jaraba_legal\Entity\UsageLimitRecord $record */
      $record = $storage->create([
        'tenant_id' => $tenant_id,
        'limit_type' => $resource_type,
        'limit_value' => $limitValue,
        'period' => 'monthly',
      ]);
    }

    // Actualizar uso.
    $record->set('current_usage', $current_usage);

    // Verificar si se excedio el limite.
    $wasExceeded = !empty($record->get('exceeded_at')->value);
    if ($record->isExceeded() && !$wasExceeded) {
      $record->set('exceeded_at', time());

      $this->logger->warning('Limite de @type excedido para tenant @tenant: @usage / @limit.', [
        '@type' => $resource_type,
        '@tenant' => $tenant_id,
        '@usage' => $current_usage,
        '@limit' => (int) $record->get('limit_value')->value,
      ]);
    }

    $record->save();

    return $record;
  }

  /**
   * Aplica una accion de enforcement gradual sobre un tenant.
   *
   * Registra la accion en el state de Drupal para que los
   * middleware y servicios puedan verificar el estado del tenant.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param string $action
   *   Accion a aplicar (warning, throttle, suspend, terminate).
   *
   * @return array
   *   Resultado de la accion aplicada.
   */
  public function enforceAction(int $tenant_id, string $action): array {
    $validActions = self::ENFORCEMENT_ACTIONS;
    if (!in_array($action, $validActions, TRUE)) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('Accion de enforcement invalida: @action', ['@action' => $action])
      );
    }

    $now = time();
    $stateKey = "jaraba_legal.enforcement.{$tenant_id}";

    // Registrar la accion en state.
    $enforcementData = [
      'tenant_id' => $tenant_id,
      'action' => $action,
      'applied_at' => $now,
      'active' => TRUE,
    ];

    \Drupal::state()->set($stateKey, $enforcementData);

    $this->logger->warning('Accion de enforcement @action aplicada a tenant @tenant.', [
      '@action' => $action,
      '@tenant' => $tenant_id,
    ]);

    return $enforcementData;
  }

  /**
   * Cuenta las violaciones recientes de un tenant (ultimos 90 dias).
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $violation_type
   *   Tipo de violacion a contar.
   *
   * @return int
   *   Numero de violaciones recientes.
   */
  protected function countRecentViolations(int $tenant_id, string $violation_type): int {
    $ninetyDaysAgo = time() - (90 * 86400);

    $storage = $this->entityTypeManager->getStorage('aup_violation');
    $count = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('violation_type', $violation_type)
      ->condition('detected_at', $ninetyDaysAgo, '>=')
      ->count()
      ->execute();

    return $count;
  }

  /**
   * Determina la severidad basada en el numero de violaciones acumuladas.
   *
   * @param int $count
   *   Numero total de violaciones (incluyendo la actual).
   *
   * @return string
   *   Nivel de severidad (low, medium, high, critical).
   */
  protected function determineSeverity(int $count): string {
    $severity = 'low';

    foreach (self::SEVERITY_ESCALATION as $threshold => $level) {
      if ($count >= $threshold) {
        $severity = $level;
      }
    }

    return $severity;
  }

  /**
   * Determina la accion a tomar basada en el numero de violaciones.
   *
   * @param int $count
   *   Numero total de violaciones (incluyendo la actual).
   *
   * @return string
   *   Accion de enforcement (warning, throttle, suspend, terminate).
   */
  protected function determineAction(int $count): string {
    $actions = self::ENFORCEMENT_ACTIONS;

    // Escalar la accion segun el numero de violaciones.
    $index = min($count - 1, count($actions) - 1);
    return $actions[$index];
  }

  /**
   * Inicializa los registros de limites por defecto para un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return array
   *   Array de limites inicializados.
   */
  protected function initializeDefaultLimits(int $tenant_id): array {
    $defaults = $this->getDefaultLimits();
    $limits = [];

    foreach ($defaults as $type => $value) {
      $record = $this->updateUsageRecord($tenant_id, $type, 0);
      $record->set('limit_value', $value);
      $record->save();

      $limits[] = [
        'id' => (int) $record->id(),
        'limit_type' => $type,
        'limit_value' => $value,
        'current_usage' => 0,
        'percentage' => 0.0,
        'exceeded' => FALSE,
        'near_limit' => FALSE,
        'period' => 'monthly',
        'action_taken' => NULL,
      ];
    }

    return $limits;
  }

  /**
   * Obtiene los limites por defecto del plan base.
   *
   * @return array
   *   Mapa de tipo de recurso => limite por defecto.
   */
  protected function getDefaultLimits(): array {
    $config = $this->configFactory->get(self::CONFIG_NAME);

    return [
      'api_calls' => (int) ($config->get('default_limit_api_calls') ?? 10000),
      'storage_mb' => (int) ($config->get('default_limit_storage_mb') ?? 1024),
      'bandwidth_mb' => (int) ($config->get('default_limit_bandwidth_mb') ?? 5120),
      'users' => (int) ($config->get('default_limit_users') ?? 50),
      'pages' => (int) ($config->get('default_limit_pages') ?? 100),
      'products' => (int) ($config->get('default_limit_products') ?? 500),
    ];
  }

}
