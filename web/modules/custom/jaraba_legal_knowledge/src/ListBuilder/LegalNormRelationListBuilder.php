<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\jaraba_legal_knowledge\Entity\LegalNormRelation;

/**
 * Listado de relaciones entre normas legales en admin.
 *
 * ESTRUCTURA: Genera la tabla en /admin/content/legal-norm-relations.
 *
 * RELACIONES:
 * - LegalNormRelationListBuilder -> LegalNormRelation (lista)
 * - LegalNormRelationListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalNormRelationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['source_norm'] = $this->t('Norma Origen');
    $header['target_norm'] = $this->t('Norma Destino');
    $header['relation_type'] = $this->t('Tipo de Relacion');
    $header['effective_date'] = $this->t('Fecha Efectiva');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $relationTypes = LegalNormRelation::RELATION_TYPES;

    $sourceNorm = $entity->get('source_norm_id')->entity;
    $targetNorm = $entity->get('target_norm_id')->entity;
    $relationType = $entity->get('relation_type')->value;
    $effectiveDate = $entity->get('effective_date')->value;

    $row['source_norm'] = $sourceNorm ? ($sourceNorm->label() ?? (string) $sourceNorm->id()) : '-';
    $row['target_norm'] = $targetNorm ? ($targetNorm->label() ?? (string) $targetNorm->id()) : '-';
    $row['relation_type'] = $relationTypes[$relationType] ?? $relationType;
    $row['effective_date'] = $effectiveDate ? date('Y-m-d', (int) $effectiveDate) : '-';
    return $row + parent::buildRow($entity);
  }

}
