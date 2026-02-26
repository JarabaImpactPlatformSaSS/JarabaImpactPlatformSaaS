<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\CommandBar;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides quick action commands for the command bar.
 *
 * Actions like "Create article", "Open copilot", "Go to dashboard"
 * filtered by user permissions.
 */
class ActionCommandProvider implements CommandProviderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function search(string $query, int $limit = 5): array {
    $actions = $this->getActions();
    $query_lower = mb_strtolower($query);
    $results = [];

    foreach ($actions as $action) {
      $label_lower = mb_strtolower($action['label']);
      $keywords = mb_strtolower($action['keywords'] ?? $action['label']);

      if (str_contains($label_lower, $query_lower) || str_contains($keywords, $query_lower)) {
        $score = str_starts_with($label_lower, $query_lower) ? 95 : 75;
        $results[] = [
          'label' => $action['label'],
          'url' => $action['url'],
          'icon' => $action['icon'],
          'category' => (string) $this->t('Actions'),
          'score' => $score,
          'permission' => $action['permission'] ?? NULL,
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
    return $account->isAuthenticated();
  }

  /**
   * Returns the list of available quick actions.
   *
   * @return array
   *   Array of action definitions.
   */
  protected function getActions(): array {
    return [
      [
        'label' => (string) $this->t('Create Article'),
        'url' => '/content-hub/articles/add',
        'icon' => 'add_circle',
        'keywords' => 'crear articulo nuevo write post',
        'permission' => 'create content article',
      ],
      [
        'label' => (string) $this->t('Create Category'),
        'url' => '/content-hub/categories/add',
        'icon' => 'create_new_folder',
        'keywords' => 'crear categoria nueva',
        'permission' => 'administer content categories',
      ],
      [
        'label' => (string) $this->t('Open AI Copilot'),
        'url' => '#copilot-toggle',
        'icon' => 'smart_toy',
        'keywords' => 'copilot ai inteligencia artificial chat asistente',
      ],
      [
        'label' => (string) $this->t('View Usage'),
        'url' => '/mi-cuenta/uso',
        'icon' => 'bar_chart',
        'keywords' => 'uso consumo metricas tokens facturacion',
      ],
      [
        'label' => (string) $this->t('Edit Profile'),
        'url' => '/mi-cuenta/editar',
        'icon' => 'edit',
        'keywords' => 'perfil editar configuracion cuenta',
      ],
    ];
  }

}
