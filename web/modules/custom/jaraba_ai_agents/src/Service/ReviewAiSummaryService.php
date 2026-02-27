<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Genera resumenes de resenas con IA (Haiku 4.5 via ModelRouter).
 *
 * Recopila las ultimas 20 resenas aprobadas, construye un prompt y genera
 * un resumen de 2-3 frases. Servicio opcional: si el proveedor de IA no
 * esta disponible, retorna NULL sin error (PRESAVE-RESILIENCE-001).
 *
 * REV-PHASE3: Servicio 5 de 5 transversales.
 */
class ReviewAiSummaryService {

  /**
   * Prompt template para resumir resenas.
   */
  private const SUMMARY_PROMPT = <<<'PROMPT'
Eres un asistente que resume resenas de clientes. Genera un resumen conciso (2-3 frases) en espanol de las siguientes resenas. Destaca los aspectos mas mencionados (positivos y negativos). No inventes informacion — solo resume lo que dicen los usuarios.

Resenas:
{reviews_text}

Resumen:
PROMPT;

  /**
   * Campo de estado de moderacion por tipo de entidad.
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
   * Campo de target por tipo de entidad.
   */
  private const TARGET_FIELD_MAP = [
    'comercio_review' => ['type_field' => 'entity_type_ref', 'id_field' => 'entity_id_ref'],
    'review_agro' => ['type_field' => 'target_entity_type', 'id_field' => 'target_entity_id'],
    'review_servicios' => ['type_field' => NULL, 'id_field' => 'provider_id'],
    'session_review' => ['type_field' => NULL, 'id_field' => 'session_id'],
    'course_review' => ['type_field' => NULL, 'id_field' => 'course_id'],
    'content_comment' => ['type_field' => NULL, 'id_field' => 'article_id'],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModelRouterService $modelRouter,
    protected readonly LoggerInterface $logger,
    protected readonly mixed $aiProvider = NULL,
  ) {}

  /**
   * Genera un resumen IA de resenas para un target.
   *
   * @param string $reviewEntityTypeId
   *   Tipo de entidad de resena (e.g., 'comercio_review').
   * @param string $targetEntityType
   *   Tipo de entidad target.
   * @param int $targetEntityId
   *   ID del target.
   * @param string $locale
   *   Idioma del resumen.
   *
   * @return string|null
   *   Resumen generado, o NULL si IA no disponible.
   */
  public function generateSummary(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId, string $locale = 'es'): ?string {
    if ($this->aiProvider === NULL) {
      return NULL;
    }

    $reviews = $this->loadRecentApprovedReviews($reviewEntityTypeId, $targetEntityType, $targetEntityId, 20);
    if (empty($reviews)) {
      return NULL;
    }

    $reviewsText = $this->buildReviewsText($reviews);
    $prompt = str_replace('{reviews_text}', $reviewsText, self::SUMMARY_PROMPT);

    try {
      $modelConfig = $this->modelRouter->route('fast');
      $modelId = $modelConfig['model_id'] ?? 'claude-haiku-4-5-20251001';

      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
      ]);

      $response = $this->aiProvider->chat($input, $modelId);
      $summary = trim($response->getNormalized()->getText());

      if (empty($summary)) {
        return NULL;
      }

      return $summary;
    }
    catch (\Exception $e) {
      $this->logger->warning('AI summary generation failed for @type targeting @target @id: @msg', [
        '@type' => $reviewEntityTypeId,
        '@target' => $targetEntityType,
        '@id' => $targetEntityId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Regenera resumenes obsoletos (para cron).
   *
   * @param int $maxAge
   *   Edad maxima en segundos antes de considerar obsoleto (default: 7 dias).
   *
   * @return int
   *   Numero de resumenes regenerados.
   */
  public function regenerateStale(int $maxAge = 604800): int {
    if ($this->aiProvider === NULL) {
      return 0;
    }

    $regenerated = 0;
    $threshold = time() - $maxAge;

    foreach (self::STATUS_FIELD_MAP as $entityTypeId => $statusField) {
      try {
        $storage = $this->entityTypeManager->getStorage($entityTypeId);

        // Buscar entidades con ai_summary_generated_at < threshold.
        $query = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('ai_summary_generated_at', $threshold, '<')
          ->condition('ai_summary_generated_at', 0, '>')
          ->range(0, 10);

        $ids = $query->execute();
        if (empty($ids)) {
          continue;
        }

        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          $targetInfo = $this->resolveTarget($entity, $entityTypeId);
          if ($targetInfo === NULL) {
            continue;
          }

          [$targetEntityType, $targetEntityId] = $targetInfo;
          $summary = $this->generateSummary($entityTypeId, $targetEntityType, $targetEntityId);

          if ($summary !== NULL) {
            $entity->set('ai_summary', $summary);
            $entity->set('ai_summary_generated_at', time());
            $entity->save();
            $regenerated++;
          }
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Error regenerating stale AI summaries for @type: @msg', [
          '@type' => $entityTypeId,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    return $regenerated;
  }

  /**
   * Carga las ultimas N resenas aprobadas para un target.
   */
  protected function loadRecentApprovedReviews(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId, int $limit): array {
    $statusField = self::STATUS_FIELD_MAP[$reviewEntityTypeId] ?? 'review_status';
    $targetMapping = self::TARGET_FIELD_MAP[$reviewEntityTypeId] ?? NULL;

    if ($targetMapping === NULL) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage($reviewEntityTypeId);
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($statusField, 'approved')
        ->condition($targetMapping['id_field'], $targetEntityId)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if ($targetMapping['type_field'] !== NULL) {
        $query->condition($targetMapping['type_field'], $targetEntityType);
      }

      $ids = $query->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Construye texto concatenado de resenas para el prompt.
   */
  protected function buildReviewsText(array $reviews): string {
    $lines = [];
    $i = 1;

    foreach ($reviews as $review) {
      $body = '';
      foreach (['body', 'comment', 'review_body'] as $field) {
        if ($review->hasField($field) && !$review->get($field)->isEmpty()) {
          $body = strip_tags((string) $review->get($field)->value);
          break;
        }
      }

      $rating = 0;
      foreach (['rating', 'overall_rating'] as $field) {
        if ($review->hasField($field) && !$review->get($field)->isEmpty()) {
          $rating = (int) $review->get($field)->value;
          break;
        }
      }

      if (!empty($body)) {
        $lines[] = "{$i}. [{$rating}/5] {$body}";
        $i++;
      }
    }

    return implode("\n", $lines);
  }

  /**
   * Resuelve el target de una entidad de resena.
   */
  protected function resolveTarget(object $entity, string $entityTypeId): ?array {
    $mapping = self::TARGET_FIELD_MAP[$entityTypeId] ?? NULL;
    if ($mapping === NULL) {
      return NULL;
    }

    $idField = $mapping['id_field'];
    if (!$entity->hasField($idField) || $entity->get($idField)->isEmpty()) {
      return NULL;
    }

    $targetEntityId = (int) ($entity->get($idField)->target_id ?? $entity->get($idField)->value ?? 0);
    if ($targetEntityId === 0) {
      return NULL;
    }

    if ($mapping['type_field'] !== NULL) {
      $targetEntityType = $entity->get($mapping['type_field'])->value ?? NULL;
      if ($targetEntityType === NULL) {
        return NULL;
      }
    }
    else {
      $targetEntityType = $mapping['fixed_type'] ?? 'unknown';
    }

    return [$targetEntityType, $targetEntityId];
  }

}
