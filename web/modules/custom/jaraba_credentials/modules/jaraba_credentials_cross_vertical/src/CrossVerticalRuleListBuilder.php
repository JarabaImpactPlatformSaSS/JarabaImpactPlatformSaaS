<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para CrossVerticalRule.
 */
class CrossVerticalRuleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['verticals'] = $this->t('Verticales');
    $header['rarity'] = $this->t('Rareza');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_credentials_cross_vertical\Entity\CrossVerticalRule $entity */
    $row['name'] = $entity->get('name')->value;
    $row['verticals'] = implode(', ', $entity->getVerticalsRequired());
    $row['rarity'] = $entity->getRarity();

    $status = (bool) $entity->get('status')->value;
    $row['status'] = $status ? $this->t('Activo') : $this->t('Inactivo');

    return $row + parent::buildRow($entity);
  }

}
