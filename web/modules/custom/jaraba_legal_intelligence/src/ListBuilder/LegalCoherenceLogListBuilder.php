<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de logs de coherencia juridica en admin.
 *
 * ESTRUCTURA: Genera la tabla en /admin/content/legal-coherence-logs.
 * Audit trail para cumplimiento EU AI Act Art. 12.
 */
class LegalCoherenceLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['created'] = $this->t('Fecha');
    $header['intent_type'] = $this->t('Intencion');
    $header['coherence_score'] = $this->t('Score');
    $header['blocked'] = $this->t('Bloqueada');
    $header['retries_needed'] = $this->t('Reintentos');
    $header['vertical'] = $this->t('Vertical');
    $header['trace_id'] = $this->t('Trace ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $intentLabels = [
      'legal' => $this->t('Legal'),
      'non_legal' => $this->t('No Legal'),
      'ambiguous' => $this->t('Ambigua'),
    ];

    $created = $entity->get('created')->value;
    $intentType = $entity->get('intent_type')->value;
    $coherenceScore = $entity->get('coherence_score')->value;
    $blocked = (bool) $entity->get('blocked')->value;

    $row['created'] = $created ? date('Y-m-d H:i', (int) $created) : '-';
    $row['intent_type'] = $intentLabels[$intentType] ?? ($intentType ?: '-');
    $row['coherence_score'] = $coherenceScore !== NULL ? number_format((float) $coherenceScore, 3) : '-';
    $row['blocked'] = $blocked ? $this->t('Si') : $this->t('No');
    $row['retries_needed'] = (string) ($entity->get('retries_needed')->value ?? 0);
    $row['vertical'] = $entity->get('vertical')->value ?: '-';
    $row['trace_id'] = $entity->get('trace_id')->value
      ? mb_substr($entity->get('trace_id')->value, 0, 8) . '...'
      : '-';
    return $row + parent::buildRow($entity);
  }

}
