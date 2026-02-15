<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de experimentacion A/B para el vertical Andalucia +ei.
 *
 * Plan Elevacion Andalucia +ei v1 — Fase 11 (A/B Testing Framework)
 *
 * PROPOSITO:
 * Gestiona experimentos A/B especificos para el vertical andalucia_ei,
 * permitiendo probar variantes de flujos clave: onboarding participante,
 * engagement copilot IA, funnel de transicion atencion→insercion
 * y upgrade freemium.
 *
 * LOGICA:
 * - getActiveExperiment(): busca un experimento activo de tipo
 *   'andalucia_ei' con estado 'running' en jaraba_ab_testing.
 * - assignVariant(): obtiene o asigna una variante determinista.
 * - trackConversion(): registra eventos de conversion del vertical.
 * - getExperimentMetrics(): calcula tasas de conversion por variante.
 *
 * RELACIONES:
 * - Consume ABExperiment y ABVariant entities de jaraba_ab_testing.
 * - Resuelve VariantAssignmentService en runtime (sin hard dependency).
 * - Consumido por controllers y servicios del vertical andalucia_ei.
 *
 * @see \Drupal\jaraba_ab_testing\Entity\ABExperiment
 * @see \Drupal\jaraba_ab_testing\Service\VariantAssignmentService
 */
class AndaluciaEiExperimentService {

  /**
   * Tipo de experimento para andalucia_ei.
   */
  protected const EXPERIMENT_TYPE = 'andalucia_ei';

  /**
   * Eventos de conversion validos para el vertical andalucia_ei.
   */
  protected const VALID_EVENTS = [
    'participant_enrolled',
    'first_ia_session',
    'diagnostic_completed',
    'training_10h',
    'training_50h',
    'orientation_10h',
    'phase_insertion',
    'plan_upgraded',
  ];

