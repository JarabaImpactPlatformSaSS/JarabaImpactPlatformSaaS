<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista administrativa de AgentFlowExecution con estado y duracion.
 *
 * PROPOSITO:
 * Renderiza la tabla de ejecuciones en /admin/content/agent-flow-executions.
 *
 * LOGICA:
 * Muestra: flujo referenciado, estado de ejecucion (badge color),
 * fecha de inicio y duracion en milisegundos.
 */
class AgentFlowExecutionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['flow'] = $this->t('Flujo');
    $header['execution_status'] = $this->t('Estado');
    $header['started_at'] = $this->t('Inicio');
    $header['duration_ms'] = $this->t('Duracion (ms)');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlowExecution $entity */

    // Obtener label del flujo referenciado.
    $flowLabel = '';
    $flowRef = $entity->get('flow_id')->entity;
    if ($flowRef) {
      $flowLabel = $flowRef->label();
    }

    // Badge de estado con colores.
    $statusColors = [
      'pending' => '#6C757D',
      'running' => '#1976D2',
      'completed' => '#43A047',
      'failed' => '#E53935',
      'cancelled' => '#FF8C42',
    ];
    $statusLabels = [
      'pending' => $this->t('Pendiente'),
      'running' => $this->t('Ejecutando'),
      'completed' => $this->t('Completada'),
      'failed' => $this->t('Fallida'),
      'cancelled' => $this->t('Cancelada'),
    ];
    $status = $entity->get('execution_status')->value ?? 'pending';
    $statusColor = $statusColors[$status] ?? '#6C757D';
    $statusLabel = $statusLabels[$status] ?? $status;

    $row['flow'] = $flowLabel;
    $row['execution_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['started_at'] = $entity->get('started_at')->value
      ? \Drupal::service('date.formatter')->format(
          (int) $entity->get('started_at')->value,
          'short',
        )
      : $this->t('N/A');
    $row['duration_ms'] = $entity->get('duration_ms')->value ?? 0;

    return $row + parent::buildRow($entity);
  }

}
