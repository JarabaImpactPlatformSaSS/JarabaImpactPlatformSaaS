176
ESPECIFICACIÓN TÉCNICA
Site Structure Manager
Árbol de Páginas | Jerarquía | URLs | Redirects | Sitemap Visual
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	27 de Enero de 2026
Horas Estimadas:	40-50 horas
 
Índice de Contenidos
1. Resumen Ejecutivo
2. Arquitectura de Datos
3. APIs REST
4. Interfaz de Usuario
5. Generación de Sitemap
6. Sistema de Redirects
7. Límites por Plan
8. Roadmap de Implementación
 
1. Resumen Ejecutivo
El Site Structure Manager permite a los tenants organizar sus páginas en una estructura jerárquica visual tipo árbol, gestionando la navegación, URLs, redirects y sitemap de forma centralizada.

Capacidades Principales
Árbol visual drag & drop | Jerarquía padre/hijo ilimitada | URLs automáticas basadas en jerarquía | Redirects 301/302 con tracking | Sitemap XML automático | Detección de enlaces rotos | Análisis SEO de estructura

1.1. Objetivos del Sistema
•	Permitir crear sitios web completos multi-página sin código
•	Gestión visual de la estructura del sitio con drag & drop
•	URLs jerárquicas automáticas (/servicios/consultoria/precios)
•	Redirects automáticos cuando se renombra una página
•	Sitemap XML dinámico para SEO
•	Configuración global del sitio (logo, contacto, social)
1.2. Integración con Page Builder
Cada página del árbol es una entidad page_content creada con el Page Builder (Doc 162). El Site Structure Manager añade:
•	Posición en el árbol (parent_id, weight, depth)
•	Configuración de navegación (mostrar/ocultar, título corto)
•	Materialized path para queries eficientes
•	Vinculación con header/footer globales
 
2. Arquitectura de Datos
Esquema completo de base de datos para gestión de estructura del sitio.
 site_structure_schema.sql
-- =============================================
-- SITE STRUCTURE MANAGER - ESQUEMA DE BASE DE DATOS
-- =============================================
 
