<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for CustomDomain entities.
 */
class CustomDomainListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['domain'] = $this->t('Domain');
    $header['tenant_id'] = $this->t('Tenant');
    $header['ssl_status'] = $this->t('SSL');
    $header['dns_verified'] = $this->t('DNS Verified');
    $header['domain_status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_whitelabel\Entity\CustomDomain $entity */
    $sslColors = [
      'pending' => '#FF8C42',
      'active' => '#43A047',
      'failed' => '#E53935',
    ];
    $ssl = $entity->get('ssl_status')->value ?? 'pending';
    $sslColor = $sslColors[$ssl] ?? '#6C757D';

    $domainStatus = $entity->get('domain_status')->value ?? 'pending';
    $domainStatusColors = [
      'pending' => '#FF8C42',
      'active' => '#43A047',
      'suspended' => '#E53935',
    ];
    $domainStatusColor = $domainStatusColors[$domainStatus] ?? '#6C757D';

    $row['domain'] = $entity->label();
    $row['tenant_id'] = $entity->get('tenant_id')->entity
      ? $entity->get('tenant_id')->entity->label()
      : '-';
    $row['ssl_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $sslColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($ssl) . '</span>',
      ],
    ];
    $row['dns_verified'] = $entity->get('dns_verified')->value ? $this->t('Yes') : $this->t('No');
    $row['domain_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $domainStatusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($domainStatus) . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
