<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\jaraba_predictive\Fraud\FraudRuleInterface;
use Psr\Log\LoggerInterface;

/**
 * Motor Unificado de Inteligencia de Fraude.
 *
 * ESTRUCTURA:
 *   Orquestador que aplica múltiples reglas de detección a un evento.
 *   Utiliza el patrón Strategy mediante inyección de servicios tagueados.
 *
 * F192 — Unified Fraud Engine (SOC2).
 */
class FraudEngineService {

  /**
   * @var \Drupal\jaraba_predictive\Fraud\FraudRuleInterface[]
   */
  protected array $rules = [];

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra una nueva regla de fraude.
   */
  public function addRule(FraudRuleInterface $rule): void {
    $this->rules[] = $rule;
  }

  /**
   * Analiza un sujeto (Pedido, Usuario, Factura) en busca de fraude.
   *
   * @return array
   *   Resultados del análisis con score global.
   */
  public function analyze(mixed $subject, array $context = []): array {
    $totalScore = 0.0;
    $findings = [];

    foreach ($this->rules as $rule) {
      $score = $rule->evaluate($subject, $context);
      if ($score > 0) {
        $contribution = $score * $rule->getWeight();
        $totalScore += $contribution;
        $findings[] = [
          'rule' => $rule->getName(),
          'score' => $score,
          'contribution' => $contribution,
        ];
      }
    }

    $finalScore = min(100, (int) round($totalScore));

    if ($finalScore > 50) {
      $this->logger->warning('FRAUDE DETECTADO: Score @score. Findings: @findings', [
        '@score' => $finalScore,
        '@findings' => json_encode($findings),
      ]);
    }

    return [
      'is_fraudulent' => $finalScore >= 75,
      'risk_level' => $this->getRiskLevel($finalScore),
      'score' => $finalScore,
      'findings' => $findings,
    ];
  }

  protected function getRiskLevel(int $score): string {
    if ($score >= 75) return 'critical';
    if ($score >= 50) return 'high';
    if ($score >= 25) return 'medium';
    return 'low';
  }

}
