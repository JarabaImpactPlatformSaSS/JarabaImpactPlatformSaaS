<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Genera JSON-LD Schema.org para AggregateRating y Review[].
 *
 * Inyectar en paginas de detalle via hook_preprocess_html() de cada vertical.
 * Se agrega como <script type="application/ld+json"> en el <head>.
 *
 * REV-PHASE3: Servicio 3 de 5 transversales.
 */
class ReviewSchemaOrgService {

  public function __construct(
    protected readonly ReviewAggregationService $aggregationService,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * Genera el fragmento JSON-LD de AggregateRating.
   *
   * @param string $reviewEntityTypeId
   *   Tipo de entidad de resena.
   * @param string $targetEntityType
   *   Tipo de entidad target.
   * @param int $targetEntityId
   *   ID del target.
   *
   * @return array|null
   *   Array con estructura AggregateRating, o NULL si no hay resenas aprobadas.
   */
  public function generateAggregateRating(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId): ?array {
    $stats = $this->aggregationService->getRatingStats($reviewEntityTypeId, $targetEntityType, $targetEntityId);

    if ($stats['count'] === 0) {
      return NULL;
    }

    return [
      '@type' => 'AggregateRating',
      'ratingValue' => (string) $stats['average'],
      'bestRating' => '5',
      'worstRating' => '1',
      'ratingCount' => (string) $stats['count'],
      'reviewCount' => (string) $stats['count'],
    ];
  }

  /**
   * Genera un array de objetos Review de Schema.org.
   *
   * @param string $reviewEntityTypeId
   *   Tipo de entidad de resena.
   * @param string $targetEntityType
   *   Tipo de entidad target.
   * @param int $targetEntityId
   *   ID del target.
   * @param int $limit
   *   Maximo de resenas a incluir.
   *
   * @return array
   *   Array de objetos Review Schema.org.
   */
  public function generateReviewList(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId, int $limit = 5): array {
    $reviews = $this->loadApprovedReviews($reviewEntityTypeId, $targetEntityType, $targetEntityId, $limit);
    $schemaReviews = [];

    foreach ($reviews as $review) {
      $schemaReview = [
        '@type' => 'Review',
        'datePublished' => date('Y-m-d', (int) $review->get('created')->value),
      ];

      // Autor.
      $authorName = $this->resolveAuthorName($review);
      if ($authorName) {
        $schemaReview['author'] = [
          '@type' => 'Person',
          'name' => $authorName,
        ];
      }

      // Rating.
      $rating = $this->resolveRating($review);
      if ($rating > 0) {
        $schemaReview['reviewRating'] = [
          '@type' => 'Rating',
          'ratingValue' => (string) $rating,
          'bestRating' => '5',
          'worstRating' => '1',
        ];
      }

      // Cuerpo.
      $body = $this->resolveBody($review);
      if ($body) {
        $schemaReview['reviewBody'] = $body;
      }

      $schemaReviews[] = $schemaReview;
    }

    return $schemaReviews;
  }

  /**
   * Ensambla el JSON-LD completo de un producto con AggregateRating y Review[].
   *
   * @param \Drupal\Core\Entity\EntityInterface $product
   *   La entidad target (producto, servicio, curso, etc.).
   * @param array $aggregateRating
   *   Fragmento AggregateRating generado por generateAggregateRating().
   * @param array $reviews
   *   Array de objetos Review generado por generateReviewList().
   *
   * @return array
   *   Estructura JSON-LD completa lista para json_encode().
   */
  public function buildProductJsonLd(EntityInterface $product, array $aggregateRating, array $reviews): array {
    $jsonLd = [
      '@context' => 'https://schema.org',
      '@type' => $this->resolveSchemaType($product),
      'name' => $product->label() ?? '',
      'aggregateRating' => $aggregateRating,
    ];

    // URL canonica.
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $jsonLd['url'] = $request->getSchemeAndHttpHost() . $request->getRequestUri();
    }

    // Descripcion si existe.
    if ($product->hasField('description') && !$product->get('description')->isEmpty()) {
      $jsonLd['description'] = strip_tags((string) $product->get('description')->value);
    }
    elseif ($product->hasField('body') && !$product->get('body')->isEmpty()) {
      $jsonLd['description'] = strip_tags(mb_substr((string) $product->get('body')->value, 0, 200));
    }

    if (!empty($reviews)) {
      $jsonLd['review'] = $reviews;
    }

    return $jsonLd;
  }

