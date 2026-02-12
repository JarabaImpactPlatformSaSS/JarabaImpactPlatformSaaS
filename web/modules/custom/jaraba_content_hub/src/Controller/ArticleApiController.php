<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para artículos del Content Hub.
 *
 * PROPÓSITO:
 * Expone endpoints REST para operaciones CRUD sobre artículos.
 * Permite integración con clientes externos, aplicaciones móviles
 * y sistemas de terceros.
 *
 * ENDPOINTS:
 * - GET /api/content-hub/articles: Listar artículos publicados
 * - GET /api/content-hub/articles/{uuid}: Obtener artículo por UUID
 * - POST /api/content-hub/articles: Crear nuevo artículo
 * - PATCH /api/content-hub/articles/{uuid}: Actualizar artículo
 * - POST /api/content-hub/articles/{uuid}/publish: Publicar artículo
 *
 * ARQUITECTURA:
 * - Utiliza ArticleService para lógica de negocio
 * - Retorna respuestas JSON estandarizadas
 * - Incluye metadatos de paginación en listados
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class ArticleApiController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * El servicio de artículos.
     *
     * Proporciona toda la lógica de negocio para operaciones con artículos.
     *
     * @var \Drupal\jaraba_content_hub\Service\ArticleService
     */
    protected ArticleService $articleService;

    /**
     * Construye un ArticleApiController.
     *
     * @param \Drupal\jaraba_content_hub\Service\ArticleService $articleService
     *   El servicio de artículos.
     */
    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_content_hub.article_service'),
        );
    }

    /**
     * Lista artículos publicados con paginación.
     *
     * Soporta filtrado por categoría y paginación via query params.
     * El límite máximo está fijado en 100 para evitar sobrecarga.
     *
     * Query params:
     * - limit: Número de resultados (default 20, max 100)
     * - offset: Offset para paginación (default 0)
     * - category: ID de categoría para filtrar
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con listado y metadatos de paginación.
     */
    public function list(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        $offset = (int) $request->query->get('offset', 0);
        $category = $request->query->get('category');

        $filters = [
            'limit' => min($limit, 100),
            'offset' => $offset,
        ];

        if ($category) {
            $filters['category'] = $category;
        }

        $articles = $this->articleService->getPublishedArticles($filters);

        $data = [];
        foreach ($articles as $article) {
            $category_entity = $article->get('category')->entity;
            $data[] = [
                'uuid' => $article->uuid(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getExcerpt(),
                'answer_capsule' => $article->getAnswerCapsule(),
                'reading_time' => $article->getReadingTime(),
                'publish_date' => $article->get('publish_date')->value,
                'category' => $category_entity ? $category_entity->getName() : NULL,
                'url' => $article->toUrl()->setAbsolute()->toString(),
            ];
        }

        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'offset' => $offset,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Obtiene un artículo específico por UUID.
     *
     * Retorna información completa del artículo incluyendo:
     * body HTML, categoría con detalles, autor, SEO y flags.
     *
     * @param string $uuid
     *   El UUID del artículo.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con datos del artículo o error 404.
     */
    public function get(string $uuid): JsonResponse
    {
        $article = $this->articleService->getByUuid($uuid);

        if (!$article) {
            return new JsonResponse(['error' => 'Artículo no encontrado'], 404);
        }

        $category_entity = $article->get('category')->entity;
        $author = $article->getOwner();

        return new JsonResponse([
            'data' => [
                'uuid' => $article->uuid(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getExcerpt(),
                'body' => $article->get('body')->value,
                'answer_capsule' => $article->getAnswerCapsule(),
                'reading_time' => $article->getReadingTime(),
                'status' => $article->getPublicationStatus(),
                'publish_date' => $article->get('publish_date')->value,
                'category' => $category_entity ? [
                    'id' => $category_entity->id(),
                    'name' => $category_entity->getName(),
                    'slug' => $category_entity->getSlug(),
                ] : NULL,
                'author' => $author ? [
                    'id' => $author->id(),
                    'name' => $author->getDisplayName(),
                ] : NULL,
                'seo' => [
                    'title' => $article->get('seo_title')->value,
                    'description' => $article->get('seo_description')->value,
                ],
                'ai_generated' => $article->isAiGenerated(),
                'url' => $article->toUrl()->setAbsolute()->toString(),
            ],
        ]);
    }

    /**
     * Crea un nuevo artículo via API.
     *
     * Requiere al menos el campo 'title' en el body JSON.
     * El artículo se crea en estado draft por defecto.
     *
     * Body JSON esperado:
     * {
     *   "title": "Título del artículo",
     *   "body": "Contenido HTML",
     *   "excerpt": "Extracto",
     *   "category_id": 1
     * }
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con datos JSON.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con datos del artículo creado (201) o error.
     */
    public function createArticle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['title'])) {
            return new JsonResponse(['error' => 'El título es requerido'], 400);
        }

        try {
            $article = $this->articleService->create($data);

            return new JsonResponse([
                'data' => [
                    'uuid' => $article->uuid(),
                    'title' => $article->getTitle(),
                    'status' => $article->getPublicationStatus(),
                ],
                'message' => 'Artículo creado exitosamente',
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza un artículo existente.
     *
     * Solo actualiza los campos proporcionados en el body JSON.
     * Campos soportados: title, body, excerpt, answer_capsule,
     * seo_title, seo_description, status.
     *
     * @param string $uuid
     *   El UUID del artículo a actualizar.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con datos JSON.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con datos actualizados o error 404.
     */
    public function update(string $uuid, Request $request): JsonResponse
    {
        $article = $this->articleService->getByUuid($uuid);

        if (!$article) {
            return new JsonResponse(['error' => 'Artículo no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), TRUE);

        // Campos permitidos para actualización via API.
        $fields = ['title', 'body', 'excerpt', 'answer_capsule', 'seo_title', 'seo_description', 'status'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $article->set($field, $data[$field]);
            }
        }

        $article->save();

        return new JsonResponse([
            'data' => [
                'uuid' => $article->uuid(),
                'title' => $article->getTitle(),
                'status' => $article->getPublicationStatus(),
            ],
            'message' => 'Artículo actualizado exitosamente',
        ]);
    }

    /**
     * Publica un artículo.
     *
     * Cambia el estado del artículo a 'published' y establece
     * la fecha de publicación si no estaba ya definida.
     *
     * @param string $uuid
     *   El UUID del artículo a publicar.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON de confirmación o error 404.
     */
    public function publish(string $uuid): JsonResponse
    {
        $success = $this->articleService->publish($uuid);

        if (!$success) {
            return new JsonResponse(['error' => 'Artículo no encontrado'], 404);
        }

        return new JsonResponse([
            'message' => 'Artículo publicado exitosamente',
        ]);
    }

}
