<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Grounding;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;

/**
 * Grounding provider for support tickets.
 *
 * Exposes resolved ticket data to the AI for knowledge-base powered answers.
 */
class SupportTicketGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('support_ticket');
    }
    catch (\Throwable) {
      return [];
    }

    // Search resolved tickets for knowledge base.
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', ['resolved', 'closed'], 'IN')
      ->sort('changed', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('subject', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('description', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('category', '%' . $keyword . '%', 'LIKE');
      }
      $query->condition($orGroup);
    }

    $ids = $query->execute();
    if ($ids === []) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      if (!$entity instanceof SupportTicketInterface) {
        continue;
      }

      $subject = (string) ($entity->get('subject')->value ?? '');
      $description = (string) ($entity->get('description')->value ?? '');
      $category = (string) ($entity->get('category')->value ?? '');
      $ticketNumber = $entity->getTicketNumber();

      $summary = sprintf(
        '[%s] %s — Categoria: %s. %s',
        $ticketNumber,
        $subject,
        $category,
        mb_substr(strip_tags($description), 0, 120),
      );

      $results[] = [
        'title' => $subject,
        'summary' => mb_substr($summary, 0, 200),
        'url' => '/soporte/ticket/' . $entity->id(),
        'type' => 'Ticket resuelto — Soporte',
        'metadata' => [
          'ticket_number' => $ticketNumber,
          'category' => $category,
          'resolution_status' => $entity->getStatus(),
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 50;
  }

}
