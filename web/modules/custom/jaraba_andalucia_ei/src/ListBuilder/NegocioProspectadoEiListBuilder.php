<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for NegocioProspectadoEi entities.
 */
class NegocioProspectadoEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildHeader(): array {
    $header = [];
    $header['nombre_negocio'] = $this->t('Nombre del Negocio');
    $header['sector'] = $this->t('Sector');
    $header['provincia'] = $this->t('Provincia');
    $header['clasificacion_urgencia'] = $this->t('Urgencia');
    $header['estado_embudo'] = $this->t('Estado Embudo');
    $header['participante_asignado'] = $this->t('Participante Asignado');
    $header['convertido_a_pago'] = $this->t('Convertido');
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
    $row['provincia'] = (string) ($entity->get('provincia')->value ?? '');
    $row['clasificacion_urgencia'] = (string) ($entity->get('clasificacion_urgencia')->value ?? '');
    $row['estado_embudo'] = (string) ($entity->get('estado_embudo')->value ?? '');

    $row['participante_asignado'] = '';
    try {
      $participanteId = $entity->get('participante_asignado')->target_id ?? NULL;
      if ($participanteId !== NULL) {
        $participante = \Drupal::entityTypeManager()
          ->getStorage('programa_participante_ei')
          ->load($participanteId);
        if ($participante !== NULL) {
          // LABEL-NULLSAFE-001: label() puede devolver NULL.
          $row['participante_asignado'] = (string) ($participante->label() ?? "id:$participanteId");
        }
        else {
          $row['participante_asignado'] = "id:$participanteId";
        }
      }
    }
    catch (\Throwable) {
      // PRESAVE-RESILIENCE-001.
    }

    $row['convertido_a_pago'] = ((bool) ($entity->get('convertido_a_pago')->value ?? FALSE))
      ? (string) $this->t('Sí')
      : (string) $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
