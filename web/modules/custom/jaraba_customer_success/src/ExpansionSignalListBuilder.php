<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ExpansionSignal con pipeline visual.
 *
 * LÓGICA:
 * Muestra tabla con tenant, tipo de señal, plan actual → recomendado,
 * ARR potencial, estado (badge de pipeline) y fecha de detección.
 */
class ExpansionSignalListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['signal_type'] = $this->t('Type');
    $header['plan'] = $this->t('Plan');
    $header['potential_arr'] = $this->t('Potential ARR');
    $header['status'] = $this->t('Status');
    $header['detected_at'] = $this->t('Detected');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\ExpansionSignal $entity */
    $tenant = $entity->get('tenant_id')->entity;
    $status = $entity->getStatus();

    $status_colors = [
      'new' => '#233D63',
      'contacted' => '#FFB84D',
      'won' => '#00A9A5',
      'lost' => '#DC3545',
      'deferred' => '#6c757d',
    ];
    $color = $status_colors[$status] ?? '#6c757d';

    $current_plan = $entity->get('current_plan')->value ?? '—';
    $recommended = $entity->get('recommended_plan')->value ?? '';
    $plan_display = $recommended ? "$current_plan → $recommended" : $current_plan;

    $row['tenant'] = $tenant ? $tenant->label() : $this->t('Unknown');
    $row['signal_type'] = ucfirst(str_replace('_', ' ', $entity->getSignalType()));
    $row['plan'] = $plan_display;
    $row['potential_arr'] = $entity->getPotentialArr() > 0
      ? '€' . number_format($entity->getPotentialArr(), 0, ',', '.')
      : '—';
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $color . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($status) . '</span>',
      ],
    ];
    $row['detected_at'] = $entity->get('detected_at')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('detected_at')->value, 'short')
      : '';

    return $row + parent::buildRow($entity);
  }

}
