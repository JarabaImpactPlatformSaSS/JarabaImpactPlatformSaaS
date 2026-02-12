<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for frontend category listing (tenant editors).
 *
 * IMPORTANTE: Este controlador reemplaza el listado de admin para que
 * los tenants no usen el tema de administración de Drupal.
 */
class CategoriesListController extends ControllerBase
{

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
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Lists categories for tenant editors (frontend theme).
   *
   * @return array
   *   Render array for the categories list.
   */
  public function list(): array
  {
    $storage = $this->entityTypeManager()->getStorage('content_category');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('name', 'ASC')
      ->execute();

    $categories = $storage->loadMultiple($ids);

    // Contar artículos por categoría.
    $article_storage = $this->entityTypeManager()->getStorage('content_article');
    $rows = [];
    foreach ($categories as $category) {
      /** @var \Drupal\jaraba_content_hub\Entity\ContentCategory $category */
      $article_count = $article_storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('category', $category->id())
        ->count()
        ->execute();

      $rows[] = [
        'id' => $category->id(),
        'name' => $category->label(),
        'color' => $category->get('color')->value ?? '#233D63',
        'description' => $category->get('description')->value ?? '',
        'article_count' => $article_count,
        'edit_url' => Url::fromRoute('entity.content_category.edit_form', [
          'content_category' => $category->id(),
        ])->toString(),
      ];
    }

    return [
      '#theme' => 'content_hub_categories_list',
      '#categories' => $rows,
      '#total_count' => count($categories),
      '#back_url' => Url::fromRoute('jaraba_content_hub.dashboard.frontend')->toString(),
      '#add_url' => Url::fromRoute('jaraba_content_hub.categories.add.frontend')->toString(),
      '#cache' => [
        'tags' => ['content_category_list'],
      ],
      '#attached' => [
        'library' => ['ecosistema_jaraba_theme/content-hub'],
      ],
    ];
  }

  /**
   * Add category form (frontend wrapper).
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
    $category = $this->entityTypeManager()
      ->getStorage('content_category')
      ->create();

    $form = $this->entityFormBuilder()->getForm($category, 'add');

    // If AJAX request, return only the form HTML
    if ($request->isXmlHttpRequest()) {
      try {
        $html = (string) $this->renderer->render($form);
        return new Response($html, 200, [
          'Content-Type' => 'text/html; charset=UTF-8',
        ]);
      } catch (\Exception $e) {
        \Drupal::logger('jaraba_content_hub')->error('Form render error: @message', [
          '@message' => $e->getMessage(),
        ]);
        return new Response(
          '<div class="slide-panel__error"><p>' . $this->t('Error loading form. Please try again.') . '</p></div>',
          500,
          ['Content-Type' => 'text/html; charset=UTF-8']
        );
      }
    }

    // Regular request - return full page with theme
    return [
      '#theme' => 'content_hub_category_form',
      '#form' => $form,
      '#title' => $this->t('New Category'),
      '#back_url' => Url::fromRoute('jaraba_content_hub.categories.frontend')->toString(),
      '#attached' => [
        'library' => ['ecosistema_jaraba_theme/content-hub'],
      ],
    ];
  }

}
