<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Psr\Log\LoggerInterface;

/**
 * Support module cron orchestrator.
 *
 * Coordinates all periodic support tasks during cron execution:
 * SLA deadline checks, pending scan processing, stale ticket
 * notifications, and scheduled survey delivery.
 */
final class SupportCronService {

  /**
   * Auto-close resolved tickets after this many days without activity.
   */
  private const AUTO_CLOSE_DAYS = 7;

  /**
   * Purge SSE events older than this many hours.
   */
  private const EVENT_PURGE_HOURS = 24;

  public function __construct(
    protected SlaEngineService $slaEngine,
    protected TicketService $ticketService,
    protected TicketNotificationService $notification,
    protected AttachmentScanService $attachmentScan,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Runs all support cron tasks.
   *
   * Executes in order:
   * 1. SLA deadline processing (warnings and breach detection).
   * 2. Pending attachment virus scans.
   * 3. Auto-close stale resolved tickets.
   * 4. Scheduled CSAT survey delivery.
   * 5. Purge old SSE events.
   */
  public function run(): void {
    $this->logger->info('Support cron started.');

    // 1. SLA processing.
    try {
      $this->slaEngine->processSlaCron();
    }
    catch (\Exception $e) {
      $this->logger->error('SLA cron failed: @msg', ['@msg' => $e->getMessage()]);
    }

    // 2. Pending attachment scans.
    try {
      $this->attachmentScan->processPendingScans();
    }
    catch (\Exception $e) {
      $this->logger->error('Attachment scan cron failed: @msg', ['@msg' => $e->getMessage()]);
    }

    // 3. Auto-close stale resolved tickets.
    try {
      $this->autoCloseStaleTickets();
    }
    catch (\Exception $e) {
      $this->logger->error('Auto-close cron failed: @msg', ['@msg' => $e->getMessage()]);
    }

    // 4. Send scheduled CSAT surveys.
    try {
      $this->sendScheduledSurveys();
    }
    catch (\Exception $e) {
      $this->logger->error('CSAT survey cron failed: @msg', ['@msg' => $e->getMessage()]);
    }

    // 5. Purge old SSE events.
    try {
      $this->purgeOldEvents();
    }
    catch (\Exception $e) {
      $this->logger->error('Event purge cron failed: @msg', ['@msg' => $e->getMessage()]);
    }

    $this->logger->info('Support cron completed.');
  }

  /**
   * Auto-closes resolved tickets with no activity for N days.
   */
  private function autoCloseStaleTickets(): void {
    $cutoff = time() - (self::AUTO_CLOSE_DAYS * 86400);

    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'resolved')
      ->condition('resolved_at', $cutoff, '<')
      ->range(0, 100)
      ->execute();

    if (empty($ids)) {
      return;
    }

    $tickets = $storage->loadMultiple($ids);
    $closed = 0;

    foreach ($tickets as $ticket) {
      try {
        $this->ticketService->transitionStatus($ticket, 'closed', NULL, 'system');
        $closed++;
      }
      catch (\Exception $e) {
        $this->logger->warning('Failed to auto-close ticket @id: @msg', [
          '@id' => $ticket->id(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    if ($closed > 0) {
      $this->logger->notice('Auto-closed @count stale resolved tickets.', ['@count' => $closed]);
    }
  }

  /**
   * Sends CSAT surveys for tickets scheduled to receive them.
   */
  private function sendScheduledSurveys(): void {
    $now = time();

    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('csat_survey_scheduled', 0, '>')
      ->condition('csat_survey_scheduled', $now, '<=')
      ->condition('satisfaction_rating', NULL, 'IS NULL')
      ->range(0, 50)
      ->execute();

    if (empty($ids)) {
      return;
    }

    $tickets = $storage->loadMultiple($ids);
    $sent = 0;

    foreach ($tickets as $ticket) {
      try {
        $this->notification->notifyTicketResolved($ticket);
        // Clear the schedule so we don't send again.
        $ticket->set('csat_survey_scheduled', 0);
        $ticket->save();
        $sent++;
      }
      catch (\Exception $e) {
        $this->logger->warning('Failed to send CSAT survey for ticket @id: @msg', [
          '@id' => $ticket->id(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    if ($sent > 0) {
      $this->logger->notice('Sent @count CSAT survey notifications.', ['@count' => $sent]);
    }
  }

  /**
   * Purges old SSE events to prevent table growth.
   */
  private function purgeOldEvents(): void {
    $cutoff = time() - (self::EVENT_PURGE_HOURS * 3600);

    try {
      $deleted = \Drupal::database()->delete('support_ticket_events')
        ->condition('created', $cutoff, '<')
        ->execute();

      if ($deleted > 0) {
        $this->logger->info('Purged @count old SSE events.', ['@count' => $deleted]);
      }
    }
    catch (\Exception $e) {
      // Table may not exist yet.
      $this->logger->info('SSE event purge skipped: @msg', ['@msg' => $e->getMessage()]);
    }

    // Also clean stale viewer registrations (> 30 minutes old).
    try {
      $viewerCutoff = time() - 1800;
      \Drupal::database()->delete('support_ticket_viewers')
        ->condition('last_seen', $viewerCutoff, '<')
        ->execute();
    }
    catch (\Exception) {
      // Table may not exist yet.
    }
  }

}
