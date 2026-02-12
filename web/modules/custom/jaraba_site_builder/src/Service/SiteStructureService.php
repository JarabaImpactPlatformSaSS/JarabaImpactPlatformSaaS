<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar la estructura del árbol de páginas.
 *
 * Operaciones principales:
 * - Obtener árbol completo por tenant
 * - Reordenar nodos (drag & drop)
 * - Mover páginas entre padres
 * - Regenerar paths materializados
 */
class SiteStructureService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
        protected TenantContextService $tenantContext,
        protected LoggerInterface $logger,
        protected RedirectService $redirectService,
    ) {
    }

    /**
     * Obtiene el árbol completo de páginas para un tenant.
     *
     * @param int|null $tenantId
     *   ID del tenant, o NULL para usar el contexto actual.
     *
     * @return array
     *   Árbol jerárquico de páginas.
     */
    public function getTree(?int $tenantId = NULL): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenantId ?? ($tenant ? (int) $tenant->id() : null);
        if (!$tenantId) {
            return [];
        }

        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $entities = $storage->loadByProperties([
            'tenant_id' => $tenantId,
        ]);

        // Ordenar por weight.
        uasort($entities, fn($a, $b) => $a->get('weight')->value <=> $b->get('weight')->value);

        // Construir árbol jerárquico.
        return $this->buildTree($entities);
    }

    /**
     * Construye árbol jerárquico a partir de lista plana de entidades.
     */
    protected function buildTree(array $entities, ?int $parentId = NULL): array
    {
        $tree = [];

        foreach ($entities as $entity) {
            $entityParentId = $entity->get('parent_id')->target_id;

            if ($entityParentId == $parentId) {
                $node = $this->entityToArray($entity);
                $node['children'] = $this->buildTree($entities, (int) $entity->id());
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * Convierte entidad a array para API/frontend.
     */
    protected function entityToArray(object $entity): array
    {
        $page = $entity->getPage();

        // Sprint B2: Calcular SEO score por página.
        $seoScore = NULL;
        if ($page) {
            try {
                $seoAuditor = \Drupal::service('jaraba_site_builder.seo_auditor');
                $result = $seoAuditor->audit($page);
                $seoScore = $result['score'];
            } catch (\Exception $e) {
                // Si falla la auditoría, no bloquear el árbol.
            }
        }

        return [
            'id' => (int) $entity->id(),
            'page_id' => (int) $entity->get('page_id')->target_id,
            'page_title' => $page ? $page->label() : '',
            'page_url' => $page && $page->hasField('path_alias') ? $page->get('path_alias')->value : '',
            'nav_title' => $entity->getNavTitle(),
            'nav_icon' => $entity->get('nav_icon')->value,
            'depth' => (int) $entity->get('depth')->value,
            'weight' => (int) $entity->get('weight')->value,
            'show_in_nav' => (bool) $entity->get('show_in_navigation')->value,
            'show_in_sitemap' => (bool) $entity->get('show_in_sitemap')->value,
            'show_in_footer' => (bool) $entity->get('show_in_footer')->value,
            'status' => $entity->get('status')->value,
            'is_external' => !empty($entity->get('nav_external_url')->value),
            'external_url' => $entity->get('nav_external_url')->value,
            'seo_score' => $seoScore,
            'children' => [],
        ];
    }

    /**
     * Reordena el árbol completo desde el frontend.
     *
     * @param array $nodes
     *   Array jerárquico con estructura [{ id, children: [...] }].
     *
     * @throws \Exception
     */
    public function reorderTree(array $nodes): void
    {
        $transaction = $this->database->startTransaction();

        try {
            $this->processReorderNodes($nodes, NULL, 0, '');

            $this->logger->info('Árbol de páginas reordenado correctamente.');
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->logger->error('Error al reordenar árbol: @error', ['@error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Procesa recursivamente los nodos para reordenar.
     */
    protected function processReorderNodes(array $nodes, ?int $parentId, int $depth, string $parentPath): void
    {
        $weight = 0;

        foreach ($nodes as $node) {
            $nodeId = (int) ($node['id'] ?? 0);
            if (!$nodeId) {
                continue;
            }

            $storage = $this->entityTypeManager->getStorage('site_page_tree');
            $entity = $storage->load($nodeId);

            if (!$entity) {
                continue;
            }

            // Calcular nuevo path materializado.
            $newPath = $parentPath . $nodeId . '/';

            // Actualizar campos.
            $entity->set('parent_id', $parentId);
            $entity->set('weight', $weight);
            $entity->set('depth', $depth);
            $entity->set('path', $newPath);
            $entity->save();

            $weight++;

            // Procesar hijos.
            if (!empty($node['children'])) {
                $this->processReorderNodes($node['children'], $nodeId, $depth + 1, $newPath);
            }
        }
    }

    /**
     * Mueve una página a un nuevo padre.
     *
     * Crea auto-redirects 301 para la URL antigua y registra en SiteUrlHistory.
     *
     * @param int $pageTreeId
     *   ID del nodo a mover.
     * @param int|null $newParentId
     *   ID del nuevo padre, NULL para raíz.
     * @param int $position
     *   Posición entre hermanos (0 = primero).
     *
     * @throws \Exception
     */
    public function movePage(int $pageTreeId, ?int $newParentId, int $position = 0): void
    {
        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $entity = $storage->load($pageTreeId);

        if (!$entity) {
            throw new \Exception('Nodo no encontrado');
        }

        // Verificar que no se crea un ciclo (el nuevo padre no puede ser descendiente del nodo).
        if ($newParentId !== NULL && $this->isDescendant($newParentId, $pageTreeId)) {
            throw new \Exception('No se puede mover un nodo a uno de sus descendientes');
        }

        // Capturar URLs anteriores del subárbol para crear redirects.
        $oldUrls = $this->collectSubtreeUrls($entity);

        $tenantId = (int) $entity->get('tenant_id')->target_id;
        $transaction = $this->database->startTransaction();

        try {
            // Actualizar peso de hermanos existentes para hacer hueco.
            $this->shiftSiblingWeights($tenantId, $newParentId, $position);

            // Mover el nodo.
            $entity->set('parent_id', $newParentId);
            $entity->set('weight', $position);
            $entity->save();

            // Regenerar paths de todo el subárbol.
            $this->regenerateSubtreePaths($entity);

            // Regenerar path_alias jerárquico del nodo movido y sus descendientes.
            $this->generateHierarchicalPathAlias($entity, TRUE);

            // Crear auto-redirects para URLs que cambiaron.
            $this->createMoveRedirects($entity, $oldUrls, $tenantId);

            $this->logger->info('Página @id movida a padre @parent.', [
                '@id' => $pageTreeId,
                '@parent' => $newParentId ?? 'raíz',
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Recopila las URLs actuales de un nodo y sus descendientes.
     *
     * @return array
     *   Map de page_id => url_actual.
     */
    protected function collectSubtreeUrls(object $entity): array
    {
        $urls = [];
        $page = $entity->getPage();
        if ($page && $page->hasField('path_alias')) {
            $alias = $page->get('path_alias')->value;
            if ($alias) {
                $urls[(int) $entity->id()] = $alias;
            }
        }

        foreach ($entity->getChildren() as $child) {
            $urls += $this->collectSubtreeUrls($child);
        }

        return $urls;
    }

    /**
     * Crea auto-redirects 301 después de mover un nodo.
     */
    protected function createMoveRedirects(object $entity, array $oldUrls, int $tenantId): void
    {
        // Recargar la entidad para obtener las URLs actualizadas.
        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $freshEntity = $storage->load($entity->id());
        if (!$freshEntity) {
            return;
        }

        $newUrls = $this->collectSubtreeUrls($freshEntity);

        foreach ($oldUrls as $nodeId => $oldUrl) {
            $newUrl = $newUrls[$nodeId] ?? null;
            if ($newUrl && $oldUrl !== $newUrl) {
                $this->redirectService->createAutoRedirect($oldUrl, $newUrl, $tenantId);

                // Registrar en SiteUrlHistory si la entidad existe.
                $this->recordUrlChange($nodeId, $oldUrl, $newUrl, $tenantId, 'parent_change');
            }
        }
    }

    /**
     * Genera y actualiza el path_alias jerárquico de una página y sus descendientes.
     *
     * Construye la URL basada en la jerarquía del árbol:
     *   /padre-slug/hijo-slug/nieto-slug
     *
     * @param object $entity
     *   Entidad SitePageTree.
     * @param bool $recursive
     *   Si TRUE, regenera también los descendientes.
     */
    public function generateHierarchicalPathAlias(object $entity, bool $recursive = TRUE): void
    {
        $page = $entity->getPage();
        if (!$page) {
            return;
        }

        // Construir el path jerárquico desde los ancestros.
        $segments = $this->buildPathSegments($entity);
        $newAlias = '/' . implode('/', $segments);

        // Solo actualizar si cambió.
        $currentAlias = $page->get('path_alias')->value ?? '';
        if ($currentAlias !== $newAlias) {
            $page->set('path_alias', $newAlias);
            $page->save();

            $this->logger->info('Path alias generado: @alias para página @page.', [
                '@alias' => $newAlias,
                '@page' => $page->label(),
            ]);
        }

        // Regenerar hijos.
        if ($recursive) {
            foreach ($entity->getChildren() as $child) {
                $this->generateHierarchicalPathAlias($child, TRUE);
            }
        }
    }

    /**
     * Construye los segmentos de URL desde la raíz hasta este nodo.
     *
     * @return array
     *   Array de slugs: ['servicios', 'consultoria', 'precios'].
     */
    protected function buildPathSegments(object $entity): array
    {
        $segments = [];

        // Recorrer ancestros desde la raíz.
        $ancestors = $this->getAncestors($entity);
        foreach ($ancestors as $ancestor) {
            $segments[] = $this->slugify($ancestor->getNavTitle());
        }

        // Añadir el nodo actual.
        $segments[] = $this->slugify($entity->getNavTitle());

        return $segments;
    }

    /**
     * Obtiene los ancestros de un nodo (desde la raíz hasta el padre directo).
     *
     * @return array
     *   Lista de entidades SitePageTree ordenadas raíz → padre.
     */
    protected function getAncestors(object $entity): array
    {
        $ancestors = [];
        $current = $entity->getParent();

        while ($current !== NULL) {
            array_unshift($ancestors, $current);
            $current = $current->getParent();
        }

        return $ancestors;
    }

    /**
     * Convierte un título a un slug URL-safe.
     *
     * @param string $text
     *   Texto a convertir.
     *
     * @return string
     *   Slug en minúsculas sin caracteres especiales.
     */
    protected function slugify(string $text): string
    {
        // Transliterar caracteres especiales.
        $text = mb_strtolower($text);

        // Reemplazar acentos y caracteres especiales comunes.
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u', 'ç' => 'c',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o',
        ];
        $text = strtr($text, $replacements);

        // Reemplazar caracteres no alfanuméricos con guiones.
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Eliminar guiones iniciales, finales y duplicados.
        $text = trim($text, '-');
        $text = preg_replace('/-+/', '-', $text);

        return $text ?: 'pagina';
    }

    /**
     * Registra un cambio de URL en SiteUrlHistory.
     */
    protected function recordUrlChange(int $nodeId, string $oldPath, string $newPath, int $tenantId, string $changeType): void
    {
        try {
            $historyStorage = $this->entityTypeManager->getStorage('site_url_history');
            $treeNode = $this->entityTypeManager->getStorage('site_page_tree')->load($nodeId);
            $pageId = $treeNode ? (int) $treeNode->get('page_id')->target_id : 0;

            $historyStorage->create([
                'tenant_id' => $tenantId,
                'page_id' => $pageId,
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'change_type' => $changeType,
                'changed_by' => \Drupal::currentUser()->id(),
                'changed_at' => \Drupal::time()->getRequestTime(),
                'auto_redirect_created' => TRUE,
            ])->save();
        } catch (\Exception $e) {
            // No bloquear la operación si falla el registro de historial.
            $this->logger->warning('No se pudo registrar cambio de URL en SiteUrlHistory: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica si un nodo es descendiente de otro.
     */
    protected function isDescendant(int $potentialDescendantId, int $ancestorId): bool
    {
        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $entity = $storage->load($potentialDescendantId);

        if (!$entity) {
            return FALSE;
        }

        // Usar path materializado para verificación rápida.
        $ancestorPath = '/' . $ancestorId . '/';
        $descendantPath = $entity->get('path')->value ?? '';

        return str_contains($descendantPath, $ancestorPath);
    }

    /**
     * Desplaza los pesos de hermanos para hacer hueco.
     */
    protected function shiftSiblingWeights(int $tenantId, ?int $parentId, int $fromPosition): void
    {
        $query = $this->database->update('site_page_tree')
            ->expression('weight', 'weight + 1')
            ->condition('tenant_id', $tenantId)
            ->condition('weight', $fromPosition, '>=');

        if ($parentId === NULL) {
            $query->isNull('parent_id');
        } else {
            $query->condition('parent_id', $parentId);
        }

        $query->execute();
    }

    /**
     * Regenera los paths materializados de un subárbol.
     */
    protected function regenerateSubtreePaths(object $entity): void
    {
        // Calcular nuevo path.
        $parent = $entity->getParent();
        $parentPath = $parent ? ($parent->get('path')->value ?? '/') : '/';
        $newPath = $parentPath . $entity->id() . '/';
        $newDepth = $parent ? ($parent->get('depth')->value + 1) : 0;

        $entity->set('path', $newPath);
        $entity->set('depth', $newDepth);
        $entity->save();

        // Regenerar hijos.
        foreach ($entity->getChildren() as $child) {
            $this->regenerateSubtreePaths($child);
        }
    }

    /**
     * Añade una página al árbol.
     *
     * @param int $pageContentId
     *   ID de la entidad page_content.
     * @param int|null $parentId
     *   ID del padre en el árbol, NULL para raíz.
     * @param array $options
     *   Opciones adicionales (nav_title, show_in_navigation, etc.).
     *
     * @return int
     *   ID del nodo creado.
     */
    public function addPage(int $pageContentId, ?int $parentId = NULL, array $options = []): int
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        if (!$tenantId) {
            throw new \Exception('No hay tenant activo');
        }

        $storage = $this->entityTypeManager->getStorage('site_page_tree');

        // Calcular peso (último entre hermanos).
        $weight = $this->getNextWeight($tenantId, $parentId);

        // Calcular profundidad y path.
        $depth = 0;
        $path = '/';
        if ($parentId) {
            $parent = $storage->load($parentId);
            if ($parent) {
                $depth = (int) $parent->get('depth')->value + 1;
                $path = $parent->get('path')->value ?? '/';
            }
        }

        $entity = $storage->create([
            'tenant_id' => $tenantId,
            'page_id' => $pageContentId,
            'parent_id' => $parentId,
            'weight' => $weight,
            'depth' => $depth,
            'path' => $path,
            'show_in_navigation' => $options['show_in_navigation'] ?? TRUE,
            'show_in_sitemap' => $options['show_in_sitemap'] ?? TRUE,
            'show_in_footer' => $options['show_in_footer'] ?? FALSE,
            'show_in_breadcrumbs' => $options['show_in_breadcrumbs'] ?? TRUE,
            'nav_title' => $options['nav_title'] ?? '',
            'nav_icon' => $options['nav_icon'] ?? '',
            'status' => $options['status'] ?? 'published',
        ]);

        $entity->save();

        // Actualizar path con el ID recién generado.
        $entity->set('path', $path . $entity->id() . '/');
        $entity->save();

        // Generar path_alias jerárquico automáticamente.
        $this->generateHierarchicalPathAlias($entity, FALSE);

        $this->logger->info('Página @page añadida al árbol.', [
            '@page' => $pageContentId,
        ]);

        return (int) $entity->id();
    }

    /**
     * Obtiene el siguiente peso para un padre dado.
     */
    protected function getNextWeight(int $tenantId, ?int $parentId): int
    {
        $query = $this->database->select('site_page_tree', 't')
            ->condition('t.tenant_id', $tenantId);

        if ($parentId === NULL) {
            $query->isNull('t.parent_id');
        } else {
            $query->condition('t.parent_id', $parentId);
        }

        $query->addExpression('MAX(weight)', 'max_weight');
        $result = $query->execute()->fetchField();

        return ($result !== FALSE) ? ((int) $result + 1) : 0;
    }

    /**
     * Elimina una página del árbol (y sus hijos).
     *
     * @param int $pageTreeId
     *   ID del nodo a eliminar.
     * @param bool $deleteChildren
     *   Si TRUE, elimina hijos. Si FALSE, los mueve al padre.
     */
    public function removePage(int $pageTreeId, bool $deleteChildren = FALSE): void
    {
        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $entity = $storage->load($pageTreeId);

        if (!$entity) {
            return;
        }

        $children = $entity->getChildren();

        if (!$deleteChildren && !empty($children)) {
            // Mover hijos al padre del nodo eliminado.
            $newParentId = $entity->get('parent_id')->target_id;
            foreach ($children as $child) {
                $child->set('parent_id', $newParentId);
                $child->save();
                $this->regenerateSubtreePaths($child);
            }
        } else {
            // Eliminar hijos recursivamente.
            foreach ($children as $child) {
                $this->removePage((int) $child->id(), TRUE);
            }
        }

        $entity->delete();

        $this->logger->info('Página @id eliminada del árbol.', ['@id' => $pageTreeId]);
    }

    /**
     * Actualiza el estado de múltiples nodos en bloque.
     *
     * @param array $nodeIds
     *   IDs de nodos SitePageTree.
     * @param string $newStatus
     *   Nuevo estado: 'published', 'draft', 'archived'.
     *
     * @return int
     *   Número de nodos actualizados.
     */
    public function bulkUpdateStatus(array $nodeIds, string $newStatus): int
    {
        $allowedStatuses = ['published', 'draft', 'archived'];
        if (!in_array($newStatus, $allowedStatuses)) {
            throw new \InvalidArgumentException('Estado no válido: ' . $newStatus);
        }

        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $entities = $storage->loadMultiple($nodeIds);
        $updated = 0;

        foreach ($entities as $entity) {
            $entity->set('status', $newStatus);
            if ($newStatus === 'published' && !$entity->get('published_at')->value) {
                $entity->set('published_at', \Drupal::time()->getRequestTime());
            }
            $entity->save();
            $updated++;
        }

        $this->logger->info('Bulk update: @count nodos cambiados a @status.', [
            '@count' => $updated,
            '@status' => $newStatus,
        ]);

        return $updated;
    }

    /**
     * Obtiene el menú de navegación (solo páginas visibles).
     *
     * @param int|null $tenantId
     *   ID del tenant.
     * @param string $location
     *   Ubicación: 'header', 'footer', 'sitemap'.
     *
     * @return array
     *   Árbol de navegación.
     */
    public function getNavigation(?int $tenantId = NULL, string $location = 'header'): array
    {
        $tree = $this->getTree($tenantId);
        return $this->filterNavigation($tree, $location);
    }

    /**
     * Filtra el árbol según visibilidad.
     */
    protected function filterNavigation(array $nodes, string $location): array
    {
        $filtered = [];

        foreach ($nodes as $node) {
            $include = FALSE;

            switch ($location) {
                case 'header':
                    $include = $node['show_in_nav'] && $node['status'] === 'published';
                    break;

                case 'footer':
                    $include = $node['show_in_footer'] && $node['status'] === 'published';
                    break;

                case 'sitemap':
                    $include = $node['show_in_sitemap'] && $node['status'] === 'published';
                    break;
            }

            if ($include) {
                $node['children'] = $this->filterNavigation($node['children'], $location);
                $filtered[] = $node;
            }
        }

        return $filtered;
    }

}
