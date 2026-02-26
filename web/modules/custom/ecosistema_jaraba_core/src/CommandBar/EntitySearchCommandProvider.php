<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\CommandBar;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides entity search results for the command bar.
 *
 * Searches ContentArticle and PageContent entities by label/title.
 * Results are tenant-scoped where applicable.
 */
class EntitySearchCommandProvider implements CommandProviderInterface {

  use StringTranslationTrait;

  /**
   * Constructs an EntitySearchCommandProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function search(string $query, int $limit = 5): array {
    $results = [];

    // Search content articles.
    $results = array_merge($results, $this->searchArticles($query, $limit));

    return array_slice($results, 0, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(AccountInterface $account): bool {
    return $account->hasPermission('access content');
  }

  /**
   * Searches ContentArticle entities by title.
   *
   * @param string $query
   *   The search query.
   * @param int $limit
   *   Maximum results.
   *
   * @return array
   *   Matching results.
   */
  protected function searchArticles(string $query, int $limit): array {
    try {
      $storage = $this->entityTypeManager->getStorage('content_article');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('title', '%' . $query . '%', 'LIKE')
        ->condition('status', 'published')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $articles = $storage->loadMultiple($ids);
      $results = [];

      foreach ($articles as $article) {
        $slug = $article->getSlug();
        $url = !empty($slug)
          ? Url::fromRoute('entity.content_article.canonical', ['content_article' => $slug])->toString()
          : $article->toUrl()->toString();

        $results[] = [
          'label' => $article->getTitle(),
          'url' => $url,
          'icon' => 'article',
          'category' => (string) $this->t('Articles'),
          'score' => 60,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
