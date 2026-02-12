<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de reglas de pricing en admin.
 */
class PricingRuleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['metric_name'] = $this->t('Métrica');
    $header['pricing_model'] = $this->t('Modelo');
    $header['unit_price'] = $this->t('Precio Unitario');
    $header['currency'] = $this->t('Moneda');
    $header['status'] = $this->t('Estado');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->get('name')->value ?? '-';
    $row['metric_name'] = $entity->get('metric_name')->value ?? '-';
    $row['pricing_model'] = $entity->get('pricing_model')->value ?? '-';
    $row['unit_price'] = $entity->get('unit_price')->value ?? '0.0000';
    $row['currency'] = $entity->get('currency')->value ?? 'EUR';
    $row['status'] = $entity->get('status')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? $this->t('Global');

    return $row + parent::buildRow($entity);
  }

}
