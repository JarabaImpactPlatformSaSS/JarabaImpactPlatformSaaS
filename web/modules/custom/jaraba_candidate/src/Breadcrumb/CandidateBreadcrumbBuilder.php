<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for candidate profile routes.
 */
class CandidateBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Routes handled by this builder.
   */
  protected const ROUTES = [
    'jaraba_candidate.profile_view',
    'jaraba_candidate.my_profile',
    'jaraba_candidate.my_profile.edit',
    'jaraba_candidate.my_profile.experience',
    'jaraba_candidate.my_profile.education',
    'jaraba_candidate.my_profile.skills',
    'jaraba_candidate.my_profile.privacy',
    'jaraba_candidate.cv_builder',
    'jaraba_candidate.cv_preview',
    'jaraba_candidate.cv_download',
    'jaraba_candidate.dashboard',
    'jaraba_candidate.dashboard.recommendations',
    'jaraba_candidate.dashboard.stats',
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
      case 'jaraba_candidate.profile_view':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Jobs'), 'jaraba_job_board.search'));
        $profile = $route_match->getParameter('candidate_profile');
        if ($profile) {
          $breadcrumb->addCacheableDependency($profile);
        }
        break;

      case 'jaraba_candidate.my_profile':
        // Home > My Profile (current page).
        break;

      case 'jaraba_candidate.my_profile.edit':
      case 'jaraba_candidate.my_profile.experience':
      case 'jaraba_candidate.my_profile.education':
      case 'jaraba_candidate.my_profile.skills':
      case 'jaraba_candidate.my_profile.privacy':
        $breadcrumb->addLink(Link::createFromRoute($this->t('My Profile'), 'jaraba_candidate.my_profile'));
        break;

      case 'jaraba_candidate.cv_builder':
        $breadcrumb->addLink(Link::createFromRoute($this->t('My Profile'), 'jaraba_candidate.my_profile'));
        break;

      case 'jaraba_candidate.cv_preview':
      case 'jaraba_candidate.cv_download':
        $breadcrumb->addLink(Link::createFromRoute($this->t('My Profile'), 'jaraba_candidate.my_profile'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('CV Builder'), 'jaraba_candidate.cv_builder'));
        break;

      case 'jaraba_candidate.dashboard':
        // Home > Dashboard (current page).
        break;

      case 'jaraba_candidate.dashboard.recommendations':
      case 'jaraba_candidate.dashboard.stats':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Dashboard'), 'jaraba_candidate.dashboard'));
        break;
    }

    return $breadcrumb;
  }

}
