<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para CsPlaybook con badges de estado y trigger.
 *
 * LÓGICA:
 * Muestra nombre, tipo de trigger, prioridad, estado (badge),
 * auto-execute, ejecuciones totales y tasa de éxito.
 */
class CsPlaybookListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['trigger_type'] = $this->t('Trigger');
    $header['priority'] = $this->t('Priority');
    $header['status'] = $this->t('Status');
    $header['auto_execute'] = $this->t('Auto');
    $header['executions'] = $this->t('Executions');
    $header['success_rate'] = $this->t('Success Rate');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\CsPlaybook $entity */
    $status = $entity->get('status')->value;
    $priority = $entity->get('priority')->value;

    $status_colors = [
      'active' => '#00A9A5',
      'paused' => '#FFB84D',
      'archived' => '#6c757d',
    ];

    $priority_colors = [
      'low' => '#6c757d',
      'medium' => '#233D63',
      'high' => '#FF8C42',
      'urgent' => '#DC3545',
    ];

    $row['name'] = $entity->getName();
    $row['trigger_type'] = [
      'data' => [
        '#markup' => '<code>' . ($entity->get('trigger_type')->value ?? '—') . '</code>',
      ],
    ];
    $row['priority'] = [
      'data' => [
        '#markup' => '<span style="background:' . ($priority_colors[$priority] ?? '#6c757d') . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($priority ?? '') . '</span>',
      ],
    ];
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background:' . ($status_colors[$status] ?? '#6c757d') . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($status ?? '') . '</span>',
      ],
    ];
    $row['auto_execute'] = $entity->get('auto_execute')->value ? '✓' : '—';
    $row['executions'] = (string) ($entity->get('execution_count')->value ?? 0);
    $row['success_rate'] = round((float) ($entity->get('success_rate')->value ?? 0)) . '%';

    return $row + parent::buildRow($entity);
  }

}
