<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for Content Hub Editor Dashboard.
 *
 * Provides a visual interface for content editors to manage
 * articles, categories, and view content performance.
 */
class ContentHubDashboardController extends ControllerBase
{

    /**
     * Renders the Content Hub Editor Dashboard.
     */
    public function dashboard(): array
    {
        $stats = $this->getContentStats();
        $recentArticles = $this->getRecentArticles(5);
        $topCategories = $this->getTopCategories(5);
        $drafts = $this->getDraftArticles(5);

        return [
            '#theme' => 'content_hub_dashboard',
            '#stats' => $stats,
            '#recent_articles' => $recentArticles,
            '#top_categories' => $topCategories,
            '#drafts' => $drafts,
            '#quick_actions' => $this->getQuickActions(),
            '#attached' => [
                'library' => ['ecosistema_jaraba_theme/content-hub'],
            ],
            '#cache' => [
                'tags' => ['content_article_list', 'content_category_list'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Renders the Content Hub Dashboard for frontend (tenant editors).
     *
     * Uses a clean page template with header/footer, no admin theme.
     */
    public function dashboardFrontend(): array
    {
        $stats = $this->getContentStats();
        $recentArticles = $this->getRecentArticles(5);
        $topCategories = $this->getTopCategories(5);
        $drafts = $this->getDraftArticles(5);

        // Get theme settings for partials using Config API (Drupal 11 compatible)
        $themeHandler = \Drupal::service('theme_handler');
        $activeTheme = $themeHandler->getDefault();

        // Get theme settings from config instead of theme_get_setting()
        $themeConfig = \Drupal::config($activeTheme . '.settings');
        $themeSettings = $themeConfig->get() ?: [];

        // Get site config
        $config = \Drupal::config('system.site');
        $siteName = $config->get('name') ?: 'Jaraba Impact Platform';

        // Get logo path using theme_get_setting with specific setting name
        $logoSettings = theme_get_setting('logo', $activeTheme);
        $logo = '';
        if (!empty($logoSettings['use_default'])) {
            $logo = '/' . \Drupal::service('extension.list.theme')->getPath($activeTheme) . '/logo.svg';
        } elseif (!empty($logoSettings['path'])) {
            $logo = $logoSettings['path'];
        }

        return [
            '#theme' => 'content_hub_dashboard_frontend',
            '#stats' => $stats,
            '#recent_articles' => $recentArticles,
            '#top_categories' => $topCategories,
            '#drafts' => $drafts,
            '#quick_actions' => $this->getQuickActionsFrontend(),
            // Theme variables for partials
            '#site_name' => $siteName,
            '#logo' => $logo,
            '#logged_in' => \Drupal::currentUser()->isAuthenticated(),
            '#theme_settings' => $themeSettings,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_theme/global',
                    'ecosistema_jaraba_theme/content-hub',
                ],
            ],
            '#cache' => [
                'tags' => ['content_article_list', 'content_category_list', 'config:system.site'],
                'max-age' => 300,
            ],
        ];
    }



    /**
     * Gets content statistics.
     */
    protected function getContentStats(): array
    {
        $articleStorage = $this->entityTypeManager()->getStorage('content_article');
        $categoryStorage = $this->entityTypeManager()->getStorage('content_category');

        // Total articles.
        $totalArticles = $articleStorage->getQuery()
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Published articles.
        $publishedArticles = $articleStorage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Draft articles.
        $draftArticles = $articleStorage->getQuery()
            ->condition('status', 'draft')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Total categories.
        $totalCategories = $categoryStorage->getQuery()
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Articles this month.
        $startOfMonth = strtotime('first day of this month midnight');
        $articlesThisMonth = $articleStorage->getQuery()
            ->condition('created', $startOfMonth, '>=')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // AI-assisted articles.
        $aiArticles = $articleStorage->getQuery()
            ->condition('ai_generated', TRUE)
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        return [
            'total_articles' => (int) $totalArticles,
            'published' => (int) $publishedArticles,
            'drafts' => (int) $draftArticles,
            'categories' => (int) $totalCategories,
            'this_month' => (int) $articlesThisMonth,
            'ai_assisted' => (int) $aiArticles,
            'publish_rate' => $totalArticles > 0
                ? round(($publishedArticles / $totalArticles) * 100)
                : 0,
        ];
    }

    /**
     * Gets recent articles.
     */
    protected function getRecentArticles(int $limit): array
    {
        $storage = $this->entityTypeManager()->getStorage('content_article');
        $ids = $storage->getQuery()
            ->sort('changed', 'DESC')
            ->range(0, $limit)
            ->accessCheck(FALSE)
            ->execute();

        $articles = $storage->loadMultiple($ids);
        $result = [];

        foreach ($articles as $article) {
            $result[] = [
                'id' => $article->id(),
                'title' => $article->label(),
                'status' => $article->get('status')->value ?? 'draft',
                'changed' => $article->get('changed')->value,
                'author' => $article->getOwner() ? $article->getOwner()->getDisplayName() : 'Unknown',
                'edit_url' => Url::fromRoute('entity.content_article.edit_form', ['content_article' => $article->id()])->toString(),
                'view_url' => $article->hasField('slug') && $article->get('slug')->value
                    ? '/blog/' . $article->get('slug')->value
                    : '/blog/article/' . $article->id(),
            ];
        }

        return $result;
    }

    /**
     * Gets draft articles awaiting publication.
     */
    protected function getDraftArticles(int $limit): array
    {
        $storage = $this->entityTypeManager()->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('status', 'draft')
            ->sort('changed', 'DESC')
            ->range(0, $limit)
            ->accessCheck(FALSE)
            ->execute();

        $articles = $storage->loadMultiple($ids);
        $result = [];

        foreach ($articles as $article) {
            $result[] = [
                'id' => $article->id(),
                'title' => $article->label(),
                'changed' => $article->get('changed')->value,
                'author' => $article->getOwner() ? $article->getOwner()->getDisplayName() : 'Unknown',
                'edit_url' => Url::fromRoute('entity.content_article.edit_form', ['content_article' => $article->id()])->toString(),
            ];
        }

        return $result;
    }

    /**
     * Gets top categories by article count.
     */
    protected function getTopCategories(int $limit): array
    {
        $categoryStorage = $this->entityTypeManager()->getStorage('content_category');
        $articleStorage = $this->entityTypeManager()->getStorage('content_article');

        $categories = $categoryStorage->loadMultiple();
        $result = [];

        foreach ($categories as $category) {
            $count = $articleStorage->getQuery()
                ->condition('category', $category->id())
                ->condition('status', 'published')
                ->accessCheck(FALSE)
                ->count()
                ->execute();

            $result[] = [
                'id' => $category->id(),
                'name' => $category->label(),
                'color' => $category->hasField('color') ? $category->get('color')->value : '#233D63',
                'count' => (int) $count,
                'url' => Url::fromRoute('entity.content_category.collection')->toString(),
            ];
        }

        // Sort by count descending.
        usort($result, fn($a, $b) => $b['count'] - $a['count']);

        return array_slice($result, 0, $limit);
    }

    /**
     * Gets quick action links.
     */
    protected function getQuickActions(): array
    {
        return [
            [
                'title' => $this->t('New Article'),
                'description' => $this->t('Create a new blog article'),
                'url' => Url::fromRoute('entity.content_article.add_form')->toString(),
                'icon' => 'plus',
                'primary' => TRUE,
            ],
            [
                'title' => $this->t('AI Assistant'),
                'description' => $this->t('Generate content with AI'),
                'url' => Url::fromRoute('entity.content_article.collection')->toString(),
                'icon' => 'sparkles',
                'primary' => FALSE,
            ],
            [
                'title' => $this->t('Manage Categories'),
                'description' => $this->t('Organize your content'),
                'url' => Url::fromRoute('entity.content_category.collection')->toString(),
                'icon' => 'folder',
                'primary' => FALSE,
            ],
            [
                'title' => $this->t('View Blog'),
                'description' => $this->t('See your live blog'),
                'url' => '/blog',
                'icon' => 'eye',
                'primary' => FALSE,
            ],
        ];
    }

    /**
     * Gets quick action links for frontend (tenant editors).
     *
     * Uses admin routes since /content-hub/* doesn't have full CRUD yet.
     * Links open in slide-panel modal via data-slide-panel attribute in template.
     */
    protected function getQuickActionsFrontend(): array
    {
        return [
            [
                'title' => $this->t('New Article'),
                'description' => $this->t('Create a new blog article'),
                // Use AJAX-aware route that returns only form HTML for slide-panel
            'url' => Url::fromRoute('jaraba_content_hub.articles.add.frontend')->toString(),
                'icon' => 'plus',
                'primary' => TRUE,
                'modal' => TRUE,
            ],
            [
                'title' => $this->t('AI Assistant'),
                'description' => $this->t('Generate content with AI'),
                // TODO: Create dedicated AI writing assistant route
            'url' => Url::fromRoute('jaraba_content_hub.articles.add.frontend')->toString(),
                'icon' => 'sparkles',
                'primary' => FALSE,
                'modal' => TRUE,
            ],
            [
                'title' => $this->t('Manage Categories'),
                'description' => $this->t('Organize your content'),
                // Use frontend categories route
            'url' => Url::fromRoute('jaraba_content_hub.categories.frontend')->toString(),
                'icon' => 'folder',
                'primary' => FALSE,
                'modal' => FALSE,
            ],
            [
                'title' => $this->t('View Blog'),
                'description' => $this->t('See your live blog'),
                'url' => Url::fromRoute('jaraba_content_hub.blog')->toString(),
                'icon' => 'eye',
                'primary' => FALSE,
                'modal' => FALSE,
            ],
        ];
    }


}
