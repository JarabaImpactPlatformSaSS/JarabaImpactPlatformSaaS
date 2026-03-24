<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for PackServicioEi entities.
 */
class PackServicioEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildHeader(): array {
    $header = [];
    $header['titulo_personalizado'] = $this->t('Título');
    $header['pack_tipo'] = $this->t('Pack');
    $header['modalidad'] = $this->t('Modalidad');
    $header['precio_mensual'] = $this->t('Precio mensual');
    $header['publicado'] = $this->t('Publicado');
    $header['participante'] = $this->t('Participante');
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
    $row['titulo_personalizado'] = (string) ($entity->get('titulo_personalizado')->value ?? '');
    $row['pack_tipo'] = (string) ($entity->get('pack_tipo')->value ?? '');
    $row['modalidad'] = (string) ($entity->get('modalidad')->value ?? '');
    $row['precio_mensual'] = (string) ($entity->get('precio_mensual')->value ?? '0.00');
    $row['publicado'] = ((bool) $entity->get('publicado')->value) ? $this->t('Sí') : $this->t('No');

    $row['participante'] = '';
    try {
      $participanteId = $entity->get('participante_id')->target_id ?? NULL;
      if ($participanteId !== NULL) {
        $participante = \Drupal::entityTypeManager()
          ->getStorage('programa_participante_ei')
          ->load($participanteId);
        if ($participante !== NULL) {
          $row['participante'] = (string) ($participante->label() ?? "id:$participanteId");
        }
        else {
          $row['participante'] = "id:$participanteId";
        }
      }
    }
    catch (\Throwable) {
      // PRESAVE-RESILIENCE-001.
    }

    return $row + parent::buildRow($entity);
  }

}
