<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for ProspeccionEmpresarial entities.
 */
class ProspeccionEmpresarialListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['empresa'] = $this->t('Empresa');
    $header['sector'] = $this->t('Sector');
    $header['estado'] = $this->t('Estado');
    $header['tipo'] = $this->t('Tipo Colaboración');
    $header['puestos'] = $this->t('Puestos');
    $header['provincia'] = $this->t('Provincia');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\ProspeccionEmpresarialInterface $entity */
    $row['empresa'] = $entity->toLink();
    $row['sector'] = $entity->getSector();

    $estados = $entity::ESTADOS;
    $estado = $entity->getEstado();
    $row['estado'] = $estados[$estado] ?? $estado;

    $tipos = $entity::TIPOS_COLABORACION;
    $tipo = $entity->getTipoColaboracion();
    $row['tipo'] = $tipos[$tipo] ?? $tipo;

    $row['puestos'] = $entity->get('puestos_disponibles')->value ?? '0';
    $row['provincia'] = $entity->get('provincia')->value ?? '';

    return $row + parent::buildRow($entity);
  }

}
