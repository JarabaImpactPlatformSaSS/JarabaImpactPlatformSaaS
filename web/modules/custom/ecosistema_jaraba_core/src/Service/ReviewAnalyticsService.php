<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analiticas de resenas.
 *
 * Proporciona metricas agregadas: volumen, tendencias, distribucion,
 * tasa de respuesta, datos por vertical y por tenant.
 *
 * B-05: Review Analytics Dashboard.
 */
class ReviewAnalyticsService {

  /**
   * Tipos de entidad de resena soportados.
   */
  private const REVIEW_TYPES = [
    'comercio_review',
    'review_agro',
    'review_servicios',
    'session_review',
    'course_review',
    'content_comment',
  ];

  /**
   * Campo de estado por tipo de entidad.
   */
  private const STATUS_FIELD_MAP = [
    'comercio_review' => 'status',
    'review_agro' => 'state',
    'review_servicios' => 'status',
    'session_review' => 'review_status',
    'course_review' => 'review_status',
    'content_comment' => 'review_status',
  ];

  /**
   * Campo de rating por tipo de entidad.
   */
  private const RATING_FIELD_MAP = [
    'comercio_review' => 'rating',
    'review_agro' => 'rating',
    'review_servicios' => 'rating',
    'session_review' => 'overall_rating',
    'course_review' => 'rating',
  ];

  /**
   * Nombre de vertical por tipo de entidad.
   */
  private const VERTICAL_MAP = [
    'comercio_review' => 'ComercioConecta',
    'review_agro' => 'AgroConecta',
    'review_servicios' => 'ServiciosConecta',
    'session_review' => 'Mentoring',
    'course_review' => 'Formacion',
    'content_comment' => 'Content Hub',
  ];

  /**
   * Campo de respuesta del propietario por tipo.
   */
  private const RESPONSE_FIELD_MAP = [
    'comercio_review' => 'merchant_response',
    'review_agro' => 'response',
    'review_servicios' => 'provider_response',
    'session_review' => NULL,
    'course_review' => 'instructor_response',
    'content_comment' => NULL,
  ];

