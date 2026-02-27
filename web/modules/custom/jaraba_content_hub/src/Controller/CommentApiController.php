<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewModerationService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API controller para comentarios del Content Hub.
 *
 * Endpoints:
 * - GET /api/v1/content/articles/{article_id}/comments — listar aprobados
 * - POST /api/v1/content/articles/{article_id}/comments — crear comentario
 * - POST /api/v1/content/comments/{comment_id}/moderate — moderar
 * - POST /api/v1/content/comments/{comment_id}/helpful — marcar util
 *
 * REV-PHASE4: API de comentarios del Content Hub.
 */
class CommentApiController extends ControllerBase
{

  /**
   * API-WHITELIST-001: Campos permitidos en la creacion de comentarios.
   */
  private const ALLOWED_CREATE_FIELDS = [
    'body',
    'author_name',
    'author_email',
    'parent_id',
  ];

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
    protected readonly ?ReviewModerationService $moderationService,
    protected readonly ?TenantContextService $tenantContext,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('logger.channel.jaraba_content_hub'),
      $container->has('ecosistema_jaraba_core.review_moderation')
      ? $container->get('ecosistema_jaraba_core.review_moderation')
      : NULL,
      $container->has('ecosistema_jaraba_core.tenant_context')
      ? $container->get('ecosistema_jaraba_core.tenant_context')
      : NULL,
    );
  }

  /**
   * GET: Lista comentarios aprobados de un articulo.
   *
   * Soporta threading: devuelve arbol de comentarios padre/hijo.
   */
  public function list(int $article_id, Request $request): JsonResponse
  {
    $storage = $this->entityTypeManager()->getStorage('content_comment');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('article_id', $article_id)
      ->condition('review_status', 'approved')
      ->sort('created', 'ASC');

    $ids = $query->execute();
    $comments = $ids ? $storage->loadMultiple($ids) : [];

    // Construir arbol de threading.
    $tree = $this->buildCommentTree($comments);

    return new JsonResponse([
      'data' => $tree,
      'meta' => [
        'total' => count($comments),
        'article_id' => $article_id,
      ],
    ]);
  }

  /**
   * POST: Crea un nuevo comentario.
   */
  public function createComment(int $article_id, Request $request): JsonResponse
  {
    // Verificar que el articulo existe.
    $article = $this->entityTypeManager()->getStorage('content_article')->load($article_id);
    if ($article === NULL) {
      return new JsonResponse(['error' => 'Articulo no encontrado.'], Response::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'JSON invalido.'], Response::HTTP_BAD_REQUEST);
    }

    // API-WHITELIST-001: Solo campos permitidos.
    $data = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));

    // Validar campo obligatorio.
    if (empty($data['body'])) {
      return new JsonResponse(['error' => 'El campo body es obligatorio.'], Response::HTTP_BAD_REQUEST);
    }

    // Sanitizar texto.
    $data['body'] = strip_tags(trim($data['body']));
    if (mb_strlen($data['body']) > 5000) {
      return new JsonResponse(['error' => 'El comentario excede los 5000 caracteres.'], Response::HTTP_BAD_REQUEST);
    }

    // Validar parent_id si existe.
    if (!empty($data['parent_id'])) {
      $parent = $this->entityTypeManager()->getStorage('content_comment')->load((int) $data['parent_id']);
      if ($parent === NULL || $parent->getArticleId() !== $article_id) {
        return new JsonResponse(['error' => 'Comentario padre invalido.'], Response::HTTP_BAD_REQUEST);
      }
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('content_comment');
      $values = [
        'article_id' => $article_id,
        'body' => $data['body'],
        'review_status' => 'pending',
      ];

      // Parent (threading).
      if (!empty($data['parent_id'])) {
        $values['parent_id'] = (int) $data['parent_id'];
      }

      // Usuario autenticado vs anonimo.
      if ($this->currentUser->isAuthenticated()) {
        $values['uid'] = $this->currentUser->id();
      } else {
        $values['uid'] = 0;
        if (!empty($data['author_name'])) {
          $values['author_name'] = strip_tags(trim($data['author_name']));
        }
        if (!empty($data['author_email'])) {
          $values['author_email'] = filter_var($data['author_email'], FILTER_VALIDATE_EMAIL) ?: '';
        }
      }

      // Tenant.
      $tenantGroupId = $this->resolveTenantGroupId();
      if ($tenantGroupId > 0) {
        $values['tenant_id'] = $tenantGroupId;
      }

      $comment = $storage->create($values);
      $comment->save();

      return new JsonResponse([
        'data' => [
          'id' => (int) $comment->id(),
          'body' => $comment->get('body')->value,
          'status' => 'pending',
          'created' => date('c', (int) $comment->get('created')->value),
        ],
        'message' => 'Comentario enviado. Pendiente de moderacion.',
      ], Response::HTTP_CREATED);
    } catch (\Exception $e) {
      $this->logger->error('Error creating comment for article @id: @msg', [
        '@id' => $article_id,
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Error al crear el comentario.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * POST: Modera un comentario (aprobar, rechazar, marcar).
   */
  public function moderate(int $comment_id, Request $request): JsonResponse
  {
    if ($this->moderationService === NULL) {
      return new JsonResponse(['error' => 'Servicio de moderacion no disponible.'], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    $data = json_decode($request->getContent(), TRUE);
    $newStatus = $data['status'] ?? '';

    $result = $this->moderationService->moderate('content_comment', $comment_id, $newStatus);

    if (!$result) {
      return new JsonResponse(['error' => 'No se pudo moderar el comentario.'], Response::HTTP_BAD_REQUEST);
    }

    return new JsonResponse([
      'message' => 'Comentario moderado correctamente.',
      'status' => $newStatus,
    ]);
  }

  /**
   * POST: Marca un comentario como util (helpful).
   */
  public function helpful(int $comment_id): JsonResponse
  {
    $storage = $this->entityTypeManager()->getStorage('content_comment');
    $comment = $storage->load($comment_id);

    if ($comment === NULL) {
      return new JsonResponse(['error' => 'Comentario no encontrado.'], Response::HTTP_NOT_FOUND);
    }

    $currentCount = (int) ($comment->get('helpful_count')->value ?? 0);
    $comment->set('helpful_count', $currentCount + 1);
    $comment->save();

    return new JsonResponse([
      'helpful_count' => $currentCount + 1,
    ]);
  }

  /**
   * Construye arbol de threading de comentarios.
   */
  protected function buildCommentTree(array $comments): array
  {
    $flat = [];
    $tree = [];

    foreach ($comments as $comment) {
      $item = [
        'id' => (int) $comment->id(),
        'body' => $comment->get('body')->value,
        'author_name' => $this->resolveAuthorName($comment),
        'created' => date('c', (int) $comment->get('created')->value),
        'helpful_count' => (int) ($comment->get('helpful_count')->value ?? 0),
        'parent_id' => $comment->getParentId(),
        'children' => [],
      ];
      $flat[(int) $comment->id()] = $item;
    }

    // Construir jerarquia.
    foreach ($flat as $id => &$item) {
      if ($item['parent_id'] !== NULL && isset($flat[$item['parent_id']])) {
        $flat[$item['parent_id']]['children'][] = &$item;
      } else {
        $tree[] = &$item;
      }
    }

    return $tree;
  }

  /**
   * Resuelve el nombre del autor para la API.
   */
  protected function resolveAuthorName(object $comment): string
  {
    if ($comment->hasField('author_name') && !$comment->get('author_name')->isEmpty()) {
      return $comment->get('author_name')->value;
    }
    if (method_exists($comment, 'getOwner') && $comment->getOwner()) {
      return $comment->getOwner()->getDisplayName() ?: 'Anonimo';
    }
    return 'Anonimo';
  }

  /**
   * Resuelve el group ID del tenant actual.
   */
  protected function resolveTenantGroupId(): int
  {
    if ($this->tenantContext === NULL) {
      return 0;
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant === NULL) {
        return 0;
      }
      return $tenant->hasField('group_id')
        ? (int) $tenant->get('group_id')->target_id
        : (int) $tenant->id();
    } catch (\Exception) {
      return 0;
    }
  }

}
