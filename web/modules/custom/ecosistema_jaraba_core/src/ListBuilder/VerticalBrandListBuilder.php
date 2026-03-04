<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder para VerticalBrandConfig.
 */
class VerticalBrandListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['id'] = $this->t('Vertical');
    $header['public_name'] = $this->t('Nombre Publico');
    $header['tagline'] = $this->t('Tagline');
    $header['revelation_level'] = $this->t('Revelacion');
    $header['enabled'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\VerticalBrandConfig $entity */
    $row = [];
    $row['id'] = $entity->id();
    $row['public_name'] = $entity->getPublicName();
    $row['tagline'] = $entity->getTagline();
    $row['revelation_level'] = $entity->getRevelationLevel();
    $row['enabled'] = $entity->isEnabled() ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
