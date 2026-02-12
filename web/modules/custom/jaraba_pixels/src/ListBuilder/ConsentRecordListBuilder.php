<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de registros de consentimiento en admin.
 */
class ConsentRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['visitor_id'] = $this->t('Visitor ID');
    $header['consent_type'] = $this->t('Tipo de Consentimiento');
    $header['status'] = $this->t('Estado');
    $header['consent_version'] = $this->t('Versión');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['visitor_id'] = $entity->get('visitor_id')->value ?? '-';
    $row['consent_type'] = $entity->get('consent_type')->value ?? '-';
    $row['status'] = $entity->get('status')->value ?? '-';
    $row['consent_version'] = $entity->get('consent_version')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    return $row + parent::buildRow($entity);
  }

}
