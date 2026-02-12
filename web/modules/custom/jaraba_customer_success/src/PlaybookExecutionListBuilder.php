<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para PlaybookExecution con progreso visual.
 *
 * LÓGICA:
 * Muestra playbook, tenant, progreso (paso actual/total),
 * estado (badge), fecha de inicio y próxima acción.
 */
class PlaybookExecutionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['playbook'] = $this->t('Playbook');
    $header['tenant'] = $this->t('Tenant');
    $header['progress'] = $this->t('Progress');
    $header['status'] = $this->t('Status');
    $header['next_action'] = $this->t('Next Action');
    $header['started'] = $this->t('Started');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\PlaybookExecution $entity */
    $playbook = $entity->get('playbook_id')->entity;
    $tenant = $entity->get('tenant_id')->entity;
    $status = $entity->get('status')->value;

    $status_colors = [
      'running' => '#233D63',
      'completed' => '#00A9A5',
      'failed' => '#DC3545',
      'cancelled' => '#6c757d',
    ];
    $color = $status_colors[$status] ?? '#6c757d';

    $current = (int) $entity->get('current_step')->value;
    $total = (int) $entity->get('total_steps')->value;

    $row['playbook'] = $playbook ? $playbook->label() : $this->t('Unknown');
    $row['tenant'] = $tenant ? $tenant->label() : $this->t('Unknown');
    $row['progress'] = $total > 0 ? "$current / $total" : '—';
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $color . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($status ?? '') . '</span>',
      ],
    ];
    $row['next_action'] = $entity->get('next_action_at')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('next_action_at')->value, 'short')
      : '—';
    $row['started'] = $entity->get('started_at')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('started_at')->value, 'short')
      : '';

    return $row + parent::buildRow($entity);
  }

}
