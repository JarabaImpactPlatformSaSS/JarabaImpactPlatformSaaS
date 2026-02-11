<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de reglas de precios con badges de estado y modelo.
 *
 * PROPÓSITO:
 * Renderiza la tabla administrativa de PricingRule en /admin/structure/pricing-rules.
 *
 * LÓGICA:
 * Muestra: nombre, plan, métrica, modelo, precio unitario, incluido, estado (badge color).
 */
class PricingRuleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['plan'] = $this->t('Plan');
    $header['metric'] = $this->t('Métrica');
    $header['model'] = $this->t('Modelo');
    $header['unit_price'] = $this->t('Precio Unit.');
    $header['included'] = $this->t('Incluido');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\PricingRule $entity */

    // Nombre del plan asociado.
    $plan = $entity->get('plan_id')->entity;
    $planName = $plan ? $plan->label() : $this->t('(Global)');

    // Badge de modelo con color.
    $modelColors = [
      'flat' => '#00A9A5',
      'tiered' => '#233D63',
      'volume' => '#FF8C42',
      'package' => '#6C757D',
    ];
    $model = $entity->get('pricing_model')->value ?? 'flat';
    $modelColor = $modelColors[$model] ?? '#6C757D';

    // Badge de estado.
    $isActive = (bool) $entity->get('is_active')->value;
    $statusColor = $isActive ? '#00A9A5' : '#DC3545';
    $statusLabel = $isActive ? $this->t('Activa') : $this->t('Inactiva');

    $row['name'] = $entity->label();
    $row['plan'] = $planName;
    $row['metric'] = $entity->get('metric_type')->value ?? '';
    $row['model'] = [
      'data' => [
        '#markup' => '<span style="background:' . $modelColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $model . '</span>',
      ],
    ];
    $row['unit_price'] = '€' . number_format((float) ($entity->get('unit_price')->value ?? 0), 5, ',', '.');
    $row['included'] = number_format((float) ($entity->get('included_quantity')->value ?? 0), 0, ',', '.');
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
