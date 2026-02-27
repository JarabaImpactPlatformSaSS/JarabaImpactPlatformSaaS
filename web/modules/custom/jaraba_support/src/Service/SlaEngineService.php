<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * SLA engine service.
 *
 * Manages Service Level Agreement deadlines, pause/resume logic,
 * breach detection, and cron-based SLA processing. Deadlines are
 * calculated using business hours to exclude non-working time.
 */
final class SlaEngineService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected BusinessHoursService $businessHours,
    protected TicketNotificationService $notification,
    protected ?SupportHealthScoreService $healthScore,
  ) {}

  /**
   * Calculates SLA deadlines for a ticket based on its priority and SLA policy.
   */
  public function calculateDeadlines(SupportTicketInterface $ticket): void {
    try {
      $policy = $this->resolvePolicy($ticket);
      if (!$policy) {
        $this->logger->info('No SLA policy found for ticket @id — skipping deadline calculation.', [
          '@id' => $ticket->id() ?? 'new',
        ]);
        return;
      }

      $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid'));
      $firstResponseHours = (int) $policy->get('first_response_hours');
      $resolutionHours = (int) $policy->get('resolution_hours');
      $useBusinessHours = (bool) $policy->get('business_hours_only');

      if ($useBusinessHours) {
        $scheduleId = $policy->get('business_hours_schedule_id') ?: 'spain_standard';
        $firstResponseDue = $this->businessHours->addBusinessHours($scheduleId, $now, $firstResponseHours);
        $resolutionDue = $this->businessHours->addBusinessHours($scheduleId, $now, $resolutionHours);
      }
      else {
        $firstResponseDue = $now->modify("+{$firstResponseHours} hours");
        $resolutionDue = $now->modify("+{$resolutionHours} hours");
      }

      $ticket->set('sla_first_response_due', $firstResponseDue->getTimestamp());
      $ticket->set('sla_resolution_due', $resolutionDue->getTimestamp());

      $this->logger->info('SLA deadlines set for ticket @id: response by @resp, resolution by @res', [
        '@id' => $ticket->id() ?? 'new',
        '@resp' => $firstResponseDue->format('Y-m-d H:i'),
        '@res' => $resolutionDue->format('Y-m-d H:i'),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to calculate SLA deadlines for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Checks the current SLA status of a ticket.
   *
   * @return array
   *   Status array with keys: response_due, resolution_due,
   *   response_breached, resolution_breached, time_remaining,
   *   percentage_elapsed, status.
   */
  public function checkSlaStatus(SupportTicketInterface $ticket): array {
    $now = time();
    $result = [
      'response_due' => NULL,
      'resolution_due' => NULL,
      'response_breached' => FALSE,
      'resolution_breached' => FALSE,
      'time_remaining' => NULL,
      'percentage_elapsed' => 0,
      'status' => 'on_track',
    ];

    $responseDue = (int) ($ticket->get('sla_first_response_due')->value ?? 0);
    $resolutionDue = (int) ($ticket->get('sla_resolution_due')->value ?? 0);
    $firstRespondedAt = (int) ($ticket->get('first_responded_at')->value ?? 0);
    $isPaused = !$ticket->get('sla_paused_at')->isEmpty();
    $isResolved = $ticket->isResolved();

    if ($isPaused) {
      $result['status'] = 'paused';
      return $result;
    }

    if ($isResolved) {
      $result['status'] = 'met';
      return $result;
    }

    // Response SLA.
    if ($responseDue > 0) {
      $result['response_due'] = $this->formatTimeRemaining($responseDue - $now);
      if ($firstRespondedAt > 0) {
        $result['response_breached'] = $firstRespondedAt > $responseDue;
      }
      else {
        $result['response_breached'] = $now > $responseDue;
      }
    }

    // Resolution SLA.
    if ($resolutionDue > 0) {
      $remaining = $resolutionDue - $now;
      $result['resolution_due'] = $this->formatTimeRemaining($remaining);
      $result['resolution_breached'] = $now > $resolutionDue;
      $result['time_remaining'] = $this->formatTimeRemaining($remaining);

      // Calculate percentage elapsed.
      $created = (int) ($ticket->get('created')->value ?? $now);
      $totalWindow = $resolutionDue - $created;
      if ($totalWindow > 0) {
        $elapsed = $now - $created;
        $result['percentage_elapsed'] = min(100, (int) round(($elapsed / $totalWindow) * 100));
      }
    }

    // Determine status.
    if ($result['response_breached'] || $result['resolution_breached']) {
      $result['status'] = 'breached';
    }
    elseif ($result['percentage_elapsed'] > 75) {
      $result['status'] = 'at_risk';
    }

    return $result;
  }

  /**
   * Pauses the SLA clock for a ticket.
   */
  public function pauseSla(SupportTicketInterface $ticket): void {
    if (!$ticket->get('sla_paused_at')->isEmpty()) {
      return;
    }
    $ticket->set('sla_paused_at', time());
    $this->logger->info('SLA paused for ticket @id.', ['@id' => $ticket->id()]);
  }

  /**
   * Resumes the SLA clock for a ticket.
   */
  public function resumeSla(SupportTicketInterface $ticket): void {
    $pausedAt = (int) ($ticket->get('sla_paused_at')->value ?? 0);
    if ($pausedAt === 0) {
      return;
    }

    $pausedDuration = (int) ($ticket->get('sla_paused_duration')->value ?? 0);
    $additionalPause = time() - $pausedAt;
    $totalPaused = $pausedDuration + $additionalPause;

    $ticket->set('sla_paused_duration', $totalPaused);
    $ticket->set('sla_paused_at', NULL);

    // Extend deadlines by paused duration.
    $responseDue = (int) ($ticket->get('sla_first_response_due')->value ?? 0);
    $resolutionDue = (int) ($ticket->get('sla_resolution_due')->value ?? 0);

    if ($responseDue > 0) {
      $ticket->set('sla_first_response_due', $responseDue + $additionalPause);
    }
    if ($resolutionDue > 0) {
      $ticket->set('sla_resolution_due', $resolutionDue + $additionalPause);
    }

    $this->logger->info('SLA resumed for ticket @id (paused @mins minutes).', [
      '@id' => $ticket->id(),
      '@mins' => (int) ($additionalPause / 60),
    ]);
  }

  /**
   * Processes all open tickets for SLA status during cron.
   */
  public function processSlaCron(): void {
    try {
      $storage = $this->entityTypeManager->getStorage('support_ticket');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', ['open', 'in_progress', 'escalated'], 'IN')
        ->condition('sla_breached', 0)
        ->condition('sla_resolution_due', 0, '>')
        ->range(0, 200)
        ->execute();

      if (empty($ids)) {
        return;
      }

      $tickets = $storage->loadMultiple($ids);
      $now = time();
      $breached = 0;
      $warned = 0;

      foreach ($tickets as $ticket) {
        $status = $this->checkSlaStatus($ticket);

        if ($status['status'] === 'breached' && !$ticket->isSlaBreached()) {
          $ticket->set('sla_breached', TRUE);
          $ticket->save();
          $this->notification->notifySlaBreached($ticket);
          $this->healthScore?->triggerChurnAlertIfNeeded($ticket);
          $breached++;
        }
        elseif ($status['status'] === 'at_risk') {
          $this->notification->notifySlaWarning($ticket);
          $warned++;
        }
      }

      if ($breached > 0 || $warned > 0) {
        $this->logger->notice('SLA cron: @breached breaches, @warned warnings out of @total tickets.', [
          '@breached' => $breached,
          '@warned' => $warned,
          '@total' => count($tickets),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('SLA cron processing failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Resolves the SLA policy for a ticket based on tenant plan + priority.
   */
  private function resolvePolicy(SupportTicketInterface $ticket): ?object {
    $priority = $ticket->getPriority();
    $planTier = 'starter';

    // Try to determine plan tier from tenant.
    if (!$ticket->get('tenant_id')->isEmpty()) {
      $tenant = $ticket->get('tenant_id')->entity;
      if ($tenant && method_exists($tenant, 'get')) {
        try {
          $planTier = $tenant->get('field_plan_tier')->value ?? 'starter';
        }
        catch (\Exception) {
          // Field may not exist — use default.
        }
      }
    }

    $policyId = $planTier . '_' . $priority;

    try {
      return $this->entityTypeManager
        ->getStorage('sla_policy')
        ->load($policyId);
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * Formats remaining seconds into human-readable string.
   */
  private function formatTimeRemaining(int $seconds): string {
    if ($seconds <= 0) {
      $abs = abs($seconds);
      $hours = (int) ($abs / 3600);
      return $hours > 0 ? "-{$hours}h" : '-' . (int) ($abs / 60) . 'min';
    }

    $hours = (int) ($seconds / 3600);
    $minutes = (int) (($seconds % 3600) / 60);

    if ($hours > 24) {
      $days = (int) ($hours / 24);
      return "{$days}d " . ($hours % 24) . 'h';
    }

    if ($hours > 0) {
      return "{$hours}h {$minutes}min";
    }

    return "{$minutes}min";
  }

}
