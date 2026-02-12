<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Motor de cálculo de precios basado en PricingRule.
 *
 * PROPÓSITO:
 * Calcula costes de uso reemplazando los precios hardcodeados de
 * TenantMeteringService::UNIT_PRICES con reglas dinámicas por plan.
 *
 * LÓGICA:
 * Cascada de resolución:
 * 1. PricingRule activa para (plan_id + metric_type) → si existe, se usa
 * 2. PricingRule activa para (NULL plan + metric_type) → regla global
 * 3. Fallback a TenantMeteringService::UNIT_PRICES (hardcoded)
 *
 * Modelos de cálculo:
 * - flat: (quantity - included) × unit_price
 * - tiered: cada tramo se cobra a su precio (graduated)
 * - volume: el tramo total determina el precio de TODAS las unidades
 * - package: ceil(quantity / package_size) × package_price
 *
 * DIRECTRICES:
 * - Siempre devuelve 0 si quantity <= included_quantity
 * - Los precios se redondean a 6 decimales durante el cálculo
 * - El resultado final se redondea a 2 decimales
 */
class PricingRuleEngine
{

  /**
   * Precios por defecto (fallback cuando no hay PricingRule).
   * Sincronizado con TenantMeteringService::UNIT_PRICES.
   */
  protected const DEFAULT_PRICES = [
    'api_calls' => 0.0001,
    'ai_tokens' => 0.00002,
    'storage_mb' => 0.001,
    'orders' => 0.50,
    'products' => 0.10,
    'customers' => 0.05,
    'emails_sent' => 0.001,
    'bandwidth_gb' => 0.05,
  ];

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Calcula el coste para una cantidad de uso de una métrica.
   *
   * @param string $metricType
   *   Tipo de métrica (api_calls, ai_tokens, etc.).
   * @param float $quantity
   *   Cantidad total de uso en el período.
   * @param string|null $planId
   *   ID del plan SaaS del tenant (NULL para usar regla global).
   *
   * @return array
   *   Array con keys: cost, unit_price, included_quantity, billable_quantity,
   *   pricing_model, rule_id (NULL si es fallback).
   */
  public function calculateCost(string $metricType, float $quantity, ?string $planId = NULL): array
  {
    $rule = $this->resolveRule($metricType, $planId);

    if ($rule === NULL) {
      // Fallback a precios hardcodeados.
      $unitPrice = self::DEFAULT_PRICES[$metricType] ?? 0;
      return [
        'cost' => round($quantity * $unitPrice, 2),
        'unit_price' => $unitPrice,
        'included_quantity' => 0,
        'billable_quantity' => $quantity,
        'pricing_model' => 'flat',
        'rule_id' => NULL,
      ];
    }

    $includedQty = (float) ($rule->get('included_quantity')->value ?? 0);
    $model = $rule->get('pricing_model')->value ?? 'flat';
    $unitPrice = (float) ($rule->get('unit_price')->value ?? 0);

    // Cantidad facturable (descontando lo incluido).
    $billable = max(0, $quantity - $includedQty);

    if ($billable <= 0) {
      return [
        'cost' => 0,
        'unit_price' => $unitPrice,
        'included_quantity' => $includedQty,
        'billable_quantity' => 0,
        'pricing_model' => $model,
        'rule_id' => (int) $rule->id(),
      ];
    }

    $cost = match ($model) {
      'tiered' => $this->calculateTiered($billable, $rule->getDecodedTiers()),
      'volume' => $this->calculateVolume($billable, $rule->getDecodedTiers()),
      'package' => $this->calculatePackage($billable, $rule->getDecodedTiers()),
      default => round($billable * $unitPrice, 6),
    };

    return [
      'cost' => round($cost, 2),
      'unit_price' => $unitPrice,
      'included_quantity' => $includedQty,
      'billable_quantity' => $billable,
      'pricing_model' => $model,
      'rule_id' => (int) $rule->id(),
    ];
  }

  /**
   * Obtiene todas las reglas activas para un plan (incluidas las globales).
   *
   * @param string|null $planId
   *   ID del plan SaaS.
   *
   * @return array
   *   Array indexado por metric_type con datos de la regla.
   */
  public function getRulesForPlan(?string $planId = NULL): array
  {
    $storage = $this->entityTypeManager->getStorage('pricing_rule');
    $rules = [];

    // Primero cargar reglas globales (plan_id = NULL).
    $globalQuery = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_active', TRUE)
      ->notExists('plan_id');
    $globalIds = $globalQuery->execute();

    if (!empty($globalIds)) {
      foreach ($storage->loadMultiple($globalIds) as $rule) {
        $metric = $rule->get('metric_type')->value;
        $rules[$metric] = $rule;
      }
    }

    // Luego sobrescribir con reglas específicas del plan.
    if ($planId !== NULL) {
      $planQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->condition('plan_id', $planId);
      $planIds = $planQuery->execute();

      if (!empty($planIds)) {
        foreach ($storage->loadMultiple($planIds) as $rule) {
          $metric = $rule->get('metric_type')->value;
          $rules[$metric] = $rule;
        }
      }
    }

    return $rules;
  }

