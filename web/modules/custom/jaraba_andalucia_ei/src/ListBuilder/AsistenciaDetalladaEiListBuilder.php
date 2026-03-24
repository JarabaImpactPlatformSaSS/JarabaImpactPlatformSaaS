<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado admin de Asistencia Detallada EI.
 */
class AsistenciaDetalladaEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['participante_id'] = $this->t('Participante');
    $header['sesion_id'] = $this->t('Sesión');
    $header['fecha'] = $this->t('Fecha');
    $header['modalidad'] = $this->t('Modalidad');
    $header['horas'] = $this->t('Horas');
    $header['asistio'] = $this->t('Asistió');
    $header['evidencia'] = $this->t('Evidencia');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // LABEL-NULLSAFE-001: entity references may not resolve.
    $participante = $entity->hasField('participante_id') && !$entity->get('participante_id')->isEmpty()
      ? $entity->get('participante_id')->entity
      : NULL;
    $participanteLabel = $participante?->label() ?? $this->t('— sin participante —');

    $sesion = $entity->hasField('sesion_id') && !$entity->get('sesion_id')->isEmpty()
      ? $entity->get('sesion_id')->entity
      : NULL;
    $sesionLabel = $sesion?->label() ?? $this->t('— sin sesión —');

    $asistio = $entity->hasField('asistio')
      ? (bool) $entity->get('asistio')->value
      : FALSE;

    $row['participante_id'] = $participanteLabel;
    $row['sesion_id'] = $sesionLabel;
    $row['fecha'] = $entity->get('fecha')->value ?? '-';
    $row['modalidad'] = $entity->get('modalidad')->value ?? '-';
    $row['horas'] = $entity->get('horas')->value ?? '0';
    $row['asistio'] = $asistio ? $this->t('Sí') : $this->t('No');
    $row['evidencia'] = $entity->get('evidencia')->value
      ? $this->t('Sí')
      : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
