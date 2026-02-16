<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de citas legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-citations.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: resolucion
 *   (referencia externa), formato de cita, insertado por y fecha
 *   de creacion.
 *
 * RELACIONES:
 * - LegalCitationListBuilder -> LegalCitation entity (lista)
 * - LegalCitationListBuilder -> LegalResolution entity (referencia external_ref)
 * - LegalCitationListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalCitationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['resolution'] = $this->t('Resolucion');
    $header['citation_format'] = $this->t('Formato Cita');
    $header['inserted_by'] = $this->t('Insertado Por');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $format_labels = [
      'apa' => $this->t('APA'),
      'chicago' => $this->t('Chicago'),
      'legal_spanish' => $this->t('Legal Espanol'),
      'eu_citation' => $this->t('Cita UE'),
      'bluebook' => $this->t('Bluebook'),
    ];

    $resolution = $entity->get('resolution_id')->entity;
    $insertedBy = $entity->get('inserted_by')->entity;
    $citationFormat = $entity->get('citation_format')->value;
    $created = $entity->get('created')->value;

    $row['resolution'] = $resolution ? ($resolution->get('external_ref')->value ?? '-') : '-';
    $row['citation_format'] = $format_labels[$citationFormat] ?? $citationFormat;
    $row['inserted_by'] = $insertedBy ? $insertedBy->getDisplayName() : '-';
    $row['created'] = $created ? date('Y-m-d H:i', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