-- Configuración global del sitio por tenant
CREATE TABLE site_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL UNIQUE,
  
  -- Identidad del sitio
  site_name VARCHAR(255) NOT NULL,
  site_tagline VARCHAR(255),
  site_logo INT UNSIGNED, -- FK file_managed
  site_favicon INT UNSIGNED,
  
  -- Configuración de URLs
  homepage_id INT UNSIGNED, -- FK page_content (página de inicio)
  blog_index_id INT UNSIGNED, -- FK page_content (listado blog)
  error_404_id INT UNSIGNED, -- FK page_content (página 404)
  
  -- SEO Global
  default_meta_title_suffix VARCHAR(100), -- " | Mi Empresa"
  default_og_image INT UNSIGNED,
  google_analytics_id VARCHAR(50),
  google_tag_manager_id VARCHAR(50),
  
  -- Social Links (JSON)
  social_links JSON, -- [{"platform":"facebook","url":"..."},...]
  
  -- Contacto global
  contact_email VARCHAR(255),
  contact_phone VARCHAR(50),
  contact_address TEXT,
  contact_coordinates JSON, -- {"lat":37.5,"lng":-4.5}
  
  -- Legal
  privacy_policy_id INT UNSIGNED,
  terms_conditions_id INT UNSIGNED,
  cookies_policy_id INT UNSIGNED,
  
  -- Configuración visual
  header_config_id INT UNSIGNED, -- FK site_header_config
  footer_config_id INT UNSIGNED, -- FK site_footer_config
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Estructura jerárquica de páginas
CREATE TABLE site_page_tree (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  page_id INT UNSIGNED NOT NULL, -- FK page_content
  
  -- Jerarquía
  parent_id INT UNSIGNED DEFAULT NULL, -- FK site_page_tree (self-reference)
  weight INT DEFAULT 0, -- Orden entre hermanos
  depth INT DEFAULT 0, -- Nivel de profundidad (0 = raíz)
  path VARCHAR(500), -- Materialized path: /1/5/12/
  
  -- Configuración de página
  show_in_navigation BOOLEAN DEFAULT TRUE,
  show_in_sitemap BOOLEAN DEFAULT TRUE,
  show_in_footer BOOLEAN DEFAULT FALSE,
  show_in_breadcrumbs BOOLEAN DEFAULT TRUE,
  
  -- Override de navegación
  nav_title VARCHAR(100), -- Título corto para menú (override)
  nav_icon VARCHAR(50), -- Icono Lucide para menú
  nav_highlight BOOLEAN DEFAULT FALSE, -- Destacar en navegación
  nav_external_url VARCHAR(500), -- URL externa (abre en nueva pestaña)
  
  -- Estado
  status ENUM('draft', 'published', 'archived') DEFAULT 'published',
  
  -- Fechas
  published_at TIMESTAMP NULL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  FOREIGN KEY (page_id) REFERENCES page_content(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES site_page_tree(id) ON DELETE SET NULL,
  
  UNIQUE KEY unique_tenant_page (tenant_id, page_id),
  INDEX idx_tenant_parent (tenant_id, parent_id),
  INDEX idx_path (path),
  INDEX idx_weight (tenant_id, parent_id, weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Redirects y URL aliases
CREATE TABLE site_redirects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  
  source_path VARCHAR(500) NOT NULL, -- /old-url
  destination_path VARCHAR(500) NOT NULL, -- /new-url o URL completa
  redirect_type ENUM('301', '302', '307') DEFAULT '301',
  
  -- Metadatos
  reason VARCHAR(255), -- "Página renombrada", "Migración"
  hit_count INT UNSIGNED DEFAULT 0,
  last_hit TIMESTAMP NULL,
  
  -- Estado
  is_active BOOLEAN DEFAULT TRUE,
  expires_at TIMESTAMP NULL,
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_source (tenant_id, source_path),
  INDEX idx_active (tenant_id, is_active, source_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Historial de URLs (para detectar cambios y crear redirects automáticos)
CREATE TABLE site_url_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  page_id INT UNSIGNED NOT NULL,
  
  old_path VARCHAR(500) NOT NULL,
  new_path VARCHAR(500) NOT NULL,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  auto_redirect_created BOOLEAN DEFAULT FALSE,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  FOREIGN KEY (page_id) REFERENCES page_content(id) ON DELETE CASCADE,
  INDEX idx_tenant_page (tenant_id, page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 
2.1. Diagrama de Relaciones
Relaciones entre entidades principales:
 entity-relationships.txt
┌─────────────────┐       ┌─────────────────┐
│   site_config   │       │  page_content   │
│─────────────────│       │─────────────────│
│ tenant_id (PK)  │       │ id (PK)         │
│ site_name       │       │ tenant_id       │
│ homepage_id ────┼──────→│ title           │
│ header_config   │       │ content_data    │
│ footer_config   │       │ path_alias      │
└─────────────────┘       └────────┬────────┘
                                   │
                                   ▼
                          ┌─────────────────┐
                          │ site_page_tree  │
                          │─────────────────│
                          │ id (PK)         │
                          │ tenant_id       │
                          │ page_id (FK) ───┼───→ page_content
                          │ parent_id (FK) ─┼───→ site_page_tree (self)
                          │ weight          │
                          │ depth           │
                          │ path (mat.)     │
                          │ show_in_nav     │
                          └─────────────────┘
                                   │
                                   ▼
                          ┌─────────────────┐
                          │ site_redirects  │
                          │─────────────────│
                          │ source_path     │
                          │ destination     │
                          │ redirect_type   │
                          │ hit_count       │
                          └─────────────────┘

 
3. APIs REST
3.1. Endpoints de Estructura
Método	Endpoint	Descripción
GET	/api/v1/site/tree	Árbol completo de páginas
POST	/api/v1/site/tree/reorder	Reordenar árbol (drag & drop)
POST	/api/v1/site/pages	Añadir página al árbol
PATCH	/api/v1/site/pages/{id}	Actualizar config de página
DELETE	/api/v1/site/pages/{id}	Quitar página del árbol
POST	/api/v1/site/pages/{id}/move	Mover página a otro padre
POST	/api/v1/site/pages/{id}/duplicate	Duplicar rama completa
GET	/api/v1/site/pages/{id}/breadcrumbs	Obtener breadcrumbs

3.2. Endpoints de Configuración
Método	Endpoint	Descripción
GET	/api/v1/site/config	Configuración global del sitio
PUT	/api/v1/site/config	Actualizar configuración
POST	/api/v1/site/config/logo	Subir logo del sitio
POST	/api/v1/site/config/favicon	Subir favicon
GET	/api/v1/site/sitemap.xml	Sitemap XML público
GET	/api/v1/site/sitemap/visual	Datos para sitemap visual

3.3. Endpoints de Redirects
Método	Endpoint	Descripción
GET	/api/v1/site/redirects	Lista de redirects
POST	/api/v1/site/redirects	Crear redirect
PATCH	/api/v1/site/redirects/{id}	Actualizar redirect
DELETE	/api/v1/site/redirects/{id}	Eliminar redirect
POST	/api/v1/site/redirects/bulk-import	Importar CSV
GET	/api/v1/site/redirects/export	Exportar CSV
 
3.4. Controller de Estructura
 SiteStructureController.php
<?php
 
namespace Drupal\jaraba_site_builder\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
 
/**
 * API Controller para Site Structure Manager.
 */
class SiteStructureController extends ControllerBase {
 
  /**
   * GET /api/v1/site/tree
   * Obtiene árbol completo de páginas del tenant.
   */
  public function getTree(): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $pages = $this->database->select('site_page_tree', 'spt')
      ->fields('spt')
      ->condition('spt.tenant_id', $tenantId)
      ->orderBy('spt.depth')
      ->orderBy('spt.weight')
      ->execute()
      ->fetchAll();
    
    // Construir árbol jerárquico
    $tree = $this->buildTree($pages);
    
    // Añadir info de cada página
    foreach ($tree as &$node) {
      $this->enrichNode($node);
    }
    
    return new JsonResponse([
      'tree' => $tree,
      'total_pages' => count($pages),
      'max_depth' => max(array_column($pages, 'depth')) ?: 0,
    ]);
  }
 
  /**
   * POST /api/v1/site/tree/reorder
   * Reordena páginas (drag & drop).
   */
  public function reorderTree(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Validar estructura
    if (empty($data['tree'])) {
      return new JsonResponse(['error' => 'Tree structure required'], 400);
    }
    
    // Iniciar transacción
    $transaction = $this->database->startTransaction();
    
    try {
      $this->updateTreeRecursive($data['tree'], $tenantId, NULL, 0);
      
      // Regenerar paths materializados
      $this->regeneratePaths($tenantId);
      
      return new JsonResponse(['success' => TRUE]);
      
    } catch (\Exception $e) {
      $transaction->rollBack();
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }
 
  /**
   * POST /api/v1/site/pages/{id}/move
   * Mueve una página a nuevo padre.
   */
  public function movePage(int $pageTreeId, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $newParentId = $data['parent_id'] ?? NULL;
    $newWeight = $data['weight'] ?? 0;
    
    // Validar que no sea su propio descendiente
    if ($newParentId) {
      $descendants = $this->getDescendantIds($pageTreeId);
      if (in_array($newParentId, $descendants)) {
        return new JsonResponse(['error' => 'Cannot move page to its own descendant'], 400);
      }
    }
    
    // Calcular nueva profundidad
    $newDepth = $newParentId ? $this->getDepth($newParentId) + 1 : 0;
    
    // Actualizar
    $this->database->update('site_page_tree')
      ->fields([
        'parent_id' => $newParentId,
        'weight' => $newWeight,
        'depth' => $newDepth,
      ])
      ->condition('id', $pageTreeId)
      ->condition('tenant_id', $tenantId)
      ->execute();
    
    // Actualizar profundidad de descendientes
    $this->updateDescendantDepths($pageTreeId, $newDepth);
    
    // Regenerar paths
    $this->regeneratePaths($tenantId);
    
    return new JsonResponse(['success' => TRUE]);
  }
 
  /**
   * POST /api/v1/site/pages
   * Añade página existente al árbol del sitio.
   */
  public function addPageToTree(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Validar página existe y pertenece al tenant
    $page = $this->pageStorage->load($data['page_id']);
    if (!$page || $page->get('tenant_id')->value != $tenantId) {
      return new JsonResponse(['error' => 'Page not found'], 404);
    }
    
    // Verificar no está ya en el árbol
    $existing = $this->database->select('site_page_tree', 'spt')
      ->condition('tenant_id', $tenantId)
      ->condition('page_id', $data['page_id'])
      ->countQuery()
      ->execute()
      ->fetchField();
    
    if ($existing) {
      return new JsonResponse(['error' => 'Page already in tree'], 409);
    }
    
    // Calcular peso (último de sus hermanos)
    $maxWeight = $this->database->select('site_page_tree', 'spt')
      ->condition('tenant_id', $tenantId)
      ->condition('parent_id', $data['parent_id'] ?? NULL, $data['parent_id'] ? '=' : 'IS NULL')
      ->addExpression('MAX(weight)', 'max_weight')
      ->execute()
      ->fetchField();
    
    $parentDepth = ($data['parent_id'] ?? NULL) ? $this->getDepth($data['parent_id']) : -1;
    
    // Insertar
    $id = $this->database->insert('site_page_tree')
      ->fields([
        'tenant_id' => $tenantId,
        'page_id' => $data['page_id'],
        'parent_id' => $data['parent_id'] ?? NULL,
        'weight' => ($maxWeight ?? -1) + 1,
        'depth' => $parentDepth + 1,
        'show_in_navigation' => $data['show_in_navigation'] ?? TRUE,
        'nav_title' => $data['nav_title'] ?? NULL,
      ])
      ->execute();
    
    // Regenerar paths
    $this->regeneratePaths($tenantId);
    
    return new JsonResponse([
      'id' => $id,
      'success' => TRUE,
    ], 201);
  }
 
  /**
   * GET /api/v1/site/config
   * Obtiene configuración global del sitio.
   */
  public function getSiteConfig(): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $config = $this->database->select('site_config', 'sc')
      ->fields('sc')
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();
    
    if (!$config) {
      // Crear configuración por defecto
      $config = $this->createDefaultConfig($tenantId);
    }
    
    // Expandir referencias
    $config['social_links'] = json_decode($config['social_links'] ?? '[]', TRUE);
    $config['contact_coordinates'] = json_decode($config['contact_coordinates'] ?? 'null', TRUE);
    
    // Cargar logos
    if ($config['site_logo']) {
      $config['site_logo_url'] = $this->fileUrlGenerator->generateAbsoluteString(
        $this->fileStorage->load($config['site_logo'])->getFileUri()
      );
    }
    
    return new JsonResponse($config);
  }
 
  /**
   * PUT /api/v1/site/config
   * Actualiza configuración global del sitio.
   */
  public function updateSiteConfig(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Preparar campos
    $fields = [
      'site_name' => $data['site_name'] ?? NULL,
      'site_tagline' => $data['site_tagline'] ?? NULL,
      'homepage_id' => $data['homepage_id'] ?? NULL,
      'blog_index_id' => $data['blog_index_id'] ?? NULL,
      'default_meta_title_suffix' => $data['default_meta_title_suffix'] ?? NULL,
      'social_links' => json_encode($data['social_links'] ?? []),
      'contact_email' => $data['contact_email'] ?? NULL,
      'contact_phone' => $data['contact_phone'] ?? NULL,
      'contact_address' => $data['contact_address'] ?? NULL,
      'contact_coordinates' => json_encode($data['contact_coordinates'] ?? NULL),
    ];
    
    // Upsert
    $this->database->merge('site_config')
      ->key('tenant_id', $tenantId)
      ->fields($fields)
      ->execute();
    
    return new JsonResponse(['success' => TRUE]);
  }
 
  /**
   * Regenera materialized paths para todo el árbol.
   */
  protected function regeneratePaths(int $tenantId): void {
    $pages = $this->database->select('site_page_tree', 'spt')
      ->fields('spt', ['id', 'parent_id'])
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAllAssoc('id');
    
    foreach ($pages as $page) {
      $path = $this->buildPath($page->id, $pages);
      
      $this->database->update('site_page_tree')
        ->fields(['path' => $path])
        ->condition('id', $page->id)
        ->execute();
    }
  }
 
  /**
   * Construye path materializado recursivamente.
   */
  protected function buildPath(int $id, array $pages, string $path = ''): string {
    $path = '/' . $id . $path;
    
    $parentId = $pages[$id]->parent_id ?? NULL;
    if ($parentId && isset($pages[$parentId])) {
      return $this->buildPath($parentId, $pages, $path);
    }
    
    return $path . '/';
  }
}

 
4. Interfaz de Usuario
4.1. Site Tree Manager (React)
Componente principal para gestión visual del árbol de páginas con drag & drop.
 SiteTreeManager.jsx
/**
 * SiteTreeManager.jsx
 * Componente React para gestión visual del árbol de páginas
 */
 
import React, { useState, useEffect, useCallback } from 'react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { 
  ChevronRight, ChevronDown, File, Folder, FolderOpen,
  Eye, EyeOff, ExternalLink, GripVertical, MoreHorizontal,
  Plus, Settings, Trash2, Edit, Copy, ArrowUpRight
} from 'lucide-react';
 
const SiteTreeManager = ({ tenantId }) => {
  const [tree, setTree] = useState([]);
  const [expandedNodes, setExpandedNodes] = useState(new Set());
  const [selectedNode, setSelectedNode] = useState(null);
  const [loading, setLoading] = useState(true);
 
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );
 
  // Cargar árbol
  useEffect(() => {
    fetchTree();
  }, [tenantId]);
 
  const fetchTree = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/site/tree');
      const data = await response.json();
      setTree(data.tree);
    } catch (error) {
      console.error('Error fetching tree:', error);
    } finally {
      setLoading(false);
    }
  };
 
  // Manejar drag & drop
  const handleDragEnd = async (event) => {
    const { active, over } = event;
    
    if (active.id !== over?.id) {
      // Actualizar UI optimistamente
      setTree((items) => {
        const oldIndex = items.findIndex(i => i.id === active.id);
        const newIndex = items.findIndex(i => i.id === over.id);
        return arrayMove(items, oldIndex, newIndex);
      });
      
      // Enviar al servidor
      try {
        await fetch('/api/v1/site/tree/reorder', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tree: tree }),
        });
      } catch (error) {
        // Revertir en caso de error
        fetchTree();
      }
    }
  };
 
  // Toggle expandir/colapsar
  const toggleExpand = (nodeId) => {
    setExpandedNodes(prev => {
      const newSet = new Set(prev);
      if (newSet.has(nodeId)) {
        newSet.delete(nodeId);
      } else {
        newSet.add(nodeId);
      }
      return newSet;
    });
  };
 
  // Renderizar nodo del árbol
  const renderNode = (node, depth = 0) => {
    const hasChildren = node.children && node.children.length > 0;
    const isExpanded = expandedNodes.has(node.id);
    const isSelected = selectedNode === node.id;
 
    return (
      <div key={node.id}>
        <div
          className={`
            flex items-center gap-2 py-2 px-3 rounded-lg cursor-pointer
            hover:bg-gray-100 transition-colors
            ${isSelected ? 'bg-blue-50 border border-blue-200' : ''}
          `}
          style={{ paddingLeft: `${depth * 24 + 12}px` }}
          onClick={() => setSelectedNode(node.id)}
        >
          {/* Drag handle */}
          <GripVertical className="w-4 h-4 text-gray-400 cursor-grab" />
          
          {/* Expand/Collapse */}
          {hasChildren ? (
            <button
              onClick={(e) => { e.stopPropagation(); toggleExpand(node.id); }}
              className="p-1 hover:bg-gray-200 rounded"
            >
              {isExpanded ? (
                <ChevronDown className="w-4 h-4" />
              ) : (
                <ChevronRight className="w-4 h-4" />
              )}
            </button>
          ) : (
            <span className="w-6" />
          )}
          
          {/* Icon */}
          {hasChildren ? (
            isExpanded ? (
              <FolderOpen className="w-5 h-5 text-yellow-500" />
            ) : (
              <Folder className="w-5 h-5 text-yellow-500" />
            )
          ) : (
            <File className="w-5 h-5 text-gray-400" />
          )}
          
          {/* Title */}
          <span className="flex-1 font-medium text-gray-800">
            {node.nav_title || node.page_title}
          </span>
          
          {/* Status indicators */}
          <div className="flex items-center gap-2">
            {!node.show_in_navigation && (
              <EyeOff className="w-4 h-4 text-gray-400" title="Oculto en navegación" />
            )}
            {node.nav_external_url && (
              <ExternalLink className="w-4 h-4 text-blue-500" title="Enlace externo" />
            )}
            {node.status === 'draft' && (
              <span className="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded">
                Borrador
              </span>
            )}
          </div>
          
          {/* Actions */}
          <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100">
            <button className="p-1 hover:bg-gray-200 rounded" title="Editar">
              <Edit className="w-4 h-4 text-gray-500" />
            </button>
            <button className="p-1 hover:bg-gray-200 rounded" title="Más opciones">
              <MoreHorizontal className="w-4 h-4 text-gray-500" />
            </button>
          </div>
        </div>
        
        {/* Children */}
        {hasChildren && isExpanded && (
          <div>
            {node.children.map(child => renderNode(child, depth + 1))}
          </div>
        )}
      </div>
    );
  };
 
  return (
    <div className="bg-white rounded-xl shadow-sm border">
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b">
        <h2 className="text-lg font-semibold">Estructura del Sitio</h2>
        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <Plus className="w-4 h-4" />
            Añadir Página
          </button>
          <button className="p-2 hover:bg-gray-100 rounded-lg" title="Configuración">
            <Settings className="w-5 h-5 text-gray-500" />
          </button>
        </div>
      </div>
      
      {/* Tree */}
      <div className="p-4 max-h-[600px] overflow-y-auto">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <div className="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full" />
          </div>
        ) : (
          <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
          >
            <SortableContext items={tree} strategy={verticalListSortingStrategy}>
              {tree.map(node => renderNode(node))}
            </SortableContext>
          </DndContext>
        )}
      </div>
      
      {/* Footer stats */}
      <div className="flex items-center justify-between p-4 border-t bg-gray-50 text-sm text-gray-500">
        <span>{tree.length} páginas en total</span>
        <span>Arrastra para reordenar</span>
      </div>
    </div>
  );
};
 
