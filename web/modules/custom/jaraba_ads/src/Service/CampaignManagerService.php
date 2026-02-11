<?php

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ads\Entity\AdCampaign;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de campañas publicitarias.
 *
 * ESTRUCTURA:
 * Servicio central que orquesta operaciones CRUD sobre campañas
 * publicitarias, sincronización de estado con plataformas externas
 * y actualización de métricas de rendimiento. Depende de
 * EntityTypeManager para CRUD de entidades, TenantContextService
 * para aislamiento multi-tenant, y del canal de log dedicado.
 *
 * LÓGICA:
 * El flujo de gestión de campañas sigue estas reglas de negocio:
 * 1. Las campañas se crean en estado 'draft' antes de activarse.
 * 2. La activación requiere presupuesto y fechas configuradas.
 * 3. Las campañas activas se pueden pausar y reanudar.
 * 4. Las campañas completadas son de solo lectura.
 * 5. La sincronización actualiza métricas desde la plataforma de ads.
 *
 * RELACIONES:
 * - CampaignManagerService -> EntityTypeManager (dependencia)
 * - CampaignManagerService -> TenantContextService (dependencia)
 * - CampaignManagerService -> AdCampaign entity (gestiona)
 * - CampaignManagerService <- AdsDashboardController (consumido por)
 * - CampaignManagerService <- AdsApiController (consumido por)
 *
 * @package Drupal\jaraba_ads\Service
 */
