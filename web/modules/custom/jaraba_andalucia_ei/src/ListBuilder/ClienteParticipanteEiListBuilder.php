<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for ClienteParticipanteEi entities.
 */
class ClienteParticipanteEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildHeader(): array {
    $header = [];
    $header['nombre_negocio'] = $this->t('Nombre del Negocio');
    $header['sector'] = $this->t('Sector');
    $header['pack_contratado'] = $this->t('Pack Contratado');
    $header['estado'] = $this->t('Estado');
    $header['es_piloto'] = $this->t('Piloto');
    $header['precio_mensual'] = $this->t('Precio Mensual');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $row = [];
    $row['nombre_negocio'] = (string) ($entity->get('nombre_negocio')->value ?? '');
    $row['sector'] = (string) ($entity->get('sector')->value ?? '');
    $row['pack_contratado'] = (string) ($entity->get('pack_contratado')->value ?? '');
    $row['estado'] = (string) ($entity->get('estado')->value ?? '');

    $row['es_piloto'] = ((bool) ($entity->get('es_piloto')->value ?? FALSE))
      ? (string) $this->t('Sí')
      : (string) $this->t('No');

    $precioMensual = $entity->get('precio_mensual')->value ?? NULL;
    $row['precio_mensual'] = $precioMensual !== NULL
      ? number_format((float) $precioMensual, 2, ',', '.') . ' €'
      : '';

    return $row + parent::buildRow($entity);
  }

}
