<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_site_builder\Service\SiteStructureService;
use Drupal\jaraba_site_builder\Service\SiteAnalyticsService;
use Drupal\jaraba_site_builder\Service\SeoAuditorService;
use Drupal\jaraba_site_builder\Service\RedirectService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Controller para operaciones del árbol de páginas.
 */
class SiteStructureApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SiteStructureService $structureService,
        protected SiteAnalyticsService $analyticsService,
        protected TenantContextService $tenantContext,
        protected SeoAuditorService $seoAuditor,
        protected RedirectService $redirectService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_site_builder.structure'),
            $container->get('jaraba_site_builder.analytics'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->get('jaraba_site_builder.seo_auditor'),
            $container->get('jaraba_site_builder.redirect'),
        );
    }

    /**
     * GET /api/v1/site/stats - KPIs del dashboard.
     *
     * Sprint B1: Dashboard Premium.
     */
    public function getStats(): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;
            $stats = $this->analyticsService->getStats($tenantId);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/site/tree - Obtiene el árbol completo.
     */
    public function getTree(): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;
            $tree = $this->structureService->getTree($tenantId);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $tree,
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/site/tree/reorder - Reordena el árbol (drag & drop).
     */
    public function reorderTree(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['nodes'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Se requiere el campo "nodes"'),
                ], 400);
            }

            $this->structureService->reorderTree($data['nodes']);

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Árbol reordenado correctamente'),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/site/pages - Añade una página al árbol.
     */
    public function addPage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['page_id'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Se requiere el campo "page_id"'),
                ], 400);
            }

            $options = [];
            if (isset($data['nav_title'])) {
                $options['nav_title'] = $data['nav_title'];
            }
            if (isset($data['show_in_navigation'])) {
                $options['show_in_navigation'] = (bool) $data['show_in_navigation'];
            }
            if (isset($data['show_in_sitemap'])) {
                $options['show_in_sitemap'] = (bool) $data['show_in_sitemap'];
            }
            if (isset($data['nav_icon'])) {
                $options['nav_icon'] = $data['nav_icon'];
            }

            $nodeId = $this->structureService->addPage(
                (int) $data['page_id'],
                isset($data['parent_id']) ? (int) $data['parent_id'] : NULL,
                $options
            );

            return new JsonResponse([
                'success' => TRUE,
                'data' => ['id' => $nodeId],
                'message' => $this->t('Página añadida al árbol'),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/site/pages/available - Obtiene páginas disponibles del Page Builder.
     *
     * Retorna páginas que aún no están en el árbol del site builder.
     */
    public function getAvailablePages(): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;

            if (!$tenantId) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('No hay tenant seleccionado'),
                ], 400);
            }

            // Obtener IDs de páginas ya en el árbol.
            $treeStorage = $this->entityTypeManager()->getStorage('site_page_tree');
            $existingQuery = $treeStorage->getQuery()
                ->condition('tenant_id', $tenantId)
                ->accessCheck(FALSE);
            $existingNodes = $treeStorage->loadMultiple($existingQuery->execute());

            $existingPageIds = [];
            foreach ($existingNodes as $node) {
                $pageId = $node->get('page_id')->target_id ?? null;
                if ($pageId) {
                    $existingPageIds[] = (int) $pageId;
                }
            }

            // Obtener páginas del Page Builder del tenant.
            $pageStorage = $this->entityTypeManager()->getStorage('page_content');
            $pageQuery = $pageStorage->getQuery()
                ->condition('tenant_id', $tenantId)
                ->sort('title')
                ->accessCheck(FALSE);

            // Excluir las que ya están en el árbol.
            if (!empty($existingPageIds)) {
                $pageQuery->condition('id', $existingPageIds, 'NOT IN');
            }

            $pageIds = $pageQuery->execute();
            $pages = $pageStorage->loadMultiple($pageIds);

            $data = [];
            foreach ($pages as $page) {
                $data[] = [
                    'id' => (int) $page->id(),
                    'title' => $page->label() ?? $this->t('Sin título'),
                    'type' => $page->bundle() ?? 'page',
                    'status' => $page->isPublished() ? 'published' : 'draft',
                    'url' => $page->toUrl()->toString(),
                ];
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/site/pages/{id} - Actualiza configuración de un nodo.
     */
    public function updatePage(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            $storage = $this->entityTypeManager()->getStorage('site_page_tree');
            $entity = $storage->load($id);

            if (!$entity) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Nodo no encontrado'),
                ], 404);
            }

            // Actualizar solo campos permitidos.
            $allowedFields = [
                'nav_title',
                'nav_icon',
                'nav_highlight',
                'nav_external_url',
                'show_in_navigation',
                'show_in_sitemap',
                'show_in_footer',
                'show_in_breadcrumbs',
                'status',
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $entity->set($field, $data[$field]);
                }
            }

            $entity->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Página actualizada'),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/site/pages/{id} - Elimina un nodo del árbol.
     */
    public function deletePage(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);
            $deleteChildren = !empty($data['delete_children']);

            $this->structureService->removePage($id, $deleteChildren);

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Página eliminada del árbol'),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/site/pages/{id}/move - Mueve un nodo a otro padre.
     */
    public function movePage(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            $newParentId = isset($data['parent_id']) ? (int) $data['parent_id'] : NULL;
            $position = (int) ($data['position'] ?? 0);

            $this->structureService->movePage($id, $newParentId, $position);

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Página movida'),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * GET /admin/structure/site-builder/tree/{id}/edit-ajax - Formulario de edición AJAX.
     *
     * Devuelve solo el HTML del formulario para cargarlo en un slide-panel.
     */
    public function editNodeAjax(int $id): Response
    {
        $storage = $this->entityTypeManager()->getStorage('site_page_tree');
        $entity = $storage->load($id);

        if (!$entity) {
            return new Response(
                '<div class="error-message">' . $this->t('Nodo no encontrado.') . '</div>',
                404
            );
        }

        $form = $this->entityFormBuilder()->getForm($entity, 'edit');
        $rendered = \Drupal::service('renderer')->renderRoot($form);

        return new Response($rendered);
    }

    /**
     * POST /api/v1/site/pages/bulk-status - Actualiza estado de múltiples nodos.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['node_ids']) || !is_array($data['node_ids'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Se requiere el campo "node_ids" como array.'),
                ], 400);
            }

            if (empty($data['status'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Se requiere el campo "status".'),
                ], 400);
            }

            $nodeIds = array_map('intval', $data['node_ids']);
            $updated = $this->structureService->bulkUpdateStatus($nodeIds, $data['status']);

            return new JsonResponse([
                'success' => TRUE,
                'data' => ['updated' => $updated],
                'message' => $this->t('@count páginas actualizadas.', ['@count' => $updated]),
            ]);
        } catch (\InvalidArgumentException $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al actualizar páginas.'),
            ], 500);
        }
    }

    /**
     * GET /api/v1/site/pages/{id}/seo-audit - Auditoría SEO de una página.
     *
     * Sprint B2: SEO Assistant Integrado.
     *
     * @param int $id
     *   ID del nodo del árbol (site_page_tree).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con score, issues y checks detallados.
     */
    public function seoAudit(int $id): JsonResponse
    {
        try {
            $storage = $this->entityTypeManager()->getStorage('site_page_tree');
            $treeNode = $storage->load($id);

            if (!$treeNode) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Nodo no encontrado.'),
                ], 404);
            }

            $page = $treeNode->getPage();
            if (!$page) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Página asociada no encontrada.'),
                ], 404);
            }

            $result = $this->seoAuditor->audit($page);

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'page_id' => (int) $page->id(),
                    'page_title' => $page->label(),
                    'score' => $result['score'],
                    'issues' => $result['issues'],
                    'checks' => $result['checks'],
                ],
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_site_builder')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/site/redirects/bulk-import - Importa redirects desde CSV.
     *
     * Espera un body JSON con 'rows': array de objetos
     * {source, destination, type, reason}.
     */
    public function bulkImportRedirects(Request $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;

            if (!$tenantId) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('No hay tenant seleccionado.'),
                ], 400);
            }

            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['rows']) || !is_array($data['rows'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Se requiere el campo "rows" como array.'),
                ], 400);
            }

            $result = $this->redirectService->bulkImport($data['rows'], $tenantId);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $result,
                'message' => $this->t('@imported redirects importados.', [
                    '@imported' => $result['imported'],
                ]),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al importar redirects.'),
            ], 500);
        }
    }

    /**
     * GET /api/v1/site/redirects/export - Exporta redirects como CSV.
     */
    public function exportRedirects(): Response
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;

            if (!$tenantId) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('No hay tenant seleccionado.'),
                ], 400);
            }

            $rows = $this->redirectService->exportAll($tenantId);

            // Generar CSV.
            $csv = "source,destination,type,reason,hits,active,auto_generated,created\n";
            foreach ($rows as $row) {
                $csv .= sprintf(
                    '"%s","%s",%s,"%s",%d,%s,%s,"%s"' . "\n",
                    str_replace('"', '""', $row['source']),
                    str_replace('"', '""', $row['destination']),
                    $row['type'],
                    str_replace('"', '""', $row['reason']),
                    $row['hits'],
                    $row['active'],
                    $row['auto_generated'],
                    $row['created']
                );
            }

            return new Response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="redirects-export.csv"',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al exportar redirects.'),
            ], 500);
        }
    }

}
