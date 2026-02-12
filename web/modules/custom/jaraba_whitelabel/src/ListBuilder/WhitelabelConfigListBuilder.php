<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for WhitelabelConfig entities.
 */
class WhitelabelConfigListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['config_key'] = $this->t('Config Key');
    $header['company_name'] = $this->t('Company');
    $header['tenant_id'] = $this->t('Tenant');
    $header['config_status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_whitelabel\Entity\WhitelabelConfig $entity */
    $status = $entity->get('config_status')->value ?? 'inactive';
    $statusColor = $status === 'active' ? '#43A047' : '#6C757D';

    $row['config_key'] = $entity->label();
    $row['company_name'] = $entity->get('company_name')->value ?? '';
    $row['tenant_id'] = $entity->get('tenant_id')->entity
      ? $entity->get('tenant_id')->entity->label()
      : '-';
    $row['config_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($status) . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