  /**
   * Carga resenas aprobadas para un target, ordenadas por fecha descendente.
   */
  protected function loadApprovedReviews(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId, int $limit): array {
    // Mapeo de campos internos.
    $statusFieldMap = [
      'comercio_review' => 'status',
      'review_agro' => 'state',
      'review_servicios' => 'status',
      'session_review' => 'review_status',
      'course_review' => 'review_status',
    ];
    $targetFieldMap = [
      'comercio_review' => ['type_field' => 'entity_type_ref', 'id_field' => 'entity_id_ref'],
      'review_agro' => ['type_field' => 'target_entity_type', 'id_field' => 'target_entity_id'],
      'review_servicios' => ['type_field' => NULL, 'id_field' => 'provider_id'],
      'session_review' => ['type_field' => NULL, 'id_field' => 'session_id'],
      'course_review' => ['type_field' => NULL, 'id_field' => 'course_id'],
    ];

    $statusField = $statusFieldMap[$reviewEntityTypeId] ?? 'review_status';
    $target = $targetFieldMap[$reviewEntityTypeId] ?? NULL;
    if ($target === NULL) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage($reviewEntityTypeId);
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition($statusField, 'approved')
        ->condition($target['id_field'], $targetEntityId)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if ($target['type_field'] !== NULL) {
        $query->condition($target['type_field'], $targetEntityType);
      }

      $ids = $query->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Resuelve el nombre del autor de una resena.
   */
  protected function resolveAuthorName(EntityInterface $review): ?string {
    // Prioridad: author_name (anonimo) > owner display name.
    if ($review->hasField('author_name') && !$review->get('author_name')->isEmpty()) {
      return $review->get('author_name')->value;
    }
    if (method_exists($review, 'getOwner') && $review->getOwner()) {
      $name = $review->getOwner()->getDisplayName();
      return $name ?: NULL;
    }
    return NULL;
  }

  /**
   * Resuelve la puntuacion de rating.
   */
  protected function resolveRating(EntityInterface $review): int {
    foreach (['rating', 'overall_rating'] as $field) {
      if ($review->hasField($field) && !$review->get($field)->isEmpty()) {
        return (int) $review->get($field)->value;
      }
    }
    return 0;
  }

  /**
   * Resuelve el cuerpo de texto de una resena.
   */
  protected function resolveBody(EntityInterface $review): ?string {
    foreach (['body', 'comment', 'review_body'] as $field) {
      if ($review->hasField($field) && !$review->get($field)->isEmpty()) {
        return strip_tags((string) $review->get($field)->value);
      }
    }
    return NULL;
  }

  /**
   * Resuelve el tipo Schema.org para una entidad.
   */
  protected function resolveSchemaType(EntityInterface $product): string {
    // Si la entidad tiene schema_type propio, usarlo.
    if ($product->hasField('schema_type') && !$product->get('schema_type')->isEmpty()) {
      return $product->get('schema_type')->value;
    }

    // Mapeo por tipo de entidad.
    $typeMap = [
      'merchant_profile' => 'LocalBusiness',
      'provider_profile' => 'Service',
      'producer_profile' => 'Product',
      'lms_course' => 'Course',
      'mentoring_session' => 'Event',
    ];

    return $typeMap[$product->getEntityTypeId()] ?? 'Product';
  }

}
