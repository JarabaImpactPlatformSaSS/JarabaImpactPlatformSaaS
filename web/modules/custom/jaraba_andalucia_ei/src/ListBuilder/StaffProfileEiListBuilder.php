<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for StaffProfileEi entities.
 */
class StaffProfileEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['display_name'] = $this->t('Nombre');
    $header['rol_programa'] = $this->t('Rol');
    $header['titulacion'] = $this->t('Titulación');
    $header['status'] = $this->t('Estado');
    $header['fecha_incorporacion'] = $this->t('Incorporación');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['display_name'] = $entity->get('display_name')->value ?? '';
    $row['rol_programa'] = $entity->get('rol_programa')->value ?? '';
    $row['titulacion'] = $entity->get('titulacion')->value ?? '';
    $row['status'] = $entity->get('status')->value ?? '';
    $row['fecha_incorporacion'] = '';

    try {
      $fecha = $entity->get('fecha_incorporacion')->value ?? NULL;
      if ($fecha) {
        $row['fecha_incorporacion'] = (new \DateTime($fecha))->format('d/m/Y');
      }
    }
    catch (\Throwable) {
    }

    return $row + parent::buildRow($entity);
  }

}
