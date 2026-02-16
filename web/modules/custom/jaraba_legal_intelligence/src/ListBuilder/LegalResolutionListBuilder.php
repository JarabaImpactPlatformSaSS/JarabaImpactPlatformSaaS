<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de resoluciones legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-resolutions.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: titulo
 *   (enlazado), fuente, referencia externa, organo emisor, fecha
 *   de emision, estado legal y tipo de resolucion.
 *
 * RELACIONES:
 * - LegalResolutionListBuilder -> LegalResolution entity (lista)
 * - LegalResolutionListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalResolutionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['source_id'] = $this->t('Fuente');
    $header['external_ref'] = $this->t('Ref. Externa');
    $header['issuing_body'] = $this->t('Organo Emisor');
    $header['date_issued'] = $this->t('Fecha Emision');
    $header['status_legal'] = $this->t('Estado Legal');
    $header['resolution_type'] = $this->t('Tipo Resolucion');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'vigente' => $this->t('Vigente'),
      'derogada' => $this->t('Derogada'),
      'anulada' => $this->t('Anulada'),
      'superada' => $this->t('Superada'),
      'parcialmente_derogada' => $this->t('Parcialmente derogada'),
    ];

    $statusLegal = $entity->get('status_legal')->value;
    $dateIssued = $entity->get('date_issued')->value;

    $row['title'] = $entity->toLink()->toString();
    $row['source_id'] = (string) ($entity->get('source_id')->target_id ?? '-');
    $row['external_ref'] = $entity->get('external_ref')->value ?? '-';
    $row['issuing_body'] = $entity->get('issuing_body')->value ?? '-';
    $row['date_issued'] = $dateIssued ? date('Y-m-d', (int) $dateIssued) : '-';
    $row['status_legal'] = $status_labels[$statusLegal] ?? $statusLegal;
    $row['resolution_type'] = $entity->get('resolution_type')->value ?? '-';
    return $row + parent::buildRow($entity);
  }

}
