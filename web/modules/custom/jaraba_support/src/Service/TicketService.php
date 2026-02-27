<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Entity\TicketMessage;
use Drupal\jaraba_support\Entity\TicketMessageInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Core service for Support Ticket CRUD and lifecycle management.
 *
 * Responsibilities:
 * - Create tickets with field validation
 * - State machine transitions with validation
 * - Agent assignment
 * - Message addition
 * - Event logging
 * - Tenant-scoped queries
 */
class TicketService {

  /**
   * Valid status transitions (state machine).
   */
  private const VALID_TRANSITIONS = [
    'new' => ['ai_handling', 'open'],
    'ai_handling' => ['resolved', 'open'],
    'open' => ['pending_customer', 'pending_internal', 'escalated', 'resolved', 'merged'],
    'pending_customer' => ['open', 'closed'],
    'pending_internal' => ['open', 'escalated'],
    'escalated' => ['open', 'resolved'],
    'resolved' => ['closed', 'reopened'],
    'closed' => ['reopened'],
    'reopened' => ['open', 'pending_customer', 'escalated', 'resolved'],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
    protected Connection $database,
    protected ?TenantContextService $tenantContext,
    protected ?EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Creates a new support ticket.
   *
   * @param array $data
   *   Ticket field data (subject, description, vertical, category, etc.).
   * @param int|null $reporterUid
   *   Reporter user ID. Defaults to current user.
   *
   * @return \Drupal\jaraba_support\Entity\SupportTicketInterface
   *   The created ticket entity.
   */
  public function createTicket(array $data, ?int $reporterUid = NULL): SupportTicketInterface {
    $storage = $this->entityTypeManager->getStorage('support_ticket');

    $reporterUid = $reporterUid ?? (int) $this->currentUser->id();
    $tenantId = $data['tenant_id'] ?? $this->tenantContext?->getCurrentTenantId();

    $values = [
      'subject' => $data['subject'] ?? '',
      'description' => $data['description'] ?? '',
      'vertical' => $data['vertical'] ?? 'platform',
      'category' => $data['category'] ?? '',
      'subcategory' => $data['subcategory'] ?? '',
      'status' => 'new',
      'priority' => $data['priority'] ?? 'medium',
      'severity' => $data['severity'] ?? NULL,
      'channel' => $data['channel'] ?? 'portal',
      'tenant_id' => $tenantId,
      'reporter_uid' => $reporterUid,
      'related_entity_type' => $data['related_entity_type'] ?? NULL,
      'related_entity_id' => $data['related_entity_id'] ?? NULL,
    ];

    /** @var \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket */
    $ticket = $storage->create($values);
    $ticket->save();

    // Log creation event.
    $this->logEvent($ticket, 'created', $reporterUid, 'customer');

    $this->logger->info('Support ticket @number created by user @uid.', [
      '@number' => $ticket->getTicketNumber(),
      '@uid' => $reporterUid,
    ]);

    return $ticket;
  }

  /**
   * Transitions a ticket to a new status.
   *
   * @throws \InvalidArgumentException
   *   If the transition is not valid.
   */
  public function transitionStatus(SupportTicketInterface $ticket, string $newStatus, ?int $actorUid = NULL, string $actorType = 'system'): void {
    $currentStatus = $ticket->getStatus();

    if (!$this->isValidTransition($currentStatus, $newStatus)) {
      throw new \InvalidArgumentException(sprintf(
        'Invalid status transition from "%s" to "%s" for ticket %s.',
        $currentStatus, $newStatus, $ticket->getTicketNumber()
      ));
    }

    $oldStatus = $currentStatus;
    $ticket->set('status', $newStatus);

    // Update timestamps for special states.
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    if ($newStatus === 'resolved') {
      $ticket->set('resolved_at', $now->format('Y-m-d\TH:i:s'));
    }
    if ($newStatus === 'closed') {
      $ticket->set('closed_at', $now->format('Y-m-d\TH:i:s'));
    }

    $ticket->save();

    $this->logEvent($ticket, 'status_changed', $actorUid, $actorType, $oldStatus, $newStatus);

    $this->logger->info('Ticket @number: status @old → @new.', [
      '@number' => $ticket->getTicketNumber(),
      '@old' => $oldStatus,
      '@new' => $newStatus,
    ]);
  }

  /**
   * Assigns an agent to a ticket.
   */
  public function assignAgent(SupportTicketInterface $ticket, int $agentUid, ?int $actorUid = NULL): void {
    $oldAssignee = $ticket->get('assignee_uid')->target_id;
    $ticket->set('assignee_uid', $agentUid);
    $ticket->save();

    $this->logEvent(
      $ticket,
      'assigned',
      $actorUid ?? $agentUid,
      'agent',
      $oldAssignee ? (string) $oldAssignee : '',
      (string) $agentUid
    );

    $this->logger->info('Ticket @number assigned to agent @uid.', [
      '@number' => $ticket->getTicketNumber(),
      '@uid' => $agentUid,
    ]);
  }

  /**
   * Resolves a ticket.
   */
  public function resolveTicket(SupportTicketInterface $ticket, string $notes = '', ?int $actorUid = NULL, string $actorType = 'agent'): void {
    if ($notes) {
      $ticket->set('resolution_notes', $notes);
    }
    $this->transitionStatus($ticket, 'resolved', $actorUid, $actorType);
  }

  /**
   * Reopens a ticket.
   */
  public function reopenTicket(SupportTicketInterface $ticket, ?int $actorUid = NULL): void {
    $this->transitionStatus($ticket, 'reopened', $actorUid, 'customer');
  }

  /**
   * Adds a message to a ticket.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket.
   * @param string $body
   *   Message body.
   * @param string $authorType
   *   One of: customer, agent, ai, system.
   * @param array $options
   *   Optional: is_internal_note, is_ai_generated, ai_confidence, ai_sources.
   *
   * @return \Drupal\jaraba_support\Entity\TicketMessageInterface
   *   The created message.
   */
  public function addMessage(SupportTicketInterface $ticket, string $body, string $authorType, array $options = []): TicketMessageInterface {
    $storage = $this->entityTypeManager->getStorage('ticket_message');

    $values = [
      'ticket_id' => $ticket->id(),
      'author_uid' => ($authorType === 'customer' || $authorType === 'agent')
        ? ($options['author_uid'] ?? (int) $this->currentUser->id())
        : NULL,
      'author_type' => $authorType,
      'body' => $body,
      'is_internal_note' => $options['is_internal_note'] ?? FALSE,
      'is_ai_generated' => $options['is_ai_generated'] ?? ($authorType === 'ai'),
      'ai_confidence' => $options['ai_confidence'] ?? NULL,
      'ai_sources' => isset($options['ai_sources']) ? json_encode($options['ai_sources']) : NULL,
    ];

    /** @var \Drupal\jaraba_support\Entity\TicketMessageInterface $message */
    $message = $storage->create($values);
    $message->save();

    // Update first_responded_at if this is the first response.
    if (!$ticket->get('first_responded_at')->value && in_array($authorType, ['agent', 'ai'], TRUE)) {
      $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
      $ticket->set('first_responded_at', $now->format('Y-m-d\TH:i:s'));
      $ticket->save();
    }

    return $message;
  }

  /**
   * Gets tickets for a tenant, with optional filters.
   *
   * @return \Drupal\jaraba_support\Entity\SupportTicketInterface[]
   */
  public function getTicketsForTenant(?int $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array {
    $storage = $this->entityTypeManager->getStorage('support_ticket');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    if (!empty($filters['reporter_uid'])) {
      $query->condition('reporter_uid', $filters['reporter_uid']);
    }
    if (!empty($filters['status'])) {
      $query->condition('status', (array) $filters['status'], 'IN');
    }
    if (!empty($filters['priority'])) {
      $query->condition('priority', (array) $filters['priority'], 'IN');
    }
    if (!empty($filters['vertical'])) {
      $query->condition('vertical', $filters['vertical']);
    }
    if (!empty($filters['assignee_uid'])) {
      $query->condition('assignee_uid', $filters['assignee_uid']);
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Gets tickets assigned to an agent.
   *
   * @return \Drupal\jaraba_support\Entity\SupportTicketInterface[]
   */
  public function getTicketsForAgent(int $agentUid, array $filters = []): array {
    $filters['assignee_uid'] = $agentUid;
    return $this->getTicketsForTenant(NULL, $filters);
  }

  /**
   * Gets messages for a ticket.
   *
   * @param bool $includeInternal
   *   Whether to include internal notes (agents only).
   *
   * @return \Drupal\jaraba_support\Entity\TicketMessageInterface[]
   */
  public function getMessages(SupportTicketInterface $ticket, bool $includeInternal = FALSE): array {
    $storage = $this->entityTypeManager->getStorage('ticket_message');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('ticket_id', $ticket->id())
      ->sort('created', 'ASC');

    if (!$includeInternal) {
      $query->condition('is_internal_note', FALSE);
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Validates a state machine transition.
   */
  public function isValidTransition(string $from, string $to): bool {
    return isset(self::VALID_TRANSITIONS[$from]) && in_array($to, self::VALID_TRANSITIONS[$from], TRUE);
  }

  /**
   * Logs a ticket event.
   */
  public function logEvent(
    SupportTicketInterface $ticket,
    string $eventType,
    ?int $actorUid = NULL,
    string $actorType = 'system',
    string $oldValue = '',
    string $newValue = '',
    array $metadata = [],
  ): void {
    try {
      $storage = $this->entityTypeManager->getStorage('ticket_event_log');
      $log = $storage->create([
        'ticket_id' => $ticket->id(),
        'event_type' => $eventType,
        'actor_uid' => $actorUid,
        'actor_type' => $actorType,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'metadata' => $metadata ? json_encode($metadata) : NULL,
        'ip_address' => \Drupal::request()->getClientIp(),
        'user_agent' => substr(\Drupal::request()->headers->get('User-Agent', ''), 0, 255),
      ]);
      $log->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to log event @type for ticket @id: @msg', [
        '@type' => $eventType,
        '@id' => $ticket->id(),
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
