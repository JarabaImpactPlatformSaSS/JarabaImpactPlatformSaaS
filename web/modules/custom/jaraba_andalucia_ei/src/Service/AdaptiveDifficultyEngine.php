<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Motor de dificultad adaptativa para participantes Andalucía +ei.
 *
 * Analiza patrones de engagement y ajusta:
 * - Frecuencia de nudges (más activo para participantes en riesgo)
 * - Recomendaciones de cursos (ajustadas a nivel real)
 * - Duración sugerida de sesiones IA
 */
class AdaptiveDifficultyEngine {

  /**
   * Engagement levels based on activity patterns.
   */
  const ENGAGEMENT_HIGH = 'high';
  const ENGAGEMENT_MEDIUM = 'medium';
  const ENGAGEMENT_LOW = 'low';
  const ENGAGEMENT_AT_RISK = 'at_risk';

  /**
   * Constructs an AdaptiveDifficultyEngine.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Evaluates the engagement level of a participant.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array
   *   Engagement data: level, score, nudge_frequency, session_duration, recommendations.
   */
  public function evaluate(ProgramaParticipanteEiInterface $participante): array {
    $score = $this->calculateEngagementScore($participante);
    $level = $this->scoreToLevel($score);

    return [
      'level' => $level,
      'score' => $score,
      'nudge_frequency' => $this->getNudgeFrequency($level),
      'session_duration' => $this->getSuggestedSessionDuration($level, $participante),
      'course_difficulty' => $this->getCourseDifficulty($participante),
      'recommendations' => $this->getRecommendations($level, $participante),
    ];
  }

  /**
   * Calculates engagement score (0-100).
   *
   * Factors:
   * - Recency of activity (40%)
   * - Hours progress vs. time enrolled (30%)
   * - Diversity of activities (20%)
   * - Document completeness (10%)
   */
  protected function calculateEngagementScore(ProgramaParticipanteEiInterface $participante): float {
    $score = 0.0;

    // Recency: days since last change (40 pts max).
    $changed = (int) ($participante->get('changed')->value ?? 0);
    $created = (int) ($participante->get('created')->value ?? 0);
    if ($changed) {
      $daysSinceActivity = ($this->time->getRequestTime() - $changed) / 86400;
      $recencyScore = match (TRUE) {
        $daysSinceActivity <= 1 => 40,
        $daysSinceActivity <= 3 => 35,
        $daysSinceActivity <= 7 => 25,
        $daysSinceActivity <= 14 => 15,
        $daysSinceActivity <= 30 => 5,
        default => 0,
      };
      $score += $recencyScore;
    }

    // Progress vs. time (30 pts max).
    if ($created) {
      $daysEnrolled = max(1, ($this->time->getRequestTime() - $created) / 86400);
      $expectedHoursPerDay = 60 / max(1, $daysEnrolled); // 60h total expected
      $actualHours = $participante->getTotalHorasOrientacion()
        + (float) ($participante->get('horas_formacion')->value ?? 0);
      $actualPerDay = $actualHours / $daysEnrolled;

      $progressRatio = min(2.0, $actualPerDay / max(0.1, $expectedHoursPerDay));
      $score += min(30, $progressRatio * 15);
    }

    // Diversity of activities (20 pts max).
    $activities = 0;
    if ($participante->getHorasMentoriaIa() > 0) {
      $activities++;
    }
    if ($participante->getHorasMentoriaHumana() > 0) {
      $activities++;
    }
    if ((float) ($participante->get('horas_orientacion_ind')->value ?? 0) > 0) {
      $activities++;
    }
    if ((float) ($participante->get('horas_orientacion_grup')->value ?? 0) > 0) {
      $activities++;
    }
    if ((float) ($participante->get('horas_formacion')->value ?? 0) > 0) {
      $activities++;
    }
    $score += min(20, $activities * 4);

    // Document completeness (10 pts max).
    try {
      $expedienteService = \Drupal::hasService('jaraba_andalucia_ei.expediente')
        ? \Drupal::service('jaraba_andalucia_ei.expediente')
        : NULL;
      if ($expedienteService) {
        $completitud = $expedienteService->getCompletuDocumental((int) $participante->id());
        $score += min(10, ($completitud['porcentaje'] / 100) * 10);
      }
    }
    catch (\Exception $e) {
      // Gracefully ignore.
    }

    return min(100, round($score, 1));
  }

