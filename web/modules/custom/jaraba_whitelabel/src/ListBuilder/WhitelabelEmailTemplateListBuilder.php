<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for WhitelabelEmailTemplate entities.
 */
class WhitelabelEmailTemplateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['template_key'] = $this->t('Template Key');
    $header['subject'] = $this->t('Subject');
    $header['tenant_id'] = $this->t('Tenant');
    $header['template_status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_whitelabel\Entity\WhitelabelEmailTemplate $entity */
    $status = $entity->get('template_status')->value ?? 'inactive';
    $statusColor = $status === 'active' ? '#43A047' : '#6C757D';

    $row['template_key'] = $entity->label();
    $row['subject'] = $entity->get('subject')->value ?? '';
    $row['tenant_id'] = $entity->get('tenant_id')->entity
      ? $entity->get('tenant_id')->entity->label()
      : '-';
    $row['template_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($status) . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
