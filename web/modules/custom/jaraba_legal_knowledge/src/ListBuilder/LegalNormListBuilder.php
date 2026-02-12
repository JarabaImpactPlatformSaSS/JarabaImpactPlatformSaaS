<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de normas legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-norms.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: titulo,
 *   tipo de norma, BOE ID, fecha de publicacion, estado, ambito
 *   y numero de chunks.
 *
 * RELACIONES:
 * - LegalNormListBuilder -> LegalNorm entity (lista)
 * - LegalNormListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalNormListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['norm_type'] = $this->t('Tipo');
    $header['boe_id'] = $this->t('BOE ID');
    $header['publication_date'] = $this->t('Fecha Publicacion');
    $header['status'] = $this->t('Estado');
    $header['scope'] = $this->t('Ambito');
    $header['chunk_count'] = $this->t('Chunks');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $norm_type_labels = [
      'ley_organica' => $this->t('Ley Organica'),
      'ley' => $this->t('Ley'),
      'real_decreto_ley' => $this->t('Real Decreto-ley'),
      'real_decreto' => $this->t('Real Decreto'),
      'orden' => $this->t('Orden'),
      'resolucion' => $this->t('Resolucion'),
      'directiva_ue' => $this->t('Directiva UE'),
      'reglamento_ue' => $this->t('Reglamento UE'),
    ];

    $status_labels = [
      'vigente' => $this->t('Vigente'),
      'derogada' => $this->t('Derogada'),
      'modificada' => $this->t('Modificada'),
      'pendiente' => $this->t('Pendiente'),
    ];

    $scope_labels = [
      'nacional' => $this->t('Nacional'),
      'autonomico' => $this->t('Autonomico'),
      'local' => $this->t('Local'),
      'europeo' => $this->t('Europeo'),
    ];

    $normType = $entity->get('norm_type')->value;
    $status = $entity->get('status')->value;
    $scope = $entity->get('scope')->value;
    $publicationDate = $entity->get('publication_date')->value;

    $row['title'] = $entity->get('title')->value ?? '-';
    $row['norm_type'] = $norm_type_labels[$normType] ?? $normType;
    $row['boe_id'] = $entity->get('boe_id')->value ?? '-';
    $row['publication_date'] = $publicationDate ? date('Y-m-d', (int) $publicationDate) : '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['scope'] = $scope_labels[$scope] ?? $scope;
    $row['chunk_count'] = (string) ($entity->get('chunk_count')->value ?? 0);
    return $row + parent::buildRow($entity);
  }

}
