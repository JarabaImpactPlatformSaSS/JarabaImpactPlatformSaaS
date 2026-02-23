<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * ListBuilder para la entidad SaasPlanTier.
 *
 * Muestra un listado con las columnas relevantes para la gestion
 * de tiers de planes SaaS.
 */
class SaasPlanTierListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Nombre');
    $header['tier_key'] = $this->t('Clave');
    $header['aliases'] = $this->t('Aliases');
    $header['weight'] = $this->t('Peso');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['tier_key'] = $entity->getTierKey();
    $row['aliases'] = implode(', ', $entity->getAliases());
    $row['weight'] = $entity->getWeight();
    $row['status'] = $entity->status() ? $this->t('Activo') : $this->t('Inactivo');

    return $row + parent::buildRow($entity);
  }

}
