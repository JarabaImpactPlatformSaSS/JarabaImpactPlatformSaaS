<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Entity\PipelineStage;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestion de etapas del pipeline de ventas.
 *
 * Proporciona operaciones CRUD, reordenacion y creacion
 * de etapas por defecto para nuevos tenants.
 */
class PipelineStageService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el storage de etapas.
   */
  protected function getStorage() {
    return $this->entityTypeManager->getStorage('crm_pipeline_stage');
  }

  /**
   * Obtiene todas las etapas activas de un tenant, ordenadas por posicion.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return PipelineStage[]
   *   Etapas ordenadas por posicion.
   */
  public function getStagesForTenant(int $tenantId): array {
    $ids = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->sort('position', 'ASC')
      ->execute();

    return $ids ? $this->getStorage()->loadMultiple($ids) : [];
  }

  /**
   * Obtiene una etapa por su ID.
   *
   * @param int $stageId
   *   ID de la etapa.
   *
   * @return PipelineStage|null
   *   La etapa o NULL si no existe.
   */
  public function getStageById(int $stageId): ?PipelineStage {
    $entity = $this->getStorage()->load($stageId);
    return $entity instanceof PipelineStage ? $entity : NULL;
  }

  /**
   * Crea las etapas por defecto para un nuevo tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return PipelineStage[]
   *   Etapas creadas.
   */
  public function createDefaultStages(int $tenantId): array {
    $defaults = [
      ['name' => 'Lead', 'machine_name' => 'lead', 'color' => '#9E9E9E', 'position' => 0, 'default_probability' => '10.00'],
      ['name' => 'Contactado', 'machine_name' => 'contacted', 'color' => '#2196F3', 'position' => 1, 'default_probability' => '20.00'],
      ['name' => 'Calificado', 'machine_name' => 'qualified', 'color' => '#FF9800', 'position' => 2, 'default_probability' => '40.00'],
      ['name' => 'Propuesta', 'machine_name' => 'proposal', 'color' => '#9C27B0', 'position' => 3, 'default_probability' => '60.00'],
      ['name' => 'Negociacion', 'machine_name' => 'negotiation', 'color' => '#E91E63', 'position' => 4, 'default_probability' => '80.00'],
      ['name' => 'Ganada', 'machine_name' => 'won', 'color' => '#4CAF50', 'position' => 5, 'default_probability' => '100.00', 'is_won_stage' => TRUE],
      ['name' => 'Perdida', 'machine_name' => 'lost', 'color' => '#F44336', 'position' => 6, 'default_probability' => '0.00', 'is_lost_stage' => TRUE],
    ];

    $stages = [];
    foreach ($defaults as $data) {
      $stage = $this->getStorage()->create($data + [
        'tenant_id' => $tenantId,
        'is_active' => TRUE,
        'rotting_days' => 14,
      ]);
      $stage->save();
      $stages[] = $stage;
    }

    $this->logger->info('Etapas por defecto creadas para tenant @id.', ['@id' => $tenantId]);

    return $stages;
  }

  /**
   * Reordena las etapas del pipeline.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param array $order
   *   Array de [stage_id => new_position].
   *
   * @return bool
   *   TRUE si se reordeno correctamente.
   */
  public function reorderStages(int $tenantId, array $order): bool {
    try {
      foreach ($order as $stageId => $position) {
        $stage = $this->getStageById((int) $stageId);
        if ($stage && (int) $stage->get('tenant_id')->target_id === $tenantId) {
          $stage->set('position', (int) $position);
          $stage->save();
        }
      }
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al reordenar etapas: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Cuenta etapas de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Numero de etapas.
   */
  public function count(int $tenantId): int {
    return (int) $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->count()
      ->execute();
  }

}
