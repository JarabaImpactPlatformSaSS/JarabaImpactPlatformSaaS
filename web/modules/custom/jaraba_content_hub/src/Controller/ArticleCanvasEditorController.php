<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para el Canvas Editor visual de artículos del Content Hub.
 *
 * PROPÓSITO:
 * Proporciona una experiencia de edición visual drag-and-drop usando GrapesJS
 * para artículos del blog. Versión simplificada del CanvasEditorController
 * del Page Builder: sin secciones, sin templates, sin partials globales.
 *
 * DIFERENCIAS CON PAGE BUILDER:
 * - Opera sobre ContentArticle (no PageContent)
 * - Sin sidebar de secciones arrastrables
 * - Sin selectores de Header/Footer variants
 * - Incluye panel lateral con metadatos del artículo (categoría, SEO, fecha)
 * - Endpoints API propios (/api/v1/articles/{id}/canvas)
 *
 * REUTILIZACIÓN:
 * El engine GrapesJS JS se carga vía dependencia de library:
 * jaraba_page_builder/grapesjs-canvas (no se duplica código JS).
 */
class ArticleCanvasEditorController extends ControllerBase {

  /**
   * Tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TenantContextService $tenant_context,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Renderiza el Canvas Editor para un artículo.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $content_article
   *   La entidad ContentArticle a editar.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP.
   *
   * @return array
   *   Render array con el Canvas Editor.
   */
  public function editor(ContentArticleInterface $content_article, Request $request): array {
    // Información del tenant para design tokens (con fallback).
    $tenantInfo = NULL;
    try {
      $tenantInfo = $this->tenantContext->getCurrentTenant();
    }
    catch (\Exception $e) {
      // Sin contexto de tenant — editor funciona sin tokens personalizados.
    }

    // URL canónica del artículo para preview.
    $previewUrl = $content_article->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Extraer URL del logo del tenant para branding del editor.
    $tenantLogo = NULL;
    $tenantName = NULL;
    if ($tenantInfo && method_exists($tenantInfo, 'getThemeOverrides')) {
      $tenantName = $tenantInfo->label() ?? 'Mi SaaS';
      $themeOverrides = $tenantInfo->getThemeOverrides();
      if (!empty($themeOverrides['logo'])) {
        $logoPath = $themeOverrides['logo'];
        $tenantLogo = str_starts_with($logoPath, '/')
          ? \Drupal::request()->getSchemeAndHttpHost() . $logoPath
          : $logoPath;
      }
    }

    // Metadatos del artículo para el panel lateral.
    $articleMeta = $this->getArticleMetadata($content_article);

    // Libraries del Canvas Editor (reutiliza engine del Page Builder).
    $libraries = [
      'jaraba_content_hub/article-canvas-editor',
      'ecosistema_jaraba_theme/slide-panel',
      'jaraba_page_builder/grapesjs-canvas',
    ];

    return [
      '#theme' => 'article_canvas_editor',
      '#article' => $content_article,
      '#preview_url' => $previewUrl,
      '#tenant_logo' => $tenantLogo,
      '#tenant_name' => $tenantName,
      '#article_meta' => $articleMeta,
      '#attached' => [
        'library' => $libraries,
        'drupalSettings' => [
          'articleCanvasEditor' => [
            'articleId' => $content_article->id(),
            'previewUrl' => $previewUrl,
            'csrfToken' => \Drupal::service('csrf_token')->get('rest'),
          ],
          // Configuración de GrapesJS (compartida con Page Builder).
          'jarabaCanvas' => [
            'editorMode' => 'article',
            'pageId' => $content_article->id(),
            'tenantId' => $tenantInfo?->id(),
            'csrfToken' => \Drupal::service('csrf_token')->get('rest'),
            'designTokens' => $this->getDesignTokens($tenantInfo),
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url'],
        'tags' => ['content_article:' . $content_article->id()],
      ],
    ];
  }

  /**
   * Título dinámico para la página del editor.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $content_article
   *   El artículo.
   *
   * @return string
   *   El título.
   */
  public function editorTitle(ContentArticleInterface $content_article): string {
    return (string) $this->t('Canvas Editor: @title', ['@title' => $content_article->getTitle()]);
  }

  /**
   * Obtiene metadatos del artículo para el panel lateral del editor.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $content_article
   *   El artículo.
   *
   * @return array
   *   Array de metadatos procesados.
   */
  protected function getArticleMetadata(ContentArticleInterface $content_article): array {
    $meta = [
      'title' => $content_article->getTitle(),
      'slug' => $content_article->getSlug(),
      'excerpt' => $content_article->getExcerpt(),
      'status' => $content_article->getPublicationStatus(),
      'reading_time' => $content_article->getReadingTime(),
      'publish_date' => $content_article->get('publish_date')->value ?? '',
      'seo_title' => '',
      'seo_description' => '',
      'category_name' => '',
      'category_id' => NULL,
    ];

    // SEO fields.
    if ($content_article->hasField('seo_title') && !$content_article->get('seo_title')->isEmpty()) {
      $meta['seo_title'] = $content_article->get('seo_title')->value;
    }
    if ($content_article->hasField('seo_description') && !$content_article->get('seo_description')->isEmpty()) {
      $meta['seo_description'] = $content_article->get('seo_description')->value;
    }

    // Categoría.
    if ($content_article->hasField('category') && !$content_article->get('category')->isEmpty()) {
      $category = $content_article->get('category')->entity;
      if ($category) {
        $meta['category_name'] = $category->label() ?? '';
        $meta['category_id'] = $category->id();
      }
    }

    return $meta;
  }

  /**
   * Obtiene los Design Tokens del tenant para inyectar en el canvas.
   *
   * @param object|null $tenantInfo
   *   Entidad del tenant o NULL.
   *
   * @return array
   *   Array de tokens CSS (color-primary, color-secondary, etc).
   */
  protected function getDesignTokens(?object $tenantInfo): array {
    if (!$tenantInfo) {
      return [];
    }

    $tokens = [];

    if (method_exists($tenantInfo, 'hasField')) {
      if ($tenantInfo->hasField('color_primary') && !$tenantInfo->get('color_primary')->isEmpty()) {
        $tokens['color-primary'] = $tenantInfo->get('color_primary')->value;
      }
      if ($tenantInfo->hasField('color_secondary') && !$tenantInfo->get('color_secondary')->isEmpty()) {
        $tokens['color-secondary'] = $tenantInfo->get('color_secondary')->value;
      }
      if ($tenantInfo->hasField('font_family') && !$tenantInfo->get('font_family')->isEmpty()) {
        $tokens['font-family'] = $tenantInfo->get('font_family')->value;
      }
    }

    return $tokens;
  }

}
