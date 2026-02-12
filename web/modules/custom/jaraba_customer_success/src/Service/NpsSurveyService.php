<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Sistema de encuestas NPS in-app con tracking longitudinal.
 *
 * PROPÓSITO:
 * Gestiona encuestas Net Promoter Score: envío, recolección,
 * cálculo del NPS score y análisis de tendencia.
 *
 * LÓGICA:
 * - NPS = % promotores (9-10) - % detractores (0-6).
 * - Rango: -100 a +100.
 * - Pasivos (7-8) no se cuentan en el cálculo.
 * - Cooldown configurable entre encuestas al mismo tenant.
 * - Almacena respuestas en State API con historial.
 */
class NpsSurveyService {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Verifica si un tenant puede recibir una encuesta NPS.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return bool
   *   TRUE si ha pasado el cooldown desde la última encuesta.
   */
  public function canSendSurvey(string $tenant_id): bool {
    $cooldown = $this->configFactory->get('jaraba_customer_success.settings')
      ->get('nps_survey_cooldown') ?? 90;

    $last_sent = $this->state->get("jaraba_cs.nps_last_sent.$tenant_id", 0);
    $cooldown_seconds = $cooldown * 86400;

    return (\Drupal::time()->getRequestTime() - $last_sent) >= $cooldown_seconds;
  }

  /**
   * Registra el envío de una encuesta NPS.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   */
  public function markSurveySent(string $tenant_id): void {
    $this->state->set(
      "jaraba_cs.nps_last_sent.$tenant_id",
      \Drupal::time()->getRequestTime()
    );
  }

  /**
   * Recolecta una respuesta NPS.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   * @param int $score
   *   Puntuación NPS (0-10).
   * @param string $feedback
   *   Comentario opcional del respondiente.
   */
  public function collectResponse(string $tenant_id, int $score, string $feedback = ''): void {
    $score = max(0, min(10, $score));

    $responses = $this->state->get("jaraba_cs.nps_responses.$tenant_id", []);
    $responses[] = [
      'score' => $score,
      'feedback' => $feedback,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];

    // Mantener solo las últimas 100 respuestas por tenant.
    if (count($responses) > 100) {
      $responses = array_slice($responses, -100);
    }

    $this->state->set("jaraba_cs.nps_responses.$tenant_id", $responses);
  }

  /**
   * Calcula el NPS score de un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return int|null
   *   NPS score (-100 a +100) o NULL si no hay respuestas.
   */
  public function getScore(string $tenant_id): ?int {
    $responses = $this->state->get("jaraba_cs.nps_responses.$tenant_id", []);
    if (empty($responses)) {
      return NULL;
    }

    $total = count($responses);
    $promoters = 0;
    $detractors = 0;

    foreach ($responses as $response) {
      $s = $response['score'] ?? 0;
      if ($s >= 9) {
        $promoters++;
      }
      elseif ($s <= 6) {
        $detractors++;
      }
    }

    return (int) round((($promoters - $detractors) / $total) * 100);
  }

  /**
   * Obtiene la tendencia del NPS en los últimos N períodos.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   * @param int $periods
   *   Número de períodos mensuales.
   *
   * @return array
   *   Array de scores por período [{month: 'YYYY-MM', score: N}, ...].
   */
  public function getTrend(string $tenant_id, int $periods = 6): array {
    $responses = $this->state->get("jaraba_cs.nps_responses.$tenant_id", []);
    if (empty($responses)) {
      return [];
    }

    // Agrupar respuestas por mes.
    $by_month = [];
    foreach ($responses as $response) {
      $month = date('Y-m', $response['timestamp'] ?? 0);
      $by_month[$month][] = $response['score'] ?? 0;
    }

    // Calcular NPS por mes.
    $trend = [];
    foreach (array_slice($by_month, -$periods, $periods, TRUE) as $month => $scores) {
      $total = count($scores);
      $promoters = count(array_filter($scores, fn($s) => $s >= 9));
      $detractors = count(array_filter($scores, fn($s) => $s <= 6));
      $trend[] = [
        'month' => $month,
        'score' => (int) round((($promoters - $detractors) / $total) * 100),
        'responses' => $total,
      ];
    }

    return $trend;
  }

  /**
   * Calcula el satisfaction score normalizado (0-100) para Health Score.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return int
   *   Puntuación de satisfacción (0-100).
   */
  public function getSatisfactionScore(string $tenant_id): int {
    $nps = $this->getScore($tenant_id);
    if ($nps === NULL) {
      return 50;
    }

    // Normalizar NPS (-100 a +100) a escala 0-100.
    return (int) max(0, min(100, ($nps + 100) / 2));
  }

}
