<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for diagnostic routes.
 */
class DiagnosticBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Routes handled by this builder.
   */
  protected const ROUTES = [
    'jaraba_diagnostic.employability.landing',
    'jaraba_diagnostic.employability.results',
    'jaraba_diagnostic.wizard.start',
    'jaraba_diagnostic.wizard.step',
    'jaraba_diagnostic.wizard.results',
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
      case 'jaraba_diagnostic.employability.landing':
        // Home > Employability Diagnostic (current page).
        break;

      case 'jaraba_diagnostic.employability.results':
        $breadcrumb->addLink(Link::createFromRoute(
          $this->t('Employability Diagnostic'),
          'jaraba_diagnostic.employability.landing'
        ));
        break;

      case 'jaraba_diagnostic.wizard.start':
        // Home > Diagnostic (current page).
        break;

      case 'jaraba_diagnostic.wizard.step':
        $breadcrumb->addLink(Link::createFromRoute(
          $this->t('Diagnostic'),
          'jaraba_diagnostic.wizard.start'
        ));
        break;

      case 'jaraba_diagnostic.wizard.results':
        $breadcrumb->addLink(Link::createFromRoute(
          $this->t('Diagnostic'),
          'jaraba_diagnostic.wizard.start'
        ));
        break;
    }

    return $breadcrumb;
  }

}
