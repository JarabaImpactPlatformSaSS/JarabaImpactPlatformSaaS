<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for WaTemplate config entities.
 */
class WaTemplateListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Template');
    $header['id'] = $this->t('Machine name');
    $header['category'] = $this->t('Categoria');
    $header['status_meta'] = $this->t('Estado Meta');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_whatsapp\Entity\WaTemplateInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['category'] = $entity->getCategory();
    $row['status_meta'] = $entity->getStatusMeta();
    return $row + parent::buildRow($entity);
  }

}
