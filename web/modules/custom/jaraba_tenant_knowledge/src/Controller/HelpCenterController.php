<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
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
 * - index(): landing con barra de búsqueda, categorías, FAQs populares,
 *   quick links, KB cross-link, trust signals, SEO Schema.org
 * - viewArticle(): vista individual de una FAQ con artículos relacionados + QAPage JSON-LD
 * - search(): búsqueda semántica vía Qdrant (sin auth)
 * - searchApi(): endpoint JSON para búsqueda unificada (FAQ + KB)
 *
 * DIRECTRICES:
 * - Rutas públicas (_access: 'TRUE') sin requerir login
 * - Solo muestra contenido publicado (is_published = TRUE)
 * - Template limpio con page--ayuda.html.twig
 * - Traducciones con t() en todas las cadenas
 * - Filtra por tenant_id cuando el contexto del tenant está disponible
 * - ROUTE-LANGPREFIX-001: URLs vía Url::fromRoute(), no hardcoded
 * - PRESAVE-RESILIENCE-001: hasDefinition() para kb_article opcional
 */
class HelpCenterController extends ControllerBase {

  /**
   * Servicio de gestión de conocimiento del tenant.
   */
  protected TenantKnowledgeManager $knowledgeManager;

  /**
   * Servicio de contexto del tenant.
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    TenantKnowledgeManager $knowledge_manager,
    TenantContextService $tenantContext,
  ) {
    $this->knowledgeManager = $knowledge_manager;
    $this->tenantContext = $tenantContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_tenant_knowledge.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Retorna metadatos de las 8 categorías SaaS.
   *
   * Método extraído para evitar duplicación entre index() y viewArticle().
   *
   * @return array
   *   Array con keys: labels (key => string), icons (key => string).
   */
  protected function getCategoryMeta(): array {
    return [
      'labels' => [
        'getting_started' => (string) $this->t('Primeros Pasos'),
        'account' => (string) $this->t('Cuenta y Perfil'),
        'features' => (string) $this->t('Funcionalidades'),
        'billing' => (string) $this->t('Planes y Facturación'),
        'ai_copilot' => (string) $this->t('IA y Copiloto'),
        'integrations' => (string) $this->t('Integraciones'),
        'security' => (string) $this->t('Seguridad y Privacidad'),
        'troubleshooting' => (string) $this->t('Solución de Problemas'),
      ],
      'icons' => [
        'getting_started' => 'play-circle',
        'account' => 'user',
        'features' => 'zap',
        'billing' => 'credit-card',
        'ai_copilot' => 'cpu',
        'integrations' => 'link',
        'security' => 'shield',
        'troubleshooting' => 'tool',
      ],
    ];
  }

  /**
   * Cuenta artículos KB publicados (cross-link con /ayuda/kb).
   *
   * PRESAVE-RESILIENCE-001: usa hasDefinition() para módulo opcional.
   *
   * @return int
   *   Número de artículos KB publicados, o 0 si el entity type no existe.
   */
  protected function getKbArticleCount(): int {
    if (!$this->entityTypeManager()->hasDefinition('kb_article')) {
      return 0;
    }
    try {
      return (int) $this->entityTypeManager()
        ->getStorage('kb_article')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_published', TRUE)
        ->count()
        ->execute();
    }
    catch (\Exception) {
      return 0;
    }
  }

  /**
   * Construye JSON-LD FAQPage para la landing.
   *
   * @param array $faqs
   *   Array de FAQ entities.
   *
   * @return array
   *   Schema.org FAQPage JSON-LD structure.
   */
  protected function buildFaqPageSchema(array $faqs): array {
    $mainEntity = [];
    foreach ($faqs as $faq) {
      $mainEntity[] = [
        '@type' => 'Question',
        'name' => $faq->get('question')->value,
        'acceptedAnswer' => [
          '@type' => 'Answer',
          'text' => strip_tags($faq->get('answer')->value ?? ''),
        ],
      ];
    }

    return [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'name' => (string) $this->t('Centro de Ayuda'),
      'description' => (string) $this->t('Encuentra respuestas a las preguntas más frecuentes sobre la plataforma.'),
      'mainEntity' => $mainEntity,
    ];
  }

  /**
   * Construye BreadcrumbList JSON-LD.
   *
   * @param array $items
   *   Array of [name, url] pairs.
   *
   * @return array
   *   Schema.org BreadcrumbList.
   */
  protected function buildBreadcrumbSchema(array $items): array {
    $list = [];
    foreach ($items as $i => $item) {
      $list[] = [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'name' => $item['name'],
        'item' => $item['url'],
      ];
    }
    return [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => $list,
    ];
  }

