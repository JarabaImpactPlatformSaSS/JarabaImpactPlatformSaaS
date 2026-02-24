<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for business tools routes.
 */
class BusinessToolsBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Routes handled by this builder.
   */
  protected const ROUTES = [
    'jaraba_business_tools.entrepreneur_dashboard',
    'jaraba_business_tools.program_dashboard',
    'entity.business_model_canvas.canonical',
    'entity.business_model_canvas.edit_form',
    'entity.business_model_canvas.collection',
    'entity.mvp_hypothesis.canonical',
    'entity.mvp_hypothesis.edit_form',
    'entity.financial_projection.canonical',
  ];

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL): bool {
    $cacheable_metadata?->addCacheContexts(['route']);
    return in_array($route_match->getRouteName(), self::ROUTES, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addCacheContexts(['route']);

    $route_name = $route_match->getRouteName();

    switch ($route_name) {
      case 'jaraba_business_tools.entrepreneur_dashboard':
        // Home > Dashboard (current page).
        break;

      case 'jaraba_business_tools.program_dashboard':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        break;

      case 'entity.business_model_canvas.collection':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        break;

      case 'entity.business_model_canvas.canonical':
      case 'entity.business_model_canvas.edit_form':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Mis Canvas'), 'entity.business_model_canvas.collection'));
        $canvas = $route_match->getParameter('business_model_canvas');
        if ($canvas) {
          $breadcrumb->addCacheableDependency($canvas);
        }
        break;

      case 'entity.mvp_hypothesis.canonical':
      case 'entity.mvp_hypothesis.edit_form':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        break;

      case 'entity.financial_projection.canonical':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        break;
    }

    return $breadcrumb;
  }

}
