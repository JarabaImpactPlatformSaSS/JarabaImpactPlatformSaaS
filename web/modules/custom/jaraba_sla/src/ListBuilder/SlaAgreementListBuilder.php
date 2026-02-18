<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for SLA Agreement entities in admin.
 *
 * Structure: Extends EntityListBuilder for admin table display.
 * Logic: Shows key columns: tenant, tier, uptime target, effective date, active status.
 */
class SlaAgreementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant_id'] = $this->t('Tenant');
    $header['sla_tier'] = $this->t('SLA Tier');
    $header['uptime_target'] = $this->t('Uptime Target');
    $header['effective_date'] = $this->t('Effective Date');
    $header['is_active'] = $this->t('Active');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $tierLabels = [
      'standard' => $this->t('Standard'),
      'premium' => $this->t('Premium'),
      'critical' => $this->t('Critical'),
    ];

    $tier = $entity->get('sla_tier')->value ?? 'standard';
    $uptimeTarget = (float) ($entity->get('uptime_target')->value ?? 99.9);
    $isActive = (bool) ($entity->get('is_active')->value ?? FALSE);

    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['sla_tier'] = $tierLabels[$tier] ?? $tier;
    $row['uptime_target'] = number_format($uptimeTarget, 3) . '%';
    $row['effective_date'] = $entity->get('effective_date')->value ?? '-';
    $row['is_active'] = $isActive ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
