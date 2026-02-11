<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Negociador de tema para rutas de usuario.
 *
 * Fuerza el uso del tema frontend para las páginas de usuario
 * (perfil, edición) en lugar del tema de administración.
 *
 * Directrices aplicadas:
 * - Nuclear #14: Frontend Limpio
 * - Branding 3.6: Portal Isolation
 */
class UserPageThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Rutas de usuario que deben usar el tema frontend.
   *
   * @var array
   */
  protected const USER_ROUTES = [
    'entity.user.canonical',
    'entity.user.edit_form',
    'user.logout.confirm',
    'user.login',
    'user.register',
    'user.pass',
    'user.reset.form',
    'user.reset.login',
  ];

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    $route_name = $route_match->getRouteName();
    return in_array($route_name, self::USER_ROUTES, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match): ?string {
    // Retornar el nombre del tema frontend de Jaraba.
    return 'ecosistema_jaraba_theme';
  }

}
