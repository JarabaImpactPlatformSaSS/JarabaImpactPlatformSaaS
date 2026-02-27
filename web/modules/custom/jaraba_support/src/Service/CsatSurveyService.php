<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * Customer Satisfaction (CSAT) survey service.
 *
 * Manages post-resolution satisfaction surveys: scheduling delivery,
 * recording responses, and calculating aggregate satisfaction scores.
 * Surveys are sent after ticket resolution with a configurable delay.
 */
final class CsatSurveyService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected TicketNotificationService $notification,
  ) {}

  /**
   * Schedules a CSAT survey for a resolved ticket.
   *
   * The survey is typically sent after a short delay (e.g., 1 hour)
   * to allow the customer to verify the resolution.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The resolved ticket.
   */
  public function scheduleSurvey(SupportTicketInterface $ticket): void {
    try {
      // Schedule CSAT survey with 1-hour delay after resolution.
      $scheduledAt = time() + 3600;
      $ticket->set('csat_survey_scheduled', $scheduledAt);
      $ticket->save();

      $this->logger->info('CSAT survey scheduled for ticket #@number (ID: @id) at @time.', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
        '@time' => date('Y-m-d H:i:s', $scheduledAt),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to schedule CSAT survey for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Records a customer satisfaction response.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket being rated.
   * @param int $rating
   *   Satisfaction rating (1-5 scale).
   * @param int|null $npsScore
   *   Optional Net Promoter Score (0-10 scale).
   * @param string|null $comment
   *   Optional free-text feedback from the customer.
   */
  public function submitSatisfaction(SupportTicketInterface $ticket, int $rating, ?int $npsScore = NULL, ?string $comment = NULL): void {
    try {
      // Clamp rating to valid range 1-5.
      $rating = max(1, min(5, $rating));

      // Set satisfaction fields on the ticket.
      $ticket->set('satisfaction_rating', $rating);
      $ticket->set('satisfaction_comment', $comment);
      $ticket->set('satisfaction_submitted_at', time());
      $ticket->save();

      $this->logger->info('CSAT submitted for ticket #@number (ID: @id): rating @rating/5.', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
        '@rating' => $rating,
      ]);

      // Flag low satisfaction for follow-up.
      if ($rating <= 2) {
        $this->logger->warning('Low CSAT rating (@rating/5) on ticket #@number (ID: @id). Follow-up recommended.', [
          '@rating' => $rating,
          '@number' => $ticket->getTicketNumber(),
          '@id' => $ticket->id(),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to submit CSAT for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
