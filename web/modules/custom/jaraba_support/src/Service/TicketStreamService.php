<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Real-time ticket event stream service.
 *
 * Provides Server-Sent Events (SSE) for live ticket updates in the
 * agent dashboard. Tracks which agents are viewing which tickets
 * to enable collaborative awareness (collision detection).
 */
final class TicketStreamService {

  public function __construct(
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets a stream of ticket events for an agent.
   *
   * Returns a Generator that yields SSE-formatted events for tickets
   * assigned to or being watched by the agent.
   *
   * @param int $agentUid
   *   The agent's user ID.
   * @param string|null $lastEventId
   *   Optional last event ID for resuming a disconnected stream.
   *
   * @return \Generator
   *   Yields arrays with keys: id, type, data, timestamp.
   */
  public function getEventsForAgent(int $agentUid, ?string $lastEventId = NULL): \Generator {
    try {
      // Get ticket IDs assigned to this agent (not closed/merged).
      $assignedQuery = $this->database->select('support_ticket_field_data', 't')
        ->fields('t', ['id'])
        ->condition('t.assignee_uid', $agentUid)
        ->condition('t.status', ['closed', 'merged'], 'NOT IN');
      $assignedIds = $assignedQuery->execute()->fetchCol();

      // Get ticket IDs watched by this agent.
      $watchedIds = [];
      try {
        $watchedQuery = $this->database->select('ticket_watcher', 'w')
          ->fields('w', ['ticket_id'])
          ->condition('w.watcher_uid', $agentUid);
        $watchedIds = $watchedQuery->execute()->fetchCol();
      }
      catch (\Exception $e) {
        // ticket_watcher table may not exist yet.
        $this->logger->debug('Could not query ticket_watcher: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }

      // Combine and deduplicate ticket IDs.
      $ticketIds = array_unique(array_merge($assignedIds, $watchedIds));

      if (empty($ticketIds)) {
        return;
      }

      // Determine the minimum event ID to fetch after.
      $minEventId = $lastEventId !== NULL ? (int) $lastEventId : 0;

      // Query events for those tickets.
      $query = $this->database->select('support_ticket_events', 'e')
        ->fields('e', ['id', 'event_type', 'data', 'created'])
        ->condition('e.ticket_id', $ticketIds, 'IN')
        ->condition('e.id', $minEventId, '>')
        ->orderBy('e.id', 'ASC')
        ->range(0, 50);

      $results = $query->execute();

      foreach ($results as $row) {
        yield [
          'id' => $row->id,
          'type' => $row->event_type,
          'data' => json_decode($row->data, TRUE),
          'timestamp' => $row->created,
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get events for agent @uid: @msg', [
        '@uid' => $agentUid,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Publishes a ticket event to the stream.
   *
   * @param string $eventType
   *   Event type: 'ticket_updated', 'message_added', 'status_changed', etc.
   * @param array $data
   *   Event data payload (ticket_id, changes, actor, etc.).
   */
  public function publishEvent(string $eventType, array $data): void {
    try {
      $this->database->insert('support_ticket_events')
        ->fields([
          'event_type' => $eventType,
          'data' => json_encode($data),
          'ticket_id' => $data['ticket_id'] ?? 0,
          'created' => time(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to publish ticket event @type: @msg', [
        '@type' => $eventType,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Registers an agent as currently viewing a ticket.
   *
   * Used for collision detection — shows other agents who is
   * currently working on a ticket.
   *
   * @param int $agentUid
   *   The agent's user ID.
   * @param int $ticketId
   *   The ticket entity ID.
   */
  public function registerAgentViewing(int $agentUid, int $ticketId): void {
    try {
      $this->database->merge('support_ticket_viewers')
        ->keys([
          'agent_uid' => $agentUid,
          'ticket_id' => $ticketId,
        ])
        ->fields([
          'last_seen' => time(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to register agent @uid viewing ticket @tid: @msg', [
        '@uid' => $agentUid,
        '@tid' => $ticketId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Gets the list of agents currently viewing a specific ticket.
   *
   * @param int $ticketId
   *   The ticket entity ID.
   *
   * @return array
   *   Array of agent UIDs currently viewing the ticket.
   */
  public function getAgentsViewingTicket(int $ticketId): array {
    try {
      $fiveMinutesAgo = time() - 300;

      $query = $this->database->select('support_ticket_viewers', 'v')
        ->fields('v', ['agent_uid'])
        ->condition('v.ticket_id', $ticketId)
        ->condition('v.last_seen', $fiveMinutesAgo, '>');

      return $query->execute()->fetchCol();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get agents viewing ticket @tid: @msg', [
        '@tid' => $ticketId,
        '@msg' => $e->getMessage(),
      ]);

      return [];
    }
  }

}