  /**
   * Calcula coste completo de un tenant para un período.
   *
   * @param array $usageMetrics
   *   Array de [metric_type => total_quantity].
   * @param string|null $planId
   *   ID del plan SaaS del tenant.
   *
   * @return array
   *   Array con keys: line_items, subtotal, tax, total, currency.
   */
  public function calculateBill(array $usageMetrics, ?string $planId = NULL): array
  {
    $lineItems = [];
    $subtotal = 0;

    foreach ($usageMetrics as $metric => $quantity) {
      if ($quantity <= 0) {
        continue;
      }

      $result = $this->calculateCost($metric, (float) $quantity, $planId);

      if ($result['cost'] > 0) {
        $lineItems[] = [
          'metric' => $metric,
          'description' => $this->getMetricLabel($metric),
          'quantity' => $quantity,
          'billable_quantity' => $result['billable_quantity'],
          'included_quantity' => $result['included_quantity'],
          'unit_price' => $result['unit_price'],
          'pricing_model' => $result['pricing_model'],
          'amount' => $result['cost'],
        ];
        $subtotal += $result['cost'];
      }
    }

    $tax = round($subtotal * 0.21, 2);

    return [
      'line_items' => $lineItems,
      'subtotal' => round($subtotal, 2),
      'tax' => $tax,
      'total' => round($subtotal + $tax, 2),
      'currency' => 'EUR',
    ];
  }

  /**
   * Resuelve la PricingRule aplicable (plan > global > NULL).
   */
  protected function resolveRule(string $metricType, ?string $planId): ?object
  {
    $storage = $this->entityTypeManager->getStorage('pricing_rule');

    // 1. Buscar regla específica del plan.
    if ($planId !== NULL) {
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->condition('metric_type', $metricType)
        ->condition('plan_id', $planId)
        ->range(0, 1);
      $ids = $query->execute();

      if (!empty($ids)) {
        return $storage->load(reset($ids));
      }
    }

    // 2. Buscar regla global (sin plan).
    $globalQuery = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_active', TRUE)
      ->condition('metric_type', $metricType)
      ->notExists('plan_id')
      ->range(0, 1);
    $globalIds = $globalQuery->execute();

    if (!empty($globalIds)) {
      return $storage->load(reset($globalIds));
    }

    // 3. No hay regla → fallback.
    return NULL;
  }

  /**
   * Cálculo escalonado (graduated): cada tramo a su precio.
   */
  protected function calculateTiered(float $quantity, array $tiers): float
  {
    $cost = 0;
    $remaining = $quantity;

    foreach ($tiers as $tier) {
      $from = (float) ($tier['from'] ?? 0);
      $to = (float) ($tier['to'] ?? PHP_FLOAT_MAX);
      $price = (float) ($tier['price'] ?? 0);

      $tierSize = $to - $from;
      if ($tierSize <= 0) {
        continue;
      }

      $unitsInTier = min($remaining, $tierSize);
      if ($unitsInTier <= 0) {
        break;
      }

      $cost += round($unitsInTier * $price, 6);
      $remaining -= $unitsInTier;
    }

    return $cost;
  }

  /**
   * Cálculo por volumen (all-units): el tramo aplica a TODAS las unidades.
   */
  protected function calculateVolume(float $quantity, array $tiers): float
  {
    $applicablePrice = 0;

    foreach ($tiers as $tier) {
      $from = (float) ($tier['from'] ?? 0);
      $to = (float) ($tier['to'] ?? PHP_FLOAT_MAX);
      $price = (float) ($tier['price'] ?? 0);

      if ($quantity >= $from && $quantity <= $to) {
        $applicablePrice = $price;
        break;
      }
    }

    return round($quantity * $applicablePrice, 6);
  }

  /**
   * Cálculo por paquete: bloques de N unidades a precio fijo.
   */
  protected function calculatePackage(float $quantity, array $tiers): float
  {
    if (empty($tiers)) {
      return 0;
    }

    // Primer tier define el paquete: to = tamaño del bloque, price = precio del bloque.
    $packageSize = (float) ($tiers[0]['to'] ?? 1);
    $packagePrice = (float) ($tiers[0]['price'] ?? 0);

    if ($packageSize <= 0) {
      return 0;
    }

    $packages = ceil($quantity / $packageSize);
    return round($packages * $packagePrice, 6);
  }

  /**
   * Etiqueta traducible para una métrica.
   */
  protected function getMetricLabel(string $metric): string
  {
    $labels = [
      'api_calls' => t('Llamadas API'),
      'ai_tokens' => t('Tokens IA'),
      'storage_mb' => t('Almacenamiento (MB)'),
      'orders' => t('Pedidos procesados'),
      'products' => t('Productos activos'),
      'customers' => t('Clientes'),
      'emails_sent' => t('Emails enviados'),
      'bandwidth_gb' => t('Ancho de banda (GB)'),
    ];

    return (string) ($labels[$metric] ?? ucfirst(str_replace('_', ' ', $metric)));
  }

}
