<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for JarabaLex LexNET routes.
 */
class LexnetBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    $route_name = $route_match->getRouteName() ?? '';
    return str_starts_with($route_name, 'jaraba_legal_lexnet.');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    // Home.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $route_name = $route_match->getRouteName();

    // Dashboard: Home > JarabaLex
    if ($route_name === 'jaraba_legal_lexnet.dashboard') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('JarabaLex'),
        'jaraba_legal_lexnet.dashboard'
      ));
      return $breadcrumb;
    }

    // Settings routes: Home > JarabaLex > Section
    $settingsSections = [
      'jaraba_legal_lexnet.lexnet_notification.settings' => $this->t('Configuración Notificaciones'),
      'jaraba_legal_lexnet.lexnet_submission.settings' => $this->t('Configuración Envíos'),
    ];

    if (isset($settingsSections[$route_name])) {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('JarabaLex'),
        'jaraba_legal_lexnet.dashboard'
      ));
      $breadcrumb->addLink(Link::createFromRoute(
        $settingsSections[$route_name],
        $route_name
      ));
      return $breadcrumb;
    }

    return $breadcrumb;
  }

}