export default SiteTreeManager;

 
4.2. Funcionalidades de la UI
Panel Árbol de Páginas
•	Vista jerárquica expandible/colapsable
•	Drag & drop para reordenar y reparentar
•	Indicadores visuales: draft, oculto, externo
•	Acciones rápidas: editar, duplicar, eliminar
•	Búsqueda y filtrado de páginas
Panel de Configuración de Página
•	Título de navegación (override)
•	Icono para menú (selector Lucide)
•	Visibilidad: navegación, sitemap, footer, breadcrumbs
•	URL personalizada o automática
•	Página padre (selector con árbol)
Panel de Configuración Global
•	Nombre y tagline del sitio
•	Logo y favicon (upload con preview)
•	Página de inicio (selector)
•	Página 404 personalizada
•	Datos de contacto globales
•	Redes sociales
 
5. Generación de Sitemap
 SitemapGenerator.php
<?php
 
namespace Drupal\jaraba_site_builder\Service;
 
/**
 * Generador de Sitemap Visual y XML.
 */
class SitemapGenerator {
 
  /**
   * Genera sitemap XML para el tenant.
   */
  public function generateXML(int $tenantId): string {
    $baseUrl = $this->getBaseUrl($tenantId);
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    // Obtener páginas del árbol
    $pages = $this->database->select('site_page_tree', 'spt')
      ->fields('spt', ['page_id', 'depth', 'changed'])
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'published')
      ->condition('show_in_sitemap', TRUE)
      ->execute()
      ->fetchAll();
    
