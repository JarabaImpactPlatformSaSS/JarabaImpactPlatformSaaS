<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de administracion de pasos de secuencia de email.
 */
class EmailSequenceStepListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['position'] = $this->t('Posicion');
    $header['step_type'] = $this->t('Tipo');
    $header['subject_line'] = $this->t('Asunto');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_email\Entity\EmailSequenceStep $entity */
    $row['position'] = $entity->get('position')->value ?? '0';

    $typeLabels = [
      'email' => $this->t('Email'),
      'delay' => $this->t('Espera'),
      'condition' => $this->t('Condicion'),
      'action' => $this->t('Accion'),
      'split_test' => $this->t('Test A/B'),
    ];
    $type = $entity->get('step_type')->value ?? 'email';
    $row['step_type'] = $typeLabels[$type] ?? $type;

    $row['subject_line'] = $entity->get('subject_line')->value ?? '-';

    $isActive = (bool) $entity->get('is_active')->value;
    $row['is_active'] = $isActive ? $this->t('Si') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
