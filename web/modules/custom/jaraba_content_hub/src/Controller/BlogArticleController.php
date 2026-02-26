<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for individual blog article view with slug support.
 *
 * Handles slug-based canonical URLs for articles. If accessed via numeric ID
 * and the article has a slug, issues a 301 redirect to the slug URL for SEO.
 */
class BlogArticleController extends ControllerBase {

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a BlogArticleController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
    );
  }

  /**
   * Renders a blog article or redirects numeric IDs to slug URLs.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface|null $content_article
   *   The article loaded by ParamConverter (by slug or ID).
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array or 301 redirect.
   */
  public function view(?ContentArticleInterface $content_article) {
    if (!$content_article) {
      throw new NotFoundHttpException();
    }

    // Access check: only published articles are publicly viewable.
    if (!$content_article->isPublished() && !$this->currentUser()->hasPermission('view unpublished content articles')) {
      throw new AccessDeniedHttpException();
    }

    // 301 redirect: if accessed via numeric ID and article has a slug,
    // redirect to the clean slug URL for SEO canonicalization.
    $request = $this->requestStack->getCurrentRequest();
    $path_parts = explode('/', trim($request->getPathInfo(), '/'));
    $raw_param = end($path_parts);

    if (is_numeric($raw_param) && !empty($content_article->getSlug())) {
      $slug_url = Url::fromRoute('entity.content_article.canonical', [
        'content_article' => $content_article->getSlug(),
      ])->setAbsolute()->toString();
      return new RedirectResponse($slug_url, 301);
    }

    // Render the article using the entity view builder.
    $view_builder = $this->entityTypeManager->getViewBuilder('content_article');
    $build = $view_builder->view($content_article, 'full');

    $build['#cache']['tags'][] = 'content_article:' . $content_article->id();
    $build['#cache']['contexts'][] = 'url.path';

    return $build;
  }

  /**
   * Title callback for the article page.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface|null $content_article
   *   The content article entity.
   *
   * @return string
   *   The article title.
   */
  public function title(?ContentArticleInterface $content_article): string {
    if (!$content_article) {
      return '';
    }
    return $content_article->getTitle();
  }

}
