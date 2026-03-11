<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for PlanFormativoEi entities.
 */
class PlanFormativoEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['titulo'] = $this->t('Titulo');
    $header['carril'] = $this->t('Carril');
    $header['horas_totales'] = $this->t('Horas Totales');
    $header['cumple_formacion'] = $this->t('Min. Formacion');
    $header['cumple_orientacion'] = $this->t('Min. Orientacion');
    $header['estado'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\PlanFormativoEiInterface $entity */
    $row['titulo'] = $entity->toLink();
    $row['carril'] = $entity->getCarril();
    $row['horas_totales'] = $entity->getHorasTotalesPrevistas();
    $row['cumple_formacion'] = $entity->cumpleMinimosFormacion()
      ? $this->t('Si')
      : $this->t('No');
    $row['cumple_orientacion'] = $entity->cumpleMinimosOrientacion()
      ? $this->t('Si')
      : $this->t('No');
    $row['estado'] = $entity->getEstado();
    return $row + parent::buildRow($entity);
  }

}