  /**
   * Scopes de experimento validos.
   *
   * - onboarding_flow: variantes del flujo de alta de participante.
   * - copilot_engagement: variantes del copilot FAB / tutor IA.
   * - transition_funnel: variantes del funnel atencion→insercion.
   * - upgrade_funnel: variantes del flujo free→starter→profesional.
   */
  protected const VALID_SCOPES = [
    'onboarding_flow',
    'copilot_engagement',
    'transition_funnel',
    'upgrade_funnel',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene el experimento de andalucia_ei activo para un scope.
   *
   * Busca un experimento con experiment_type 'andalucia_ei' y estado
   * 'running'. Opcionalmente filtra por scope (onboarding_flow,
   * copilot_engagement, transition_funnel, upgrade_funnel).
   *
   * @param string $scope
   *   Scope del experimento. Vacio = cualquiera.
   *
   * @return object|null
   *   La entidad ABExperiment activa, o NULL si no hay ninguna.
   */
  public function getActiveExperiment(string $scope = ''): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('ab_experiment');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_type', self::EXPERIMENT_TYPE)
        ->condition('status', 'running');

      if (!empty($scope)) {
        $query->condition('machine_name', $scope, 'STARTS_WITH');
      }

      $query->range(0, 1);
      $ids = $query->execute();

      if (empty($ids)) {
        return NULL;
      }

      return $storage->load(reset($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando experimento andalucia_ei activo: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Asigna una variante a un usuario para un experimento andalucia_ei.
   *
   * Delegacion determinista en VariantAssignmentService de jaraba_ab_testing.
   * Si el modulo no esta instalado, devuelve NULL (control silencioso).
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $scope
   *   Scope del experimento.
   *
   * @return array|null
   *   Array con variant_id, variant_name, config, o NULL.
   */
  public function assignVariant(int $userId, string $scope = ''): ?array {
    try {
      $experiment = $this->getActiveExperiment($scope);

      if (!$experiment) {
        return NULL;
      }

      $machineName = $experiment->get('machine_name')->value ?? '';
      if (empty($machineName)) {
        return NULL;
      }

      // Resolver VariantAssignmentService en runtime.
      if (!\Drupal::hasService('jaraba_ab_testing.variant_assignment')) {
        return NULL;
      }

      /** @var \Drupal\jaraba_ab_testing\Service\VariantAssignmentService $variantAssignment */
      $variantAssignment = \Drupal::service('jaraba_ab_testing.variant_assignment');
      $assignment = $variantAssignment->assignVariant($machineName);

      if (!$assignment) {
        return NULL;
      }

      // Cargar variante para config completa.
      $variant = $this->entityTypeManager
        ->getStorage('ab_variant')
        ->load($assignment['variant_id']);

      $config = [];
      if ($variant && $variant->hasField('configuration')) {
        $configValue = $variant->get('configuration')->value ?? '{}';
        $decoded = json_decode($configValue, TRUE);
        $config = is_array($decoded) ? $decoded : [];
      }

      $this->logger->info('Andalucia EI: usuario @uid asignado a variante "@variant" del experimento "@experiment".', [
        '@uid' => $userId,
        '@variant' => $assignment['variant_name'],
        '@experiment' => $machineName,
      ]);

      return [
        'variant_id' => $assignment['variant_id'],
        'variant_name' => $assignment['variant_name'],
        'config' => $config,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error asignando variante andalucia_ei al usuario @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Registra un evento de conversion del vertical andalucia_ei.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $eventType
   *   Tipo de evento (participant_enrolled, first_ia_session, etc.).
   * @param array $data
   *   Datos adicionales (revenue, milestone, etc.).
   */
  public function trackConversion(int $userId, string $eventType, array $data = []): void {
    if (!in_array($eventType, self::VALID_EVENTS, TRUE)) {
      $this->logger->warning('Evento andalucia_ei no valido: @event', ['@event' => $eventType]);
      return;
    }

    try {
      $experiment = $this->getActiveExperiment();

      if (!$experiment) {
        return;
      }

      $machineName = $experiment->get('machine_name')->value ?? '';
      if (empty($machineName)) {
        return;
      }

      if (!\Drupal::hasService('jaraba_ab_testing.variant_assignment')) {
        return;
      }

      /** @var \Drupal\jaraba_ab_testing\Service\VariantAssignmentService $variantAssignment */
      $variantAssignment = \Drupal::service('jaraba_ab_testing.variant_assignment');
      $revenue = (float) ($data['revenue'] ?? 0.0);
      $variantAssignment->recordConversion($machineName, $revenue);

      $this->logger->info('Conversion andalucia_ei: usuario @uid, evento "@event", experimento "@experiment".', [
        '@uid' => $userId,
        '@event' => $eventType,
        '@experiment' => $machineName,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversion andalucia_ei para usuario @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene metricas de conversion del vertical andalucia_ei por variante.
   *
   * @param string $scope
   *   Scope del experimento. Vacio = cualquiera.
   *
   * @return array
   *   Array de metricas por variante.
   */
  public function getExperimentMetrics(string $scope = ''): array {
    try {
      $experiment = $this->getActiveExperiment($scope);

      if (!$experiment) {
        return [];
      }

      $experimentId = (int) $experiment->id();
      $variantStorage = $this->entityTypeManager->getStorage('ab_variant');
      $variantIds = $variantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experimentId)
        ->execute();

      if (empty($variantIds)) {
        return [];
      }

      $variants = $variantStorage->loadMultiple($variantIds);
      $metrics = [];

      foreach ($variants as $variant) {
        $visitors = (int) ($variant->get('visitors')->value ?? 0);
        $conversions = (int) ($variant->get('conversions')->value ?? 0);
        $conversionRate = $visitors > 0 ? $conversions / $visitors : 0.0;

        $metrics[] = [
          'variant_id' => (int) $variant->id(),
          'variant_name' => $variant->get('label')->value ?? '',
          'visitors' => $visitors,
          'conversions' => $conversions,
          'conversion_rate' => round($conversionRate, 4),
          'is_control' => (bool) ($variant->get('is_control')->value ?? FALSE),
        ];
      }

      return $metrics;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo metricas andalucia_ei: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
