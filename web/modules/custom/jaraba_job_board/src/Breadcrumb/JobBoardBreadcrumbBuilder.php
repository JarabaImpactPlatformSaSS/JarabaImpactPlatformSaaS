<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for job board routes.
 */
class JobBoardBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Routes handled by this builder.
   */
  protected const ROUTES = [
    'jaraba_job_board.search',
    'jaraba_job_board.job_detail',
    'jaraba_job_board.my_applications',
    'jaraba_job_board.saved_jobs',
    'jaraba_job_board.job_alerts',
    'jaraba_job_board.apply',
    'jaraba_job_board.employer_dashboard',
    'jaraba_job_board.employer_jobs',
    'jaraba_job_board.employer_applications',
    'jaraba_job_board.application_detail',
    'jaraba_job_board.my_company',
    'jaraba_job_board.my_company_analytics',
    'jaraba_job_board.my_company_jobs',
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
      case 'jaraba_job_board.search':
        // Home > Jobs (current page, no link).
        break;

      case 'jaraba_job_board.job_detail':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Jobs'), 'jaraba_job_board.search'));
        $job_posting = $route_match->getParameter('job_posting');
        if ($job_posting) {
          $breadcrumb->addCacheableDependency($job_posting);
        }
        break;

      case 'jaraba_job_board.apply':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Jobs'), 'jaraba_job_board.search'));
        $job_posting = $route_match->getParameter('job_posting');
        if ($job_posting) {
          $breadcrumb->addLink(Link::createFromRoute(
            $job_posting->getTitle(),
            'jaraba_job_board.job_detail',
            ['job_posting' => $job_posting->id()]
          ));
          $breadcrumb->addCacheableDependency($job_posting);
        }
        break;

      case 'jaraba_job_board.my_applications':
        // Home > My Applications.
        break;

      case 'jaraba_job_board.saved_jobs':
        // Home > Saved Jobs.
        break;

      case 'jaraba_job_board.job_alerts':
        // Home > Job Alerts.
        break;

      case 'jaraba_job_board.employer_dashboard':
        // Home > Employer Dashboard.
        break;

      case 'jaraba_job_board.employer_jobs':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Employer Dashboard'), 'jaraba_job_board.employer_dashboard'));
        break;

      case 'jaraba_job_board.employer_applications':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Employer Dashboard'), 'jaraba_job_board.employer_dashboard'));
        break;

      case 'jaraba_job_board.application_detail':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Employer Dashboard'), 'jaraba_job_board.employer_dashboard'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Applications'), 'jaraba_job_board.employer_applications'));
        break;

      case 'jaraba_job_board.my_company':
        // Home > My Company.
        break;

      case 'jaraba_job_board.my_company_analytics':
        $breadcrumb->addLink(Link::createFromRoute($this->t('My Company'), 'jaraba_job_board.my_company'));
        break;

      case 'jaraba_job_board.my_company_jobs':
        $breadcrumb->addLink(Link::createFromRoute($this->t('My Company'), 'jaraba_job_board.my_company'));
        break;
    }

    return $breadcrumb;
  }

}
