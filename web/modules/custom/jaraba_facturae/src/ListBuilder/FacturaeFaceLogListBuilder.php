<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Facturae FACe Log entities.
 */
class FacturaeFaceLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['operation'] = $this->t('Operation');
    $header['response_code'] = $this->t('Response Code');
    $header['http_status'] = $this->t('HTTP Status');
    $header['duration_ms'] = $this->t('Duration (ms)');
    $header['created'] = $this->t('Timestamp');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['operation'] = $entity->get('operation')->value ?? '';
    $row['response_code'] = $entity->get('response_code')->value ?? '';
    $row['http_status'] = $entity->get('http_status')->value ?? '';
    $row['duration_ms'] = $entity->get('duration_ms')->value ?? '';
    $row['created'] = $entity->get('created')->value
      ? date('Y-m-d H:i:s', (int) $entity->get('created')->value)
      : '';
    return $row + parent::buildRow($entity);
  }

}
