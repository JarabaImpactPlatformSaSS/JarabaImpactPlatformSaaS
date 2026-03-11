<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for SesionProgramadaEi entities.
 */
class SesionProgramadaEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['titulo'] = $this->t('Título');
    $header['tipo_sesion'] = $this->t('Tipo');
    $header['fecha'] = $this->t('Fecha');
    $header['estado'] = $this->t('Estado');
    $header['plazas'] = $this->t('Plazas');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $entity */
    $row['titulo'] = $entity->toLink();
    $row['tipo_sesion'] = $entity->getTipoSesion();
    $row['fecha'] = $entity->getFecha() ?? '';
    $row['estado'] = $entity->getEstado();
    $row['plazas'] = $entity->getPlazasOcupadas() . '/' . $entity->getMaxPlazas();
    return $row + parent::buildRow($entity);
  }

}
