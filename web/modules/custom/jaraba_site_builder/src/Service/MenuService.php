<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestión de menús y items del sitio.
 */
class MenuService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene todos los menús del tenant actual.
   *
   * @return array
   *   Array de menús del tenant.
   */
  public function getMenus(?int $tenantId = NULL): array {
    $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('site_menu');
    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->sort('label', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene un menú por su machine_name para el tenant.
   *
   * @param string $machineName
   *   Nombre máquina del menú.
   * @param int|null $tenantId
   *   ID del tenant (o null para usar el actual).
   *
   * @return \Drupal\jaraba_site_builder\Entity\SiteMenu|null
   *   El menú o NULL.
   */
  public function getMenuByMachineName(string $machineName, ?int $tenantId = NULL): ?object {
    $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('site_menu');
    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('machine_name', $machineName)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene el árbol jerárquico de items de un menú.
   *
   * @param int $menuId
   *   ID del menú.
   *
   * @return array
   *   Árbol jerárquico de items.
   */
  public function getMenuTree(int $menuId): array {
    $storage = $this->entityTypeManager->getStorage('site_menu_item');
    $ids = $storage->getQuery()
      ->condition('menu_id', $menuId)
      ->condition('is_enabled', TRUE)
      ->accessCheck(FALSE)
      ->sort('weight', 'ASC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $items = $storage->loadMultiple($ids);
    return $this->buildTree($items);
  }

  /**
   * Obtiene todos los items de un menú (lista plana, incluyendo deshabilitados).
   *
   * @param int $menuId
   *   ID del menú.
   *
   * @return array
   *   Lista plana de items.
   */
  public function getAllMenuItems(int $menuId): array {
    $storage = $this->entityTypeManager->getStorage('site_menu_item');
    $ids = $storage->getQuery()
      ->condition('menu_id', $menuId)
      ->accessCheck(FALSE)
      ->sort('weight', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Añade un item a un menú.
   *
   * @param int $menuId
   *   ID del menú.
   * @param array $data
   *   Datos del item.
   *
   * @return \Drupal\jaraba_site_builder\Entity\SiteMenuItem
   *   El item creado.
   */
  public function addItem(int $menuId, array $data): object {
    $storage = $this->entityTypeManager->getStorage('site_menu_item');

    // Calcular peso automáticamente si no se provee.
    if (!isset($data['weight'])) {
      $parentId = $data['parent_id'] ?? NULL;
      $data['weight'] = $this->getNextWeight($menuId, $parentId);
    }

    // Calcular profundidad.
    if (!isset($data['depth']) && !empty($data['parent_id'])) {
      $parent = $storage->load($data['parent_id']);
      $data['depth'] = $parent ? ((int) $parent->get('depth')->value + 1) : 0;
    }

    $item = $storage->create([
      'menu_id' => $menuId,
      'title' => $data['title'] ?? '',
      'url' => $data['url'] ?? NULL,
      'page_id' => $data['page_id'] ?? NULL,
      'parent_id' => $data['parent_id'] ?? NULL,
      'item_type' => $data['item_type'] ?? 'link',
      'icon' => $data['icon'] ?? NULL,
      'badge_text' => $data['badge_text'] ?? NULL,
      'badge_color' => $data['badge_color'] ?? NULL,
      'highlight' => $data['highlight'] ?? FALSE,
      'mega_content' => $data['mega_content'] ?? NULL,
      'open_in_new_tab' => $data['open_in_new_tab'] ?? FALSE,
      'is_enabled' => $data['is_enabled'] ?? TRUE,
      'weight' => $data['weight'] ?? 0,
      'depth' => $data['depth'] ?? 0,
    ]);

    $item->save();

    $this->logger->info('Item de menú "@title" creado en menú @menu.', [
      '@title' => $item->label(),
      '@menu' => $menuId,
    ]);

    return $item;
  }

  /**
   * Actualiza un item de menú.
   *
   * @param int $itemId
   *   ID del item.
   * @param array $data
   *   Datos a actualizar.
   *
   * @return \Drupal\jaraba_site_builder\Entity\SiteMenuItem|null
   *   El item actualizado o NULL.
   */
  public function updateItem(int $itemId, array $data): ?object {
    $storage = $this->entityTypeManager->getStorage('site_menu_item');
    $item = $storage->load($itemId);

    if (!$item) {
      return NULL;
    }

    $allowedFields = [
      'title', 'url', 'page_id', 'parent_id', 'item_type', 'icon',
      'badge_text', 'badge_color', 'highlight', 'mega_content',
      'open_in_new_tab', 'is_enabled', 'weight', 'depth',
    ];

    foreach ($allowedFields as $field) {
      if (array_key_exists($field, $data)) {
        $item->set($field, $data[$field]);
      }
    }

    $item->save();
    return $item;
  }

  /**
   * Elimina un item de menú y sus hijos.
   *
   * @param int $itemId
   *   ID del item a eliminar.
   *
   * @return bool
   *   TRUE si se eliminó correctamente.
   */
  public function deleteItem(int $itemId): bool {
    $storage = $this->entityTypeManager->getStorage('site_menu_item');
    $item = $storage->load($itemId);

    if (!$item) {
      return FALSE;
    }

    // Eliminar hijos recursivamente.
    $childIds = $storage->getQuery()
      ->condition('parent_id', $itemId)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($childIds)) {
      foreach ($childIds as $childId) {
        $this->deleteItem((int) $childId);
      }
    }

    $item->delete();

    $this->logger->info('Item de menú @id eliminado.', ['@id' => $itemId]);
    return TRUE;
  }

  /**
   * Reordena items de un menú.
   *
   * @param int $menuId
   *   ID del menú.
   * @param array $order
   *   Array de {id, parent_id, weight, depth}.
   */
  public function reorderItems(int $menuId, array $order): void {
    $storage = $this->entityTypeManager->getStorage('site_menu_item');

    foreach ($order as $itemData) {
      $item = $storage->load($itemData['id']);
      if (!$item || (int) $item->get('menu_id')->target_id !== $menuId) {
        continue;
      }

      $item->set('weight', $itemData['weight'] ?? 0);
      $item->set('depth', $itemData['depth'] ?? 0);
      $item->set('parent_id', $itemData['parent_id'] ?? NULL);
      $item->save();
    }

    $this->logger->info('Menú @menu reordenado (@count items).', [
      '@menu' => $menuId,
      '@count' => count($order),
    ]);
  }

  /**
   * Serializa un item para respuesta API.
   *
   * @param object $item
   *   Entidad SiteMenuItem.
   *
   * @return array
   *   Array serializado.
   */
  public function serializeItem(object $item): array {
    return [
      'id' => (int) $item->id(),
      'menu_id' => (int) $item->get('menu_id')->target_id,
      'parent_id' => $item->get('parent_id')->target_id ? (int) $item->get('parent_id')->target_id : NULL,
      'title' => $item->get('title')->value,
      'url' => $item->get('url')->value,
      'page_id' => $item->get('page_id')->target_id ? (int) $item->get('page_id')->target_id : NULL,
      'item_type' => $item->get('item_type')->value,
      'icon' => $item->get('icon')->value,
      'badge_text' => $item->get('badge_text')->value,
      'badge_color' => $item->get('badge_color')->value,
      'highlight' => (bool) $item->get('highlight')->value,
      'open_in_new_tab' => (bool) $item->get('open_in_new_tab')->value,
      'is_enabled' => (bool) $item->get('is_enabled')->value,
      'weight' => (int) $item->get('weight')->value,
      'depth' => (int) $item->get('depth')->value,
    ];
  }

  /**
   * Serializa un menú para respuesta API.
   *
   * @param object $menu
   *   Entidad SiteMenu.
   *
   * @return array
   *   Array serializado.
   */
  public function serializeMenu(object $menu): array {
    return [
      'id' => (int) $menu->id(),
      'machine_name' => $menu->get('machine_name')->value,
      'label' => $menu->label(),
      'description' => $menu->get('description')->value,
      'created' => $menu->get('created')->value,
      'changed' => $menu->get('changed')->value,
    ];
  }

  /**
   * Construye un árbol jerárquico a partir de una lista plana.
   *
   * @param array $items
   *   Lista plana de entidades SiteMenuItem.
   *
   * @return array
   *   Árbol jerárquico.
   */
  protected function buildTree(array $items): array {
    $tree = [];
    $map = [];

    foreach ($items as $item) {
      $data = $this->serializeItem($item);
      $data['children'] = [];
      $map[$data['id']] = $data;
    }

    foreach ($map as $id => $data) {
      if ($data['parent_id'] && isset($map[$data['parent_id']])) {
        $map[$data['parent_id']]['children'][] = &$map[$id];
      }
      else {
        $tree[] = &$map[$id];
      }
    }

    return $tree;
  }

  /**
   * Calcula el siguiente peso para un item en el menú.
   *
   * @param int $menuId
   *   ID del menú.
   * @param int|null $parentId
   *   ID del padre (NULL para nivel raíz).
   *
   * @return int
   *   Siguiente peso disponible.
   */
  protected function getNextWeight(int $menuId, ?int $parentId = NULL): int {
    $query = $this->entityTypeManager->getStorage('site_menu_item')
      ->getQuery()
      ->condition('menu_id', $menuId)
      ->accessCheck(FALSE)
      ->sort('weight', 'DESC')
      ->range(0, 1);

    if ($parentId) {
      $query->condition('parent_id', $parentId);
    }
    else {
      $query->notExists('parent_id');
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return 0;
    }

    $lastItem = $this->entityTypeManager->getStorage('site_menu_item')->load(reset($ids));
    return $lastItem ? ((int) $lastItem->get('weight')->value + 1) : 0;
  }

}
