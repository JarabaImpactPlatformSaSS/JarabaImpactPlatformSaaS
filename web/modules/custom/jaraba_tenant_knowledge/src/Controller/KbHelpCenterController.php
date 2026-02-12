<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_tenant_knowledge\Service\KbArticleManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador público del Centro de Ayuda KB.
 *
 * PROPÓSITO:
 * Renderiza el centro de ayuda público basado en entidades KB.
 * Proporciona landing con categorías, vista de categoría individual,
 * detalle de artículo y página de resultados de búsqueda.
 *
 * DIRECTRICES:
 * - Rutas públicas (_access: 'TRUE') sin requerir login
 * - Solo muestra contenido publicado
 * - Traducciones con t() en todas las cadenas
 * - Patrón try-catch con logger en todos los métodos
 * - No usar promoted properties para entityTypeManager en ControllerBase
 */
class KbHelpCenterController extends ControllerBase {

  /**
   * Servicio de gestión de artículos KB.
   */
  protected KbArticleManagerService $articleManager;

  /**
   * Canal de log.
   */
  protected LoggerInterface $kbLogger;

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    KbArticleManagerService $article_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->articleManager = $article_manager;
    $this->kbLogger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_tenant_knowledge.kb_manager'),
      $container->get('logger.channel.jaraba_tenant_knowledge'),
    );
  }

  /**
   * Landing del Centro de Ayuda KB: /ayuda/kb.
   *
   * Muestra grid de categorías, artículos populares y barra de búsqueda.
   */
  public function index(): array {
    try {
      $tenantId = $this->getCurrentTenantId();

      // Cargar categorías activas.
      $categoryStorage = $this->entityTypeManager()->getStorage('kb_category');
      $categoryQuery = $categoryStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('category_status', 'active')
        ->sort('sort_order', 'ASC');

      if ($tenantId !== NULL) {
        $categoryQuery->condition('tenant_id', $tenantId);
      }

      $categoryIds = $categoryQuery->execute();
      $categoryEntities = $categoryIds ? $categoryStorage->loadMultiple($categoryIds) : [];

      $categories = [];
      foreach ($categoryEntities as $cat) {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbCategory $cat */
        // Contar artículos en la categoría.
        $articleCount = (int) $this->entityTypeManager()->getStorage('kb_article')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('category_id', $cat->id())
          ->condition('article_status', 'published')
          ->count()
          ->execute();

        $categories[] = [
          'id' => (int) $cat->id(),
          'name' => $cat->getName(),
          'slug' => $cat->getSlug(),
          'description' => $cat->getDescription(),
          'icon' => $cat->getIcon(),
          'article_count' => $articleCount,
        ];
      }

      // Artículos populares.
      $popularArticles = $this->articleManager->getPopularArticles($tenantId, 6);

      // Total de artículos publicados.
      $totalQuery = $this->entityTypeManager()->getStorage('kb_article')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published');
      if ($tenantId !== NULL) {
        $totalQuery->condition('tenant_id', $tenantId);
      }
      $totalArticles = (int) $totalQuery->count()->execute();

      return [
        '#theme' => 'kb_help_center',
        '#categories' => $categories,
        '#popular_articles' => $popularArticles,
        '#total_articles' => $totalArticles,
        '#attached' => [
          'library' => [
            'jaraba_tenant_knowledge/kb-help-center',
            'jaraba_tenant_knowledge/kb-search',
          ],
        ],
        '#cache' => [
          'tags' => ['kb_article_list', 'kb_category_list'],
          'max-age' => 300,
        ],
      ];
    }
    catch (\Exception $e) {
      $this->kbLogger->error('Error en landing del centro de ayuda KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        '#theme' => 'kb_help_center',
        '#categories' => [],
        '#popular_articles' => [],
        '#total_articles' => 0,
      ];
    }
  }

  /**
   * Vista de categoría: /ayuda/kb/categoria/{slug}.
   */
  public function category(string $slug): array {
    try {
      $tenantId = $this->getCurrentTenantId();
      $categoryStorage = $this->entityTypeManager()->getStorage('kb_category');

      // Buscar categoría por slug.
      $query = $categoryStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('slug', $slug)
        ->condition('category_status', 'active')
        ->range(0, 1);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return [
          '#theme' => 'kb_category_listing',
          '#category' => NULL,
          '#articles' => [],
          '#other_categories' => [],
        ];
      }

      /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbCategory $category */
      $category = $categoryStorage->load(reset($ids));

      // Obtener artículos de esta categoría.
      $articlesData = $this->articleManager->getPublishedArticles($tenantId, (int) $category->id());

      // Obtener otras categorías activas para la sidebar.
      $otherQuery = $categoryStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('category_status', 'active')
        ->condition('id', $category->id(), '<>')
        ->sort('sort_order', 'ASC');

      if ($tenantId !== NULL) {
        $otherQuery->condition('tenant_id', $tenantId);
      }

      $otherIds = $otherQuery->execute();
      $otherEntities = $otherIds ? $categoryStorage->loadMultiple($otherIds) : [];

      $otherCategories = [];
      foreach ($otherEntities as $other) {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbCategory $other */
        $otherCategories[] = [
          'id' => (int) $other->id(),
          'name' => $other->getName(),
          'slug' => $other->getSlug(),
          'icon' => $other->getIcon(),
        ];
      }

      return [
        '#theme' => 'kb_category_listing',
        '#category' => [
          'id' => (int) $category->id(),
          'name' => $category->getName(),
          'slug' => $category->getSlug(),
          'description' => $category->getDescription(),
          'icon' => $category->getIcon(),
        ],
        '#articles' => $articlesData['articles'],
        '#other_categories' => $otherCategories,
        '#attached' => [
          'library' => [
            'jaraba_tenant_knowledge/kb-help-center',
          ],
        ],
        '#cache' => [
          'tags' => ['kb_article_list', 'kb_category_list'],
          'max-age' => 300,
        ],
      ];
    }
    catch (\Exception $e) {
      $this->kbLogger->error('Error en vista de categoría KB "@slug": @error', [
        '@slug' => $slug,
        '@error' => $e->getMessage(),
      ]);
      return [
        '#theme' => 'kb_category_listing',
        '#category' => NULL,
        '#articles' => [],
        '#other_categories' => [],
      ];
    }
  }

  /**
   * Vista de artículo: /ayuda/kb/articulo/{slug}.
   */
  public function article(string $slug): array {
    try {
      $tenantId = $this->getCurrentTenantId();
      $articleData = $this->articleManager->getArticleBySlug($slug, $tenantId);

      if (!$articleData) {
        return [
          '#theme' => 'kb_article_detail',
          '#article' => NULL,
          '#related_articles' => [],
          '#videos' => [],
        ];
      }

      // Incrementar contador de vistas.
      $this->articleManager->incrementViewCount($articleData['id']);

      // Obtener artículos relacionados (misma categoría).
      $relatedArticles = [];
      if ($articleData['category_id']) {
        $relatedData = $this->articleManager->getPublishedArticles(
          $tenantId,
          $articleData['category_id'],
          0,
          5
        );
        foreach ($relatedData['articles'] as $related) {
          if ($related['id'] !== $articleData['id']) {
            $relatedArticles[] = $related;
          }
        }
        $relatedArticles = array_slice($relatedArticles, 0, 4);
      }

      return [
        '#theme' => 'kb_article_detail',
        '#article' => $articleData,
        '#related_articles' => $relatedArticles,
        '#videos' => $articleData['videos'] ?? [],
        '#attached' => [
          'library' => [
            'jaraba_tenant_knowledge/kb-article',
            'jaraba_tenant_knowledge/kb-help-center',
          ],
          'drupalSettings' => [
            'kbArticle' => [
              'articleId' => $articleData['id'],
            ],
          ],
        ],
        '#cache' => [
          'tags' => ['kb_article:' . $articleData['id']],
          'max-age' => 300,
        ],
      ];
    }
    catch (\Exception $e) {
      $this->kbLogger->error('Error en vista de artículo KB "@slug": @error', [
        '@slug' => $slug,
        '@error' => $e->getMessage(),
      ]);
      return [
        '#theme' => 'kb_article_detail',
        '#article' => NULL,
        '#related_articles' => [],
        '#videos' => [],
      ];
    }
  }

  /**
   * Página de resultados de búsqueda: /ayuda/kb/buscar?q={query}.
   */
  public function search(Request $request): array {
    try {
      $query = trim((string) $request->query->get('q', ''));
      $tenantId = $this->getCurrentTenantId();
      $results = [];

      if (mb_strlen($query) >= 2) {
        /** @var \Drupal\jaraba_tenant_knowledge\Service\KbSemanticSearchService $searchService */
        $searchService = \Drupal::service('jaraba_tenant_knowledge.kb_search');
        $results = $searchService->search($query, $tenantId, 20);
      }

      return [
        '#theme' => 'kb_search_results',
        '#query' => $query,
        '#results' => $results,
        '#result_count' => count($results),
        '#attached' => [
          'library' => [
            'jaraba_tenant_knowledge/kb-search',
            'jaraba_tenant_knowledge/kb-help-center',
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->kbLogger->error('Error en búsqueda KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        '#theme' => 'kb_search_results',
        '#query' => '',
        '#results' => [],
        '#result_count' => 0,
      ];
    }
  }

  /**
   * Obtiene el tenant ID actual.
   */
  protected function getCurrentTenantId(): ?int {
    if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
      $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
      $tenant = $tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    return NULL;
  }

}
