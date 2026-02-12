<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for WhitelabelReseller entities.
 */
class WhitelabelResellerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['company_name'] = $this->t('Company');
    $header['contact_email'] = $this->t('Email');
    $header['commission_rate'] = $this->t('Commission (%)');
    $header['reseller_status'] = $this->t('Status');
    $header['revenue_share_model'] = $this->t('Revenue Model');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_whitelabel\Entity\WhitelabelReseller $entity */
    $statusColors = [
      'active' => '#43A047',
      'suspended' => '#E53935',
      'pending' => '#FF8C42',
    ];
    $statusLabels = [
      'active' => $this->t('Active'),
      'suspended' => $this->t('Suspended'),
      'pending' => $this->t('Pending'),
    ];
    $status = $entity->get('reseller_status')->value ?? 'pending';
    $statusColor = $statusColors[$status] ?? '#6C757D';
    $statusLabel = $statusLabels[$status] ?? $status;

    $modelLabels = [
      'percentage' => $this->t('Percentage'),
      'flat_fee' => $this->t('Flat fee'),
      'tiered' => $this->t('Tiered'),
    ];
    $model = $entity->get('revenue_share_model')->value ?? 'percentage';
    $modelLabel = $modelLabels[$model] ?? $model;

    $row['name'] = $entity->label();
    $row['company_name'] = $entity->get('company_name')->value ?? '';
    $row['contact_email'] = $entity->get('contact_email')->value ?? '';
    $row['commission_rate'] = number_format((float) ($entity->get('commission_rate')->value ?? 0), 2, ',', '.') . '%';
    $row['reseller_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['revenue_share_model'] = $modelLabel;

    return $row + parent::buildRow($entity);
  }

}
