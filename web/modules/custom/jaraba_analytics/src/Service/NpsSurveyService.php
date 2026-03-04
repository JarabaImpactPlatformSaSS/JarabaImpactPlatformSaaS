<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Servicio de encuestas NPS (Net Promoter Score).
 *
 * Registra respuestas NPS, calcula el score, y gestiona
 * la frecuencia de encuestas (maximo cada 90 dias).
 */
class NpsSurveyService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Registra una respuesta NPS.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   El usuario que responde.
   * @param int $score
   *   Puntuacion NPS (0-10).
   * @param string $vertical
   *   Vertical canonico.
   */
  public function recordResponse(AccountInterface $user, int $score, string $vertical): void {
    if ($score < 0 || $score > 10) {
      return;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('analytics_event');
      $event = $storage->create([
        'event_type' => 'nps_response',
        'user_id' => $user->id(),
        'event_data' => [
          'score' => $score,
          'vertical' => $vertical,
          'category' => $this->categorizeScore($score),
        ],
      ]);
      $event->save();
    }
    catch (\Throwable) {
      // NPS recording should not break the application.
    }
  }

  /**
   * Calcula el NPS para un vertical/tenant.
   *
   * NPS = %Promotores - %Detractores.
   * Promotores: 9-10, Pasivos: 7-8, Detractores: 0-6.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param string $tenantId
   *   ID del tenant.
   *
   * @return float
   *   NPS score (-100 a 100).
   */
  public function calculateNps(string $vertical, string $tenantId): float {
    try {
      $storage = $this->entityTypeManager->getStorage('analytics_event');

      // Obtener todas las respuestas NPS de los ultimos 90 dias.
      $since = strtotime('-90 days');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'nps_response')
        ->condition('created', $since, '>=');

      $ids = $query->execute();
      if ($ids === []) {
        return 0.0;
      }

      $events = $storage->loadMultiple($ids);
      $promoters = 0;
      $detractors = 0;
      $total = 0;

      foreach ($events as $event) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $event */
        $data = $event->get('event_data')->getValue();
        $eventData = $data[0] ?? [];
        $eventVertical = $eventData['vertical'] ?? '';

        if ($eventVertical !== $vertical) {
          continue;
        }

        $score = (int) ($eventData['score'] ?? 0);
        $total++;

        if ($score >= 9) {
          $promoters++;
        }
        elseif ($score <= 6) {
          $detractors++;
        }
      }

      if ($total === 0) {
        return 0.0;
      }

      return round((($promoters - $detractors) / $total) * 100, 1);
    }
    catch (\Throwable) {
      return 0.0;
    }
  }

  /**
   * Determina si se debe mostrar la encuesta NPS al usuario.
   *
   * Maximo una encuesta cada 90 dias.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   El usuario.
   *
   * @return bool
   *   TRUE si se debe mostrar la encuesta.
   */
  public function shouldShowSurvey(AccountInterface $user): bool {
    if ($user->isAnonymous()) {
      return FALSE;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('analytics_event');
      $since = strtotime('-90 days');

      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'nps_response')
        ->condition('user_id', $user->id())
        ->condition('created', $since, '>=')
        ->count()
        ->execute();

      return $count === 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Categoriza un score NPS.
   */
  protected function categorizeScore(int $score): string {
    if ($score >= 9) {
      return 'promoter';
    }
    if ($score >= 7) {
      return 'passive';
    }
    return 'detractor';
  }

  /**
   * Obtiene el desglose NPS (promotores, pasivos, detractores).
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Array con keys: promoters, passives, detractors, total, score.
   */
  public function getNpsBreakdown(string $vertical): array {
    $result = [
      'promoters' => 0,
      'passives' => 0,
      'detractors' => 0,
      'total' => 0,
      'score' => 0.0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('analytics_event');
      $since = strtotime('-90 days');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'nps_response')
        ->condition('created', $since, '>=')
        ->execute();

      if ($ids === []) {
        return $result;
      }

      foreach ($storage->loadMultiple($ids) as $event) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $event */
        $data = $event->get('event_data')->getValue();
        $eventData = $data[0] ?? [];
        if (($eventData['vertical'] ?? '') !== $vertical) {
          continue;
        }

        $score = (int) ($eventData['score'] ?? 0);
        $result['total']++;

        if ($score >= 9) {
          $result['promoters']++;
        }
        elseif ($score >= 7) {
          $result['passives']++;
        }
        else {
          $result['detractors']++;
        }
      }

      if ($result['total'] > 0) {
        $result['score'] = round(
          (($result['promoters'] - $result['detractors']) / $result['total']) * 100,
          1
        );
      }
    }
    catch (\Throwable) {
      // Return empty result on error.
    }

    return $result;
  }

}