  /**
   * Maps score to engagement level.
   */
  protected function scoreToLevel(float $score): string {
    return match (TRUE) {
      $score >= 70 => self::ENGAGEMENT_HIGH,
      $score >= 40 => self::ENGAGEMENT_MEDIUM,
      $score >= 20 => self::ENGAGEMENT_LOW,
      default => self::ENGAGEMENT_AT_RISK,
    };
  }

  /**
   * Gets nudge frequency based on engagement level.
   *
   * @return array
   *   With keys: interval_hours, max_per_day, channels.
   */
  protected function getNudgeFrequency(string $level): array {
    return match ($level) {
      self::ENGAGEMENT_HIGH => [
        'interval_hours' => 72,
        'max_per_day' => 1,
        'channels' => ['fab_dot'],
      ],
      self::ENGAGEMENT_MEDIUM => [
        'interval_hours' => 24,
        'max_per_day' => 2,
        'channels' => ['fab_dot', 'fab_expand'],
      ],
      self::ENGAGEMENT_LOW => [
        'interval_hours' => 12,
        'max_per_day' => 3,
        'channels' => ['fab_dot', 'fab_expand', 'email'],
      ],
      self::ENGAGEMENT_AT_RISK => [
        'interval_hours' => 6,
        'max_per_day' => 4,
        'channels' => ['fab_dot', 'fab_expand', 'email', 'sms'],
      ],
    };
  }

  /**
   * Gets suggested IA session duration based on engagement.
   */
  protected function getSuggestedSessionDuration(string $level, ProgramaParticipanteEiInterface $participante): int {
    $baseMinutes = match ($level) {
      self::ENGAGEMENT_HIGH => 30,
      self::ENGAGEMENT_MEDIUM => 20,
      self::ENGAGEMENT_LOW => 10,
      self::ENGAGEMENT_AT_RISK => 5,
    };

    // Adjust for experience with IA.
    $iaHours = $participante->getHorasMentoriaIa();
    if ($iaHours > 10) {
      $baseMinutes = min(45, $baseMinutes + 10);
    }

    return $baseMinutes;
  }

  /**
   * Determines appropriate course difficulty level.
   *
   * @return string
   *   'beginner', 'intermediate', or 'advanced'.
   */
  protected function getCourseDifficulty(ProgramaParticipanteEiInterface $participante): string {
    $formacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    $iaHours = $participante->getHorasMentoriaIa();

    if ($formacion >= 40 && $iaHours >= 5) {
      return 'advanced';
    }
    if ($formacion >= 15 || $iaHours >= 2) {
      return 'intermediate';
    }
    return 'beginner';
  }

  /**
   * Generates personalized recommendations based on engagement.
   *
   * @return array
   *   Array of recommendation strings.
   */
  protected function getRecommendations(string $level, ProgramaParticipanteEiInterface $participante): array {
    $recommendations = [];

    if ($level === self::ENGAGEMENT_AT_RISK) {
      $recommendations[] = t('Intenta dedicar al menos 10 minutos al dia al programa.');
      $recommendations[] = t('Una sesion corta con tu tutor IA puede ayudarte a retomar el ritmo.');
    }

    if ($participante->getHorasMentoriaIa() < 1) {
      $recommendations[] = t('Prueba una sesion con el tutor IA para personalizar tu itinerario.');
    }

    $formacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    if ($formacion < 25 && $level !== self::ENGAGEMENT_AT_RISK) {
      $recommendations[] = t('Completa modulos de formacion para acercarte a las 50h requeridas.');
    }

    if ($participante->getFaseActual() === 'atencion' && $participante->canTransitToInsercion()) {
      $recommendations[] = t('Cumples los requisitos para avanzar a insercion. Consulta con tu orientador.');
    }

    return $recommendations;
  }

}
