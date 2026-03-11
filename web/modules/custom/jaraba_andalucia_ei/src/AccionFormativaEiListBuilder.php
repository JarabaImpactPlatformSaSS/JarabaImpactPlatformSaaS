<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for AccionFormativaEi entities.
 */
class AccionFormativaEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['titulo'] = $this->t('Título');
    $header['tipo_formacion'] = $this->t('Tipo');
    $header['carril'] = $this->t('Carril');
    $header['horas_previstas'] = $this->t('Horas');
    $header['estado'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface $entity */
    $row['titulo'] = $entity->toLink();
    $row['tipo_formacion'] = $entity->getTipoFormacion();
    $row['carril'] = $entity->getCarril();
    $row['horas_previstas'] = $entity->getHorasPrevistas();
    $row['estado'] = $entity->getEstado();
    return $row + parent::buildRow($entity);
  }

}