    foreach ($pages as $page) {
      $pageContent = $this->pageStorage->load($page->page_id);
      if (!$pageContent) continue;
      
      $url = $baseUrl . $pageContent->get('path_alias')->value;
      $lastmod = date('Y-m-d', strtotime($page->changed));
      $priority = $this->calculatePriority($page->depth);
      $changefreq = $this->determineChangeFreq($pageContent);
      
      $xml .= '<url>';
      $xml .= "<loc>{$url}</loc>";
      $xml .= "<lastmod>{$lastmod}</lastmod>";
      $xml .= "<changefreq>{$changefreq}</changefreq>";
      $xml .= "<priority>{$priority}</priority>";
      $xml .= '</url>';
    }
    
    $xml .= '</urlset>';
    
    return $xml;
  }
 
  /**
   * Genera vista visual del sitemap (para panel de administración).
   */
  public function generateVisualMap(int $tenantId): array {
    $tree = $this->siteStructureService->getTree($tenantId);
    
    return [
      'tree' => $tree,
      'stats' => [
        'total_pages' => $this->countPages($tree),
        'indexed_pages' => $this->countIndexedPages($tree),
        'orphan_pages' => $this->findOrphanPages($tenantId),
        'broken_links' => $this->findBrokenLinks($tenantId),
      ],
      'seo_issues' => $this->analyzeSEOIssues($tree),
    ];
  }
 
  /**
   * Analiza problemas de SEO en la estructura.
   */
  protected function analyzeSEOIssues(array $tree): array {
    $issues = [];
    
    $this->walkTree($tree, function($node, $depth) use (&$issues) {
      // Páginas muy profundas (>3 niveles)
      if ($depth > 3) {
        $issues[] = [
          'type' => 'deep_page',
          'severity' => 'warning',
          'page_id' => $node['page_id'],
          'message' => "Página a {$depth} niveles de profundidad. Considerar reestructurar.",
        ];
      }
      
      // Páginas sin meta description
      if (empty($node['meta_description'])) {
        $issues[] = [
          'type' => 'missing_meta',
          'severity' => 'error',
          'page_id' => $node['page_id'],
          'message' => "Falta meta description.",
        ];
      }
      
      // URLs muy largas
      if (strlen($node['path_alias']) > 100) {
        $issues[] = [
          'type' => 'long_url',
          'severity' => 'warning',
          'page_id' => $node['page_id'],
          'message' => "URL demasiado larga (" . strlen($node['path_alias']) . " caracteres).",
        ];
      }
    });
    
    return $issues;
  }
 
  /**
   * Calcula prioridad basada en profundidad.
   */
  protected function calculatePriority(int $depth): string {
    $priorities = [
      0 => '1.0',  // Homepage
      1 => '0.8',  // Secciones principales
      2 => '0.6',  // Subsecciones
      3 => '0.4',  // Páginas profundas
    ];
    
    return $priorities[$depth] ?? '0.3';
  }
}

