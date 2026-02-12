<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de exposiciones de experimento en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/experiment-exposures.
 *
 * Logica: Muestra columnas clave para gestion rapida: visitor_id,
 *   variant_id, device_type y estado de conversion.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class ExperimentExposureListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['visitor_id'] = $this->t('Visitante');
    $header['variant_id'] = $this->t('Variante');
    $header['device_type'] = $this->t('Dispositivo');
    $header['converted'] = $this->t('Convertido');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $device_labels = [
      'desktop' => $this->t('Desktop'),
      'mobile' => $this->t('Mobile'),
      'tablet' => $this->t('Tablet'),
    ];

    $device = $entity->get('device_type')->value;
    $converted = (bool) $entity->get('converted')->value;

    $row['visitor_id'] = $entity->get('visitor_id')->value ?? '';
    $row['variant_id'] = $entity->get('variant_id')->value ?? '';
    $row['device_type'] = $device_labels[$device] ?? $device ?? '-';
    $row['converted'] = $converted ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
