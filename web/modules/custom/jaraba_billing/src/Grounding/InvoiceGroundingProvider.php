<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider for billing invoices.
 *
 * Exposes invoice data to the AI grounding system for financial context.
 */
class InvoiceGroundingProvider implements GroundingProviderInterface {

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
      $storage = $this->entityTypeManager->getStorage('billing_invoice');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('invoice_number', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('status', $keyword);
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

      $number = (string) ($entity->get('invoice_number')->value ?? '');
      $status = (string) ($entity->get('status')->value ?? '');
      $total = (float) ($entity->get('total')->value ?? 0);
      $currency = (string) ($entity->get('currency')->value ?? 'EUR');

      $summary = sprintf(
        'Factura %s — Estado: %s, Total: %.2f %s',
        $number,
        $status,
        $total,
        $currency,
      );

      $results[] = [
        'title' => 'Factura ' . $number,
        'summary' => mb_substr($summary, 0, 200),
        'url' => '/billing/dashboard',
        'type' => 'Factura — Billing',
        'metadata' => [
          'invoice_number' => $number,
          'status' => $status,
          'total' => $total,
          'currency' => $currency,
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 45;
  }

}
