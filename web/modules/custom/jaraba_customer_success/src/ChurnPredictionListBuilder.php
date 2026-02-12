<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ChurnPrediction con badges de riesgo.
 *
 * LÓGICA:
 * Muestra tabla con tenant, probabilidad, nivel de riesgo coloreado,
 * fecha predicha de churn, confianza y versión del modelo.
 */
class ChurnPredictionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['probability'] = $this->t('Probability');
    $header['risk_level'] = $this->t('Risk Level');
    $header['predicted_date'] = $this->t('Predicted Date');
    $header['confidence'] = $this->t('Confidence');
    $header['model'] = $this->t('Model');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\ChurnPrediction $entity */
    $tenant = $entity->get('tenant_id')->entity;
    $risk = $entity->getRiskLevel();

    $risk_colors = [
      'low' => '#00A9A5',
      'medium' => '#FFB84D',
      'high' => '#FF8C42',
      'critical' => '#DC3545',
    ];
    $color = $risk_colors[$risk] ?? '#6c757d';

    $row['tenant'] = $tenant ? $tenant->label() : $this->t('Unknown');
    $row['probability'] = [
      'data' => [
        '#markup' => '<strong>' . round($entity->getProbability() * 100) . '%</strong>',
      ],
    ];
    $row['risk_level'] = [
      'data' => [
        '#markup' => '<span style="background:' . $color . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst($risk) . '</span>',
      ],
    ];
    $row['predicted_date'] = $entity->get('predicted_churn_date')->value ?? '—';
    $row['confidence'] = round((float) $entity->get('confidence')->value * 100) . '%';
    $row['model'] = $entity->get('model_version')->value ?? '—';
    $row['created'] = $entity->get('created')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('created')->value, 'short')
      : '';

    return $row + parent::buildRow($entity);
  }

}
