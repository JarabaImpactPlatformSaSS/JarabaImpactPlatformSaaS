<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad Hypothesis.
 */
class HypothesisListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['statement'] = $this->t('Hipotesis');
    $header['hypothesis_type'] = $this->t('Tipo');
    $header['bmc_block'] = $this->t('Bloque BMC');
    $header['importance_score'] = $this->t('Importancia');
    $header['evidence_score'] = $this->t('Evidencia');
    $header['validation_status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_copilot_v2\Entity\Hypothesis $entity */
    $statement = $entity->get('statement')->value ?? '-';
    $row['statement'] = [
      '#markup' => mb_substr($statement, 0, 80) . (mb_strlen($statement) > 80 ? '...' : ''),
    ];

    $type = $entity->get('hypothesis_type')->value ?? '-';
    $type_classes = [
      'DESIRABILITY' => 'badge--info',
      'FEASIBILITY' => 'badge--warning',
      'VIABILITY' => 'badge--success',
    ];
    $row['hypothesis_type'] = [
      '#markup' => '<span class="badge ' . ($type_classes[$type] ?? 'badge--default') . '">' . $type . '</span>',
    ];

    $row['bmc_block'] = $entity->get('bmc_block')->value ?? '-';
    $row['importance_score'] = $entity->get('importance_score')->value ?? '0';
    $row['evidence_score'] = $entity->get('evidence_score')->value ?? '0';

    $status = $entity->get('validation_status')->value ?? 'PENDING';
    $status_classes = [
      'PENDING' => 'badge--default',
      'IN_PROGRESS' => 'badge--info',
      'VALIDATED' => 'badge--success',
      'INVALIDATED' => 'badge--error',
      'INCONCLUSIVE' => 'badge--warning',
    ];
    $row['validation_status'] = [
      '#markup' => '<span class="badge ' . ($status_classes[$status] ?? 'badge--default') . '">' . $status . '</span>',
    ];

    return $row + parent::buildRow($entity);
  }

}
