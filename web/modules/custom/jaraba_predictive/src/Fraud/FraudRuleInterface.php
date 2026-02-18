<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Fraud;

/**
 * Interfaz para Reglas de Detección de Fraude.
 *
 * Cada vertical (Agro, LMS, Billing) implementará sus propias reglas.
 */
interface FraudRuleInterface {

  /**
   * Nombre único de la regla.
   */
  public function getName(): string;

  /**
   * Evalúa un objeto/contexto y devuelve un score de sospecha (0-100).
   */
  public function evaluate(mixed $subject, array $context = []): int;

  /**
   * Define el peso de esta regla en el cálculo global (0.0 - 1.0).
   */
  public function getWeight(): float;

}
