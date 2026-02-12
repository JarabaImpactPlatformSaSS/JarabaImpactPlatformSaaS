<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista administrativa de AgentFlow con badges de estado y trigger.
 *
 * PROPOSITO:
 * Renderiza la tabla de flujos de agente en /admin/content/agent-flows.
 *
 * LOGICA:
 * Muestra: nombre, estado (badge color), tipo de trigger,
 * ejecuciones totales, fecha de modificacion y operaciones.
 */
class AgentFlowListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['flow_status'] = $this->t('Estado');
    $header['trigger_type'] = $this->t('Trigger');
    $header['execution_count'] = $this->t('Ejecuciones');
    $header['changed'] = $this->t('Modificado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow $entity */

    // Badge de estado con colores.
    $statusColors = [
      'draft' => '#6C757D',
      'active' => '#43A047',
      'paused' => '#FF8C42',
      'archived' => '#E53935',
    ];
    $statusLabels = [
      'draft' => $this->t('Borrador'),
      'active' => $this->t('Activo'),
      'paused' => $this->t('Pausado'),
      'archived' => $this->t('Archivado'),
    ];
    $status = $entity->get('flow_status')->value ?? 'draft';
    $statusColor = $statusColors[$status] ?? '#6C757D';
    $statusLabel = $statusLabels[$status] ?? $status;

    // Labels de trigger.
    $triggerLabels = [
      'manual' => $this->t('Manual'),
      'cron' => $this->t('Cron'),
      'webhook' => $this->t('Webhook'),
      'event' => $this->t('Evento'),
    ];
    $triggerType = $entity->get('trigger_type')->value ?? 'manual';
    $triggerLabel = $triggerLabels[$triggerType] ?? $triggerType;

    $row['name'] = $entity->label();
    $row['flow_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['trigger_type'] = $triggerLabel;
    $row['execution_count'] = $entity->get('execution_count')->value ?? 0;
    $row['changed'] = \Drupal::service('date.formatter')->format(
      (int) ($entity->get('changed')->value ?? 0),
      'short',
    );

    return $row + parent::buildRow($entity);
  }

}
