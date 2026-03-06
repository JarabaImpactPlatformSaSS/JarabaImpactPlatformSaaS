<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Base class con defaults para secciones de perfil de usuario.
 *
 * Patron identico a AbstractTenantSettingsSection.
 *
 * @see \Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection
 */
abstract class AbstractUserProfileSection implements UserProfileSectionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraData(int $uid): array {
    return [];
  }

  /**
   * Resuelve una ruta a URL string. Retorna NULL si la ruta no existe.
   *
   * ROUTE-LANGPREFIX-001: URLs SIEMPRE via Url::fromRoute().
   */
  protected function resolveRoute(string $route, array $params = []): ?string {
    try {
      return Url::fromRoute($route, $params)->toString();
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Construye un link solo si la ruta existe.
   *
   * @return array<string, mixed>|null
   *   Array con formato de link para el template, o NULL si la ruta no existe.
   */
  protected function makeLink(
    string|\Stringable $label,
    string $route,
    string $iconCategory,
    string $iconName,
    string $color,
    array $options = [],
  ): ?array {
    $url = $this->resolveRoute($route, $options['params'] ?? []);
    if ($url === NULL) {
      return NULL;
    }
    return [
      'label' => $label,
      'url' => $url,
      'icon_category' => $iconCategory,
      'icon_name' => $iconName,
      'color' => $color,
      'description' => $options['description'] ?? '',
      'slide_panel' => $options['slide_panel'] ?? FALSE,
      'slide_panel_title' => $options['slide_panel_title'] ?? $label,
      'cross_vertical' => $options['cross_vertical'] ?? FALSE,
    ];
  }

}
