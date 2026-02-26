<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\CommandBar;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides navigation commands for the command bar.
 *
 * Searches through predefined site routes (dashboard, blog, plans, etc.)
 * and returns matching navigation items.
 */
class NavigationCommandProvider implements CommandProviderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function search(string $query, int $limit = 5): array {
    $routes = $this->getNavigationRoutes();
    $query_lower = mb_strtolower($query);
    $results = [];

    foreach ($routes as $route) {
      $label_lower = mb_strtolower($route['label']);
      $keywords = mb_strtolower($route['keywords'] ?? $route['label']);

      // Match against label and keywords.
      if (str_contains($label_lower, $query_lower) || str_contains($keywords, $query_lower)) {
        $score = str_starts_with($label_lower, $query_lower) ? 90 : 70;
        $results[] = [
          'label' => $route['label'],
          'url' => $route['url'],
          'icon' => $route['icon'],
          'category' => (string) $this->t('Navigation'),
          'score' => $score,
        ];
      }

      if (count($results) >= $limit) {
        break;
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(AccountInterface $account): bool {
    return TRUE;
  }

  /**
   * Returns the list of navigable routes.
   *
   * @return array
   *   Array of route definitions with label, url, icon, and keywords.
   */
  protected function getNavigationRoutes(): array {
    return [
      [
        'label' => (string) $this->t('Dashboard'),
        'url' => Url::fromRoute('ecosistema_jaraba_core.tenant.dashboard')->toString(),
        'icon' => 'dashboard',
        'keywords' => 'dashboard panel inicio home',
      ],
      [
        'label' => (string) $this->t('Blog'),
        'url' => Url::fromRoute('jaraba_content_hub.blog')->toString(),
        'icon' => 'article',
        'keywords' => 'blog articulos posts noticias',
      ],
      [
        'label' => (string) $this->t('Content Hub'),
        'url' => Url::fromRoute('jaraba_content_hub.dashboard.frontend')->toString(),
        'icon' => 'hub',
        'keywords' => 'content hub contenido articulos categorias',
      ],
      [
        'label' => (string) $this->t('Plans & Pricing'),
        'url' => '/planes',
        'icon' => 'payments',
        'keywords' => 'planes precios pricing suscripcion',
      ],
      [
        'label' => (string) $this->t('My Account'),
        'url' => '/mi-cuenta',
        'icon' => 'account_circle',
        'keywords' => 'cuenta perfil profile settings configuracion',
      ],
      [
        'label' => (string) $this->t('Usage'),
        'url' => '/mi-cuenta/uso',
        'icon' => 'analytics',
        'keywords' => 'uso consumo usage metricas tokens',
      ],
      [
        'label' => (string) $this->t('AI Playground'),
        'url' => '/demo/ai-playground',
        'icon' => 'smart_toy',
        'keywords' => 'ai inteligencia artificial playground demo copilot',
      ],
      [
        'label' => (string) $this->t('Contact'),
        'url' => '/contacto',
        'icon' => 'contact_mail',
        'keywords' => 'contacto soporte help ayuda',
      ],
    ];
  }

}
