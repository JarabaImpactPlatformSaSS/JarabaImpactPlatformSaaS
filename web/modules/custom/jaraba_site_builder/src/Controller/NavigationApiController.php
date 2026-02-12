<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_site_builder\Service\MenuService;
use Drupal\jaraba_site_builder\Service\NavigationRenderService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller para navegación global: header, footer, menús, breadcrumbs.
 */
class NavigationApiController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected MenuService $menuService,
    protected NavigationRenderService $navigationRender,
    protected TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_site_builder.menu'),
      $container->get('jaraba_site_builder.navigation_render'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  // =========================================================================
  // Header API
  // =========================================================================

  /**
   * GET /api/v1/site/header - Configuración del header del tenant.
   */
  public function getHeader(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $config = $this->navigationRender->getHeaderConfig($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $config,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * PUT /api/v1/site/header - Actualizar configuración del header.
   */
  public function updateHeader(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Datos inválidos.')->__toString(),
        ], 400);
      }

      $storage = $this->entityTypeManager()->getStorage('site_header_config');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $config = $storage->load(reset($ids));
      }
      else {
        $config = $storage->create(['tenant_id' => $tenantId]);
      }

      $allowedFields = [
        'header_type', 'logo_alt', 'logo_width', 'is_sticky', 'sticky_offset',
        'transparent_on_hero', 'hide_on_scroll_down', 'main_menu_position',
        'main_menu_id', 'show_cta', 'cta_text', 'cta_url', 'cta_style',
        'cta_icon', 'show_search', 'show_language_switcher', 'show_user_menu',
        'show_phone', 'show_email', 'show_topbar', 'topbar_content',
        'topbar_bg_color', 'topbar_text_color', 'bg_color', 'text_color',
        'height_desktop', 'height_mobile', 'shadow',
      ];

      foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
          $config->set($field, $data[$field]);
        }
      }

      $config->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->navigationRender->getHeaderConfig($tenantId),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * POST /api/v1/site/header/logo - Subir logo del header.
   */
  public function uploadHeaderLogo(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $file = $request->files->get('logo');

      if (!$file) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('No se recibió archivo.')->__toString(),
        ], 400);
      }

      $validators = [
        'file_validate_extensions' => ['png jpg jpeg svg webp'],
        'file_validate_size' => [5 * 1024 * 1024],
      ];

      $savedFile = file_save_upload('logo', $validators, 'public://site-header-logos', 0);

      if (!$savedFile) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Error al guardar el archivo.')->__toString(),
        ], 500);
      }

      $savedFile->setPermanent();
      $savedFile->save();

      // Asociar al header config.
      $storage = $this->entityTypeManager()->getStorage('site_header_config');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $config = $storage->load(reset($ids));
        $config->set('logo_id', $savedFile->id());
        $config->save();
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'file_id' => (int) $savedFile->id(),
          'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($savedFile->getFileUri()),
        ],
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * DELETE /api/v1/site/header/logo - Eliminar logo del header.
   */
  public function deleteHeaderLogo(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();

      $storage = $this->entityTypeManager()->getStorage('site_header_config');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $config = $storage->load(reset($ids));
        $config->set('logo_id', NULL);
        $config->save();
      }

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  // =========================================================================
  // Footer API
  // =========================================================================

  /**
   * GET /api/v1/site/footer - Configuración del footer del tenant.
   */
  public function getFooter(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $config = $this->navigationRender->getFooterConfig($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $config,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * PUT /api/v1/site/footer - Actualizar configuración del footer.
   */
  public function updateFooter(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Datos inválidos.')->__toString(),
        ], 400);
      }

      $storage = $this->entityTypeManager()->getStorage('site_footer_config');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $config = $storage->load(reset($ids));
      }
      else {
        $config = $storage->create(['tenant_id' => $tenantId]);
      }

      $allowedFields = [
        'footer_type', 'show_logo', 'description', 'columns_config',
        'show_social', 'social_position', 'show_newsletter', 'newsletter_title',
        'newsletter_placeholder', 'newsletter_cta', 'cta_title', 'cta_subtitle',
        'cta_button_text', 'cta_button_url', 'copyright_text', 'show_legal_links',
        'bg_color', 'text_color', 'accent_color',
      ];

      foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
          $value = $data[$field];
          if ($field === 'columns_config' && is_array($value)) {
            $value = json_encode($value);
          }
          $config->set($field, $value);
        }
      }

      $config->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->navigationRender->getFooterConfig($tenantId),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * POST /api/v1/site/footer/logo - Subir logo del footer.
   */
  public function uploadFooterLogo(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $file = $request->files->get('logo');

      if (!$file) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('No se recibió archivo.')->__toString(),
        ], 400);
      }

      $validators = [
        'file_validate_extensions' => ['png jpg jpeg svg webp'],
        'file_validate_size' => [5 * 1024 * 1024],
      ];

      $savedFile = file_save_upload('logo', $validators, 'public://site-footer-logos', 0);

      if (!$savedFile) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Error al guardar el archivo.')->__toString(),
        ], 500);
      }

      $savedFile->setPermanent();
      $savedFile->save();

      $storage = $this->entityTypeManager()->getStorage('site_footer_config');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $config = $storage->load(reset($ids));
        $config->set('logo_id', $savedFile->id());
        $config->save();
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'file_id' => (int) $savedFile->id(),
          'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($savedFile->getFileUri()),
        ],
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  // =========================================================================
  // Menus API
  // =========================================================================

  /**
   * GET /api/v1/site/menus - Listar menús del tenant.
   */
  public function listMenus(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $menus = $this->menuService->getMenus($tenantId);

      $data = [];
      foreach ($menus as $menu) {
        $data[] = $this->menuService->serializeMenu($menu);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * POST /api/v1/site/menus - Crear menú.
   */
  public function createMenu(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['label']) || empty($data['machine_name'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Se requiere label y machine_name.')->__toString(),
        ], 400);
      }

      if (!preg_match('/^[a-z0-9_]+$/', $data['machine_name'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('El machine_name solo permite minúsculas, números y guiones bajos.')->__toString(),
        ], 400);
      }

      // Verificar unicidad.
      $existing = $this->menuService->getMenuByMachineName($data['machine_name'], $tenantId);
      if ($existing) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Ya existe un menú con ese nombre máquina.')->__toString(),
        ], 409);
      }

      $storage = $this->entityTypeManager()->getStorage('site_menu');
      $menu = $storage->create([
        'tenant_id' => $tenantId,
        'label' => $data['label'],
        'machine_name' => $data['machine_name'],
        'description' => $data['description'] ?? NULL,
      ]);
      $menu->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->menuService->serializeMenu($menu),
      ], 201);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * GET /api/v1/site/menus/{id} - Detalle de menú.
   */
  public function getMenu(int $id): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->menuService->serializeMenu($menu),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * DELETE /api/v1/site/menus/{id} - Eliminar menú.
   */
  public function deleteMenu(int $id): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      // Eliminar todos los items del menú primero.
      $items = $this->menuService->getAllMenuItems($id);
      foreach ($items as $item) {
        $item->delete();
      }

      $menu->delete();

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * GET /api/v1/site/menus/{id}/tree - Árbol jerárquico de items.
   */
  public function getMenuTree(int $id): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      $tree = $this->menuService->getMenuTree($id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'menu' => $this->menuService->serializeMenu($menu),
          'tree' => $tree,
        ],
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * POST /api/v1/site/menus/{id}/items - Añadir item a menú.
   */
  public function addMenuItem(int $id, Request $request): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['title'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Se requiere un título.')->__toString(),
        ], 400);
      }

      $item = $this->menuService->addItem($id, $data);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->menuService->serializeItem($item),
      ], 201);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * PATCH /api/v1/site/menus/{id}/items/{itemId} - Actualizar item.
   */
  public function updateMenuItem(int $id, int $itemId, Request $request): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      $item = $this->menuService->updateItem($itemId, $data);

      if (!$item) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Item no encontrado.')->__toString(),
        ], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->menuService->serializeItem($item),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * DELETE /api/v1/site/menus/{id}/items/{itemId} - Eliminar item.
   */
  public function deleteMenuItem(int $id, int $itemId): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      $deleted = $this->menuService->deleteItem($itemId);

      if (!$deleted) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Item no encontrado.')->__toString(),
        ], 404);
      }

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  /**
   * POST /api/v1/site/menus/{id}/reorder - Reordenar items.
   */
  public function reorderMenuItems(int $id, Request $request): JsonResponse {
    try {
      $menu = $this->loadMenuForTenant($id);
      if (!$menu) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Menú no encontrado.')->__toString(),
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['items']) || !is_array($data['items'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $this->t('Se requiere un array de items con id, weight, parent_id y depth.')->__toString(),
        ], 400);
      }

      $this->menuService->reorderItems($id, $data['items']);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->menuService->getMenuTree($id),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  // =========================================================================
  // Breadcrumbs API
  // =========================================================================

  /**
   * GET /api/v1/site/breadcrumbs/{pageTreeId} - Breadcrumbs de una página.
   */
  public function getBreadcrumbs(int $pageTreeId): JsonResponse {
    try {
      $breadcrumbs = $this->navigationRender->renderBreadcrumbs($pageTreeId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $breadcrumbs,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }
  }

  // =========================================================================
  // Helpers
  // =========================================================================

  /**
   * Obtiene el ID del tenant actual.
   */
  protected function getCurrentTenantId(): ?int {
    $tenant = $this->tenantContext->getCurrentTenant();
    return $tenant ? (int) $tenant->id() : NULL;
  }

  /**
   * Carga un menú verificando que pertenece al tenant actual.
   */
  protected function loadMenuForTenant(int $menuId): ?object {
    $storage = $this->entityTypeManager()->getStorage('site_menu');
    $menu = $storage->load($menuId);

    if (!$menu) {
      return NULL;
    }

    $tenantId = $this->getCurrentTenantId();
    if ($tenantId && (int) $menu->get('tenant_id')->target_id !== $tenantId) {
      return NULL;
    }

    return $menu;
  }

  /**
   * Genera una respuesta de error estándar.
   */
  protected function errorResponse(\Exception $e): JsonResponse {
    $this->getLogger('jaraba_site_builder')->error(
      'Error en navegación API: @error',
      ['@error' => $e->getMessage()]
    );

    return new JsonResponse([
      'success' => FALSE,
      'error' => $this->t('Error interno del servidor.')->__toString(),
    ], 500);
  }

}
