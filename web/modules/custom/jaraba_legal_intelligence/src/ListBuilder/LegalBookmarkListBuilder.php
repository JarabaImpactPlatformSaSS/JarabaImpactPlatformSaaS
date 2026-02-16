<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de marcadores legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-bookmarks.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: usuario,
 *   resolucion (referencia externa de la entidad referenciada),
 *   carpeta y fecha de creacion.
 *
 * RELACIONES:
 * - LegalBookmarkListBuilder -> LegalBookmark entity (lista)
 * - LegalBookmarkListBuilder -> LegalResolution entity (referencia external_ref)
 * - LegalBookmarkListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalBookmarkListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user_id'] = $this->t('Usuario');
    $header['resolution'] = $this->t('Resolucion');
    $header['folder'] = $this->t('Carpeta');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $userId = $entity->get('user_id')->entity;
    $resolution = $entity->get('resolution_id')->entity;
    $created = $entity->get('created')->value;

    $row['user_id'] = $userId ? $userId->getDisplayName() : '-';
    $row['resolution'] = $resolution ? ($resolution->get('external_ref')->value ?? '-') : '-';
    $row['folder'] = $entity->get('folder')->value ?? '-';
    $row['created'] = $created ? date('Y-m-d H:i', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
