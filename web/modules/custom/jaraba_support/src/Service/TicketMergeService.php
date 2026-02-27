<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * Ticket merge service.
 *
 * Merges duplicate tickets into a canonical ticket by moving messages,
 * attachments, and watchers to the canonical ticket and closing the
 * duplicate with a reference to the canonical.
 */
final class TicketMergeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected TicketService $ticketService,
  ) {}

  /**
   * Merges a duplicate ticket into a canonical ticket.
   *
   * Moves all messages and attachments from the duplicate to the canonical
   * ticket. Adds a system message noting the merge. Transitions the
   * duplicate ticket to 'merged' status with a reference to the canonical.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $canonical
   *   The canonical (surviving) ticket.
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $duplicate
   *   The duplicate ticket to merge into the canonical.
   *
   * @throws \InvalidArgumentException
   *   If both tickets are the same entity.
   */
  public function merge(SupportTicketInterface $canonical, SupportTicketInterface $duplicate): void {
    if ($canonical->id() === $duplicate->id()) {
      throw new \InvalidArgumentException('Cannot merge a ticket into itself.');
    }

    try {
      // Move all messages from duplicate to canonical.
      $messageIds = $this->entityTypeManager
        ->getStorage('ticket_message')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('ticket_id', $duplicate->id())
        ->execute();

      if ($messageIds) {
        $messages = $this->entityTypeManager->getStorage('ticket_message')->loadMultiple($messageIds);
        foreach ($messages as $message) {
          $message->set('ticket_id', $canonical->id());
          $message->save();
        }
      }

      // Move all attachments from duplicate to canonical.
      $attachmentIds = $this->entityTypeManager
        ->getStorage('ticket_attachment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('ticket_id', $duplicate->id())
        ->execute();

      if ($attachmentIds) {
        $attachments = $this->entityTypeManager->getStorage('ticket_attachment')->loadMultiple($attachmentIds);
        foreach ($attachments as $attachment) {
          $attachment->set('ticket_id', $canonical->id());
          $attachment->save();
        }
      }

      // Move watchers, avoiding duplicates.
      $watcherIds = $this->entityTypeManager
        ->getStorage('ticket_watcher')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('ticket_id', $duplicate->id())
        ->execute();

      if ($watcherIds) {
        // Get existing watcher UIDs on canonical to detect duplicates.
        $canonicalWatcherIds = $this->entityTypeManager
          ->getStorage('ticket_watcher')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('ticket_id', $canonical->id())
          ->execute();

        $canonicalWatcherUids = [];
        if ($canonicalWatcherIds) {
          $canonicalWatchers = $this->entityTypeManager->getStorage('ticket_watcher')->loadMultiple($canonicalWatcherIds);
          foreach ($canonicalWatchers as $cw) {
            $canonicalWatcherUids[] = $cw->get('uid')->target_id;
          }
        }

        $watchers = $this->entityTypeManager->getStorage('ticket_watcher')->loadMultiple($watcherIds);
        foreach ($watchers as $watcher) {
          $watcherUid = $watcher->get('uid')->target_id;
          if (in_array($watcherUid, $canonicalWatcherUids)) {
            // Already watching canonical — delete the duplicate watcher.
            $watcher->delete();
          }
          else {
            // Move watcher to canonical.
            $watcher->set('ticket_id', $canonical->id());
            $watcher->save();
            $canonicalWatcherUids[] = $watcherUid;
          }
        }
      }

      // Add system message to canonical noting the merge.
      $this->ticketService->addMessage(
        $canonical,
        "Ticket #{$duplicate->getTicketNumber()} merged into this ticket.",
        'system',
      );

      // Mark duplicate as merged.
      $duplicate->set('status', 'merged');
      $duplicate->set('merged_into_id', $canonical->id());
      $duplicate->save();

      // Log the merge event on canonical.
      $this->ticketService->logEvent(
        $canonical,
        'ticket_merged',
        NULL,
        'system',
        $duplicate->getTicketNumber(),
        $canonical->getTicketNumber(),
        [
          'duplicate_id' => $duplicate->id(),
          'messages_moved' => count($messageIds),
          'attachments_moved' => count($attachmentIds),
          'watchers_processed' => count($watcherIds),
        ],
      );

      $this->logger->info('Ticket #@dup (ID: @dupId) merged into ticket #@canon (ID: @canonId). Moved @msgs messages, @atts attachments, @watchers watchers.', [
        '@dup' => $duplicate->getTicketNumber(),
        '@dupId' => $duplicate->id(),
        '@canon' => $canonical->getTicketNumber(),
        '@canonId' => $canonical->id(),
        '@msgs' => count($messageIds),
        '@atts' => count($attachmentIds),
        '@watchers' => count($watcherIds),
      ]);
    }
    catch (\InvalidArgumentException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to merge ticket @dup into @canon: @msg', [
        '@dup' => $duplicate->id(),
        '@canon' => $canonical->id(),
        '@msg' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
