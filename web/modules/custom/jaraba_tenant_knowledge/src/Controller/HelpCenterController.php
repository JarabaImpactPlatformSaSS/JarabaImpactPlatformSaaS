<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_tenant_knowledge\Service\TenantKnowledgeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controlador público del Centro de Ayuda.
 *
 * PROPÓSITO:
 * Renderiza el centro de ayuda público en /ayuda sin requerir autenticación.
 * Los tenants exponen su base de conocimiento (FAQs, políticas) al público.
 *
 * LÓGICA:
 * - index(): landing con barra de búsqueda, categorías y FAQs populares
 * - viewArticle(): vista individual de una FAQ con artículos relacionados
 * - search(): búsqueda semántica vía Qdrant (sin auth)
 * - searchApi(): endpoint JSON para búsqueda asíncrona
 *
 * DIRECTRICES:
 * - Rutas públicas (_access: 'TRUE') sin requerir login
 * - Solo muestra contenido publicado (is_published = TRUE)
 * - Template limpio con page--ayuda.html.twig
 * - Traducciones con t() en todas las cadenas
 * - Filtra por tenant_id cuando el contexto del tenant está disponible
 */
class HelpCenterController extends ControllerBase {

  /**
   * Servicio de gestión de conocimiento del tenant.
   */
  protected TenantKnowledgeManager $knowledgeManager;

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    TenantKnowledgeManager $knowledge_manager,
    TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
  ) {
    $this->tenantContext = $tenantContext; // AUDIT-CONS-N10: Proper DI for tenant context.
    $this->knowledgeManager = $knowledge_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_tenant_knowledge.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-CONS-N10: Proper DI for tenant context.
    );
  }

  /**
   * Landing del Centro de Ayuda: /ayuda.
   *
   * Muestra las categorías de FAQs, artículos populares y barra de búsqueda.
   */
  public function index(): array {
    $faqStorage = $this->entityTypeManager()->getStorage('tenant_faq');

    // Obtener todas las FAQs publicadas agrupadas por categoría.
    $query = $faqStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_published', TRUE)
      ->sort('priority', 'DESC')
      ->sort('created', 'DESC');

    $ids = $query->execute();
    $faqs = $ids ? $faqStorage->loadMultiple($ids) : [];

    // Agrupar por categoría.
    $categories = [];
    $categoryLabels = [
      'general' => (string) $this->t('General'),
      'products' => (string) $this->t('Productos y Servicios'),
      'shipping' => (string) $this->t('Envíos'),
      'returns' => (string) $this->t('Devoluciones'),
      'payment' => (string) $this->t('Pagos'),
      'support' => (string) $this->t('Soporte'),
      'promotions' => (string) $this->t('Promociones'),
      'other' => (string) $this->t('Otro'),
    ];

    $categoryIcons = [
      'general' => 'info-circle',
      'products' => 'shopping-bag',
      'shipping' => 'truck',
      'returns' => 'rotate-ccw',
      'payment' => 'credit-card',
      'support' => 'headphones',
      'promotions' => 'tag',
      'other' => 'help-circle',
    ];

    foreach ($faqs as $faq) {
      $cat = $faq->get('category')->value ?? 'general';
      if (!isset($categories[$cat])) {
        $categories[$cat] = [
          'key' => $cat,
          'label' => $categoryLabels[$cat] ?? ucfirst($cat),
          'icon' => $categoryIcons[$cat] ?? 'help-circle',
          'count' => 0,
          'faqs' => [],
        ];
      }
      $categories[$cat]['count']++;
      $categories[$cat]['faqs'][] = [
        'id' => $faq->id(),
        'question' => $faq->get('question')->value,
        'answer_preview' => $this->truncate($faq->get('answer')->value ?? '', 150),
        'priority' => (int) ($faq->get('priority')->value ?? 0),
      ];
    }

    // Top FAQs (las 6 con mayor prioridad, cualquier categoría).
    $topFaqs = [];
    foreach ($faqs as $faq) {
      $topFaqs[] = [
        'id' => $faq->id(),
        'question' => $faq->get('question')->value,
        'answer_preview' => $this->truncate($faq->get('answer')->value ?? '', 120),
        'category' => $faq->get('category')->value ?? 'general',
        'category_label' => $categoryLabels[$faq->get('category')->value ?? 'general'] ?? '',
      ];
      if (count($topFaqs) >= 6) {
        break;
      }
    }

    // FAQ Bot: resolver tenant y suggestions iniciales (G114-4).
    $faqBotTenantId = 0;
    $faqBotTenantName = '';
    $faqBotSuggestions = [];

    $config = $this->knowledgeManager->getConfig();
    if ($config) {
      $faqBotTenantName = $config->getBusinessName() ?: '';
    }

    // Resolver tenant ID.
    if ($this->tenantContext !== NULL) {
      $tenantContext = $this->tenantContext;
      $tenant = $tenantContext->getCurrentTenant();
      if ($tenant) {
        $faqBotTenantId = (int) $tenant->id();
      }
    }

    // Top 3 FAQs como sugerencias iniciales.
    foreach (array_slice($topFaqs, 0, 3) as $faq) {
      $faqBotSuggestions[] = [
        'id' => $faq['id'],
        'question' => $faq['question'],
      ];
    }

    return [
      '#theme' => 'help_center',
      '#categories' => $categories,
      '#top_faqs' => $topFaqs,
      '#total_articles' => count($faqs),
      '#faq_bot_tenant_id' => $faqBotTenantId,
      '#faq_bot_tenant_name' => $faqBotTenantName,
      '#faq_bot_suggestions' => $faqBotSuggestions,
      '#attached' => [
        'library' => [
          'jaraba_tenant_knowledge/help-center',
          'jaraba_tenant_knowledge/faq-bot',
        ],
        'drupalSettings' => [
          'faqBot' => [
            'tenantId' => $faqBotTenantId,
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['tenant_faq_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Vista individual de una FAQ: /ayuda/{faq_id}.
   */
  public function viewArticle(string $faq_id): array {
    $faqStorage = $this->entityTypeManager()->getStorage('tenant_faq');
    $faq = $faqStorage->load($faq_id);

    if (!$faq || !(bool) $faq->get('is_published')->value) {
      return [
        '#theme' => 'help_center_not_found',
        '#message' => $this->t('El artículo que buscas no está disponible.'),
        '#attached' => [
          'library' => ['jaraba_tenant_knowledge/help-center'],
        ],
      ];
    }

    // Obtener artículos relacionados (misma categoría).
    $category = $faq->get('category')->value ?? 'general';
    $relatedQuery = $faqStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_published', TRUE)
      ->condition('category', $category)
      ->condition('id', $faq->id(), '<>')
      ->sort('priority', 'DESC')
      ->range(0, 4);
    $relatedIds = $relatedQuery->execute();
    $relatedFaqs = $relatedIds ? $faqStorage->loadMultiple($relatedIds) : [];

    $related = [];
    foreach ($relatedFaqs as $rel) {
      $related[] = [
        'id' => $rel->id(),
        'question' => $rel->get('question')->value,
      ];
    }

    $categoryLabels = [
      'general' => (string) $this->t('General'),
      'products' => (string) $this->t('Productos y Servicios'),
      'shipping' => (string) $this->t('Envíos'),
      'returns' => (string) $this->t('Devoluciones'),
      'payment' => (string) $this->t('Pagos'),
      'support' => (string) $this->t('Soporte'),
      'promotions' => (string) $this->t('Promociones'),
      'other' => (string) $this->t('Otro'),
    ];

    return [
      '#theme' => 'help_center_article',
      '#faq' => [
        'id' => $faq->id(),
        'question' => $faq->get('question')->value,
        'answer' => $faq->get('answer')->value,
        'category' => $category,
        'category_label' => $categoryLabels[$category] ?? ucfirst($category),
        'created' => $faq->get('created')->value,
      ],
      '#related' => $related,
      '#attached' => [
        'library' => ['jaraba_tenant_knowledge/help-center'],
      ],
      '#cache' => [
        'tags' => ['tenant_faq:' . $faq->id()],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Búsqueda en el Centro de Ayuda: /ayuda/buscar?q={query}.
   */
  public function search(Request $request): array {
    $query = trim((string) $request->query->get('q', ''));
    $results = [];

    if (mb_strlen($query) >= 2) {
      $faqStorage = $this->entityTypeManager()->getStorage('tenant_faq');

      // Búsqueda simple por LIKE en question y answer.
      $dbQuery = $faqStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_published', TRUE);

      $orGroup = $dbQuery->orConditionGroup()
        ->condition('question', '%' . $query . '%', 'LIKE')
        ->condition('answer', '%' . $query . '%', 'LIKE');
      $dbQuery->condition($orGroup);
      $dbQuery->sort('priority', 'DESC');
      $dbQuery->range(0, 20);

      $ids = $dbQuery->execute();
      $faqs = $ids ? $faqStorage->loadMultiple($ids) : [];

      foreach ($faqs as $faq) {
        $results[] = [
          'id' => $faq->id(),
          'question' => $faq->get('question')->value,
          'answer_preview' => $this->truncate($faq->get('answer')->value ?? '', 200),
          'category' => $faq->get('category')->value ?? 'general',
        ];
      }
    }

    return [
      '#theme' => 'help_center_search',
      '#query' => $query,
      '#results' => $results,
      '#result_count' => count($results),
      '#attached' => [
        'library' => ['jaraba_tenant_knowledge/help-center'],
      ],
    ];
  }

  /**
   * API de búsqueda para autocompletado: /api/v1/help/search.
   */
  public function searchApi(Request $request): JsonResponse {
    $query = trim((string) $request->query->get('q', ''));

    if (mb_strlen($query) < 2) {
      return new JsonResponse(['success' => TRUE, 'data' => [], 'meta' => ['query' => $query]]);
    }

    $faqStorage = $this->entityTypeManager()->getStorage('tenant_faq');
    $dbQuery = $faqStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_published', TRUE);

    $orGroup = $dbQuery->orConditionGroup()
      ->condition('question', '%' . $query . '%', 'LIKE')
      ->condition('answer', '%' . $query . '%', 'LIKE');
    $dbQuery->condition($orGroup);
    $dbQuery->sort('priority', 'DESC');
    $dbQuery->range(0, 10);

    $ids = $dbQuery->execute();
    $faqs = $ids ? $faqStorage->loadMultiple($ids) : [];

    $results = [];
    foreach ($faqs as $faq) {
      $results[] = [
        'id' => $faq->id(),
        'question' => $faq->get('question')->value,
        'answer_preview' => $this->truncate($faq->get('answer')->value ?? '', 150),
        'category' => $faq->get('category')->value ?? 'general',
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $results,
      'meta' => ['query' => $query, 'count' => count($results)],
    ]);
  }

  /**
   * Trunca texto al límite especificado sin cortar palabras.
   */
  protected function truncate(string $text, int $limit): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $limit) {
      return $text;
    }
    $truncated = mb_substr($text, 0, $limit);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== FALSE) {
      $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return $truncated . '...';
  }

}
