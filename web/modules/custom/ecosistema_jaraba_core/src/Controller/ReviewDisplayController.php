<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use Drupal\ecosistema_jaraba_core\Service\ReviewModerationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador publico para mostrar resenas de cualquier vertical.
 *
 * Renderiza la pagina de resenas para un target entity (producto, proveedor,
 * curso, sesion). Reutilizable por todas las verticales.
 *
 * REV-PHASE4: Controlador frontend de resenas.
 */
class ReviewDisplayController extends ControllerBase {

  /**
   * Mapeo de vertical canonica a tipo de entidad de resena.
   */
  private const VERTICAL_REVIEW_MAP = [
    'comercioconecta' => 'comercio_review',
    'agroconecta' => 'review_agro',
    'serviciosconecta' => 'review_servicios',
    'formacion' => 'course_review',
    'mentoring' => 'session_review',
  ];

  /**
   * Mapeo de vertical canonica a tipo de entidad target.
   */
  private const VERTICAL_TARGET_MAP = [
    'comercioconecta' => 'merchant_profile',
    'agroconecta' => 'producer_profile',
    'serviciosconecta' => 'provider_profile',
    'formacion' => 'lms_course',
    'mentoring' => 'mentoring_session',
  ];

  /**
   * Mapeo de target entity type a review entity type.
   *
   * REV-PHASE6: Usado por el widget GrapesJS para resolver stats.
   */
  private const TARGET_TO_REVIEW_MAP = [
    'merchant_profile' => 'comercio_review',
    'producer_profile' => 'review_agro',
    'provider_profile' => 'review_servicios',
    'lms_course' => 'course_review',
    'mentoring_session' => 'session_review',
  ];

  public function __construct(
    protected readonly ReviewAggregationService $aggregationService,
    protected readonly ReviewModerationService $moderationService,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.review_aggregation'),
      $container->get('ecosistema_jaraba_core.review_moderation'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Pagina publica de resenas para una entidad target.
   *
   * @param string $vertical
   *   Vertical canonica (VERTICAL-CANONICAL-001).
   * @param string $entity_type
   *   Tipo de entidad target.
   * @param int $entity_id
   *   ID de la entidad target.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return array
   *   Render array.
   */
  public function reviewsPage(string $vertical, string $entity_type, int $entity_id, Request $request): array {
    $reviewEntityType = self::VERTICAL_REVIEW_MAP[$vertical] ?? NULL;
    if ($reviewEntityType === NULL) {
      return ['#markup' => $this->t('Vertical no soportada.')];
    }

    // Cargar entidad target.
    $targetEntity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
    if ($targetEntity === NULL) {
      return ['#markup' => $this->t('Entidad no encontrada.')];
    }

    // Estadisticas de rating.
    $stats = $this->aggregationService->getRatingStats($reviewEntityType, $entity_type, $entity_id);

    // Paginacion.
    $page = max(0, (int) $request->query->get('page', 0));
    $perPage = 10;
    $reviews = $this->loadApprovedReviewsPaginated($reviewEntityType, $entity_type, $entity_id, $perPage, $page * $perPage);

    // Renderizar como slide-panel si es peticion AJAX.
    $build = [
      '#theme' => 'reviews_page',
      '#target_entity' => $targetEntity,
      '#vertical' => $vertical,
      '#stats' => $stats,
      '#reviews' => $reviews,
      '#current_page' => $page,
      '#per_page' => $perPage,
      '#cache' => [
        'tags' => ["review_stats:{$entity_type}:{$entity_id}"],
        'contexts' => ['url.query_args:page'],
      ],
    ];

    // SLIDE-PANEL-RENDER-001: Si es slide-panel, renderizar sin BigPipe.
    if ($this->isSlidePanelRequest($request)) {
      $build['#theme'] = 'reviews_page__slide_panel';
    }

    return $build;
  }

  /**
   * Title callback para la pagina de resenas.
   */
  public function reviewsPageTitle(string $vertical, string $entity_type, int $entity_id): string {
    $targetEntity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
    $label = $targetEntity ? ($targetEntity->label() ?? '') : '';
    return (string) $this->t('Resenas de @name', ['@name' => $label]);
  }

  /**
   * Carga resenas aprobadas con paginacion.
   */
  protected function loadApprovedReviewsPaginated(string $reviewEntityType, string $targetEntityType, int $targetEntityId, int $limit, int $offset): array {
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

    $statusField = $statusFieldMap[$reviewEntityType] ?? 'review_status';
    $target = $targetFieldMap[$reviewEntityType] ?? NULL;
    if ($target === NULL) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager()->getStorage($reviewEntityType);
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition($statusField, 'approved')
        ->condition($target['id_field'], $targetEntityId)
        ->sort('created', 'DESC')
        ->range($offset, $limit);

      if ($target['type_field'] !== NULL) {
        $query->condition($target['type_field'], $targetEntityType);
      }

      $ids = $query->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * API endpoint: estadisticas de resenas para un target entity.
   *
   * REV-PHASE6: Consumido por el widget GrapesJS para preview en editor.
   *
   * GET /api/v1/reviews/stats/{target_entity_type}/{target_entity_id}
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con average, count, distribution.
   */
  public function apiStats(string $target_entity_type, int $target_entity_id): JsonResponse {
    $reviewEntityType = self::TARGET_TO_REVIEW_MAP[$target_entity_type] ?? NULL;
    if ($reviewEntityType === NULL) {
      return new JsonResponse(['error' => 'Unsupported entity type.'], 400);
    }

    try {
      $stats = $this->aggregationService->getRatingStats($reviewEntityType, $target_entity_type, $target_entity_id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'average' => $stats['average'] ?? 0,
          'count' => $stats['count'] ?? 0,
          'distribution' => $stats['distribution'] ?? [],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error fetching stats.'], 500);
    }
  }

  /**
   * Detecta si la peticion es slide-panel.
   *
   * SLIDE-PANEL-RENDER-001.
   */
  private function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest()
      && !$request->query->has('_wrapper_format');
  }

}
