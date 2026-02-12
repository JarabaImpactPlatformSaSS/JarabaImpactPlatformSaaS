<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio para renderizar componentes de navegación (header, footer, menús).
 *
 * Este servicio proporciona datos pre-procesados para los parciales Twig:
 * _jaraba-header.html.twig, _jaraba-footer.html.twig, etc.
 */
class NavigationRenderService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected MenuService $menuService,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Obtiene la configuración del header para un tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant (o null para usar el actual).
   *
   * @return array
   *   Datos del header para el template.
   */
  public function getHeaderConfig(?int $tenantId = NULL): array {
    $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return $this->getDefaultHeaderConfig();
    }

    $storage = $this->entityTypeManager->getStorage('site_header_config');
    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return $this->getDefaultHeaderConfig();
    }

    $config = $storage->load(reset($ids));
    return $this->serializeHeaderConfig($config);
  }

  /**
   * Obtiene la configuración del footer para un tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant (o null para usar el actual).
   *
   * @return array
   *   Datos del footer para el template.
   */
  public function getFooterConfig(?int $tenantId = NULL): array {
    $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return $this->getDefaultFooterConfig();
    }

    $storage = $this->entityTypeManager->getStorage('site_footer_config');
    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return $this->getDefaultFooterConfig();
    }

    $config = $storage->load(reset($ids));
    return $this->serializeFooterConfig($config);
  }

  /**
   * Obtiene el árbol del menú principal del tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Árbol jerárquico de items del menú principal.
   */
  public function getMainMenuTree(?int $tenantId = NULL): array {
    $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return [];
    }

    // Buscar el header config para obtener el menú principal asignado.
    $headerConfig = $this->getHeaderConfig($tenantId);
    $menuId = $headerConfig['main_menu_id'] ?? NULL;

    if (!$menuId) {
      // Intentar cargar el menú 'main' por convención.
      $mainMenu = $this->menuService->getMenuByMachineName('main', $tenantId);
      $menuId = $mainMenu ? (int) $mainMenu->id() : NULL;
    }

    if (!$menuId) {
      return [];
    }

    return $this->menuService->getMenuTree($menuId);
  }

  /**
   * Obtiene los menús del footer del tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con los menús del footer.
   */
  public function getFooterMenus(?int $tenantId = NULL): array {
    $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return [];
    }

    // Buscar menús con prefijo 'footer' en machine_name.
    $allMenus = $this->menuService->getMenus($tenantId);
    $footerMenus = [];

    foreach ($allMenus as $menu) {
      $machineName = $menu->getMachineName();
      if (str_starts_with($machineName, 'footer')) {
        $footerMenus[] = [
          'menu' => $this->menuService->serializeMenu($menu),
          'items' => $this->menuService->getMenuTree((int) $menu->id()),
        ];
      }
    }

    return $footerMenus;
  }

  /**
   * Genera breadcrumbs para un nodo del árbol de páginas.
   *
   * @param int $pageTreeId
   *   ID del nodo en site_page_tree.
   *
   * @return array
   *   Array de breadcrumbs [{title, url, is_current}].
   */
  public function renderBreadcrumbs(int $pageTreeId): array {
    $storage = $this->entityTypeManager->getStorage('site_page_tree');
    $node = $storage->load($pageTreeId);

    if (!$node) {
      return [];
    }

    $breadcrumbs = [];
    $current = $node;

    // Recorrer ancestros.
    while ($current) {
      $breadcrumbs[] = [
        'id' => (int) $current->id(),
        'title' => $current->getNavTitle(),
        'url' => $this->getPageUrl($current),
        'is_current' => ((int) $current->id() === $pageTreeId),
      ];

      $parentId = $current->get('parent_id')->target_id;
      $current = $parentId ? $storage->load($parentId) : NULL;
    }

    // Invertir para que vaya de raíz a hoja.
    return array_reverse($breadcrumbs);
  }

  /**
   * Serializa la configuración del header para template/API.
   */
  protected function serializeHeaderConfig(object $config): array {
    return [
      'id' => (int) $config->id(),
      'header_type' => $config->get('header_type')->value ?? 'standard',
      'logo_url' => $this->getFileUrl($config->get('logo_id')->target_id),
      'logo_alt' => $config->get('logo_alt')->value ?? '',
      'logo_width' => (int) ($config->get('logo_width')->value ?? 150),
      'logo_mobile_url' => $this->getFileUrl($config->get('logo_mobile_id')->target_id),
      'is_sticky' => (bool) $config->get('is_sticky')->value,
      'sticky_offset' => (int) ($config->get('sticky_offset')->value ?? 0),
      'transparent_on_hero' => (bool) $config->get('transparent_on_hero')->value,
      'hide_on_scroll_down' => (bool) $config->get('hide_on_scroll_down')->value,
      'main_menu_position' => $config->get('main_menu_position')->value ?? 'right',
      'main_menu_id' => $config->get('main_menu_id')->target_id ? (int) $config->get('main_menu_id')->target_id : NULL,
      'show_cta' => (bool) $config->get('show_cta')->value,
      'cta_text' => $config->get('cta_text')->value,
      'cta_url' => $config->get('cta_url')->value,
      'cta_style' => $config->get('cta_style')->value ?? 'primary',
      'cta_icon' => $config->get('cta_icon')->value,
      'show_search' => (bool) $config->get('show_search')->value,
      'show_language_switcher' => (bool) $config->get('show_language_switcher')->value,
      'show_user_menu' => (bool) $config->get('show_user_menu')->value,
      'show_phone' => (bool) $config->get('show_phone')->value,
      'show_email' => (bool) $config->get('show_email')->value,
      'show_topbar' => (bool) $config->get('show_topbar')->value,
      'topbar_content' => $config->get('topbar_content')->value,
      'topbar_bg_color' => $config->get('topbar_bg_color')->value ?? '#1E3A5F',
      'topbar_text_color' => $config->get('topbar_text_color')->value ?? '#FFFFFF',
      'bg_color' => $config->get('bg_color')->value ?? '#FFFFFF',
      'text_color' => $config->get('text_color')->value ?? '#1E293B',
      'height_desktop' => (int) ($config->get('height_desktop')->value ?? 80),
      'height_mobile' => (int) ($config->get('height_mobile')->value ?? 64),
      'shadow' => $config->get('shadow')->value ?? 'sm',
    ];
  }

  /**
   * Serializa la configuración del footer para template/API.
   */
  protected function serializeFooterConfig(object $config): array {
    return [
      'id' => (int) $config->id(),
      'footer_type' => $config->get('footer_type')->value ?? 'columns',
      'logo_url' => $this->getFileUrl($config->get('logo_id')->target_id),
      'show_logo' => (bool) $config->get('show_logo')->value,
      'description' => $config->get('description')->value,
      'columns_config' => $config->getColumnsConfig(),
      'show_social' => (bool) $config->get('show_social')->value,
      'social_position' => $config->get('social_position')->value ?? 'bottom',
      'show_newsletter' => (bool) $config->get('show_newsletter')->value,
      'newsletter_title' => $config->get('newsletter_title')->value,
      'newsletter_placeholder' => $config->get('newsletter_placeholder')->value,
      'newsletter_cta' => $config->get('newsletter_cta')->value,
      'cta_title' => $config->get('cta_title')->value,
      'cta_subtitle' => $config->get('cta_subtitle')->value,
      'cta_button_text' => $config->get('cta_button_text')->value,
      'cta_button_url' => $config->get('cta_button_url')->value,
      'copyright_text' => $config->getCopyrightText(),
      'show_legal_links' => (bool) $config->get('show_legal_links')->value,
      'bg_color' => $config->get('bg_color')->value ?? '#1E293B',
      'text_color' => $config->get('text_color')->value ?? '#94A3B8',
      'accent_color' => $config->get('accent_color')->value ?? '#3B82F6',
    ];
  }

  /**
   * Obtiene configuración por defecto del header.
   */
  protected function getDefaultHeaderConfig(): array {
    return [
      'id' => NULL,
      'header_type' => 'standard',
      'logo_url' => NULL,
      'logo_alt' => '',
      'logo_width' => 150,
      'logo_mobile_url' => NULL,
      'is_sticky' => TRUE,
      'sticky_offset' => 0,
      'transparent_on_hero' => FALSE,
      'hide_on_scroll_down' => FALSE,
      'main_menu_position' => 'right',
      'main_menu_id' => NULL,
      'show_cta' => FALSE,
      'cta_text' => NULL,
      'cta_url' => NULL,
      'cta_style' => 'primary',
      'cta_icon' => NULL,
      'show_search' => FALSE,
      'show_language_switcher' => FALSE,
      'show_user_menu' => FALSE,
      'show_phone' => FALSE,
      'show_email' => FALSE,
      'show_topbar' => FALSE,
      'topbar_content' => NULL,
      'topbar_bg_color' => '#1E3A5F',
      'topbar_text_color' => '#FFFFFF',
      'bg_color' => '#FFFFFF',
      'text_color' => '#1E293B',
      'height_desktop' => 80,
      'height_mobile' => 64,
      'shadow' => 'sm',
    ];
  }

  /**
   * Obtiene configuración por defecto del footer.
   */
  protected function getDefaultFooterConfig(): array {
    return [
      'id' => NULL,
      'footer_type' => 'columns',
      'logo_url' => NULL,
      'show_logo' => TRUE,
      'description' => NULL,
      'columns_config' => [],
      'show_social' => TRUE,
      'social_position' => 'bottom',
      'show_newsletter' => FALSE,
      'newsletter_title' => NULL,
      'newsletter_placeholder' => NULL,
      'newsletter_cta' => NULL,
      'cta_title' => NULL,
      'cta_subtitle' => NULL,
      'cta_button_text' => NULL,
      'cta_button_url' => NULL,
      'copyright_text' => str_replace('{year}', date('Y'), '© {year} Todos los derechos reservados.'),
      'show_legal_links' => TRUE,
      'bg_color' => '#1E293B',
      'text_color' => '#94A3B8',
      'accent_color' => '#3B82F6',
    ];
  }

  /**
   * Obtiene la URL de un archivo por su ID.
   */
  protected function getFileUrl(?int $fileId): ?string {
    if (!$fileId) {
      return NULL;
    }

    try {
      $file = $this->entityTypeManager->getStorage('file')->load($fileId);
      if ($file) {
        return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }
    catch (\Exception $e) {
      // File not found, return null.
    }

    return NULL;
  }

  /**
   * Obtiene la URL de una página del árbol.
   */
  protected function getPageUrl(object $pageTreeNode): ?string {
    $page = $pageTreeNode->get('page_id')->entity;
    if ($page && $page->hasField('path_alias')) {
      return $page->get('path_alias')->value;
    }
    return NULL;
  }

}
