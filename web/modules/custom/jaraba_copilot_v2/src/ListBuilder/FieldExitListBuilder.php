<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad FieldExit.
 */
class FieldExitListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['contact_name'] = $this->t('Contacto');
    $header['exit_type'] = $this->t('Tipo');
    $header['contacts_count'] = $this->t('Contactos');
    $header['learnings'] = $this->t('Aprendizajes');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_copilot_v2\Entity\FieldExit $entity */
    $row['contact_name'] = $entity->toLink();

    $type = $entity->get('exit_type')->value ?? '-';
    $type_labels = [
      'interview' => 'Entrevista',
      'observation' => 'Observacion',
      'survey' => 'Encuesta',
      'focus_group' => 'Focus Group',
      'prototype_test' => 'Test Prototipo',
      'sales_test' => 'Test Ventas',
      'landing_page' => 'Landing Page',
      'event' => 'Evento',
      'cold_call' => 'Llamada Fria',
      'other' => 'Otro',
    ];
    $row['exit_type'] = $type_labels[$type] ?? $type;

    $row['contacts_count'] = $entity->get('contacts_count')->value ?? '0';

    $learnings = $entity->get('learnings')->value ?? '-';
    $row['learnings'] = [
      '#markup' => mb_substr($learnings, 0, 80) . (mb_strlen($learnings) > 80 ? '...' : ''),
    ];

    $row['created'] = \Drupal::service('date.formatter')->format(
      (int) $entity->get('created')->value,
      'short'
    );

    return $row + parent::buildRow($entity);
  }

}
