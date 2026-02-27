<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * B-14: Review import/export service.
 *
 * Provides CSV/JSON import and export of reviews for bulk operations,
 * data migration, and third-party platform integration.
 */
class ReviewImportExportService {

  /**
   * Review entity types.
   */
  private const REVIEW_TYPES = [
    'comercio_review',
    'review_agro',
    'review_servicios',
    'session_review',
    'course_review',
  ];

  /**
   * Rating field map.
   */
  private const RATING_FIELD_MAP = [
    'comercio_review' => 'rating',
    'review_agro' => 'rating',
    'review_servicios' => 'rating',
    'session_review' => 'rating',
    'course_review' => 'rating',
  ];

  /**
   * Status field map.
   */
  private const STATUS_FIELD_MAP = [
    'comercio_review' => 'status',
    'review_agro' => 'status',
    'review_servicios' => 'status',
    'session_review' => 'review_status',
    'course_review' => 'review_status',
  ];

  /**
   * Body field map — campo de texto principal por tipo de entidad.
   */
  private const BODY_FIELD_MAP = [
    'comercio_review' => 'body',
    'review_agro' => 'comment',
    'review_servicios' => 'comment',
    'session_review' => 'body',
    'course_review' => 'body',
  ];

  /**
   * Target ID field map — campo que referencia a la entidad objetivo.
   */
  private const TARGET_FIELD_MAP = [
    'comercio_review' => 'entity_id_ref',
    'review_agro' => 'target_entity_id',
    'review_servicios' => 'provider_id',
    'session_review' => 'session_id',
    'course_review' => 'course_id',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Export reviews to CSV format.
   *
   * @param string|null $entityType
   *   Specific entity type or NULL for all.
   * @param array $filters
   *   Optional filters (status, date_from, date_to, rating_min).
   *
   * @return array
   *   Array of rows, each row is an associative array.
   */
  public function exportCsv(?string $entityType = NULL, array $filters = []): array {
    $types = $entityType ? [$entityType] : self::REVIEW_TYPES;
    $rows = [];

    foreach ($types as $type) {
      if (!in_array($type, self::REVIEW_TYPES, TRUE)) {
        continue;
      }

      try {
        $storage = $this->entityTypeManager->getStorage($type);
        $query = $storage->getQuery()->accessCheck(FALSE);

        // Apply filters.
        $statusField = self::STATUS_FIELD_MAP[$type];
        if (!empty($filters['status'])) {
          $query->condition($statusField, $filters['status']);
        }
        if (!empty($filters['date_from'])) {
          $query->condition('created', (int) $filters['date_from'], '>=');
        }
        if (!empty($filters['date_to'])) {
          $query->condition('created', (int) $filters['date_to'], '<=');
        }

        $query->sort('created', 'DESC');
        $ids = $query->execute();

        if (empty($ids)) {
          continue;
        }

        $entities = $storage->loadMultiple($ids);
        $ratingField = self::RATING_FIELD_MAP[$type];

        foreach ($entities as $entity) {
          $body = '';
          foreach (['body', 'comment', 'review_body'] as $bf) {
            if ($entity->hasField($bf) && !$entity->get($bf)->isEmpty()) {
              $body = (string) ($entity->get($bf)->value ?? '');
              break;
            }
          }

          $rows[] = [
            'entity_type' => $type,
            'id' => $entity->id(),
            'uid' => $entity->hasField('uid') ? (int) ($entity->get('uid')->target_id ?? 0) : 0,
            'rating' => $entity->hasField($ratingField) ? (int) ($entity->get($ratingField)->value ?? 0) : 0,
            'status' => $entity->hasField($statusField) ? (string) ($entity->get($statusField)->value ?? '') : '',
            'body' => $body,
            'sentiment' => $entity->hasField('sentiment') ? (string) ($entity->get('sentiment')->value ?? '') : '',
            'authenticity_score' => $entity->hasField('authenticity_score') ? (float) ($entity->get('authenticity_score')->value ?? 0) : 0,
            'created' => (int) ($entity->get('created')->value ?? 0),
          ];
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Export failed for @type: @msg', ['@type' => $type, '@msg' => $e->getMessage()]);
      }
    }

    return $rows;
  }

  /**
   * Export reviews to JSON format.
   */
  public function exportJson(?string $entityType = NULL, array $filters = []): string {
    $rows = $this->exportCsv($entityType, $filters);
    return json_encode([
      'version' => '1.0',
      'exported_at' => date('c'),
      'total' => count($rows),
      'reviews' => $rows,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Import reviews from CSV data.
   *
   * @param array $rows
   *   Array of associative arrays with keys: entity_type, rating, body, uid, status.
   *
   * @return array
   *   ['imported' => int, 'skipped' => int, 'errors' => string[]]
   */
  public function importFromArray(array $rows): array {
    $imported = 0;
    $skipped = 0;
    $errors = [];

    foreach ($rows as $i => $row) {
      $type = $row['entity_type'] ?? '';
      if (!in_array($type, self::REVIEW_TYPES, TRUE)) {
        $errors[] = "Row $i: invalid entity_type '$type'";
        $skipped++;
        continue;
      }

      try {
        $storage = $this->entityTypeManager->getStorage($type);
        $ratingField = self::RATING_FIELD_MAP[$type];
        $statusField = self::STATUS_FIELD_MAP[$type];

        $uid = (int) ($row['uid'] ?? 0);
        $targetId = (int) ($row['target_id'] ?? 0);

        // Deduplication: skip if user already reviewed the same target.
        if ($uid > 0 && $targetId > 0) {
          $targetField = self::TARGET_FIELD_MAP[$type] ?? NULL;
          if ($targetField !== NULL) {
            $existing = $storage->getQuery()
              ->accessCheck(FALSE)
              ->condition('uid', $uid)
              ->condition($targetField, $targetId)
              ->count()
              ->execute();
            if ((int) $existing > 0) {
              $errors[] = "Row $i: duplicate — uid $uid already reviewed target $targetId";
              $skipped++;
              continue;
            }
          }
        }

        $values = [
          $ratingField => (int) ($row['rating'] ?? 3),
          $statusField => $row['status'] ?? 'pending',
        ];

        if ($uid > 0) {
          $values['uid'] = $uid;
        }

        if ($targetId > 0) {
          $targetField = self::TARGET_FIELD_MAP[$type] ?? NULL;
          if ($targetField !== NULL) {
            $values[$targetField] = $targetId;
          }
        }

        // Set body on the correct field for this entity type.
        $body = $row['body'] ?? '';
        $bodyField = self::BODY_FIELD_MAP[$type] ?? 'body';
        $values[$bodyField] = $body;

        $entity = $storage->create($values);
        $entity->save();
        $imported++;
      }
      catch (\Exception $e) {
        $errors[] = "Row $i: " . $e->getMessage();
        $skipped++;
      }
    }

    return [
      'imported' => $imported,
      'skipped' => $skipped,
      'errors' => $errors,
    ];
  }

  /**
   * Parse CSV string into rows.
   */
  public function parseCsv(string $csvContent): array {
    $rows = [];
    $lines = explode("\n", $csvContent);

    if (count($lines) < 2) {
      return $rows;
    }

    $headers = str_getcsv(array_shift($lines));

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') {
        continue;
      }

      $values = str_getcsv($line);
      if (count($values) === count($headers)) {
        $rows[] = array_combine($headers, $values);
      }
    }

    return $rows;
  }

}
