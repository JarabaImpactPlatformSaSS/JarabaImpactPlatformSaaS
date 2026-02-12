<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de sesiones de conversación del Copiloto.
 *
 * Gestiona el ciclo de vida completo de las sesiones de chat:
 * - Creación y finalización de sesiones
 * - Seguimiento de mensajes por sesión
 * - Métricas agregadas de uso
 * - Historial de sesiones por usuario
 *
 * Utiliza Drupal State API para persistencia de sesiones, lo que permite
 * almacenamiento rápido sin necesidad de esquema de base de datos adicional.
 *
 * @see \Drupal\jaraba_copilot_v2\Service\FeatureUnlockService
 */
class CopilotSessionService {

  /**
   * Prefix for state keys.
   */
  const STATE_PREFIX = 'jaraba_copilot.session.';

  /**
   * Prefix for user active session keys.
   */
  const ACTIVE_SESSION_PREFIX = 'jaraba_copilot.active_session.user.';

  /**
   * Prefix for user session history keys.
   */
  const HISTORY_PREFIX = 'jaraba_copilot.session_history.user.';

  /**
   * Key for aggregated metrics.
   */
  const METRICS_KEY = 'jaraba_copilot.session_metrics';

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The feature unlock service.
   */
  protected FeatureUnlockService $featureUnlock;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a CopilotSessionService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for loading user profiles.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy.
   * @param \Drupal\jaraba_copilot_v2\Service\FeatureUnlockService $featureUnlock
   *   The feature unlock service for mode availability checks.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    FeatureUnlockService $featureUnlock,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->featureUnlock = $featureUnlock;
    $this->logger = $logger;
  }

  /**
   * Starts a new copilot conversation session.
   *
   * If the user already has an active session, it will be ended first
   * before creating a new one.
   *
   * @param int $userId
   *   The user ID starting the session.
   * @param string $mode
   *   The copilot mode (coach, consultor, sparring, cfo, fiscal, laboral, devil).
   * @param array $context
   *   Additional context for the session (e.g., page, entrepreneur profile).
   *
   * @return array
   *   The created session data with keys:
   *   - session_id: Unique session identifier.
   *   - user_id: The user ID.
   *   - mode: The copilot mode.
   *   - status: Session status ('active').
   *   - started_at: UNIX timestamp of session start.
   *   - context: The provided context.
   *   - messages: Empty array (no messages yet).
   */
  public function startSession(int $userId, string $mode, array $context = []): array {
    // End any existing active session for this user.
    $activeSession = $this->getActiveSession($userId);
    if ($activeSession) {
      $this->endSession($activeSession['session_id']);
    }

    $sessionId = $this->generateSessionId();
    $now = (int) \Drupal::time()->getRequestTime();

    $session = [
      'session_id' => $sessionId,
      'user_id' => $userId,
      'mode' => $mode,
      'status' => 'active',
      'started_at' => $now,
      'ended_at' => NULL,
      'duration_seconds' => 0,
      'context' => $context,
      'messages' => [],
      'message_count' => 0,
      'satisfaction_rating' => NULL,
    ];

    // Store session data.
    $state = \Drupal::state();
    $state->set(self::STATE_PREFIX . $sessionId, $session);

    // Set as user's active session.
    $state->set(self::ACTIVE_SESSION_PREFIX . $userId, $sessionId);

    // Update metrics.
    $this->incrementMetric('total_sessions_started');
    $this->incrementMetric('active_sessions', 1);

    $this->logger->info('Copilot session started: @session_id for user @user in mode @mode', [
      '@session_id' => $sessionId,
      '@user' => $userId,
      '@mode' => $mode,
    ]);

    return $session;
  }

  /**
   * Ends an active copilot session.
   *
   * Calculates session duration and moves the session to the user's history.
   *
   * @param string $sessionId
   *   The session ID to end.
   *
   * @return array
   *   The ended session data with updated duration and status, or an empty
   *   array if the session was not found.
   */
  public function endSession(string $sessionId): array {
    $state = \Drupal::state();
    $session = $state->get(self::STATE_PREFIX . $sessionId);

    if (!$session) {
      $this->logger->warning('Attempted to end non-existent session: @id', ['@id' => $sessionId]);
      return [];
    }

    $now = (int) \Drupal::time()->getRequestTime();
    $session['status'] = 'ended';
    $session['ended_at'] = $now;
    $session['duration_seconds'] = $now - $session['started_at'];

    // Update session data.
    $state->set(self::STATE_PREFIX . $sessionId, $session);

    // Clear user's active session.
    $state->delete(self::ACTIVE_SESSION_PREFIX . $session['user_id']);

    // Add to session history.
    $this->addToHistory($session['user_id'], $sessionId);

    // Update metrics.
    $this->incrementMetric('active_sessions', -1);
    $this->updateAverageDuration($session['duration_seconds']);

    $this->logger->info('Copilot session ended: @session_id (duration: @duration s)', [
      '@session_id' => $sessionId,
      '@duration' => $session['duration_seconds'],
    ]);

    return $session;
  }

  /**
   * Gets the active session for a user.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array|null
   *   The active session data, or NULL if no active session exists.
   */
  public function getActiveSession(int $userId): ?array {
    $state = \Drupal::state();
    $sessionId = $state->get(self::ACTIVE_SESSION_PREFIX . $userId);

    if (!$sessionId) {
      return NULL;
    }

    $session = $state->get(self::STATE_PREFIX . $sessionId);

    // Verify the session is still active.
    if (!$session || $session['status'] !== 'active') {
      // Clean up stale reference.
      $state->delete(self::ACTIVE_SESSION_PREFIX . $userId);
      return NULL;
    }

    return $session;
  }

  /**
   * Gets the session history for a user.
   *
   * @param int $userId
   *   The user ID.
   * @param int $limit
   *   Maximum number of sessions to return.
   *
   * @return array
   *   Array of session data arrays, sorted by most recent first.
   */
  public function getSessionHistory(int $userId, int $limit = 20): array {
    $state = \Drupal::state();
    $historyIds = $state->get(self::HISTORY_PREFIX . $userId, []);

    // Take only the most recent sessions.
    $historyIds = array_slice($historyIds, 0, $limit);

    $sessions = [];
    foreach ($historyIds as $sessionId) {
      $session = $state->get(self::STATE_PREFIX . $sessionId);
      if ($session) {
        // Return a summary (without full message content for performance).
        $sessions[] = [
          'session_id' => $session['session_id'],
          'mode' => $session['mode'],
          'status' => $session['status'],
          'started_at' => $session['started_at'],
          'ended_at' => $session['ended_at'],
          'duration_seconds' => $session['duration_seconds'],
          'message_count' => $session['message_count'],
          'satisfaction_rating' => $session['satisfaction_rating'],
        ];
      }
    }

    return $sessions;
  }

  /**
   * Adds a message to a session.
   *
   * @param string $sessionId
   *   The session ID.
   * @param string $role
   *   The message role ('user' or 'assistant').
   * @param string $content
   *   The message content.
   * @param array $metadata
   *   Additional metadata (e.g., mode detection info, tokens used).
   *
   * @return array
   *   The created message data, or an empty array if session not found.
   */
  public function addMessage(string $sessionId, string $role, string $content, array $metadata = []): array {
    $state = \Drupal::state();
    $session = $state->get(self::STATE_PREFIX . $sessionId);

    if (!$session || $session['status'] !== 'active') {
      $this->logger->warning('Cannot add message to inactive/missing session: @id', ['@id' => $sessionId]);
      return [];
    }

    $now = (int) \Drupal::time()->getRequestTime();

    $message = [
      'id' => $session['message_count'] + 1,
      'role' => $role,
      'content' => $content,
      'timestamp' => $now,
      'metadata' => $metadata,
    ];

    $session['messages'][] = $message;
    $session['message_count']++;

    $state->set(self::STATE_PREFIX . $sessionId, $session);

    // Update total messages metric.
    $this->incrementMetric('total_messages');

    return $message;
  }

  /**
   * Gets aggregated session metrics.
   *
   * @return array
   *   Metrics array with keys:
   *   - active_sessions: Number of currently active sessions.
   *   - total_sessions_started: Total sessions ever started.
   *   - total_messages: Total messages across all sessions.
   *   - average_duration_seconds: Average session duration.
   *   - average_satisfaction: Average satisfaction rating (1-5).
   */
  public function getSessionMetrics(): array {
    $state = \Drupal::state();
    $metrics = $state->get(self::METRICS_KEY, []);

    $activeSessions = max(0, (int) ($metrics['active_sessions'] ?? 0));
    $totalSessions = (int) ($metrics['total_sessions_started'] ?? 0);
    $totalMessages = (int) ($metrics['total_messages'] ?? 0);
    $avgDuration = (float) ($metrics['average_duration_seconds'] ?? 0);
    $satisfactionSum = (float) ($metrics['satisfaction_sum'] ?? 0);
    $satisfactionCount = (int) ($metrics['satisfaction_count'] ?? 0);

    return [
      'active_sessions' => $activeSessions,
      'total_sessions_started' => $totalSessions,
      'total_messages' => $totalMessages,
      'average_duration_seconds' => round($avgDuration, 1),
      'average_satisfaction' => $satisfactionCount > 0
        ? round($satisfactionSum / $satisfactionCount, 2)
        : 0,
      'messages_per_session' => $totalSessions > 0
        ? round($totalMessages / $totalSessions, 1)
        : 0,
    ];
  }

  /**
   * Records a satisfaction rating for a session.
   *
   * @param string $sessionId
   *   The session ID.
   * @param int $rating
   *   Rating from 1 (poor) to 5 (excellent).
   *
   * @return bool
   *   TRUE if the rating was recorded.
   */
  public function rateSatisfaction(string $sessionId, int $rating): bool {
    if ($rating < 1 || $rating > 5) {
      return FALSE;
    }

    $state = \Drupal::state();
    $session = $state->get(self::STATE_PREFIX . $sessionId);

    if (!$session) {
      return FALSE;
    }

    $session['satisfaction_rating'] = $rating;
    $state->set(self::STATE_PREFIX . $sessionId, $session);

    // Update global satisfaction metrics.
    $metrics = $state->get(self::METRICS_KEY, []);
    $metrics['satisfaction_sum'] = ($metrics['satisfaction_sum'] ?? 0) + $rating;
    $metrics['satisfaction_count'] = ($metrics['satisfaction_count'] ?? 0) + 1;
    $state->set(self::METRICS_KEY, $metrics);

    return TRUE;
  }

  /**
   * Generates a unique session ID.
   *
   * @return string
   *   A unique session identifier.
   */
  protected function generateSessionId(): string {
    return 'cs_' . bin2hex(random_bytes(16));
  }

  /**
   * Adds a session ID to the user's history.
   *
   * Maintains a rolling list of the most recent 100 session IDs per user.
   *
   * @param int $userId
   *   The user ID.
   * @param string $sessionId
   *   The session ID to add.
   */
  protected function addToHistory(int $userId, string $sessionId): void {
    $state = \Drupal::state();
    $history = $state->get(self::HISTORY_PREFIX . $userId, []);

    // Prepend the new session (most recent first).
    array_unshift($history, $sessionId);

    // Keep only the last 100 entries.
    $history = array_slice($history, 0, 100);

    $state->set(self::HISTORY_PREFIX . $userId, $history);
  }

  /**
   * Increments a metric counter.
   *
   * @param string $key
   *   The metric key.
   * @param int $amount
   *   The amount to increment (can be negative).
   */
  protected function incrementMetric(string $key, int $amount = 1): void {
    $state = \Drupal::state();
    $metrics = $state->get(self::METRICS_KEY, []);
    $metrics[$key] = ($metrics[$key] ?? 0) + $amount;
    $state->set(self::METRICS_KEY, $metrics);
  }

  /**
   * Updates the running average session duration.
   *
   * Uses an incremental averaging formula to avoid storing all durations.
   *
   * @param int $newDuration
   *   The duration of the just-ended session in seconds.
   */
  protected function updateAverageDuration(int $newDuration): void {
    $state = \Drupal::state();
    $metrics = $state->get(self::METRICS_KEY, []);

    $totalEnded = ($metrics['total_ended_sessions'] ?? 0) + 1;
    $currentAvg = (float) ($metrics['average_duration_seconds'] ?? 0);

    // Incremental average: new_avg = old_avg + (new_value - old_avg) / n
    $newAvg = $currentAvg + ($newDuration - $currentAvg) / $totalEnded;

    $metrics['total_ended_sessions'] = $totalEnded;
    $metrics['average_duration_seconds'] = $newAvg;
    $state->set(self::METRICS_KEY, $metrics);
  }

}
