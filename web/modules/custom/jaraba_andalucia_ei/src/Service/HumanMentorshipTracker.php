<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tracks human mentorship hours for Andalucía +ei participants.
 *
 * Mirrors the pattern of AiMentorshipTracker but for human mentor sessions.
 * Updates the ProgramaParticipanteEi.horas_mentoria_humana field
 * when mentoring sessions are completed.
 */
class HumanMentorshipTracker {

  /**
   * Constructs a HumanMentorshipTracker.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Records a completed human mentoring session.
   *
   * Called from hook_entity_presave when mentoring_session status → completed.
   *
   * @param int $sessionId
   *   The mentoring_session entity ID.
   */
  public function registrarSesionHumana(int $sessionId): void {
    try {
      $session = $this->entityTypeManager->getStorage('mentoring_session')->load($sessionId);
      if (!$session) {
        return;
      }

      $menteeId = $session->get('mentee_id')->target_id;
      if (!$menteeId) {
        return;
      }

      // Calculate session duration in hours.
      $hours = $this->calculateSessionHours($session);
      if ($hours <= 0) {
        return;
      }

      // Find the participante from the mentee user.
      $participante = $this->resolveParticipante((int) $menteeId);
      if (!$participante) {
        $this->logger->info('No participante found for mentee @uid, skipping hours tracking.', ['@uid' => $menteeId]);
        return;
      }

      // Update accumulated hours.
      $currentHours = (float) ($participante->get('horas_mentoria_humana')->value ?? 0);
      $participante->set('horas_mentoria_humana', $currentHours + $hours);
      $participante->save();

      $this->logger->info('Recorded @hours hours of human mentoring for participante @id (session @session).', [
        '@hours' => number_format($hours, 2),
        '@id' => $participante->id(),
        '@session' => $sessionId,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error recording human mentoring session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Gets total human mentoring hours for a participant.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return float
   *   Total hours.
   */
  public function getHorasHumanas(int $participanteId): float {
    try {
      $participante = $this->entityTypeManager->getStorage('programa_participante_ei')->load($participanteId);
      if (!$participante) {
        return 0.0;
      }
      return (float) ($participante->get('horas_mentoria_humana')->value ?? 0);
    }
    catch (\Throwable $e) {
      return 0.0;
    }
  }

  /**
   * Gets a summary of human mentoring hours and sessions.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return array
   *   Summary with 'hours', 'session_count', 'last_session_date'.
   */
  public function getResumenHoras(int $participanteId): array {
    try {
      $participante = $this->entityTypeManager->getStorage('programa_participante_ei')->load($participanteId);
      if (!$participante) {
        return ['hours' => 0, 'session_count' => 0, 'last_session_date' => NULL];
      }

      // Count completed sessions for this user.
      $userId = $participante->getOwnerId();
      $sessionIds = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('mentee_id', $userId)
        ->condition('status', 'completed')
        ->sort('scheduled_start', 'DESC')
        ->execute();

      $lastDate = NULL;
      if (!empty($sessionIds)) {
        $lastSession = $this->entityTypeManager->getStorage('mentoring_session')->load(reset($sessionIds));
        if ($lastSession) {
          $lastDate = $lastSession->get('scheduled_start')->value;
        }
      }

      return [
        'hours' => (float) ($participante->get('horas_mentoria_humana')->value ?? 0),
        'session_count' => count($sessionIds),
        'last_session_date' => $lastDate,
      ];
    }
    catch (\Throwable $e) {
      return ['hours' => 0, 'session_count' => 0, 'last_session_date' => NULL];
    }
  }

  /**
   * Calculates session duration in hours.
   */
  protected function calculateSessionHours(mixed $session): float {
    $start = $session->get('actual_start')->value ?? $session->get('scheduled_start')->value;
    $end = $session->get('actual_end')->value ?? $session->get('scheduled_end')->value;

    if ($start && $end) {
      $diff = abs(strtotime($end) - strtotime($start));
      return round($diff / 3600, 2);
    }

    // Default to 1 hour if times are missing.
    return 1.0;
  }

  /**
   * Resolves the participante entity from a user ID.
   */
  protected function resolveParticipante(int $userId): mixed {
    $ids = $this->entityTypeManager->getStorage('programa_participante_ei')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $userId)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    return !empty($ids) ? $this->entityTypeManager->getStorage('programa_participante_ei')->load(reset($ids)) : NULL;
  }

}
