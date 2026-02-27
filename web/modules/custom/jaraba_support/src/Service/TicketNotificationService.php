<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Entity\TicketMessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Ticket notification service.
 *
 * Sends email and in-app notifications for ticket lifecycle events:
 * creation, new messages, SLA warnings/breaches, and resolution.
 * Delegates to the core NotificationService when available.
 */
final class TicketNotificationService {

  /**
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail plugin manager.
   * @param object|null $notificationService
   *   Optional core notification service (ecosistema_jaraba_core.notification).
   */
  public function __construct(
    protected LoggerInterface $logger,
    protected MailManagerInterface $mailManager,
    protected ?object $notificationService,
  ) {}

  /**
   * Sends notification when a new ticket is created.
   *
   * Notifies the assigned agent (if any) and the support team.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The newly created ticket.
   */
  public function notifyTicketCreated(SupportTicketInterface $ticket): void {
    try {
      // Notify reporter with confirmation email.
      $reporterUid = $ticket->get('reporter_uid')->target_id;
      $reporter = $reporterUid ? \Drupal\user\Entity\User::load($reporterUid) : NULL;
      if ($reporter && $reporter->getEmail()) {
        $this->mailManager->mail('jaraba_support', 'ticket_created', $reporter->getEmail(), 'es', [
          'ticket' => $ticket,
        ]);
      }

      // Notify assignee if set.
      $assigneeUid = $ticket->get('assignee_uid')->target_id;
      if ($assigneeUid) {
        $assignee = \Drupal\user\Entity\User::load($assigneeUid);
        if ($assignee && $assignee->getEmail()) {
          $this->mailManager->mail('jaraba_support', 'ticket_created', $assignee->getEmail(), 'es', [
            'ticket' => $ticket,
            'is_assignee' => TRUE,
          ]);
        }

        // Create in-app notification for assignee.
        if ($this->notificationService) {
          $this->notificationService->create([
            'type' => 'support_ticket_created',
            'entity_type' => 'support_ticket',
            'entity_id' => $ticket->id(),
            'uid' => $assigneeUid,
            'message' => "Nuevo ticket #{$ticket->getTicketNumber()}: {$ticket->label()}",
          ]);
        }
      }

      $this->logger->info('Ticket created notifications sent for ticket #@number (ID: @id).', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send ticket created notifications for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sends notification when a new message is added to a ticket.
   *
   * Notifies the reporter or agent depending on message author type.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket.
   * @param \Drupal\jaraba_support\Entity\TicketMessageInterface $message
   *   The new message.
   */
  public function notifyNewMessage(SupportTicketInterface $ticket, TicketMessageInterface $message): void {
    try {
      $authorType = $message->getAuthorType();
      $recipientUid = NULL;

      // If message is from customer, notify the assigned agent.
      // If message is from agent or AI, notify the reporter.
      if ($authorType === 'customer') {
        $recipientUid = $ticket->get('assignee_uid')->target_id;
      }
      else {
        $recipientUid = $ticket->get('reporter_uid')->target_id;
      }

      if (!$recipientUid) {
        $this->logger->notice('No recipient found for new message notification on ticket @id (author_type: @type).', [
          '@id' => $ticket->id(),
          '@type' => $authorType,
        ]);
        return;
      }

      $recipient = \Drupal\user\Entity\User::load($recipientUid);
      if (!$recipient || !$recipient->getEmail()) {
        return;
      }

      // Send email notification.
      $this->mailManager->mail('jaraba_support', 'new_message', $recipient->getEmail(), 'es', [
        'ticket' => $ticket,
        'message' => $message,
      ]);

      // Create in-app notification if available.
      if ($this->notificationService) {
        $this->notificationService->create([
          'type' => 'support_ticket_new_message',
          'entity_type' => 'support_ticket',
          'entity_id' => $ticket->id(),
          'uid' => $recipientUid,
          'message' => "Nuevo mensaje en ticket #{$ticket->getTicketNumber()}: {$ticket->label()}",
        ]);
      }

      $this->logger->info('New message notification sent for ticket #@number (ID: @id) to user @uid.', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
        '@uid' => $recipientUid,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send new message notification for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sends SLA warning notification before deadline breach.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket approaching SLA deadline.
   * @param int $minutesRemaining
   *   Minutes remaining before SLA breach.
   */
  public function notifySlaWarning(SupportTicketInterface $ticket, int $minutesRemaining = 0): void {
    try {
      $assigneeUid = $ticket->get('assignee_uid')->target_id;
      if (!$assigneeUid) {
        $this->logger->notice('SLA warning skipped for ticket @id: no assignee.', [
          '@id' => $ticket->id(),
        ]);
        return;
      }

      $assignee = \Drupal\user\Entity\User::load($assigneeUid);
      if (!$assignee || !$assignee->getEmail()) {
        return;
      }

      // Send SLA warning email to assignee.
      $this->mailManager->mail('jaraba_support', 'sla_warning', $assignee->getEmail(), 'es', [
        'ticket' => $ticket,
        'minutes_remaining' => $minutesRemaining,
      ]);

      // Create in-app notification if available.
      if ($this->notificationService) {
        $this->notificationService->create([
          'type' => 'support_sla_warning',
          'entity_type' => 'support_ticket',
          'entity_id' => $ticket->id(),
          'uid' => $assigneeUid,
          'message' => "Advertencia SLA: ticket #{$ticket->getTicketNumber()} — {$minutesRemaining} minutos restantes.",
        ]);
      }

      $this->logger->warning('SLA warning sent for ticket #@number (ID: @id). @min minutes remaining.', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
        '@min' => $minutesRemaining,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send SLA warning notification for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sends notification when SLA has been breached.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket that breached its SLA.
   */
  public function notifySlaBreached(SupportTicketInterface $ticket): void {
    try {
      $assigneeUid = $ticket->get('assignee_uid')->target_id;
      $notifiedUids = [];

      // Notify assignee if set.
      if ($assigneeUid) {
        $assignee = \Drupal\user\Entity\User::load($assigneeUid);
        if ($assignee && $assignee->getEmail()) {
          $this->mailManager->mail('jaraba_support', 'sla_breached', $assignee->getEmail(), 'es', [
            'ticket' => $ticket,
          ]);
          $notifiedUids[] = $assigneeUid;
        }
      }

      // Escalate to all support managers.
      $managerIds = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('roles', 'support_manager')
        ->execute();

      foreach ($managerIds as $managerUid) {
        $manager = \Drupal\user\Entity\User::load($managerUid);
        if ($manager && $manager->getEmail()) {
          $this->mailManager->mail('jaraba_support', 'sla_breached', $manager->getEmail(), 'es', [
            'ticket' => $ticket,
            'is_escalation' => TRUE,
          ]);
        }

        // Create in-app notification for each manager.
        if ($this->notificationService) {
          $this->notificationService->create([
            'type' => 'support_sla_breached',
            'entity_type' => 'support_ticket',
            'entity_id' => $ticket->id(),
            'uid' => (int) $managerUid,
            'message' => "SLA incumplido: ticket #{$ticket->getTicketNumber()}: {$ticket->label()}",
          ]);
        }
      }

      // Also notify assignee via in-app if available and not already a manager.
      if ($assigneeUid && $this->notificationService && !in_array($assigneeUid, $managerIds)) {
        $this->notificationService->create([
          'type' => 'support_sla_breached',
          'entity_type' => 'support_ticket',
          'entity_id' => $ticket->id(),
          'uid' => $assigneeUid,
          'message' => "SLA incumplido: ticket #{$ticket->getTicketNumber()}: {$ticket->label()}",
        ]);
      }

      $this->logger->warning('SLA breached notifications sent for ticket #@number (ID: @id). Notified @count manager(s).', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
        '@count' => count($managerIds),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send SLA breached notifications for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sends notification when a ticket is resolved.
   *
   * Notifies the ticket reporter that their issue has been resolved.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The resolved ticket.
   */
  public function notifyTicketResolved(SupportTicketInterface $ticket): void {
    try {
      $reporterUid = $ticket->get('reporter_uid')->target_id;
      $reporter = $reporterUid ? \Drupal\user\Entity\User::load($reporterUid) : NULL;

      if ($reporter && $reporter->getEmail()) {
        // Build CSAT survey link.
        $surveyPath = '/support/tickets/' . $ticket->id() . '/satisfaction';

        $this->mailManager->mail('jaraba_support', 'ticket_resolved', $reporter->getEmail(), 'es', [
          'ticket' => $ticket,
          'csat_survey_link' => $surveyPath,
        ]);
      }

      // Create in-app notification for reporter.
      if ($reporterUid && $this->notificationService) {
        $this->notificationService->create([
          'type' => 'support_ticket_resolved',
          'entity_type' => 'support_ticket',
          'entity_id' => $ticket->id(),
          'uid' => $reporterUid,
          'message' => "Tu ticket #{$ticket->getTicketNumber()} ha sido resuelto: {$ticket->label()}",
        ]);
      }

      $this->logger->info('Ticket resolved notifications sent for ticket #@number (ID: @id).', [
        '@number' => $ticket->getTicketNumber(),
        '@id' => $ticket->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send ticket resolved notifications for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
