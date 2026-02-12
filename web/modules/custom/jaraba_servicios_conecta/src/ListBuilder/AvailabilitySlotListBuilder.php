<?php

namespace Drupal\jaraba_servicios_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de slots de disponibilidad en admin.
 */
class AvailabilitySlotListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['provider_id'] = $this->t('Profesional');
    $header['day_of_week'] = $this->t('Día');
    $header['start_time'] = $this->t('Inicio');
    $header['end_time'] = $this->t('Fin');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $day_labels = [
      1 => $this->t('Lunes'),
      2 => $this->t('Martes'),
      3 => $this->t('Miércoles'),
      4 => $this->t('Jueves'),
      5 => $this->t('Viernes'),
      6 => $this->t('Sábado'),
      7 => $this->t('Domingo'),
    ];

    $provider = $entity->get('provider_id')->entity;
    $day = (int) $entity->get('day_of_week')->value;

    $row['provider_id'] = $provider ? $provider->get('display_name')->value : '-';
    $row['day_of_week'] = $day_labels[$day] ?? $day;
    $row['start_time'] = $entity->get('start_time')->value ?? '';
    $row['end_time'] = $entity->get('end_time')->value ?? '';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
