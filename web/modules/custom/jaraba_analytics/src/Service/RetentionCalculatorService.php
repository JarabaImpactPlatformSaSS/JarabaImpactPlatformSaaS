<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio de calculo de retencion de usuarios.
 *
 * Calcula metricas de retencion D7, D30 y cohorte para un vertical/tenant.
 */
class RetentionCalculatorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {}

  /**
   * Calcula la retencion para un vertical/tenant a N dias.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param string $tenantId
   *   ID del tenant.
   * @param int $days
   *   Dias de retencion (7, 30, etc.).
   *
   * @return float
   *   Tasa de retencion (0-1).
   */
  public function calculateRetention(string $vertical, string $tenantId, int $days): float {
    try {
      $cutoffDate = strtotime("-{$days} days");
      $periodStart = strtotime("-" . ($days * 2) . " days");

      // Usuarios que se registraron en el periodo.
      $registeredQuery = $this->database->select('users_field_data', 'u')
        ->condition('u.created', $periodStart, '>=')
        ->condition('u.created', $cutoffDate, '<')
        ->condition('u.status', 1);
      $registeredQuery->addExpression('COUNT(DISTINCT u.uid)', 'total');
      $totalRegistered = (int) $registeredQuery->execute()->fetchField();

      if ($totalRegistered === 0) {
        return 0.0;
      }

      // De esos, cuantos tienen actividad despues de $days.
      $retainedQuery = $this->database->select('analytics_event', 'ae')
        ->condition('ae.created', $cutoffDate, '>=');
      $retainedQuery->join('users_field_data', 'u', 'ae.user_id = u.uid');
      $retainedQuery->condition('u.created', $periodStart, '>=')
        ->condition('u.created', $cutoffDate, '<');
      $retainedQuery->addExpression('COUNT(DISTINCT ae.user_id)', 'retained');
      $totalRetained = (int) $retainedQuery->execute()->fetchField();

      return $totalRegistered > 0 ? (float) ($totalRetained / $totalRegistered) : 0.0;
    }
    catch (\Throwable) {
      return 0.0;
    }
  }

  /**
   * Obtiene curva de retencion por cohorte mensual.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param string $cohortMonth
   *   Mes de la cohorte en formato Y-m.
   *
   * @return array
   *   Array con retencion por semana: [week_1 => 0.85, week_2 => 0.70, ...].
   */
  public function getCohortRetention(string $vertical, string $cohortMonth): array {
    $result = [];

    try {
      $monthStart = strtotime($cohortMonth . '-01');
      $monthEnd = strtotime('+1 month', $monthStart);

      // Usuarios del cohorte.
      $cohortQuery = $this->database->select('users_field_data', 'u')
        ->condition('u.created', $monthStart, '>=')
        ->condition('u.created', $monthEnd, '<')
        ->condition('u.status', 1);
      $cohortQuery->addExpression('COUNT(DISTINCT u.uid)', 'total');
      $cohortSize = (int) $cohortQuery->execute()->fetchField();

      if ($cohortSize === 0) {
        return $result;
      }

      // Retencion por semana (hasta 12 semanas).
      for ($week = 1; $week <= 12; $week++) {
        $weekStart = strtotime("+{$week} weeks", $monthStart);
        $weekEnd = strtotime('+1 week', $weekStart);

        if ($weekStart > time()) {
          break;
        }

        $retainedQuery = $this->database->select('analytics_event', 'ae')
          ->condition('ae.created', $weekStart, '>=')
          ->condition('ae.created', $weekEnd, '<');
        $retainedQuery->join('users_field_data', 'u', 'ae.user_id = u.uid');
        $retainedQuery->condition('u.created', $monthStart, '>=')
          ->condition('u.created', $monthEnd, '<');
        $retainedQuery->addExpression('COUNT(DISTINCT ae.user_id)', 'retained');
        $retained = (int) $retainedQuery->execute()->fetchField();

        $result["week_{$week}"] = round($retained / $cohortSize, 4);
      }
    }
    catch (\Throwable) {
      // Return partial results on error.
    }

    return $result;
  }

}
