<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for mentoring routes.
 */
class MentoringBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Routes handled by this builder.
   */
  protected const ROUTES = [
    'jaraba_mentoring.mentor_catalog',
    'jaraba_mentoring.mentor_public_profile',
    'jaraba_mentoring.become_mentor',
    'jaraba_mentoring.mentor_dashboard',
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
      case 'jaraba_mentoring.mentor_catalog':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        break;

      case 'jaraba_mentoring.mentor_public_profile':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Mentores'), 'jaraba_mentoring.mentor_catalog'));
        $mentor = $route_match->getParameter('mentor_profile');
        if ($mentor) {
          $breadcrumb->addCacheableDependency($mentor);
        }
        break;

      case 'jaraba_mentoring.become_mentor':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Mentores'), 'jaraba_mentoring.mentor_catalog'));
        break;

      case 'jaraba_mentoring.mentor_dashboard':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Emprendimiento'), 'jaraba_business_tools.entrepreneur_dashboard'));
        break;
    }

    return $breadcrumb;
  }

}
