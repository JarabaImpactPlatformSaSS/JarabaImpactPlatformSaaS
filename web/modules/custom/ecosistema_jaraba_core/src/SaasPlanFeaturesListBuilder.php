<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * ListBuilder para la entidad SaasPlanFeatures.
 *
 * Muestra un listado con las columnas relevantes para la gestion
 * de features por vertical y tier.
 */
class SaasPlanFeaturesListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Nombre');
    $header['vertical'] = $this->t('Vertical');
    $header['tier'] = $this->t('Tier');
    $header['features_count'] = $this->t('Features');
    $header['limits_count'] = $this->t('Limites');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['vertical'] = $entity->getVertical();
    $row['tier'] = $entity->getTier();
    $row['features_count'] = count($entity->getFeatures());
    $row['limits_count'] = count($entity->getLimits());
    $row['status'] = $entity->status() ? $this->t('Activo') : $this->t('Inactivo');

    return $row + parent::buildRow($entity);
  }

}