5.1. Sitemap Visual
Panel de administración que muestra estructura del sitio con estadísticas SEO:
•	Visualización gráfica del árbol
•	Páginas indexadas vs ocultas
•	Páginas huérfanas (no en árbol)
•	Enlaces rotos detectados
•	Alertas de profundidad excesiva
•	Páginas sin meta description
 
6. Sistema de Redirects
 RedirectsController.php
<?php
 
namespace Drupal\jaraba_site_builder\Controller;
 
/**
 * API Controller para Redirects.
 */
class RedirectsController extends ControllerBase {
 
  /**
   * GET /api/v1/site/redirects
   * Lista redirects del tenant.
   */
  public function listRedirects(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    $page = $request->query->get('page', 0);
    $limit = min($request->query->get('limit', 50), 100);
    
    $query = $this->database->select('site_redirects', 'sr')
      ->fields('sr')
      ->condition('tenant_id', $tenantId)
      ->orderBy('created', 'DESC')
      ->range($page * $limit, $limit);
    
    $redirects = $query->execute()->fetchAll();
    
    $total = $this->database->select('site_redirects', 'sr')
      ->condition('tenant_id', $tenantId)
      ->countQuery()
      ->execute()
      ->fetchField();
    
    return new JsonResponse([
      'redirects' => $redirects,
      'pagination' => [
        'page' => (int) $page,
        'limit' => (int) $limit,
        'total' => (int) $total,
        'pages' => ceil($total / $limit),
      ],
    ]);
  }
 
