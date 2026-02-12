<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de administracion de etapas del pipeline.
 */
class PipelineStageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['machine_name'] = $this->t('ID interno');
    $header['position'] = $this->t('Posicion');
    $header['probability'] = $this->t('Probabilidad');
    $header['is_active'] = $this->t('Activa');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_crm\Entity\PipelineStage $entity */
    $row['name'] = $entity->toLink();
    $row['machine_name'] = $entity->get('machine_name')->value ?? '-';
    $row['position'] = $entity->get('position')->value ?? '0';
    $row['probability'] = ($entity->get('default_probability')->value ?? '0') . '%';

    $isActive = (bool) $entity->get('is_active')->value;
    $row['is_active'] = $isActive ? $this->t('Si') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
