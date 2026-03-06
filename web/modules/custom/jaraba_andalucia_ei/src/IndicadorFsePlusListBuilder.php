<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for IndicadorFsePlus entities.
 */
class IndicadorFsePlusListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['momento_recogida'] = $this->t('Momento');
    $header['fecha_recogida'] = $this->t('Fecha');
    $header['participante'] = $this->t('Participante');
    $header['completado'] = $this->t('Completado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $momentoLabels = [
      'entrada' => $this->t('Entrada'),
      'salida' => $this->t('Salida'),
      'seguimiento_6m' => $this->t('Seguimiento 6m'),
    ];

    $momento = $entity->get('momento_recogida')->value ?? '';
    $completado = (bool) ($entity->get('completado')->value ?? FALSE);

    // LABEL-NULLSAFE-001: participante_id may be empty.
    $participanteLabel = '-';
    $participanteRef = $entity->get('participante_id')->entity ?? NULL;
    if ($participanteRef !== NULL) {
      $participanteLabel = $participanteRef->label() ?? (string) $participanteRef->id();
    }

    $row['momento_recogida'] = $momentoLabels[$momento] ?? $momento;
    $row['fecha_recogida'] = $entity->get('fecha_recogida')->value ?? '-';
    $row['participante'] = $participanteLabel;
    $row['completado'] = $completado ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
