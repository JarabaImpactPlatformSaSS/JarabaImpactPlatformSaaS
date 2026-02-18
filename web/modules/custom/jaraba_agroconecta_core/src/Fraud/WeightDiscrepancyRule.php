<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Fraud;

use Drupal\jaraba_predictive\Fraud\FraudRuleInterface;
use Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface;

/**
 * Regla que detecta discrepancias de peso en envíos.
 */
class WeightDiscrepancyRule implements FraudRuleInterface {

  public function getName(): string {
    return 'agro_weight_discrepancy';
  }

  public function getWeight(): float {
    return 0.8; // Alta importancia.
  }

  public function evaluate(mixed $subject, array $context = []): int {
    if (!$subject instanceof AgroShipmentInterface) {
      return 0;
    }

    $declaredWeight = (float) $subject->get('weight_value')->value;
    $carrierWeight = (float) ($context['carrier_weight'] ?? $declaredWeight);

    if ($carrierWeight === 0.0) return 0;

    $diff = abs($declaredWeight - $carrierWeight) / $declaredWeight;

    if ($diff > 0.30) return 100; // 30% de diferencia = Fraude Seguro.
    if ($diff > 0.15) return 50;  // 15% de diferencia = Sospechoso.

    return 0;
  }

}