  /**
   * POST /api/v1/site/redirects
   * Crea nuevo redirect.
   */
  public function createRedirect(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Validar source no existe
    $existing = $this->database->select('site_redirects', 'sr')
      ->condition('tenant_id', $tenantId)
      ->condition('source_path', $data['source_path'])
      ->countQuery()
      ->execute()
      ->fetchField();
    
    if ($existing) {
      return new JsonResponse(['error' => 'Source path already has a redirect'], 409);
    }
    
    // Validar no es loop
    if ($data['source_path'] === $data['destination_path']) {
      return new JsonResponse(['error' => 'Source and destination cannot be the same'], 400);
    }
    
    $id = $this->database->insert('site_redirects')
      ->fields([
        'tenant_id' => $tenantId,
        'source_path' => $this->normalizePath($data['source_path']),
        'destination_path' => $data['destination_path'],
        'redirect_type' => $data['redirect_type'] ?? '301',
        'reason' => $data['reason'] ?? NULL,
        'is_active' => TRUE,
      ])
      ->execute();
    
    return new JsonResponse(['id' => $id, 'success' => TRUE], 201);
  }
 
  /**
   * POST /api/v1/site/redirects/bulk-import
   * Importa redirects desde CSV.
   */
  public function bulkImportRedirects(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    $file = $request->files->get('file');
    
    if (!$file || $file->getClientOriginalExtension() !== 'csv') {
      return new JsonResponse(['error' => 'CSV file required'], 400);
    }
    
    $handle = fopen($file->getPathname(), 'r');
    $header = fgetcsv($handle);
    
    $imported = 0;
    $errors = [];
    
    while (($row = fgetcsv($handle)) !== FALSE) {
      $data = array_combine($header, $row);
      
      try {
        $this->database->insert('site_redirects')
          ->fields([
            'tenant_id' => $tenantId,
            'source_path' => $this->normalizePath($data['source']),
            'destination_path' => $data['destination'],
            'redirect_type' => $data['type'] ?? '301',
            'reason' => 'Bulk import',
            'is_active' => TRUE,
          ])
          ->execute();
        $imported++;
      } catch (\Exception $e) {
        $errors[] = "Line {$imported}: {$e->getMessage()}";
      }
    }
    
    fclose($handle);
    
    return new JsonResponse([
      'imported' => $imported,
      'errors' => $errors,
    ]);
  }
 
