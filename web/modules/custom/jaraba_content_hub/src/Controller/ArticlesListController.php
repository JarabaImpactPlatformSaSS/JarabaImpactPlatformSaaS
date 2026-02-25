<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Controller\PremiumFormAjaxTrait;
use Drupal\jaraba_content_hub\Entity\ContentArticle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for frontend article listing (tenant editors).
 *
 * IMPORTANTE: Este controlador reemplaza el listado de admin para que
 * los tenants no usen el tema de administración de Drupal.
 *
 * @see docs/00_DIRECTRICES_PROYECTO.md section 2.2.2
 */
class ArticlesListController extends ControllerBase
{

    use PremiumFormAjaxTrait;

    /**
     * Items per page for pagination.
     */
    protected const ITEMS_PER_PAGE = 20;

    /**
     * The pager manager.
     */
    protected PagerManagerInterface $pagerManager;

    /**
     * The renderer service.
     */
    protected RendererInterface $renderer;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->pagerManager = $container->get('pager.manager');
        $instance->renderer = $container->get('renderer');
        return $instance;
    }

    /**
     * Lists articles for tenant editors (frontend theme).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @return array
     *   Render array for the articles list.
     */
    public function list(Request $request): array
    {
        $storage = $this->entityTypeManager()->getStorage('content_article');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('changed', 'DESC');

        // Filtrar por estado si se especifica.
        $status = $request->query->get('status');
        if ($status === 'published') {
            $query->condition('status', 1);
        } elseif ($status === 'draft') {
            $query->condition('status', 0);
        }

        // Búsqueda por título.
        $search = $request->query->get('q');
        if ($search) {
            $query->condition('title', '%' . $search . '%', 'LIKE');
        }

        // Contar total para paginación.
        $count_query = clone $query;
        $total = $count_query->count()->execute();

        // Paginación.
        $page = $this->pagerManager->createPager($total, self::ITEMS_PER_PAGE)->getCurrentPage();
        $query->range($page * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);

        $ids = $query->execute();
        $articles = $storage->loadMultiple($ids);

        // Preparar datos para el template.
        $rows = [];
        foreach ($articles as $article) {
            /** @var \Drupal\jaraba_content_hub\Entity\ContentArticle $article */
            $rows[] = [
                'id' => $article->id(),
                'title' => $article->label(),
                'status' => $article->isPublished() ? 'published' : 'draft',
                'status_label' => $article->isPublished() ? $this->t('Published') : $this->t('Draft'),
                'author' => $article->getOwner()?->getDisplayName() ?? $this->t('Unknown'),
                'changed' => $article->getChangedTime(),
                'category' => $article->get('category')->entity?->label() ?? '-',
                'edit_url' => Url::fromRoute('jaraba_content_hub.articles.edit.frontend', [
                    'content_article' => $article->id(),
                ])->toString(),
                'view_url' => $article->toUrl()->toString(),
            ];
        }

        // Estadísticas para cabecera.
        $all_count = $storage->getQuery()
            ->accessCheck(TRUE)
            ->count()
            ->execute();
        $published_count = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->count()
            ->execute();
        $draft_count = $all_count - $published_count;

        return [
            '#theme' => 'content_hub_articles_list',
            '#articles' => $rows,
            '#stats' => [
                'total' => $all_count,
                'published' => $published_count,
                'drafts' => $draft_count,
            ],
            '#current_filter' => $status ?? 'all',
            '#search_query' => $search ?? '',
            '#pager' => [
                '#type' => 'pager',
            ],
            '#back_url' => Url::fromRoute('jaraba_content_hub.dashboard.frontend')->toString(),
            '#add_url' => Url::fromRoute('jaraba_content_hub.articles.add.frontend')->toString(),
            '#cache' => [
                'tags' => ['content_article_list'],
                'contexts' => ['url.query_args'],
            ],
            '#attached' => [
                'library' => ['ecosistema_jaraba_theme/content-hub'],
            ],
        ];
    }

    /**
     * Add article form (frontend wrapper).
     *
     * Detects AJAX requests and returns only the form HTML for slide-panel.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   Render array or Response for AJAX requests.
     */
    public function add(Request $request): array|Response
    {
        $article = $this->entityTypeManager()
            ->getStorage('content_article')
            ->create();

        $form = $this->entityFormBuilder()->getForm($article, 'add');

        // AJAX → return only the form HTML for slide-panel.
        if ($ajax = $this->renderFormForAjax($form, $request)) {
            return $ajax;
        }

        // Regular request → full page with premium wrapper.
        return [
            '#theme' => 'premium_form_wrapper',
            '#form' => $form,
            '#title' => $this->t('New Article'),
            '#back_url' => Url::fromRoute('jaraba_content_hub.articles.frontend')->toString(),
            '#back_label' => $this->t('Back to Articles'),
            '#entity_type_label' => $this->t('Article'),
            '#attached' => [
                'library' => ['ecosistema_jaraba_theme/content-hub'],
            ],
        ];
    }

    /**
     * Edit article form (frontend wrapper).
     *
     * Detects AJAX requests and returns only the form HTML for slide-panel.
     *
     * @param \Drupal\jaraba_content_hub\Entity\ContentArticle $content_article
     *   The article to edit.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   Render array or Response for AJAX requests.
     */
    public function edit(ContentArticle $content_article, Request $request): array|Response
    {
        $form = $this->entityFormBuilder()->getForm($content_article, 'edit');

        // AJAX → return only the form HTML for slide-panel.
        if ($ajax = $this->renderFormForAjax($form, $request)) {
            return $ajax;
        }

        // Regular request → full page with premium wrapper.
        return [
            '#theme' => 'premium_form_wrapper',
            '#form' => $form,
            '#title' => $this->t('Edit: @title', ['@title' => $content_article->label()]),
            '#back_url' => Url::fromRoute('jaraba_content_hub.articles.frontend')->toString(),
            '#back_label' => $this->t('Back to Articles'),
            '#entity_type_label' => $this->t('Article'),
            '#attached' => [
                'library' => ['ecosistema_jaraba_theme/content-hub'],
            ],
        ];
    }

}