  /**
   * Construye head SEO para el Centro de Ayuda.
   *
   * @param string $title
   *   Page title.
   * @param string $description
   *   Meta description.
   * @param string $canonical_url
   *   Canonical URL.
   * @param array $json_ld_items
   *   Array of JSON-LD objects to embed.
   *
   * @return array
   *   Render array of #attached html_head items.
   */
  protected function buildHelpCenterSeoHead(string $title, string $description, string $canonical_url, array $json_ld_items = []): array {
    $head = [];

    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['name' => 'description', 'content' => $description]],
      'help_center_description',
    ];
    $head[] = [
      ['#tag' => 'link', '#attributes' => ['rel' => 'canonical', 'href' => $canonical_url]],
      'help_center_canonical',
    ];

    // Open Graph.
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['property' => 'og:title', 'content' => $title]],
      'help_center_og_title',
    ];
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['property' => 'og:description', 'content' => $description]],
      'help_center_og_description',
    ];
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['property' => 'og:type', 'content' => 'website']],
      'help_center_og_type',
    ];
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['property' => 'og:url', 'content' => $canonical_url]],
      'help_center_og_url',
    ];

    // Twitter Card.
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['name' => 'twitter:card', 'content' => 'summary']],
      'help_center_tw_card',
    ];
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['name' => 'twitter:title', 'content' => $title]],
      'help_center_tw_title',
    ];
    $head[] = [
      ['#tag' => 'meta', '#attributes' => ['name' => 'twitter:description', 'content' => $description]],
      'help_center_tw_description',
    ];

    // JSON-LD.
    foreach ($json_ld_items as $i => $json_ld) {
      $head[] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ],
        'help_center_jsonld_' . $i,
      ];
    }

    return $head;
  }

  /**
   * Landing del Centro de Ayuda: /ayuda.
   *
   * Muestra las categorías de FAQs, artículos populares, quick links,
   * trust signals, CTA con botones y SEO Schema.org FAQPage.
   */
  public function index(): array {
    $faqStorage = $this->entityTypeManager()->getStorage('tenant_faq');
    $categoryMeta = $this->getCategoryMeta();
    $categoryLabels = $categoryMeta['labels'];
    $categoryIcons = $categoryMeta['icons'];

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
    foreach ($faqs as $faq) {
      $cat = $faq->get('category')->value ?? 'getting_started';
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

    // Ordenar categorías según el orden fijo de getCategoryMeta().
    $orderedCategories = [];
    foreach (array_keys($categoryLabels) as $key) {
      if (isset($categories[$key])) {
        $orderedCategories[$key] = $categories[$key];
      }
    }
    $categories = $orderedCategories;

    // Top FAQs (las 6 con mayor prioridad, cualquier categoría).
    $topFaqs = [];
    foreach ($faqs as $faq) {
      $topFaqs[] = [
        'id' => $faq->id(),
        'question' => $faq->get('question')->value,
        'answer_preview' => $this->truncate($faq->get('answer')->value ?? '', 120),
        'category' => $faq->get('category')->value ?? 'getting_started',
        'category_label' => $categoryLabels[$faq->get('category')->value ?? 'getting_started'] ?? '',
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
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant) {
        $faqBotTenantId = (int) $tenant->id();
      }
    }

    // Fallback para usuarios anónimos: cargar el primer tenant disponible.
    if ($faqBotTenantId <= 0) {
      try {
        $tenants = $this->entityTypeManager()->getStorage('tenant')->loadMultiple();
        if (!empty($tenants)) {
          $faqBotTenantId = (int) reset($tenants)->id();
        }
      }
      catch (\Exception $e) {
        // Si falla, el widget enviará tenant_id: 0 y el API usará su propio fallback.
      }
    }

    // Top 3 FAQs como sugerencias iniciales.
    foreach (array_slice($topFaqs, 0, 3) as $faq) {
      $faqBotSuggestions[] = [
        'id' => $faq['id'],
        'question' => $faq['question'],
      ];
    }

    // Quick links: enlaces de acción rápida.
    // ROUTE-LANGPREFIX-001: Todas las URLs vía Url::fromRoute().
    $quickLinks = [
      [
        'label' => (string) $this->t('Crear ticket de soporte'),
        'url' => Url::fromRoute('jaraba_support.portal.create')->toString(),
        'icon' => 'plus-circle',
        'description' => (string) $this->t('Abre un ticket y nuestro equipo te responderá.'),
      ],
      [
        'label' => (string) $this->t('Contacto'),
        'url' => Url::fromRoute('ecosistema_jaraba_core.contact')->toString(),
        'icon' => 'mail',
        'description' => (string) $this->t('Envíanos un mensaje directamente.'),
      ],
      [
        'label' => (string) $this->t('Base de Conocimiento'),
        'url' => Url::fromRoute('jaraba_tenant_knowledge.kb.index')->toString(),
        'icon' => 'book-open',
        'description' => (string) $this->t('Guías detalladas y tutoriales.'),
      ],
      [
        'label' => (string) $this->t('Blog'),
        'url' => Url::fromRoute('jaraba_content_hub.blog')->toString(),
        'icon' => 'edit-3',
        'description' => (string) $this->t('Novedades y artículos del equipo.'),
      ],
    ];

    // Contact channels para CTA.
    // ROUTE-LANGPREFIX-001: URLs vía Url::fromRoute().
    $contactChannels = [
      [
        'label' => (string) $this->t('Contactar'),
        'url' => Url::fromRoute('ecosistema_jaraba_core.contact')->toString(),
        'type' => 'primary',
      ],
      [
        'label' => (string) $this->t('Crear ticket'),
        'url' => Url::fromRoute('jaraba_support.portal.create')->toString(),
        'type' => 'secondary',
      ],
    ];

    // KB articles count y URL (cross-link).
    // ROUTE-LANGPREFIX-001: URL vía Url::fromRoute().
    $kbArticlesCount = $this->getKbArticleCount();
    $kbUrl = Url::fromRoute('jaraba_tenant_knowledge.kb.index')->toString();

    // Flag para template: hay contenido?
    $hasContent = count($faqs) > 0;

    // SEO: FAQPage + BreadcrumbList JSON-LD + OG/Twitter + canonical.
    $canonicalUrl = Url::fromRoute('jaraba_tenant_knowledge.help.index', [], ['absolute' => TRUE])->toString();
    $seoTitle = (string) $this->t('Centro de Ayuda — Jaraba Impact Platform');
    $seoDescription = (string) $this->t('Encuentra respuestas a las preguntas más frecuentes sobre la plataforma. Soporte, guías y tutoriales.');

    $jsonLd = [];
    if ($hasContent) {
      $jsonLd[] = $this->buildFaqPageSchema($faqs);
    }
    $jsonLd[] = $this->buildBreadcrumbSchema([
      ['name' => (string) $this->t('Inicio'), 'url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString()],
      ['name' => (string) $this->t('Centro de Ayuda'), 'url' => $canonicalUrl],
    ]);

    $seoHead = $this->buildHelpCenterSeoHead($seoTitle, $seoDescription, $canonicalUrl, $jsonLd);

    // ROUTE-LANGPREFIX-001: URL de búsqueda API vía Url::fromRoute().
    $searchApiUrl = Url::fromRoute('jaraba_tenant_knowledge.help.search_api')->toString();

    return [
      '#theme' => 'help_center',
      '#categories' => $categories,
      '#top_faqs' => $topFaqs,
      '#total_articles' => count($faqs),
      '#faq_bot_tenant_id' => $faqBotTenantId,
      '#faq_bot_tenant_name' => $faqBotTenantName,
      '#faq_bot_suggestions' => $faqBotSuggestions,
      '#quick_links' => $quickLinks,
      '#contact_channels' => $contactChannels,
      '#kb_articles_count' => $kbArticlesCount,
      '#kb_url' => $kbUrl,
      '#has_content' => $hasContent,
      '#attached' => [
        'library' => [
          'jaraba_tenant_knowledge/help-center',
          'jaraba_tenant_knowledge/faq-bot',
        ],
        'drupalSettings' => [
          'faqBot' => [
            'tenantId' => $faqBotTenantId,
          ],
          'helpCenter' => [
            'searchApiUrl' => $searchApiUrl,
          ],
        ],
        'html_head' => $seoHead,
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
    $category = $faq->get('category')->value ?? 'getting_started';
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

    $categoryMeta = $this->getCategoryMeta();
    $categoryLabels = $categoryMeta['labels'];

    // SEO: QAPage + BreadcrumbList JSON-LD.
    $question = $faq->get('question')->value;
    $answer = strip_tags($faq->get('answer')->value ?? '');
    $canonicalUrl = Url::fromRoute('jaraba_tenant_knowledge.help.article', ['faq_id' => $faq_id], ['absolute' => TRUE])->toString();
    $helpCenterUrl = Url::fromRoute('jaraba_tenant_knowledge.help.index', [], ['absolute' => TRUE])->toString();
    $homeUrl = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    $qaPageSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'QAPage',
      'mainEntity' => [
        '@type' => 'Question',
        'name' => $question,
        'answerCount' => 1,
        'acceptedAnswer' => [
          '@type' => 'Answer',
          'text' => $answer,
        ],
      ],
    ];

    $breadcrumbSchema = $this->buildBreadcrumbSchema([
      ['name' => (string) $this->t('Inicio'), 'url' => $homeUrl],
      ['name' => (string) $this->t('Centro de Ayuda'), 'url' => $helpCenterUrl],
      ['name' => $question, 'url' => $canonicalUrl],
    ]);

    $seoHead = $this->buildHelpCenterSeoHead(
      $question . ' — ' . (string) $this->t('Centro de Ayuda'),
      $this->truncate($answer, 160),
      $canonicalUrl,
      [$qaPageSchema, $breadcrumbSchema],
    );

    return [
      '#theme' => 'help_center_article',
      '#faq' => [
        'id' => $faq->id(),
        'question' => $question,
        'answer' => $faq->get('answer')->value,
        'category' => $category,
        'category_label' => $categoryLabels[$category] ?? ucfirst($category),
        'created' => $faq->get('created')->value,
      ],
      '#related' => $related,
      '#attached' => [
        'library' => ['jaraba_tenant_knowledge/help-center'],
        'html_head' => $seoHead,
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
          'category' => $faq->get('category')->value ?? 'getting_started',
          'type' => 'faq',
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
   * API de búsqueda unificada (FAQ + KB): /api/v1/help/search.
   *
   * ROUTE-LANGPREFIX-001: consumido vía drupalSettings.helpCenter.searchApiUrl.
   * PRESAVE-RESILIENCE-001: hasDefinition() para kb_article opcional.
   */
  public function searchApi(Request $request): JsonResponse {
    $query = trim((string) $request->query->get('q', ''));

    if (mb_strlen($query) < 2) {
      return new JsonResponse(['success' => TRUE, 'data' => [], 'meta' => ['query' => $query]]);
    }

    $results = [];

    // --- Buscar en FAQs ---
    $faqStorage = $this->entityTypeManager()->getStorage('tenant_faq');
    $dbQuery = $faqStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_published', TRUE);

    $orGroup = $dbQuery->orConditionGroup()
      ->condition('question', '%' . $query . '%', 'LIKE')
      ->condition('answer', '%' . $query . '%', 'LIKE');
    $dbQuery->condition($orGroup);
    $dbQuery->sort('priority', 'DESC');
    $dbQuery->range(0, 8);

    $ids = $dbQuery->execute();
    $faqs = $ids ? $faqStorage->loadMultiple($ids) : [];

    foreach ($faqs as $faq) {
      $results[] = [
        'id' => $faq->id(),
        'question' => $faq->get('question')->value,
        'answer_preview' => $this->truncate($faq->get('answer')->value ?? '', 150),
        'category' => $faq->get('category')->value ?? 'getting_started',
        'type' => 'faq',
        'url' => Url::fromRoute('jaraba_tenant_knowledge.help.article', ['faq_id' => $faq->id()])->toString(),
      ];
    }

    // --- Buscar en KB articles (si el entity type existe) ---
    if ($this->entityTypeManager()->hasDefinition('kb_article')) {
      try {
        $kbStorage = $this->entityTypeManager()->getStorage('kb_article');
        $kbQuery = $kbStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('is_published', TRUE)
          ->condition('title', '%' . $query . '%', 'LIKE')
          ->sort('created', 'DESC')
          ->range(0, 4);

        $kbIds = $kbQuery->execute();
        $kbArticles = $kbIds ? $kbStorage->loadMultiple($kbIds) : [];

        foreach ($kbArticles as $kb) {
          $slug = $kb->get('slug')->value ?? '';
          if (empty($slug)) {
            continue;
          }
          $results[] = [
            'id' => $kb->id(),
            'question' => $kb->get('title')->value,
            'answer_preview' => $this->truncate($kb->get('summary')->value ?? $kb->get('body')->value ?? '', 150),
            'category' => 'kb',
            'type' => 'kb',
            'url' => Url::fromRoute('jaraba_tenant_knowledge.kb.article', ['slug' => $slug])->toString(),
          ];
        }
      }
      catch (\Exception) {
        // KB module may not be fully configured.
      }
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