  /**
   * Middleware: Procesa redirects entrantes.
   */
  public function processRedirect(string $path): ?string {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $redirect = $this->database->select('site_redirects', 'sr')
      ->fields('sr', ['destination_path', 'redirect_type'])
      ->condition('tenant_id', $tenantId)
      ->condition('source_path', $path)
      ->condition('is_active', TRUE)
      ->execute()
      ->fetchAssoc();
    
    if ($redirect) {
      // Incrementar contador
      $this->database->update('site_redirects')
        ->expression('hit_count', 'hit_count + 1')
        ->fields(['last_hit' => date('Y-m-d H:i:s')])
        ->condition('tenant_id', $tenantId)
        ->condition('source_path', $path)
        ->execute();
      
      return $redirect['destination_path'];
    }
    
    return NULL;
  }
}

6.1. Redirects Automáticos
El sistema crea redirects 301 automáticamente cuando:
•	Se cambia el path_alias de una página
•	Se mueve una página a otro padre (cambia URL jerárquica)
•	Se renombra una página (si genera nuevo slug)
6.2. Gestión Manual
•	Crear redirects personalizados
•	Importar/exportar CSV para migraciones
•	Estadísticas de uso (hits)
•	Expiración automática opcional
 
7. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Páginas en árbol	10	50	Ilimitadas
Niveles de profundidad	2	4	Ilimitados
Redirects	10	100	Ilimitados
Sitemap XML	Básico	Con imágenes	Completo + news
Análisis SEO estructura	—	Básico	Completo
Historial de URLs	—	30 días	1 año
Importar redirects CSV	—	✓	✓

8. Roadmap de Implementación
Sprint	Componente	Horas	Entregables
1	Modelo de datos + migraciones	10-12h	Tablas site_config, site_page_tree, site_redirects
1	APIs CRUD estructura	10-12h	Endpoints de árbol y configuración
2	UI Site Tree Manager	12-15h	React component con drag & drop
2	UI Configuración global	8-10h	Panel de settings del sitio
3	Sitemap generator	6-8h	XML dinámico + visual
3	Sistema redirects + auto-redirect	8-10h	CRUD + middleware

Total: 40-50 horas (€3,200-€4,000 @ €80/h)

Dependencias
Doc 162 (Page Builder Sistema Completo) - Entidad page_content | Doc 177 (Global Navigation) - Header/Footer | Doc 178 (Blog System) - Integración posts

Fin del documento.
