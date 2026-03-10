<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for PlanEmprendimientoEi entities.
 */
class PlanEmprendimientoEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Plan');
    $header['fase'] = $this->t('Fase');
    $header['viabilidad'] = $this->t('Viabilidad');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->toLink();
    $row['fase'] = $entity->get('fase_emprendimiento')->value ?? '';
    $row['viabilidad'] = $entity->get('diagnostico_viabilidad')->value ?? '';
    $row['status'] = $entity->get('status')->value ? $this->t('Activo') : $this->t('Inactivo');
    return $row + parent::buildRow($entity);
  }

}