class CampaignManagerService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de contexto de tenant para aislamiento multi-tenant.
   *
   * @var object
   */
  protected $tenantContext;

  /**
   * Canal de log dedicado para el módulo de ads.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de gestión de campañas.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param object $tenant_context
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones del módulo.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    $tenant_context,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
    $this->logger = $logger;
  }

  /**
   * Crea una nueva campaña publicitaria.
   *
   * ESTRUCTURA: Método público para crear campañas.
   *
   * LÓGICA: Crea una entidad AdCampaign con los datos proporcionados,
   *   asigna el tenant del contexto actual y la guarda en estado 'draft'.
   *
   * RELACIONES: Consume AdCampaign storage, TenantContextService.
   *
   * @param array $data
   *   Datos de la campaña con las siguientes claves:
   *   - 'label' (string): Nombre de la campaña.
   *   - 'platform' (string): Plataforma de ads.
   *   - 'campaign_id_external' (string, optional): ID externo.
   *   - 'budget_daily' (float, optional): Presupuesto diario.
   *   - 'budget_total' (float, optional): Presupuesto total.
   *   - 'start_date' (string, optional): Fecha de inicio.
   *   - 'end_date' (string, optional): Fecha de fin.
   *
   * @return \Drupal\jaraba_ads\Entity\AdCampaign
   *   La entidad AdCampaign creada.
   */
  public function createCampaign(array $data): AdCampaign {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');

    // Obtener tenant_id del contexto actual.
    $tenant_id = NULL;
    if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
      $tenant_id = $this->tenantContext->getCurrentTenantId();
    }

    /** @var \Drupal\jaraba_ads\Entity\AdCampaign $campaign */
    $campaign = $storage->create([
      'label' => $data['label'] ?? '',
      'platform' => $data['platform'] ?? 'google_ads',
      'campaign_id_external' => $data['campaign_id_external'] ?? '',
      'status' => 'draft',
      'budget_daily' => $data['budget_daily'] ?? 0,
      'budget_total' => $data['budget_total'] ?? 0,
      'start_date' => $data['start_date'] ?? NULL,
      'end_date' => $data['end_date'] ?? NULL,
      'tenant_id' => $tenant_id,
    ]);

    $campaign->save();

    $this->logger->info('Campaña creada: @label en @platform (ID: @id)', [
      '@label' => $campaign->label(),
      '@platform' => $data['platform'] ?? 'google_ads',
      '@id' => $campaign->id(),
    ]);

    return $campaign;
  }

  /**
   * Actualiza el estado de una campaña.
   *
   * ESTRUCTURA: Método público para transiciones de estado.
   *
   * LÓGICA: Valida la transición de estado (draft->active, active->paused,
   *   paused->active, active->completed) y actualiza la entidad.
   *
   * RELACIONES: Consume AdCampaign storage.
   *
   * @param int $campaign_id
   *   ID de la campaña a actualizar.
   * @param string $new_status
   *   Nuevo estado: 'active', 'paused', 'completed'.
   *
   * @return \Drupal\jaraba_ads\Entity\AdCampaign|null
   *   La campaña actualizada o NULL si no existe.
   *
   * @throws \RuntimeException
   *   Si la transición de estado no es válida.
   */
  public function updateStatus(int $campaign_id, string $new_status): ?AdCampaign {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');
    /** @var \Drupal\jaraba_ads\Entity\AdCampaign|null $campaign */
    $campaign = $storage->load($campaign_id);

    if (!$campaign) {
      return NULL;
    }

    $current_status = $campaign->get('status')->value;
    $valid_transitions = [
      'draft' => ['active'],
      'active' => ['paused', 'completed'],
      'paused' => ['active', 'completed'],
    ];

    if (!isset($valid_transitions[$current_status]) ||
        !in_array($new_status, $valid_transitions[$current_status])) {
      throw new \RuntimeException(
        sprintf('Transición de estado no válida: %s -> %s para campaña "%s".',
          $current_status,
          $new_status,
          $campaign->label()
        )
      );
    }

    $campaign->set('status', $new_status);
    $campaign->save();

    $this->logger->info('Campaña @label: estado cambiado de @old a @new', [
      '@label' => $campaign->label(),
      '@old' => $current_status,
      '@new' => $new_status,
    ]);

    return $campaign;
  }

  /**
   * Sincroniza métricas de rendimiento de una campaña.
   *
   * ESTRUCTURA: Método público para actualizar métricas.
   *
   * LÓGICA: Recibe datos de rendimiento (impressions, clicks, conversions,
   *   spend) y actualiza la entidad con los nuevos valores. Recalcula
   *   las métricas derivadas (CTR, CPC) automáticamente.
   *
   * RELACIONES: Consume AdCampaign storage, invoca recalculateMetrics().
   *
   * @param int $campaign_id
   *   ID de la campaña a actualizar.
   * @param array $metrics
   *   Datos de métricas:
   *   - 'impressions' (int): Total de impresiones.
   *   - 'clicks' (int): Total de clics.
   *   - 'conversions' (int): Total de conversiones.
   *   - 'spend' (float): Gasto acumulado.
   *   - 'roas' (float, optional): ROAS calculado externamente.
   *
   * @return \Drupal\jaraba_ads\Entity\AdCampaign|null
   *   La campaña actualizada o NULL si no existe.
   */
  public function syncMetrics(int $campaign_id, array $metrics): ?AdCampaign {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');
    /** @var \Drupal\jaraba_ads\Entity\AdCampaign|null $campaign */
    $campaign = $storage->load($campaign_id);

    if (!$campaign) {
      return NULL;
    }

    if (isset($metrics['impressions'])) {
      $campaign->set('impressions', (int) $metrics['impressions']);
    }
    if (isset($metrics['clicks'])) {
      $campaign->set('clicks', (int) $metrics['clicks']);
    }
    if (isset($metrics['conversions'])) {
      $campaign->set('conversions', (int) $metrics['conversions']);
    }
    if (isset($metrics['spend'])) {
      $campaign->set('spend_to_date', (float) $metrics['spend']);
    }
    if (isset($metrics['roas'])) {
      $campaign->set('roas', (float) $metrics['roas']);
    }

    // Recalcular CTR y CPC.
    $campaign->recalculateMetrics();
    $campaign->save();

    $this->logger->info('Métricas sincronizadas para campaña @label: @clicks clics, @conv conversiones', [
      '@label' => $campaign->label(),
      '@clicks' => $metrics['clicks'] ?? 0,
      '@conv' => $metrics['conversions'] ?? 0,
    ]);

    return $campaign;
  }

  /**
   * Obtiene todas las campañas de un tenant.
   *
   * ESTRUCTURA: Método público de consulta por tenant.
   *
   * LÓGICA: Consulta todas las campañas del tenant especificado,
   *   ordenadas por fecha de creación descendente.
   *
   * RELACIONES: Consume AdCampaign storage.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string|null $status_filter
   *   Filtro opcional por estado.
   *
   * @return array
   *   Array de entidades AdCampaign.
   */
  public function getTenantCampaigns(int $tenant_id, ?string $status_filter = NULL): array {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->sort('created', 'DESC');

    if ($status_filter) {
      $query->condition('status', $status_filter);
    }

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return array_values($storage->loadMultiple($ids));
  }

}
