<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de experimentación A/B para el flujo de onboarding.
 *
 * PROPÓSITO:
 * Gestiona experimentos A/B específicos para el flujo de onboarding,
 * permitiendo probar diferentes variantes de registro, primer login,
 * selección de plan y pasos iniciales.
 *
 * LÓGICA:
 * - getActiveOnboardingExperiment(): busca un experimento activo con
 *   experiment_type 'onboarding' y estado 'running'.
 * - assignVariant(): obtiene o asigna una variante para un usuario,
 *   delegando en VariantAssignmentService.
 * - trackConversion(): registra eventos de conversión del onboarding
 *   (registration_complete, first_login, plan_selected, etc.).
 * - getOnboardingMetrics(): calcula tasas de conversión por variante.
 *
 * RELACIONES:
 * - Consume ABExperiment y ABVariant entities.
 * - Consume VariantAssignmentService para asignación de variantes.
 * - Consumido por hooks y controladores del flujo de onboarding.
 */
class OnboardingExperimentService {

  /**
   * Gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de asignación de variantes.
   *
   * @var \Drupal\jaraba_ab_testing\Service\VariantAssignmentService
   */
  protected VariantAssignmentService $variantAssignment;

  /**
   * Canal de log.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Tipo de experimento para onboarding.
   */
  protected const EXPERIMENT_TYPE = 'onboarding';

  /**
   * Eventos de conversión válidos para el onboarding.
   */
  protected const VALID_EVENTS = [
    'registration_complete',
    'first_login',
    'plan_selected',
    'profile_completed',
    'first_content_created',
    'onboarding_completed',
  ];

  /**
   * Constructor del servicio de experimentación de onboarding.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad.
   * @param \Drupal\jaraba_ab_testing\Service\VariantAssignmentService $variant_assignment
   *   Servicio de asignación de variantes.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para A/B testing.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    VariantAssignmentService $variant_assignment,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->variantAssignment = $variant_assignment;
    $this->logger = $logger;
  }

  /**
   * Obtiene el experimento de onboarding activo.
   *
   * Busca un experimento con experiment_type 'onboarding' y estado
   * 'running'. Solo devuelve el primero encontrado (no debe haber
   * más de un experimento de onboarding activo simultáneamente).
   *
   * @return object|null
   *   La entidad ABExperiment activa, o NULL si no hay ninguna.
   */
  public function getActiveOnboardingExperiment(): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('ab_experiment');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_type', self::EXPERIMENT_TYPE)
        ->condition('status', 'running')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $id = reset($ids);
      return $storage->load($id);
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando experimento de onboarding activo: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Asigna una variante de onboarding a un usuario.
   *
   * Si ya tiene una variante asignada, la devuelve. Si no,
   * delega en VariantAssignmentService para la asignación.
   *
   * @param int $userId
   *   ID del usuario al que asignar la variante.
   *
   * @return array|null
   *   Array con datos de la variante:
   *   - 'variant_id' (int): ID de la variante.
   *   - 'variant_name' (string): Nombre de la variante.
   *   - 'config' (array): Configuración JSON decodificada de la variante.
   *   O NULL si no hay experimento activo o no se pudo asignar.
   */
  public function assignVariant(int $userId): ?array {
    try {
      $experiment = $this->getActiveOnboardingExperiment();

      if (!$experiment) {
        $this->logger->debug('No hay experimento de onboarding activo para asignar al usuario @uid.', [
          '@uid' => $userId,
        ]);
        return NULL;
      }

      $machineName = $experiment->get('machine_name')->value ?? '';
      if (empty($machineName)) {
        return NULL;
      }

      // Delegar la asignación al servicio de variantes.
      $assignment = $this->variantAssignment->assignVariant($machineName);

      if (!$assignment) {
        return NULL;
      }

      // Cargar la variante para obtener la configuración completa.
      $variant = $this->entityTypeManager
        ->getStorage('ab_variant')
        ->load($assignment['variant_id']);

      $config = [];
      if ($variant && $variant->hasField('configuration')) {
        $configValue = $variant->get('configuration')->value ?? '{}';
        $decoded = json_decode($configValue, TRUE);
        $config = is_array($decoded) ? $decoded : [];
      }

      $this->logger->info('Usuario @uid asignado a variante "@variant" del experimento de onboarding "@experiment".', [
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
      $this->logger->error('Error asignando variante de onboarding al usuario @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Registra un evento de conversión del onboarding.
   *
   * Registra conversiones específicas del flujo de onboarding como
   * registro completado, primer login, selección de plan, etc.
   *
   * @param int $userId
   *   ID del usuario que generó la conversión.
   * @param string $eventType
   *   Tipo de evento (registration_complete, first_login, plan_selected, etc.).
   * @param array $data
   *   Datos adicionales del evento (plan_id, step_number, etc.).
   */
  public function trackConversion(int $userId, string $eventType, array $data = []): void {
    try {
      $experiment = $this->getActiveOnboardingExperiment();

      if (!$experiment) {
        return;
      }

      $machineName = $experiment->get('machine_name')->value ?? '';
      if (empty($machineName)) {
        return;
      }

      // Registrar la conversión en el servicio de variantes.
      $revenue = (float) ($data['revenue'] ?? 0.0);
      $this->variantAssignment->recordConversion($machineName, $revenue);

      $this->logger->info('Conversión de onboarding registrada: usuario @uid, evento "@event", experimento "@experiment".', [
        '@uid' => $userId,
        '@event' => $eventType,
        '@experiment' => $machineName,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversión de onboarding para usuario @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene métricas de conversión del onboarding por variante.
   *
   * Calcula las tasas de conversión de cada variante del experimento
   * de onboarding activo.
   *
   * @return array
   *   Array de métricas por variante, cada una con:
   *   - 'variant_id' (int): ID de la variante.
   *   - 'variant_name' (string): Nombre de la variante.
   *   - 'visitors' (int): Número de visitantes asignados.
   *   - 'conversions' (int): Número de conversiones.
   *   - 'conversion_rate' (float): Tasa de conversión (0.0 a 1.0).
   *   - 'is_control' (bool): Si es la variante de control.
   */
  public function getOnboardingMetrics(): array {
    try {
      $experiment = $this->getActiveOnboardingExperiment();

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
      $this->logger->error('Error obteniendo métricas de onboarding: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
