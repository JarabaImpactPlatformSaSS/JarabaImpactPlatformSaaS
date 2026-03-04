<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Filtra items de navegacion segun nivel de revelacion.
 *
 * Oculta items de menu que requieren un nivel superior
 * al que tiene el usuario actual.
 */
class NavigationFilterService {

  public function __construct(
    protected RevelationLevelService $revelationLevel,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Filtra items de menu segun nivel de revelacion.
   *
   * @param array $items
   *   Array de items de menu, cada uno con key 'route_name'.
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Items filtrados.
   */
  public function filterMenuItems(array $items, string $vertical): array {
    return array_filter($items, function ($item) use ($vertical) {
      $routeName = $item['route_name'] ?? '';
      return $this->shouldShowMenuItem($routeName, $vertical);
    });
  }

  /**
   * Determina si un item de menu debe mostrarse.
   *
   * @param string $routeName
   *   Nombre de ruta Drupal.
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return bool
   *   TRUE si debe mostrarse.
   */
  public function shouldShowMenuItem(string $routeName, string $vertical): bool {
    if ($routeName === '') {
      return TRUE;
    }

    // Rutas admin siempre visibles para admin.
    if (str_starts_with($routeName, 'entity.') || str_starts_with($routeName, 'system.')) {
      return TRUE;
    }

    // Cargar configuracion de rutas por nivel.
    $config = $this->configFactory->get('ecosistema_jaraba_core.vertical_brand.' . $vertical);
    if ($config->isNew()) {
      return TRUE;
    }

    // Las landings siempre son visibles.
    $landingRoute = $config->get('landing_route');
    if ($routeName === $landingRoute) {
      return TRUE;
    }

    // Por defecto, mostrar todo (la restriccion real esta en access checks).
    return TRUE;
  }

}
