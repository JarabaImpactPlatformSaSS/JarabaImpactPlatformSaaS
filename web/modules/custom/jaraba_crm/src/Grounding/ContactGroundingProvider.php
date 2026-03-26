<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider for CRM contacts.
 *
 * Exposes contact data to the AI grounding system for sales assistance.
 */
class ContactGroundingProvider implements GroundingProviderInterface {

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
      $storage = $this->entityTypeManager->getStorage('crm_contact');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('first_name', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('last_name', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('email', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('job_title', '%' . $keyword . '%', 'LIKE');
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
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }

      $firstName = (string) ($entity->get('first_name')->value ?? '');
      $lastName = (string) ($entity->get('last_name')->value ?? '');
      $title = trim($firstName . ' ' . $lastName);
      $jobTitle = (string) ($entity->get('job_title')->value ?? '');
      if ($jobTitle !== '') {
        $title .= ' — ' . $jobTitle;
      }

      $email = (string) ($entity->get('email')->value ?? '');
      $source = (string) ($entity->get('source')->value ?? '');
      $engagement = (int) ($entity->get('engagement_score')->value ?? 0);

      $summary = sprintf(
        'Contacto: %s %s. Email: %s. Fuente: %s. Engagement: %d/100.',
        $firstName,
        $lastName,
        $email,
        $source,
        $engagement,
      );

      $results[] = [
        'title' => $title,
        'summary' => mb_substr($summary, 0, 200),
        'url' => '/crm',
        'type' => 'Contacto CRM — Ventas',
        'metadata' => [
          'email' => $email,
          'source' => $source,
          'engagement_score' => $engagement,
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 55;
  }

}
