177
ESPECIFICACIÓN TÉCNICA
Global Navigation System
Header Builder | Menús Multinivel | Mobile Menu | Footer Builder
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	27 de Enero de 2026
Horas Estimadas:	50-60 horas
 
Índice de Contenidos
1. Resumen Ejecutivo
2. Arquitectura de Datos
3. Header Builder
4. Sistema de Menús
5. Footer Builder
6. Templates Twig
7. Estilos CSS
8. APIs REST
9. Límites por Plan
10. Roadmap de Implementación
 
1. Resumen Ejecutivo
El Global Navigation System permite a los tenants crear headers y footers personalizados que se comparten entre todas las páginas del sitio, con soporte para menús multinivel, mega menus, y diseño responsive.

Componentes del Sistema
Header Builder visual | 5 tipos de header | Menús drag & drop | Mega menus | Mobile menu slide | Footer multi-columna | Newsletter integrado | Top bar promocional

1.1. Tipos de Header Soportados
Tipo	Descripción	Uso Recomendado
standard	Logo izquierda, menú derecha, CTA	Sitios corporativos, servicios
centered	Logo centrado, menú a ambos lados	Marcas de moda, portfolio
minimal	Solo logo y hamburger	Landing pages, apps
mega	Con mega menus desplegables	E-commerce, sitios grandes
transparent	Fondo transparente sobre hero	Sitios visuales, fotografía
1.2. Tipos de Footer Soportados
Tipo	Descripción	Columnas
simple	Logo, copyright, social links	1
columns	Múltiples columnas con menús	3-4
mega	Columnas + descripción + newsletter	4-5
minimal	Solo copyright y legal	1
cta	CTA prominente + columnas	3-4
 
2. Arquitectura de Datos
Esquema completo de base de datos para header, footer y sistema de menús.
 navigation_schema.sql
-- =============================================
-- GLOBAL NAVIGATION SYSTEM - ESQUEMA DE BASE DE DATOS
-- =============================================
 
