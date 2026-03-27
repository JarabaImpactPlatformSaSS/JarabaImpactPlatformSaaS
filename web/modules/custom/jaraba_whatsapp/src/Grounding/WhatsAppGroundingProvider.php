<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider for WhatsApp conversations.
 *
 * GROUNDING-PROVIDER-001: Provides WhatsApp context to cascading search.
 */
class WhatsAppGroundingProvider implements GroundingProviderInterface {

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
    if (!$this->entityTypeManager->hasDefinition('wa_conversation')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('wa_conversation');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', ['active', 'escalated'], 'IN')
      ->sort('last_message_at', 'DESC')
      ->range(0, $limit);

    $ids = $query->execute();
    if ($ids === []) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }

      $leadType = (string) ($entity->get('lead_type')->value ?? 'sin_clasificar');
      $status = (string) ($entity->get('status')->value ?? 'active');
      $phone = (string) ($entity->get('wa_phone')->value ?? '');

      $results[] = [
        'title' => 'Conversacion WhatsApp #' . $entity->id(),
        'summary' => sprintf(
          'Lead: %s | Estado: %s | Mensajes: %d | Tel: ...%s',
          $leadType,
          $status,
          (int) ($entity->get('message_count')->value ?? 0),
          mb_substr($phone, -4),
        ),
        'url' => '/whatsapp/conversation/' . $entity->id(),
        'type' => 'Conversacion WhatsApp',
        'metadata' => [
          'lead_type' => $leadType,
          'status' => $status,
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 30;
  }

  /**
   * {@inheritdoc}
   */
  public function searchContext(array $keywords, int $limit = 3): array {
    return $this->search($keywords, $limit);
  }

}
