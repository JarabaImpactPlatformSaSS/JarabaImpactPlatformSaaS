<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para CustomerHealth con badges de categoría.
 *
 * LÓGICA:
 * Muestra tabla admin con tenant, score, categoría coloreada,
 * tendencia y fecha de cálculo. Ordenado por score ascendente
 * para priorizar tenants en riesgo.
 */
class CustomerHealthListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['overall_score'] = $this->t('Score');
    $header['category'] = $this->t('Category');
    $header['trend'] = $this->t('Trend');
    $header['engagement'] = $this->t('Engagement');
    $header['adoption'] = $this->t('Adoption');
    $header['calculated_at'] = $this->t('Calculated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\CustomerHealth $entity */
    $tenant = $entity->get('tenant_id')->entity;
    $category = $entity->getCategory();
    $trend = $entity->get('trend')->value ?? 'stable';

    $category_colors = [
      'healthy' => '#00A9A5',
      'neutral' => '#FFB84D',
      'at_risk' => '#FF8C42',
      'critical' => '#DC3545',
    ];

    $trend_icons = [
      'improving' => '↑',
      'stable' => '→',
      'declining' => '↓',
    ];

    $color = $category_colors[$category] ?? '#6c757d';

    $row['tenant'] = $tenant ? $tenant->label() : $this->t('Unknown');
    $row['overall_score'] = [
      'data' => [
        '#markup' => '<strong>' . $entity->getOverallScore() . '</strong>/100',
      ],
    ];
    $row['category'] = [
      'data' => [
        '#markup' => '<span style="background:' . $color . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . ucfirst(str_replace('_', ' ', $category)) . '</span>',
      ],
    ];
    $row['trend'] = ($trend_icons[$trend] ?? '→') . ' ' . ucfirst($trend);
    $row['engagement'] = (string) $entity->get('engagement_score')->value;
    $row['adoption'] = (string) $entity->get('adoption_score')->value;
    $row['calculated_at'] = $entity->get('calculated_at')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('calculated_at')->value, 'short')
      : '';

    return $row + parent::buildRow($entity);
  }

}