-- Configuración del Header por tenant
CREATE TABLE site_header_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL UNIQUE,
  
  -- Tipo de header
  header_type ENUM('standard', 'centered', 'minimal', 'mega', 'transparent') DEFAULT 'standard',
  
  -- Logo
  logo_id INT UNSIGNED, -- FK file_managed
  logo_alt VARCHAR(255),
  logo_width INT DEFAULT 150, -- px
  logo_mobile_id INT UNSIGNED, -- Logo alternativo para móvil
  
  -- Comportamiento
  is_sticky BOOLEAN DEFAULT TRUE,
  sticky_offset INT DEFAULT 0, -- px desde top para activar sticky
  transparent_on_hero BOOLEAN DEFAULT FALSE, -- Header transparente sobre hero
  hide_on_scroll_down BOOLEAN DEFAULT FALSE, -- Ocultar al hacer scroll down
  
  -- Menú principal
  main_menu_position ENUM('left', 'center', 'right') DEFAULT 'right',
  main_menu_id INT UNSIGNED, -- FK site_menu
  
  -- CTA del header
  show_cta BOOLEAN DEFAULT TRUE,
  cta_text VARCHAR(100),
  cta_url VARCHAR(500),
  cta_style ENUM('primary', 'secondary', 'outline', 'ghost') DEFAULT 'primary',
  cta_icon VARCHAR(50), -- Icono Lucide
  
  -- Elementos adicionales
  show_search BOOLEAN DEFAULT FALSE,
  show_language_switcher BOOLEAN DEFAULT FALSE,
  show_user_menu BOOLEAN DEFAULT FALSE,
  
  -- Contacto en header
  show_phone BOOLEAN DEFAULT FALSE,
  show_email BOOLEAN DEFAULT FALSE,
  
  -- Top bar (barra sobre header)
  show_topbar BOOLEAN DEFAULT FALSE,
  topbar_content TEXT, -- HTML simple o texto
  topbar_bg_color VARCHAR(7) DEFAULT '#1E3A5F',
  topbar_text_color VARCHAR(7) DEFAULT '#FFFFFF',
  
  -- Estilos
  bg_color VARCHAR(7) DEFAULT '#FFFFFF',
  text_color VARCHAR(7) DEFAULT '#1E293B',
  height_desktop INT DEFAULT 80, -- px
  height_mobile INT DEFAULT 64, -- px
  shadow ENUM('none', 'sm', 'md', 'lg') DEFAULT 'sm',
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Configuración del Footer por tenant
CREATE TABLE site_footer_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL UNIQUE,
  
  -- Tipo de footer
  footer_type ENUM('simple', 'columns', 'mega', 'minimal', 'cta') DEFAULT 'columns',
  
  -- Logo
  logo_id INT UNSIGNED,
  show_logo BOOLEAN DEFAULT TRUE,
  
  -- Descripción
  description TEXT,
  
  -- Columnas (para footer_type = 'columns' o 'mega')
  columns_config JSON, -- Array de columnas con menús
  
  -- Social links
  show_social BOOLEAN DEFAULT TRUE,
  social_position ENUM('top', 'bottom', 'column') DEFAULT 'bottom',
  
  -- Newsletter
  show_newsletter BOOLEAN DEFAULT FALSE,
  newsletter_title VARCHAR(255),
  newsletter_placeholder VARCHAR(100),
  newsletter_cta VARCHAR(50),
  
  -- CTA Footer (para footer_type = 'cta')
  cta_title VARCHAR(255),
  cta_subtitle TEXT,
  cta_button_text VARCHAR(100),
  cta_button_url VARCHAR(500),
  
  -- Legal
  copyright_text VARCHAR(500),
  show_legal_links BOOLEAN DEFAULT TRUE,
  
  -- Estilos
  bg_color VARCHAR(7) DEFAULT '#1E293B',
  text_color VARCHAR(7) DEFAULT '#94A3B8',
  accent_color VARCHAR(7) DEFAULT '#3B82F6',
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Menús personalizados
CREATE TABLE site_menu (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  machine_name VARCHAR(100) NOT NULL,
  label VARCHAR(255) NOT NULL,
  description TEXT,
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_menu (tenant_id, machine_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Items de menú
CREATE TABLE site_menu_item (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED DEFAULT NULL, -- Self-reference para submenús
  
  -- Contenido
  title VARCHAR(255) NOT NULL,
  url VARCHAR(500), -- URL interna (/servicios) o externa (https://...)
  page_id INT UNSIGNED, -- FK page_content (alternativa a URL)
  
  -- Tipo de item
  item_type ENUM('link', 'page', 'dropdown', 'mega_column', 'divider', 'heading') DEFAULT 'link',
  
  -- Apariencia
  icon VARCHAR(50), -- Icono Lucide
  badge_text VARCHAR(50), -- "Nuevo", "Beta"
  badge_color VARCHAR(7),
  highlight BOOLEAN DEFAULT FALSE, -- Destacar item
  
  -- Mega menu (si item_type = 'mega_column')
  mega_content JSON, -- Contenido enriquecido del mega menu
  
  -- Comportamiento
  open_in_new_tab BOOLEAN DEFAULT FALSE,
  is_enabled BOOLEAN DEFAULT TRUE,
  
  -- Orden
  weight INT DEFAULT 0,
  depth INT DEFAULT 0,
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (menu_id) REFERENCES site_menu(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES site_menu_item(id) ON DELETE CASCADE,
  FOREIGN KEY (page_id) REFERENCES page_content(id) ON DELETE SET NULL,
  
  INDEX idx_menu_parent (menu_id, parent_id, weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 
3. Header Builder
3.1. Configuración Visual
Panel de administración para configurar el header sin código:
Sección: Identidad
•	Upload de logo (PNG, SVG, WebP)
•	Logo alternativo para móvil
•	Ancho del logo (slider 80-300px)
•	Texto alternativo (alt)
Sección: Comportamiento
•	Sticky: fijo al hacer scroll
•	Ocultar al scroll down (aparece al scroll up)
•	Transparente sobre hero
•	Offset de activación sticky
Sección: Navegación
•	Seleccionar menú principal
•	Posición: izquierda / centro / derecha
Sección: CTA
•	Mostrar/ocultar botón CTA
•	Texto del botón
•	URL destino
•	Estilo: primary / secondary / outline
•	Icono opcional (selector Lucide)
Sección: Elementos Adicionales
•	Mostrar buscador
•	Mostrar selector de idioma
•	Mostrar teléfono / email
Sección: Top Bar
•	Activar barra superior
•	Contenido (texto/HTML simple)
•	Colores de fondo y texto
Sección: Estilos
•	Color de fondo
•	Color de texto
•	Altura desktop (px)
•	Altura mobile (px)
•	Sombra: none / sm / md / lg
 
3.2. API del Header Builder
 HeaderController.php
<?php
 
namespace Drupal\jaraba_site_builder\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
 
/**
 * API Controller para Header Builder.
 */
class HeaderController extends ControllerBase {
 
  /**
   * GET /api/v1/site/header
   * Obtiene configuración del header.
   */
  public function getHeaderConfig(): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $config = $this->database->select('site_header_config', 'shc')
      ->fields('shc')
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();
    
    if (!$config) {
      $config = $this->createDefaultHeaderConfig($tenantId);
    }
    
    // Cargar logo
    if ($config['logo_id']) {
      $logo = $this->fileStorage->load($config['logo_id']);
      $config['logo_url'] = $this->fileUrlGenerator->generateAbsoluteString($logo->getFileUri());
    }
    
    // Cargar menú principal
    if ($config['main_menu_id']) {
      $config['main_menu'] = $this->getMenuTree($config['main_menu_id']);
    }
    
    return new JsonResponse($config);
  }
 
  /**
   * PUT /api/v1/site/header
   * Actualiza configuración del header.
   */
  public function updateHeaderConfig(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $fields = [
      'header_type' => $data['header_type'] ?? 'standard',
      'logo_alt' => $data['logo_alt'] ?? NULL,
      'logo_width' => $data['logo_width'] ?? 150,
      'is_sticky' => $data['is_sticky'] ?? TRUE,
      'sticky_offset' => $data['sticky_offset'] ?? 0,
      'transparent_on_hero' => $data['transparent_on_hero'] ?? FALSE,
      'hide_on_scroll_down' => $data['hide_on_scroll_down'] ?? FALSE,
      'main_menu_position' => $data['main_menu_position'] ?? 'right',
      'main_menu_id' => $data['main_menu_id'] ?? NULL,
      'show_cta' => $data['show_cta'] ?? TRUE,
      'cta_text' => $data['cta_text'] ?? NULL,
      'cta_url' => $data['cta_url'] ?? NULL,
      'cta_style' => $data['cta_style'] ?? 'primary',
      'cta_icon' => $data['cta_icon'] ?? NULL,
      'show_search' => $data['show_search'] ?? FALSE,
      'show_language_switcher' => $data['show_language_switcher'] ?? FALSE,
      'show_topbar' => $data['show_topbar'] ?? FALSE,
      'topbar_content' => $data['topbar_content'] ?? NULL,
      'topbar_bg_color' => $data['topbar_bg_color'] ?? '#1E3A5F',
      'bg_color' => $data['bg_color'] ?? '#FFFFFF',
      'text_color' => $data['text_color'] ?? '#1E293B',
      'height_desktop' => $data['height_desktop'] ?? 80,
      'height_mobile' => $data['height_mobile'] ?? 64,
      'shadow' => $data['shadow'] ?? 'sm',
    ];
    
    $this->database->merge('site_header_config')
      ->key('tenant_id', $tenantId)
      ->fields($fields)
      ->execute();
    
    // Invalidar caché
    $this->cacheTagsInvalidator->invalidateTags(["site_header:{$tenantId}"]);
    
    return new JsonResponse(['success' => TRUE]);
  }
 
  /**
   * POST /api/v1/site/header/logo
   * Sube logo del header.
   */
  public function uploadLogo(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    $file = $request->files->get('logo');
    
    if (!$file) {
      return new JsonResponse(['error' => 'No file uploaded'], 400);
    }
    
    // Validar tipo
    $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
    if (!in_array($file->getMimeType(), $allowedTypes)) {
      return new JsonResponse(['error' => 'Invalid file type'], 400);
    }
    
    // Guardar archivo
    $destination = "public://tenant-{$tenantId}/logos/";
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    
    $savedFile = $this->fileRepository->writeData(
      file_get_contents($file->getPathname()),
      $destination . $file->getClientOriginalName()
    );
    $savedFile->setPermanent();
    $savedFile->save();
    
    // Actualizar config
    $this->database->merge('site_header_config')
      ->key('tenant_id', $tenantId)
      ->fields(['logo_id' => $savedFile->id()])
      ->execute();
    
    return new JsonResponse([
      'file_id' => $savedFile->id(),
      'url' => $this->fileUrlGenerator->generateAbsoluteString($savedFile->getFileUri()),
    ]);
  }
 
  /**
   * GET /api/v1/site/menus
   * Lista menús del tenant.
   */
  public function listMenus(): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $menus = $this->database->select('site_menu', 'sm')
      ->fields('sm')
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAll();
    
    // Añadir conteo de items
    foreach ($menus as &$menu) {
      $menu->items_count = $this->database->select('site_menu_item', 'smi')
        ->condition('menu_id', $menu->id)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    
    return new JsonResponse(['menus' => $menus]);
  }
 
  /**
   * POST /api/v1/site/menus
   * Crea nuevo menú.
   */
  public function createMenu(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Generar machine_name
    $machineName = $this->generateMachineName($data['label']);
    
    $id = $this->database->insert('site_menu')
      ->fields([
        'tenant_id' => $tenantId,
        'machine_name' => $machineName,
        'label' => $data['label'],
        'description' => $data['description'] ?? NULL,
      ])
      ->execute();
    
    return new JsonResponse(['id' => $id, 'machine_name' => $machineName], 201);
  }
 
  /**
   * GET /api/v1/site/menus/{id}/tree
   * Obtiene árbol de items del menú.
   */
  public function getMenuTree(int $menuId): array {
    $items = $this->database->select('site_menu_item', 'smi')
      ->fields('smi')
      ->condition('menu_id', $menuId)
      ->condition('is_enabled', TRUE)
      ->orderBy('depth')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();
    
    return $this->buildMenuTree($items);
  }
 
  /**
   * POST /api/v1/site/menus/{id}/items
   * Añade item al menú.
   */
  public function addMenuItem(int $menuId, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    
    // Calcular peso
    $maxWeight = $this->database->select('site_menu_item', 'smi')
      ->condition('menu_id', $menuId)
      ->condition('parent_id', $data['parent_id'] ?? NULL, $data['parent_id'] ? '=' : 'IS NULL')
      ->addExpression('MAX(weight)', 'max_weight')
      ->execute()
      ->fetchField();
    
    // Calcular profundidad
    $depth = 0;
    if (!empty($data['parent_id'])) {
      $parent = $this->database->select('site_menu_item', 'smi')
        ->fields('smi', ['depth'])
        ->condition('id', $data['parent_id'])
        ->execute()
        ->fetchField();
      $depth = $parent + 1;
    }
    
    $id = $this->database->insert('site_menu_item')
      ->fields([
        'menu_id' => $menuId,
        'parent_id' => $data['parent_id'] ?? NULL,
        'title' => $data['title'],
        'url' => $data['url'] ?? NULL,
        'page_id' => $data['page_id'] ?? NULL,
        'item_type' => $data['item_type'] ?? 'link',
        'icon' => $data['icon'] ?? NULL,
        'badge_text' => $data['badge_text'] ?? NULL,
        'badge_color' => $data['badge_color'] ?? NULL,
        'highlight' => $data['highlight'] ?? FALSE,
        'mega_content' => isset($data['mega_content']) ? json_encode($data['mega_content']) : NULL,
        'open_in_new_tab' => $data['open_in_new_tab'] ?? FALSE,
        'weight' => ($maxWeight ?? -1) + 1,
        'depth' => $depth,
      ])
      ->execute();
    
    return new JsonResponse(['id' => $id], 201);
  }
 
  /**
   * POST /api/v1/site/menus/{id}/reorder
   * Reordena items del menú.
   */
  public function reorderMenuItems(int $menuId, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    
    $transaction = $this->database->startTransaction();
    
    try {
      $this->updateMenuItemsRecursive($data['items'], $menuId, NULL, 0);
      return new JsonResponse(['success' => TRUE]);
    } catch (\Exception $e) {
      $transaction->rollBack();
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }
 
  /**
   * Construye árbol jerárquico de items.
   */
  protected function buildMenuTree(array $items, ?int $parentId = NULL): array {
    $tree = [];
    
    foreach ($items as $item) {
      if ($item->parent_id == $parentId) {
        $node = (array) $item;
        $node['children'] = $this->buildMenuTree($items, $item->id);
        
        // Cargar página si existe
        if ($item->page_id) {
          $page = $this->pageStorage->load($item->page_id);
          if ($page) {
            $node['computed_url'] = $page->get('path_alias')->value;
          }
        }
        
        $tree[] = $node;
      }
    }
    
    return $tree;
  }
}

 
4. Sistema de Menús
4.1. Estructura Jerárquica
Los menús soportan hasta 3 niveles de profundidad:
 menu-structure.txt
Menú Principal
├── Inicio (link)
├── Servicios (dropdown)
│   ├── Consultoría
│   ├── Formación
│   └── Desarrollo
├── Productos (mega menu)
│   ├── [Columna 1: Categoría A]
│   │   ├── Producto 1
│   │   └── Producto 2
│   ├── [Columna 2: Categoría B]
│   │   ├── Producto 3
│   │   └── Producto 4
│   └── [Columna 3: Destacado - HTML custom]
├── Blog (link)
└── Contacto (link destacado)

4.2. Tipos de Items
Tipo	Descripción	Hijos Permitidos
link	Enlace simple a página o URL	No
page	Enlace a página del Page Builder	No
dropdown	Desplegable con subitems	Sí (links, pages)
mega_column	Columna de mega menu	Sí (cualquiera)
heading	Título de sección (no clickeable)	No
divider	Separador visual	No
4.3. UI del Menu Builder
•	Drag & drop para reordenar items
•	Arrastrar para crear jerarquía (nesting)
•	Edición inline del título
•	Panel lateral para configuración completa
•	Preview en tiempo real del menú
•	Selector visual de iconos Lucide
 
5. Footer Builder
 FooterController.php
<?php
 
namespace Drupal\jaraba_site_builder\Controller;
 
/**
 * API Controller para Footer Builder.
 */
class FooterController extends ControllerBase {
 
  /**
   * GET /api/v1/site/footer
   * Obtiene configuración del footer.
   */
  public function getFooterConfig(): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $config = $this->database->select('site_footer_config', 'sfc')
      ->fields('sfc')
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();
    
    if (!$config) {
      $config = $this->createDefaultFooterConfig($tenantId);
    }
    
    // Expandir JSON
    $config['columns_config'] = json_decode($config['columns_config'] ?? '[]', TRUE);
    
    // Cargar logo
    if ($config['logo_id']) {
      $logo = $this->fileStorage->load($config['logo_id']);
      $config['logo_url'] = $this->fileUrlGenerator->generateAbsoluteString($logo->getFileUri());
    }
    
    // Cargar menús de columnas
    foreach ($config['columns_config'] as &$column) {
      if (!empty($column['menu_id'])) {
        $column['menu_items'] = $this->headerController->getMenuTree($column['menu_id']);
      }
    }
    
    // Cargar social links desde site_config
    $siteConfig = $this->database->select('site_config', 'sc')
      ->fields('sc', ['social_links', 'contact_email', 'contact_phone', 'contact_address'])
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();
    
    $config['social_links'] = json_decode($siteConfig['social_links'] ?? '[]', TRUE);
    $config['contact'] = [
      'email' => $siteConfig['contact_email'],
      'phone' => $siteConfig['contact_phone'],
      'address' => $siteConfig['contact_address'],
    ];
    
    return new JsonResponse($config);
  }
 
  /**
   * PUT /api/v1/site/footer
   * Actualiza configuración del footer.
   */
  public function updateFooterConfig(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $fields = [
      'footer_type' => $data['footer_type'] ?? 'columns',
      'show_logo' => $data['show_logo'] ?? TRUE,
      'description' => $data['description'] ?? NULL,
      'columns_config' => json_encode($data['columns_config'] ?? []),
      'show_social' => $data['show_social'] ?? TRUE,
      'social_position' => $data['social_position'] ?? 'bottom',
      'show_newsletter' => $data['show_newsletter'] ?? FALSE,
      'newsletter_title' => $data['newsletter_title'] ?? NULL,
      'newsletter_placeholder' => $data['newsletter_placeholder'] ?? NULL,
      'newsletter_cta' => $data['newsletter_cta'] ?? NULL,
      'cta_title' => $data['cta_title'] ?? NULL,
      'cta_subtitle' => $data['cta_subtitle'] ?? NULL,
      'cta_button_text' => $data['cta_button_text'] ?? NULL,
      'cta_button_url' => $data['cta_button_url'] ?? NULL,
      'copyright_text' => $data['copyright_text'] ?? NULL,
      'show_legal_links' => $data['show_legal_links'] ?? TRUE,
      'bg_color' => $data['bg_color'] ?? '#1E293B',
      'text_color' => $data['text_color'] ?? '#94A3B8',
      'accent_color' => $data['accent_color'] ?? '#3B82F6',
    ];
    
    $this->database->merge('site_footer_config')
      ->key('tenant_id', $tenantId)
      ->fields($fields)
      ->execute();
    
    // Invalidar caché
    $this->cacheTagsInvalidator->invalidateTags(["site_footer:{$tenantId}"]);
    
    return new JsonResponse(['success' => TRUE]);
  }
 
  /**
   * Crea configuración por defecto del footer.
   */
  protected function createDefaultFooterConfig(int $tenantId): array {
    $siteConfig = $this->database->select('site_config', 'sc')
      ->fields('sc', ['site_name'])
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();
    
    $siteName = $siteConfig['site_name'] ?? 'Mi Sitio';
    
    $defaultConfig = [
      'footer_type' => 'columns',
      'show_logo' => TRUE,
      'description' => NULL,
      'columns_config' => json_encode([
        ['title' => 'Enlaces', 'menu_id' => NULL],
        ['title' => 'Legal', 'menu_id' => NULL],
        ['title' => 'Contacto', 'type' => 'contact'],
      ]),
      'show_social' => TRUE,
      'social_position' => 'bottom',
      'show_newsletter' => FALSE,
      'copyright_text' => "© " . date('Y') . " {$siteName}. Todos los derechos reservados.",
      'show_legal_links' => TRUE,
      'bg_color' => '#1E293B',
      'text_color' => '#94A3B8',
      'accent_color' => '#3B82F6',
    ];
    
    $this->database->insert('site_footer_config')
      ->fields(array_merge(['tenant_id' => $tenantId], $defaultConfig))
      ->execute();
    
    return $defaultConfig;
  }
}

5.1. Configuración de Columnas
El footer multi-columna permite configurar cada columna:
 footer-columns-config.json
{
  "columns_config": [
    {
      "title": "Empresa",
      "type": "menu",
      "menu_id": 5
    },
    {
      "title": "Servicios",
      "type": "menu",
      "menu_id": 6
    },
    {
      "title": "Contacto",
      "type": "contact",
      "show_email": true,
      "show_phone": true,
      "show_address": true
    },
    {
      "title": "Newsletter",
      "type": "newsletter"
    }
  ]
}

 
6. Templates Twig
Template principal del header con soporte para todos los tipos y mega menus.
 header.html.twig
{# header.html.twig - Template global del header #}
{% set config = site_header_config %}
{% set menu = config.main_menu %}
 
{# Top Bar #}
{% if config.show_topbar and config.topbar_content %}
<div class="jaraba-topbar" style="background-color: {{ config.topbar_bg_color }}; color: {{ config.topbar_text_color }};">
  <div class="jaraba-container">
    {{ config.topbar_content|raw }}
  </div>
</div>
{% endif %}
 
{# Header Principal #}
<header 
  class="jaraba-header jaraba-header--{{ config.header_type }}{% if config.is_sticky %} jaraba-header--sticky{% endif %}{% if config.transparent_on_hero %} jaraba-header--transparent{% endif %}"
  style="--header-bg: {{ config.bg_color }}; --header-text: {{ config.text_color }}; --header-height: {{ config.height_desktop }}px; --header-height-mobile: {{ config.height_mobile }}px;"
  data-sticky="{{ config.is_sticky ? 'true' : 'false' }}"
  data-sticky-offset="{{ config.sticky_offset }}"
  data-hide-on-scroll="{{ config.hide_on_scroll_down ? 'true' : 'false' }}"
>
  <div class="jaraba-container">
    <div class="jaraba-header__inner">
      
      {# Logo #}
      <a href="/" class="jaraba-header__logo" aria-label="Ir al inicio">
        {% if config.logo_url %}
          <img 
            src="{{ config.logo_url }}" 
            alt="{{ config.logo_alt ?: site_config.site_name }}"
            width="{{ config.logo_width }}"
            loading="eager"
          />
        {% else %}
          <span class="jaraba-header__logo-text">{{ site_config.site_name }}</span>
        {% endif %}
      </a>
 
      {# Navegación Principal #}
      <nav 
        class="jaraba-header__nav jaraba-header__nav--{{ config.main_menu_position }}"
        aria-label="Navegación principal"
      >
        <ul class="jaraba-nav" role="menubar">
          {% for item in menu %}
            {{ _self.render_menu_item(item, loop.index) }}
          {% endfor %}
        </ul>
      </nav>
 
      {# Actions #}
      <div class="jaraba-header__actions">
        
        {# Search #}
        {% if config.show_search %}
        <button class="jaraba-header__action jaraba-header__search-toggle" aria-label="Buscar">
          <i data-lucide="search"></i>
        </button>
        {% endif %}
        
        {# Language Switcher #}
        {% if config.show_language_switcher and tenant_languages|length > 1 %}
        <div class="jaraba-header__lang-switcher">
          {{ language_switcher(tenant_languages, current_path) }}
        </div>
        {% endif %}
        
        {# CTA #}
        {% if config.show_cta and config.cta_text %}
        <a 
          href="{{ config.cta_url }}" 
          class="jaraba-btn jaraba-btn--{{ config.cta_style }} jaraba-header__cta"
        >
          {% if config.cta_icon %}
            <i data-lucide="{{ config.cta_icon }}"></i>
          {% endif %}
          {{ config.cta_text }}
        </a>
        {% endif %}
        
        {# Mobile Menu Toggle #}
        <button 
          class="jaraba-header__mobile-toggle"
          aria-label="Abrir menú"
          aria-expanded="false"
          aria-controls="mobile-menu"
        >
          <span class="jaraba-hamburger">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </button>
      </div>
    </div>
  </div>
</header>
 
{# Mobile Menu #}
<div id="mobile-menu" class="jaraba-mobile-menu" aria-hidden="true">
  <div class="jaraba-mobile-menu__overlay"></div>
  <div class="jaraba-mobile-menu__panel">
    <div class="jaraba-mobile-menu__header">
      {% if config.logo_mobile_url %}
        <img src="{{ config.logo_mobile_url }}" alt="{{ config.logo_alt }}" class="jaraba-mobile-menu__logo" />
      {% endif %}
      <button class="jaraba-mobile-menu__close" aria-label="Cerrar menú">
        <i data-lucide="x"></i>
      </button>
    </div>
    <nav class="jaraba-mobile-menu__nav" aria-label="Navegación móvil">
      <ul class="jaraba-mobile-nav">
        {% for item in menu %}
          {{ _self.render_mobile_menu_item(item) }}
        {% endfor %}
      </ul>
    </nav>
    {% if config.show_cta and config.cta_text %}
    <div class="jaraba-mobile-menu__footer">
      <a href="{{ config.cta_url }}" class="jaraba-btn jaraba-btn--{{ config.cta_style }} jaraba-btn--block">
        {{ config.cta_text }}
      </a>
    </div>
    {% endif %}
  </div>
</div>
 
{# Macro: Render Menu Item #}
{% macro render_menu_item(item, index) %}
  {% set has_children = item.children|length > 0 %}
  {% set is_mega = item.item_type == 'mega_column' %}
  
  <li 
    class="jaraba-nav__item{% if has_children %} jaraba-nav__item--has-dropdown{% endif %}{% if is_mega %} jaraba-nav__item--mega{% endif %}{% if item.highlight %} jaraba-nav__item--highlight{% endif %}"
    role="none"
  >
    {% if item.item_type == 'divider' %}
      <span class="jaraba-nav__divider" role="separator"></span>
    {% elseif item.item_type == 'heading' %}
      <span class="jaraba-nav__heading">{{ item.title }}</span>
    {% else %}
      <a 
        href="{{ item.computed_url ?: item.url ?: '#' }}"
        class="jaraba-nav__link"
        role="menuitem"
        {% if has_children %}
          aria-haspopup="true"
          aria-expanded="false"
        {% endif %}
        {% if item.open_in_new_tab %}
          target="_blank"
          rel="noopener noreferrer"
        {% endif %}
      >
        {% if item.icon %}
          <i data-lucide="{{ item.icon }}" class="jaraba-nav__icon"></i>
        {% endif %}
        <span>{{ item.title }}</span>
        {% if item.badge_text %}
          <span class="jaraba-nav__badge" style="background-color: {{ item.badge_color ?: '#3B82F6' }}">
            {{ item.badge_text }}
          </span>
        {% endif %}
        {% if has_children %}
          <i data-lucide="chevron-down" class="jaraba-nav__arrow"></i>
        {% endif %}
      </a>
      
      {# Dropdown / Mega Menu #}
      {% if has_children %}
        {% if is_mega %}
          <div class="jaraba-mega-menu" role="menu">
            <div class="jaraba-container">
              <div class="jaraba-mega-menu__grid">
                {% for child in item.children %}
                  <div class="jaraba-mega-menu__column">
                    {% if child.title %}
                      <h3 class="jaraba-mega-menu__title">{{ child.title }}</h3>
                    {% endif %}
                    {% if child.mega_content %}
                      {{ child.mega_content|raw }}
                    {% elseif child.children %}
                      <ul class="jaraba-mega-menu__list">
                        {% for subitem in child.children %}
                          <li>
                            <a href="{{ subitem.computed_url ?: subitem.url }}">
                              {% if subitem.icon %}<i data-lucide="{{ subitem.icon }}"></i>{% endif %}
                              {{ subitem.title }}
                            </a>
                          </li>
                        {% endfor %}
                      </ul>
                    {% endif %}
                  </div>
                {% endfor %}
              </div>
            </div>
          </div>
        {% else %}
          <ul class="jaraba-dropdown" role="menu">
            {% for child in item.children %}
              {{ _self.render_menu_item(child, loop.index) }}
            {% endfor %}
          </ul>
        {% endif %}
      {% endif %}
    {% endif %}
  </li>
{% endmacro %}
 
{# Macro: Render Mobile Menu Item #}
{% macro render_mobile_menu_item(item) %}
  {% set has_children = item.children|length > 0 %}
  
  <li class="jaraba-mobile-nav__item{% if has_children %} jaraba-mobile-nav__item--has-children{% endif %}">
    <a 
      href="{{ item.computed_url ?: item.url ?: '#' }}"
      class="jaraba-mobile-nav__link"
      {% if has_children %}data-toggle="submenu"{% endif %}
    >
      {% if item.icon %}<i data-lucide="{{ item.icon }}"></i>{% endif %}
      <span>{{ item.title }}</span>
      {% if has_children %}<i data-lucide="chevron-right" class="jaraba-mobile-nav__arrow"></i>{% endif %}
    </a>
    
    {% if has_children %}
      <ul class="jaraba-mobile-nav__submenu">
        <li class="jaraba-mobile-nav__back">
          <button data-toggle="back"><i data-lucide="arrow-left"></i> Volver</button>
        </li>
        {% for child in item.children %}
          {{ _self.render_mobile_menu_item(child) }}
        {% endfor %}
      </ul>
    {% endif %}
  </li>
{% endmacro %}

 
7. Estilos CSS
Sistema de estilos completo para header, navegación y menú móvil.
 jaraba-header.css
/* =============================================
   HEADER STYLES - jaraba-header.css
   ============================================= */
 
/* Variables del Header */
.jaraba-header {
  --header-bg: #FFFFFF;
  --header-text: #1E293B;
  --header-height: 80px;
  --header-height-mobile: 64px;
  --header-shadow: 0 1px 3px rgba(0,0,0,0.1);
  --header-transition: all 0.3s ease;
}
 
/* Base */
.jaraba-header {
  position: relative;
  z-index: 1000;
  background: var(--header-bg);
  color: var(--header-text);
  height: var(--header-height);
  transition: var(--header-transition);
}
 
.jaraba-header__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  gap: 2rem;
}
 
/* Sticky Header */
.jaraba-header--sticky {
  position: sticky;
  top: 0;
}
 
.jaraba-header--sticky.is-scrolled {
  box-shadow: var(--header-shadow);
}
 
.jaraba-header--sticky.is-hidden {
  transform: translateY(-100%);
}
 
/* Transparent Header */
.jaraba-header--transparent:not(.is-scrolled) {
  background: transparent;
  --header-text: #FFFFFF;
}
 
/* Logo */
.jaraba-header__logo {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}
 
.jaraba-header__logo img {
  height: auto;
  max-height: calc(var(--header-height) - 24px);
}
 
.jaraba-header__logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--header-text);
}
 
/* Navigation */
.jaraba-header__nav {
  display: none;
  flex: 1;
}
 
@media (min-width: 1024px) {
  .jaraba-header__nav {
    display: flex;
  }
}
 
.jaraba-header__nav--left { justify-content: flex-start; }
.jaraba-header__nav--center { justify-content: center; }
.jaraba-header__nav--right { justify-content: flex-end; }
 
.jaraba-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  list-style: none;
  margin: 0;
  padding: 0;
}
 
.jaraba-nav__link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  color: var(--header-text);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9375rem;
  border-radius: 0.5rem;
  transition: background 0.2s, color 0.2s;
}
 
.jaraba-nav__link:hover {
  background: rgba(0,0,0,0.05);
}
 
.jaraba-nav__item--highlight .jaraba-nav__link {
  background: var(--primary-color, #3B82F6);
  color: #FFFFFF;
}
 
.jaraba-nav__icon {
  width: 18px;
  height: 18px;
}
 
.jaraba-nav__arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s;
}
 
.jaraba-nav__item:hover .jaraba-nav__arrow {
  transform: rotate(180deg);
}
 
.jaraba-nav__badge {
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #FFFFFF;
  border-radius: 9999px;
}
 
/* Dropdown */
.jaraba-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 220px;
  background: #FFFFFF;
  border-radius: 0.75rem;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
  padding: 0.5rem;
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: all 0.2s ease;
  list-style: none;
  margin: 0;
}
 
.jaraba-nav__item:hover > .jaraba-dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
 
.jaraba-dropdown .jaraba-nav__link {
  padding: 0.625rem 1rem;
  border-radius: 0.5rem;
  color: #374151;
}
 
.jaraba-dropdown .jaraba-nav__link:hover {
  background: #F3F4F6;
  color: #1E293B;
}
 
/* Mega Menu */
.jaraba-mega-menu {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(10px);
  width: 100vw;
  max-width: 1200px;
  background: #FFFFFF;
  border-radius: 1rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  padding: 2rem;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}
 
.jaraba-nav__item--mega:hover > .jaraba-mega-menu {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0);
}
 
.jaraba-mega-menu__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
}
 
.jaraba-mega-menu__title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6B7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 1rem;
}
 
.jaraba-mega-menu__list {
  list-style: none;
  padding: 0;
  margin: 0;
}
 
.jaraba-mega-menu__list a {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
  color: #374151;
  text-decoration: none;
  transition: color 0.2s;
}
 
.jaraba-mega-menu__list a:hover {
  color: var(--primary-color, #3B82F6);
}
 
/* Header Actions */
.jaraba-header__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
 
.jaraba-header__action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  color: var(--header-text);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.2s;
}
 
.jaraba-header__action:hover {
  background: rgba(0,0,0,0.05);
}
 
.jaraba-header__cta {
  display: none;
}
 
@media (min-width: 768px) {
  .jaraba-header__cta {
    display: inline-flex;
  }
}
 
/* Mobile Menu Toggle */
.jaraba-header__mobile-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 0;
}
 
@media (min-width: 1024px) {
  .jaraba-header__mobile-toggle {
    display: none;
  }
}
 
.jaraba-hamburger {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
 
.jaraba-hamburger span {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--header-text);
  transition: all 0.3s ease;
}
 
/* Mobile Menu */
.jaraba-mobile-menu {
  position: fixed;
  inset: 0;
  z-index: 9999;
  visibility: hidden;
}
 
.jaraba-mobile-menu.is-open {
  visibility: visible;
}
 
.jaraba-mobile-menu__overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.5);
  opacity: 0;
  transition: opacity 0.3s;
}
 
.jaraba-mobile-menu.is-open .jaraba-mobile-menu__overlay {
  opacity: 1;
}
 
.jaraba-mobile-menu__panel {
  position: absolute;
  top: 0;
  right: 0;
  width: 100%;
  max-width: 320px;
  height: 100%;
  background: #FFFFFF;
  transform: translateX(100%);
  transition: transform 0.3s ease;
  display: flex;
  flex-direction: column;
}
 
.jaraba-mobile-menu.is-open .jaraba-mobile-menu__panel {
  transform: translateX(0);
}
 
.jaraba-mobile-menu__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-bottom: 1px solid #E5E7EB;
}
 
.jaraba-mobile-menu__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  background: #F3F4F6;
  border-radius: 0.5rem;
  cursor: pointer;
}
 
.jaraba-mobile-menu__nav {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}
 
.jaraba-mobile-nav {
  list-style: none;
  padding: 0;
  margin: 0;
}
 
.jaraba-mobile-nav__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  color: #1E293B;
  text-decoration: none;
  font-weight: 500;
  border-radius: 0.5rem;
  transition: background 0.2s;
}
 
.jaraba-mobile-nav__link:hover {
  background: #F3F4F6;
}
 
.jaraba-mobile-menu__footer {
  padding: 1rem;
  border-top: 1px solid #E5E7EB;
}
 
/* Topbar */
.jaraba-topbar {
  padding: 0.5rem 0;
  font-size: 0.875rem;
  text-align: center;
}
 
@media (max-width: 767px) {
  .jaraba-header {
    height: var(--header-height-mobile);
  }
}

 
8. APIs REST Completas
8.1. Endpoints de Header
Método	Endpoint	Descripción
GET	/api/v1/site/header	Obtener config header
PUT	/api/v1/site/header	Actualizar config
POST	/api/v1/site/header/logo	Subir logo
DELETE	/api/v1/site/header/logo	Eliminar logo
8.2. Endpoints de Footer
Método	Endpoint	Descripción
GET	/api/v1/site/footer	Obtener config footer
PUT	/api/v1/site/footer	Actualizar config
POST	/api/v1/site/footer/logo	Subir logo footer
8.3. Endpoints de Menús
Método	Endpoint	Descripción
GET	/api/v1/site/menus	Lista menús
POST	/api/v1/site/menus	Crear menú
GET	/api/v1/site/menus/{id}	Detalle menú
DELETE	/api/v1/site/menus/{id}	Eliminar menú
GET	/api/v1/site/menus/{id}/tree	Árbol de items
POST	/api/v1/site/menus/{id}/items	Añadir item
PATCH	/api/v1/site/menus/{id}/items/{itemId}	Actualizar item
DELETE	/api/v1/site/menus/{id}/items/{itemId}	Eliminar item
POST	/api/v1/site/menus/{id}/reorder	Reordenar items
 
9. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Tipos de header	standard, minimal	Todos	Todos + custom
Menús personalizados	1	5	Ilimitados
Items por menú	10	50	Ilimitados
Niveles de profundidad	1	2	3
Mega menus	—	✓	✓
Top bar	—	✓	✓
Footer columnas	2	4	Ilimitadas
Newsletter en footer	—	✓	✓
CSS personalizado	—	—	✓

10. Roadmap de Implementación
Sprint	Componente	Horas	Entregables
1	Modelo de datos + migraciones	8-10h	Tablas header, footer, menu, menu_item
1	APIs CRUD header y footer	10-12h	Endpoints completos
2	UI Header Builder	12-15h	Panel visual de configuración
2	Sistema de menús + drag & drop	12-15h	Menu builder interactivo
3	Templates Twig + CSS	10-12h	Renderizado frontend
3	Mobile menu + mega menus	8-10h	Navegación responsive

Total: 50-60 horas (€4,000-€4,800 @ €80/h)

Dependencias
Doc 176 (Site Structure Manager) - Integración con árbol de páginas | Doc 162 (Page Builder) - Páginas enlazadas | Doc 166 (i18n) - Selector de idioma

Fin del documento.
