<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Fase B: Puente entre SiteMenuItem entities y el mega menú del header.
 *
 * Lee los SiteMenuItem de tipo 'mega_column' y 'heading' de un menú
 * configurado y genera el array mega_menu_columns que consume
 * _header-classic.html.twig.
 *
 * Prioridad de datos:
 * 1. Si hay SiteMenuItems configurados → usa datos dinámicos de DB.
 * 2. Si no → fallback a datos hardcodeados en preprocess (array PHP estático).
 *
 * Esto permite editar el mega menú desde /admin/structure/site-menu-items
 * sin tocar código.
 */
class MegaMenuBridgeService {

  use StringTranslationTrait;

  /**
   * Machine name del menú para el mega menú del SaaS principal.
   */
  private const MEGA_MENU_NAME = 'mega_menu_soluciones';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtener las columnas del mega menú desde SiteMenuItem entities.
   *
   * @return array|null
   *   Array de columnas para el mega menú, o NULL si no hay menú configurado
   *   (en cuyo caso se usa el fallback hardcodeado).
   */
  public function getMegaMenuColumns(): ?array {
    try {
      if (!$this->entityTypeManager->hasDefinition('site_menu_item')) {
        return NULL;
      }

      $storage = $this->entityTypeManager->getStorage('site_menu_item');

      // Buscar el menú por machine name.
      $menuStorage = $this->entityTypeManager->getStorage('site_menu');
      $menus = $menuStorage->loadByProperties([
        'machine_name' => self::MEGA_MENU_NAME,
      ]);

      if (empty($menus)) {
        return NULL;
      }

      $menu = reset($menus);
      $menuId = $menu->id();

      // Cargar items de primer nivel (headings = columnas).
      $items = $storage->loadByProperties([
        'menu_id' => $menuId,
        'parent_id' => NULL,
        'status' => TRUE,
      ]);

      if (empty($items)) {
        return NULL;
      }

      // Ordenar por peso.
      uasort($items, function ($a, $b) {
        return ($a->get('weight')->value ?? 0) <=> ($b->get('weight')->value ?? 0);
      });

      $columns = [];
      foreach ($items as $item) {
        $column = [
          'title' => $item->get('title')->value ?? '',
          'items' => [],
          'has_promo' => FALSE,
        ];

        // Cargar hijos (los links dentro de cada columna).
        $children = $storage->loadByProperties([
          'menu_id' => $menuId,
          'parent_id' => $item->id(),
          'status' => TRUE,
        ]);

        uasort($children, function ($a, $b) {
          return ($a->get('weight')->value ?? 0) <=> ($b->get('weight')->value ?? 0);
        });

        foreach ($children as $child) {
          $itemType = $child->get('item_type')->value ?? 'link';

          if ($itemType === 'divider') {
            // Divider = promo flag para la columna.
            $column['has_promo'] = TRUE;
            continue;
          }

          $megaContent = $child->getMegaContent();
          $column['items'][] = [
            'title' => $child->get('title')->value ?? '',
            'subtitle' => $megaContent['subtitle'] ?? '',
            'icon_cat' => $megaContent['icon_cat'] ?? 'verticals',
            'icon_name' => $megaContent['icon_name'] ?? $child->get('icon')->value ?? '',
            'color' => $megaContent['color'] ?? 'azul-corporativo',
            'url' => $child->get('url')->value ?? '#',
          ];
        }

        $columns[] = $column;
      }

      return !empty($columns) ? $columns : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning('MegaMenuBridge error: @e', ['@e' => $e->getMessage()]);
      return NULL;
    }
  }

}
