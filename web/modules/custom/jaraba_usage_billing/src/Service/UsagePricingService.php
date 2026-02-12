<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_usage_billing\Entity\PricingRule;
use Psr\Log\LoggerInterface;

/**
 * Motor de cálculo de precios por uso.
 *
 * Aplica las reglas de pricing (flat, tiered, per_unit, package)
 * para calcular el coste de consumo de cada métrica.
 */
class UsagePricingService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected object $pricingEngine,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el coste para una métrica y cantidad dados.
   *
   * @param string $metric
   *   Nombre de la métrica.
   * @param float $quantity
   *   Cantidad consumida.
   * @param int|null $tenantId
   *   ID del tenant para reglas específicas, o NULL para globales.
   *
   * @return float
   *   Coste calculado.
   */
  public function calculateCost(string $metric, float $quantity, ?int $tenantId = NULL): float {
    try {
      $rule = $this->findApplicableRule($metric, $tenantId);

      if (!$rule) {
        $this->logger->warning('No se encontró regla de pricing para la métrica @metric (tenant: @tenant).', [
          '@metric' => $metric,
          '@tenant' => $tenantId ?? 'global',
        ]);
        return 0.0;
      }

      $model = $rule->get('pricing_model')->value;
      $unitPrice = (float) $rule->get('unit_price')->value;

      switch ($model) {
        case PricingRule::MODEL_FLAT:
          return $unitPrice;

        case PricingRule::MODEL_PER_UNIT:
          return round($quantity * $unitPrice, 4);

        case PricingRule::MODEL_TIERED:
          return $this->calculateTieredCost($rule, $quantity);

        case PricingRule::MODEL_PACKAGE:
          return $this->calculatePackageCost($rule, $quantity);

        default:
          $this->logger->warning('Modelo de pricing desconocido: @model', [
            '@model' => $model,
          ]);
          return 0.0;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando coste para @metric: @error', [
        '@metric' => $metric,
        '@error' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Obtiene las reglas de pricing aplicables a un tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant, o NULL para solo globales.
   *
   * @return array
   *   Array de entidades PricingRule.
   */
  public function getPricingRules(?int $tenantId = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('pricing_rule');

      // Primero buscar reglas específicas del tenant.
      $rules = [];
      if ($tenantId !== NULL) {
        $query = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('status', PricingRule::STATUS_ACTIVE);
        $ids = $query->execute();

        if (!empty($ids)) {
          $rules = $storage->loadMultiple($ids);
        }
      }

      // Complementar con reglas globales (sin tenant).
      $globalQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->notExists('tenant_id')
        ->condition('status', PricingRule::STATUS_ACTIVE);
      $globalIds = $globalQuery->execute();

      if (!empty($globalIds)) {
        $globalRules = $storage->loadMultiple($globalIds);
        // Las reglas de tenant tienen prioridad.
        $coveredMetrics = array_map(
          fn($rule) => $rule->get('metric_name')->value,
          $rules
        );
        foreach ($globalRules as $globalRule) {
          if (!in_array($globalRule->get('metric_name')->value, $coveredMetrics, TRUE)) {
            $rules[] = $globalRule;
          }
        }
      }

      return $rules;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo reglas de pricing: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Busca la regla de pricing aplicable a una métrica.
   *
   * Prioridad: regla de tenant > regla global.
   *
   * @param string $metric
   *   Nombre de la métrica.
   * @param int|null $tenantId
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_usage_billing\Entity\PricingRule|null
   *   La regla encontrada, o NULL.
   */
  protected function findApplicableRule(string $metric, ?int $tenantId): ?PricingRule {
    try {
      $storage = $this->entityTypeManager->getStorage('pricing_rule');

      // Primero buscar regla específica del tenant.
      if ($tenantId !== NULL) {
        $query = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('metric_name', $metric)
          ->condition('tenant_id', $tenantId)
          ->condition('status', PricingRule::STATUS_ACTIVE)
          ->range(0, 1);
        $ids = $query->execute();

        if (!empty($ids)) {
          return $storage->load(reset($ids));
        }
      }

      // Buscar regla global.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('metric_name', $metric)
        ->notExists('tenant_id')
        ->condition('status', PricingRule::STATUS_ACTIVE)
        ->range(0, 1);
      $ids = $query->execute();

      if (!empty($ids)) {
        return $storage->load(reset($ids));
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando regla de pricing para @metric: @error', [
        '@metric' => $metric,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Calcula el coste con modelo tiered (escalonado).
   *
   * @param \Drupal\jaraba_usage_billing\Entity\PricingRule $rule
   *   Regla de pricing.
   * @param float $quantity
   *   Cantidad consumida.
   *
   * @return float
   *   Coste calculado.
   */
  protected function calculateTieredCost(PricingRule $rule, float $quantity): float {
    $tiers = $rule->getDecodedTiers();
    if (empty($tiers)) {
      return 0.0;
    }

    $totalCost = 0.0;
    $remaining = $quantity;

    foreach ($tiers as $tier) {
      $tierLimit = (float) ($tier['up_to'] ?? PHP_FLOAT_MAX);
      $tierPrice = (float) ($tier['unit_price'] ?? 0);
      $tierFlat = (float) ($tier['flat_price'] ?? 0);
      $previousLimit = (float) ($tier['from'] ?? 0);

      $tierRange = $tierLimit - $previousLimit;
      $applicable = min($remaining, $tierRange);

      if ($applicable <= 0) {
        break;
      }

      $totalCost += $tierFlat + ($applicable * $tierPrice);
      $remaining -= $applicable;

      if ($remaining <= 0) {
        break;
      }
    }

    return round($totalCost, 4);
  }

  /**
   * Calcula el coste con modelo package (paquete).
   *
   * @param \Drupal\jaraba_usage_billing\Entity\PricingRule $rule
   *   Regla de pricing.
   * @param float $quantity
   *   Cantidad consumida.
   *
   * @return float
   *   Coste calculado.
   */
  protected function calculatePackageCost(PricingRule $rule, float $quantity): float {
    $tiers = $rule->getDecodedTiers();
    if (empty($tiers)) {
      return 0.0;
    }

    // El modelo package cobra por paquetes enteros.
    $packageSize = (float) ($tiers[0]['package_size'] ?? 1);
    $packagePrice = (float) ($tiers[0]['package_price'] ?? 0);

    if ($packageSize <= 0) {
      return 0.0;
    }

    $packages = ceil($quantity / $packageSize);
    return round($packages * $packagePrice, 4);
  }

}