  public function __construct(
    protected readonly Connection $database,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene metricas globales del dashboard.
   *
   * @param string|null $vertical
   *   Filtro por vertical (entity type ID) o NULL para todas.
   * @param int|null $tenantGroupId
   *   Filtro por tenant group ID o NULL para todos.
   * @param int $days
   *   Periodo en dias para tendencias.
   *
   * @return array
   *   Metricas completas del dashboard.
   */
  public function getDashboardMetrics(?string $vertical = NULL, ?int $tenantGroupId = NULL, int $days = 30): array {
    $types = $vertical !== NULL ? [$vertical] : self::REVIEW_TYPES;

    return [
      'summary' => $this->getSummaryMetrics($types, $tenantGroupId),
      'trend' => $this->getRatingTrend($types, $tenantGroupId, $days),
      'distribution' => $this->getGlobalDistribution($types, $tenantGroupId),
      'by_vertical' => $this->getMetricsByVertical($tenantGroupId),
      'response_rate' => $this->getResponseRate($types, $tenantGroupId),
      'recent_reviews' => $this->getRecentReviews($types, $tenantGroupId, 10),
      'moderation_queue' => $this->getModerationQueueSize($types, $tenantGroupId),
    ];
  }

  /**
   * Metricas de resumen.
   */
  public function getSummaryMetrics(array $types, ?int $tenantGroupId): array {
    $total = 0;
    $approved = 0;
    $pending = 0;
    $ratingSum = 0;
    $ratingCount = 0;

    foreach ($types as $type) {
      $statusField = self::STATUS_FIELD_MAP[$type] ?? 'review_status';
      $ratingField = self::RATING_FIELD_MAP[$type] ?? NULL;

      try {
        if (!$this->database->schema()->tableExists($type)) {
          continue;
        }

        $query = $this->database->select($type, 'r');
        if ($tenantGroupId !== NULL && $this->database->schema()->fieldExists($type, 'tenant_id')) {
          $query->condition('r.tenant_id', $tenantGroupId);
        }
        $query->addExpression('COUNT(*)', 'total');
        $query->addExpression("SUM(CASE WHEN r.{$statusField} = 'approved' THEN 1 ELSE 0 END)", 'approved');
        $query->addExpression("SUM(CASE WHEN r.{$statusField} = 'pending' THEN 1 ELSE 0 END)", 'pending');

        if ($ratingField !== NULL) {
          $query->addExpression("SUM(CASE WHEN r.{$statusField} = 'approved' THEN r.{$ratingField} ELSE 0 END)", 'rating_sum');
          $query->addExpression("SUM(CASE WHEN r.{$statusField} = 'approved' AND r.{$ratingField} > 0 THEN 1 ELSE 0 END)", 'rating_count');
        }

        $row = $query->execute()->fetch();
        if ($row) {
          $total += (int) $row->total;
          $approved += (int) $row->approved;
          $pending += (int) $row->pending;
          if ($ratingField !== NULL) {
            $ratingSum += (int) $row->rating_sum;
            $ratingCount += (int) $row->rating_count;
          }
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Analytics query error for @type: @msg', [
          '@type' => $type,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    return [
      'total_reviews' => $total,
      'approved_reviews' => $approved,
      'pending_reviews' => $pending,
      'average_rating' => $ratingCount > 0 ? round($ratingSum / $ratingCount, 2) : 0.0,
      'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0.0,
    ];
  }

  /**
   * Tendencia de rating promedio por dia.
   */
  public function getRatingTrend(array $types, ?int $tenantGroupId, int $days): array {
    $trend = [];
    $startTimestamp = strtotime("-{$days} days");

    foreach ($types as $type) {
      $statusField = self::STATUS_FIELD_MAP[$type] ?? 'review_status';
      $ratingField = self::RATING_FIELD_MAP[$type] ?? NULL;
      if ($ratingField === NULL) {
        continue;
      }

      try {
        if (!$this->database->schema()->tableExists($type)) {
          continue;
        }

        $query = $this->database->select($type, 'r')
          ->condition('r.' . $statusField, 'approved')
          ->condition('r.created', $startTimestamp, '>=');

        if ($tenantGroupId !== NULL && $this->database->schema()->fieldExists($type, 'tenant_id')) {
          $query->condition('r.tenant_id', $tenantGroupId);
        }

        $query->addExpression("FROM_UNIXTIME(r.created, '%Y-%m-%d')", 'day');
        $query->addExpression("AVG(r.{$ratingField})", 'avg_rating');
        $query->addExpression("COUNT(*)", 'count');
        $query->groupBy("FROM_UNIXTIME(r.created, '%Y-%m-%d')");
        $query->orderBy('day');

        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          if (!isset($trend[$row->day])) {
            $trend[$row->day] = ['rating_sum' => 0, 'count' => 0];
          }
          $trend[$row->day]['rating_sum'] += (float) $row->avg_rating * (int) $row->count;
          $trend[$row->day]['count'] += (int) $row->count;
        }
      }
      catch (\Exception) {
        continue;
      }
    }

    $formatted = [];
    foreach ($trend as $day => $data) {
      $formatted[] = [
        'date' => $day,
        'average_rating' => $data['count'] > 0 ? round($data['rating_sum'] / $data['count'], 2) : 0,
        'count' => $data['count'],
      ];
    }

    return $formatted;
  }

  /**
   * Distribucion global de estrellas.
   */
  public function getGlobalDistribution(array $types, ?int $tenantGroupId): array {
    $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

    foreach ($types as $type) {
      $statusField = self::STATUS_FIELD_MAP[$type] ?? 'review_status';
      $ratingField = self::RATING_FIELD_MAP[$type] ?? NULL;
      if ($ratingField === NULL) {
        continue;
      }

      try {
        if (!$this->database->schema()->tableExists($type)) {
          continue;
        }

        $query = $this->database->select($type, 'r')
          ->condition('r.' . $statusField, 'approved');

        if ($tenantGroupId !== NULL && $this->database->schema()->fieldExists($type, 'tenant_id')) {
          $query->condition('r.tenant_id', $tenantGroupId);
        }

        $query->addExpression("r.{$ratingField}", 'rating');
        $query->addExpression("COUNT(*)", 'cnt');
        $query->groupBy("r.{$ratingField}");

        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $rating = (int) $row->rating;
          if ($rating >= 1 && $rating <= 5) {
            $distribution[$rating] += (int) $row->cnt;
          }
        }
      }
      catch (\Exception) {
        continue;
      }
    }

    return $distribution;
  }

  /**
   * Metricas desglosadas por vertical.
   */
  public function getMetricsByVertical(?int $tenantGroupId): array {
    $result = [];

    foreach (self::REVIEW_TYPES as $type) {
      $metrics = $this->getSummaryMetrics([$type], $tenantGroupId);
      $result[] = [
        'entity_type' => $type,
        'vertical_name' => self::VERTICAL_MAP[$type] ?? $type,
        'total' => $metrics['total_reviews'],
        'approved' => $metrics['approved_reviews'],
        'pending' => $metrics['pending_reviews'],
        'average_rating' => $metrics['average_rating'],
      ];
    }

    return $result;
  }

  /**
   * Tasa de respuesta del propietario.
   */
  public function getResponseRate(array $types, ?int $tenantGroupId): array {
    $totalWithResponse = 0;
    $totalApproved = 0;

    foreach ($types as $type) {
      $responseField = self::RESPONSE_FIELD_MAP[$type] ?? NULL;
      if ($responseField === NULL) {
        continue;
      }

      $statusField = self::STATUS_FIELD_MAP[$type] ?? 'review_status';

      try {
        if (!$this->database->schema()->tableExists($type) || !$this->database->schema()->fieldExists($type, $responseField)) {
          continue;
        }

        $query = $this->database->select($type, 'r')
          ->condition('r.' . $statusField, 'approved');

        if ($tenantGroupId !== NULL && $this->database->schema()->fieldExists($type, 'tenant_id')) {
          $query->condition('r.tenant_id', $tenantGroupId);
        }

        $query->addExpression("COUNT(*)", 'total');
        $query->addExpression("SUM(CASE WHEN r.{$responseField} IS NOT NULL AND r.{$responseField} != '' THEN 1 ELSE 0 END)", 'with_response');

        $row = $query->execute()->fetch();
        if ($row) {
          $totalApproved += (int) $row->total;
          $totalWithResponse += (int) $row->with_response;
        }
      }
      catch (\Exception) {
        continue;
      }
    }

    return [
      'total_eligible' => $totalApproved,
      'with_response' => $totalWithResponse,
      'rate' => $totalApproved > 0 ? round(($totalWithResponse / $totalApproved) * 100, 1) : 0.0,
    ];
  }

  /**
   * Resenas recientes.
   */
  public function getRecentReviews(array $types, ?int $tenantGroupId, int $limit): array {
    $reviews = [];

    foreach ($types as $type) {
      try {
        $storage = $this->entityTypeManager->getStorage($type);
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->sort('created', 'DESC')
          ->range(0, $limit);

        if ($tenantGroupId !== NULL) {
          $definition = $this->entityTypeManager->getDefinition($type);
          if ($definition->hasKey('tenant_id') || in_array('tenant_id', array_keys($definition->get('entity_keys') ?? []))) {
            $query->condition('tenant_id', $tenantGroupId);
          }
        }

        $ids = $query->execute();
        if ($ids) {
          $entities = $storage->loadMultiple($ids);
          foreach ($entities as $entity) {
            $reviews[] = [
              'entity_type' => $type,
              'vertical' => self::VERTICAL_MAP[$type] ?? $type,
              'id' => (int) $entity->id(),
              'label' => $entity->label() ?? "#{$entity->id()}",
              'created' => (int) ($entity->get('created')->value ?? 0),
              'status' => method_exists($entity, 'getReviewStatus') ? $entity->getReviewStatus() : 'unknown',
              'rating' => method_exists($entity, 'getReviewRating') ? $entity->getReviewRating() : 0,
            ];
          }
        }
      }
      catch (\Exception) {
        continue;
      }
    }

    // Ordenar por created DESC y limitar.
    usort($reviews, fn($a, $b) => $b['created'] <=> $a['created']);
    return array_slice($reviews, 0, $limit);
  }

  /**
   * Tamano de la cola de moderacion.
   */
  public function getModerationQueueSize(array $types, ?int $tenantGroupId): int {
    $total = 0;

    foreach ($types as $type) {
      $statusField = self::STATUS_FIELD_MAP[$type] ?? 'review_status';

      try {
        if (!$this->database->schema()->tableExists($type)) {
          continue;
        }

        $query = $this->database->select($type, 'r')
          ->condition('r.' . $statusField, 'pending');

        if ($tenantGroupId !== NULL && $this->database->schema()->fieldExists($type, 'tenant_id')) {
          $query->condition('r.tenant_id', $tenantGroupId);
        }

        $query->addExpression("COUNT(*)", 'cnt');
        $total += (int) $query->execute()->fetchField();
      }
      catch (\Exception) {
        continue;
      }
    }

    return $total;
  }

  /**
   * Exporta datos como array para CSV.
   */
  public function exportCsv(array $types, ?int $tenantGroupId): array {
    $rows = [['Vertical', 'ID', 'Rating', 'Status', 'Created', 'Helpful Count', 'Has Response']];

    foreach ($types as $type) {
      try {
        $storage = $this->entityTypeManager->getStorage($type);
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->sort('created', 'DESC');

        $ids = $query->execute();
        if (!$ids) {
          continue;
        }

        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          $responseField = self::RESPONSE_FIELD_MAP[$type] ?? NULL;
          $hasResponse = FALSE;
          if ($responseField !== NULL && $entity->hasField($responseField)) {
            $hasResponse = !$entity->get($responseField)->isEmpty();
          }

          $rows[] = [
            self::VERTICAL_MAP[$type] ?? $type,
            $entity->id(),
            method_exists($entity, 'getReviewRating') ? $entity->getReviewRating() : '',
            method_exists($entity, 'getReviewStatus') ? $entity->getReviewStatus() : '',
            date('Y-m-d H:i', (int) ($entity->get('created')->value ?? 0)),
            $entity->hasField('helpful_count') ? (int) $entity->get('helpful_count')->value : 0,
            $hasResponse ? 'Yes' : 'No',
          ];
        }
      }
      catch (\Exception) {
        continue;
      }
    }

    return $rows;
  }

}
