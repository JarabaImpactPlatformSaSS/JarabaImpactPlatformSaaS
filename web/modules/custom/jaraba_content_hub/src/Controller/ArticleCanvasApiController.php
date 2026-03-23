<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\CanvasSanitizationService;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador API REST para el Canvas Editor de artículos.
 *
 * Endpoints para persistencia del contenido del canvas visual:
 * - GET  /api/v1/articles/{content_article}/canvas — Cargar datos canvas
 * - PATCH /api/v1/articles/{content_article}/canvas — Guardar datos canvas
 *
 * SEPARACIÓN del Page Builder:
 * Endpoints propios para artículos (no compartidos con PageContent):
 * - Permisos distintos (edit content article vs edit page builder)
 * - Logging y observabilidad independientes
 * - Sanitización centralizada via CanvasSanitizationService
 *   (CANVAS-SANITIZER-EXTRACT-001).
 *
 * SEGURIDAD:
 * - CSRF-API-001: Ruta PATCH usa _csrf_request_header_token
 * - API-WHITELIST-001: Solo acepta claves components, styles, html, css
 * - Sanitización HTML/CSS idéntica al Page Builder
 */
class ArticleCanvasApiController extends ControllerBase {

  /**
   * Campos permitidos en el payload JSON (API-WHITELIST-001).
   */
  private const ALLOWED_FIELDS = ['components', 'styles', 'html', 'css'];

  /**
   * Servicio de sanitización canvas.
   */
  protected CanvasSanitizationService $canvasSanitization;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->canvasSanitization = $container->get('ecosistema_jaraba_core.canvas_sanitization');
    return $instance;
  }

  /**
   * GET /api/v1/articles/{content_article}/canvas
   *
   * Obtiene el contenido del canvas GrapesJS para un artículo.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $content_article
   *   La entidad de artículo.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con componentes y estilos del canvas.
   */
  public function getCanvas(ContentArticleInterface $content_article): JsonResponse {
    try {
      $canvasData = [];

      if ($content_article->hasField('canvas_data') && !$content_article->get('canvas_data')->isEmpty()) {
        $raw = $content_article->get('canvas_data')->value;
        $canvasData = json_decode($raw, TRUE) ?? [];
      }

      return new JsonResponse([
        'article_id' => $content_article->id(),
        'components' => $canvasData['components'] ?? [],
        'styles' => $canvasData['styles'] ?? [],
        'html' => $canvasData['html'] ?? '',
        'css' => $canvasData['css'] ?? '',
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_content_hub')->error(
        'Error obteniendo canvas para artículo @id: @error',
        ['@id' => $content_article->id(), '@error' => $e->getMessage()]
      );

      return new JsonResponse(
        ['error' => 'Error al obtener el canvas del artículo'],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * PATCH /api/v1/articles/{content_article}/canvas
   *
   * Guarda el contenido del canvas GrapesJS de un artículo.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $content_article
   *   La entidad de artículo.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP con JSON body.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta con estado del guardado.
   */
  public function saveCanvas(ContentArticleInterface $content_article, Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data)) {
        return new JsonResponse(
          ['error' => 'Datos inválidos'],
          Response::HTTP_BAD_REQUEST
        );
      }

      // API-WHITELIST-001: Solo aceptar campos permitidos.
      $canvasData = [
        'updated_at' => date('c'),
      ];
      foreach (self::ALLOWED_FIELDS as $field) {
        if ($field === 'html') {
          $canvasData[$field] = $this->sanitizePageBuilderHtml($data[$field] ?? '');
        }
        elseif ($field === 'css') {
          $canvasData[$field] = $this->sanitizeCss($data[$field] ?? '');
        }
        else {
          $canvasData[$field] = $data[$field] ?? [];
        }
      }

      // Almacenar canvas_data como JSON.
      if ($content_article->hasField('canvas_data')) {
        $content_article->set('canvas_data', json_encode($canvasData, JSON_UNESCAPED_UNICODE));
      }

      // Actualizar HTML renderizado para la vista pública.
      if ($content_article->hasField('rendered_html') && !empty($data['html'])) {
        $content_article->set('rendered_html', $this->sanitizeHtml($data['html']));
      }

      // Asegurar layout_mode = 'canvas' al guardar desde el editor.
      if ($content_article->hasField('layout_mode')) {
        $content_article->set('layout_mode', 'canvas');
      }

      // ContentArticle NO es revisionable — guardado directo.
      $content_article->save();

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Canvas del artículo guardado correctamente',
        'article_id' => $content_article->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_content_hub')->error(
        'Error guardando canvas para artículo @id: @error',
        ['@id' => $content_article->id(), '@error' => $e->getMessage()]
      );

      return new JsonResponse(
        ['error' => 'Error al guardar el canvas: ' . $e->getMessage()],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Sanitiza HTML con lista blanca ampliada para el editor visual.
   *
   * CANVAS-SANITIZER-EXTRACT-001: Delega en CanvasSanitizationService.
   */
  protected function sanitizePageBuilderHtml(string $html): string {
    return $this->canvasSanitization->sanitizePageBuilderHtml($html);
  }

  /**
   * Sanitiza HTML para almacenamiento público.
   *
   * CANVAS-SANITIZER-EXTRACT-001: Delega en CanvasSanitizationService.
   */
  protected function sanitizeHtml(string $html): string {
    return $this->canvasSanitization->sanitizeHtml($html);
  }

  /**
   * Sanitiza CSS para prevenir inyección de código.
   *
   * CANVAS-SANITIZER-EXTRACT-001: Delega en CanvasSanitizationService.
   */
  protected function sanitizeCss(string $css): string {
    return $this->canvasSanitization->sanitizeCss($css);
  }

}
