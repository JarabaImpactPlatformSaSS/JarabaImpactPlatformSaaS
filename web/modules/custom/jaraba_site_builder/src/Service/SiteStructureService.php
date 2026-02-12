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

        $transaction = $this->database->startTransaction();

        try {
            // Actualizar peso de hermanos existentes para hacer hueco.
            $this->shiftSiblingWeights($entity->get('tenant_id')->target_id, $newParentId, $position);

            // Mover el nodo.
            $entity->set('parent_id', $newParentId);
            $entity->set('weight', $position);
            $entity->save();

            // Regenerar paths de todo el subárbol.
            $this->regenerateSubtreePaths($entity);

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
