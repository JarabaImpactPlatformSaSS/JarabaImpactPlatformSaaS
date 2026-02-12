<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_site_builder\Service\SiteStructureService;
use Drupal\jaraba_site_builder\Service\RedirectService;
use Drupal\jaraba_site_builder\Service\SitemapGeneratorService;
use Drupal\jaraba_site_builder\Service\SiteAnalyticsService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para las páginas de administración del Site Builder.
 *
 * Renderiza las páginas con tabs: Árbol, Configuración, Redirects, Sitemap.
 */
class SiteTreePageController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SiteStructureService $structureService,
        protected RedirectService $redirectService,
        protected SitemapGeneratorService $sitemapService,
        protected SiteAnalyticsService $analyticsService,
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_site_builder.structure'),
            $container->get('jaraba_site_builder.redirect'),
            $container->get('jaraba_site_builder.sitemap'),
            $container->get('jaraba_site_builder.analytics'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Página principal del Site Builder (dashboard).
     *
     * Sprint B1: Muestra dashboard premium con KPIs glassmorphism.
     */
    public function dashboard(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $tree = $this->structureService->getTree($tenantId);
        $kpis = $this->analyticsService->getStats($tenantId);

        $build = [
            '#theme' => 'site_tree_manager',
            '#tree' => $tree,
            '#config' => [
                'api_base' => '/api/v1/site',
                'can_edit' => $this->currentUser()->hasPermission('administer site structure'),
            ],
            '#can_edit' => $this->currentUser()->hasPermission('administer site structure'),
            '#attached' => [
                'library' => ['jaraba_site_builder/site-tree-manager'],
                'drupalSettings' => [
                    'jaraba_site_builder' => [
                        'tree' => $tree,
                        'kpis' => $kpis,
                        'apiBase' => '/api/v1/site',
                        'canEdit' => $this->currentUser()->hasPermission('administer site structure'),
                        'labels' => [
                            'addPage' => $this->t('Añadir página'),
                            'editPage' => $this->t('Editar'),
                            'removePage' => $this->t('Quitar del árbol'),
                            'movePage' => $this->t('Mover'),
                            'confirmRemove' => $this->t('¿Eliminar esta página del árbol?'),
                            'draft' => $this->t('Borrador'),
                            'archived' => $this->t('Archivado'),
                            'hidden' => $this->t('Oculto en navegación'),
                            'external' => $this->t('Enlace externo'),
                        ],
                    ],
                ],
            ],
        ];

        return $build;
    }

    /**
     * Página del árbol de páginas.
     */
    public function tree(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $tree = $this->structureService->getTree($tenantId);

        $build = [
            '#theme' => 'site_tree_manager',
            '#tree' => $tree,
            '#config' => [
                'api_base' => '/api/v1/site',
                'can_edit' => $this->currentUser()->hasPermission('administer site structure'),
            ],
            '#can_edit' => $this->currentUser()->hasPermission('administer site structure'),
            '#attached' => [
                'library' => ['jaraba_site_builder/site-tree-manager'],
                'drupalSettings' => [
                    'jaraba_site_builder' => [
                        'tree' => $tree,
                        'apiBase' => '/api/v1/site',
                        'canEdit' => $this->currentUser()->hasPermission('administer site structure'),
                        'labels' => [
                            'addPage' => $this->t('Añadir página'),
                            'editPage' => $this->t('Editar'),
                            'removePage' => $this->t('Quitar del árbol'),
                            'movePage' => $this->t('Mover'),
                            'confirmRemove' => $this->t('¿Eliminar esta página del árbol?'),
                            'draft' => $this->t('Borrador'),
                            'archived' => $this->t('Archivado'),
                            'hidden' => $this->t('Oculto en navegación'),
                            'external' => $this->t('Enlace externo'),
                        ],
                    ],
                ],
            ],
        ];

        // Botón para añadir página.
        if ($this->currentUser()->hasPermission('administer site structure')) {
            $build['actions'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['site-builder-actions']],
                'add_page' => [
                    '#type' => 'link',
                    '#title' => $this->t('Añadir página al árbol'),
                    '#url' => Url::fromRoute('jaraba_site_builder.api.pages_add'),
                    '#attributes' => [
                        'class' => ['button', 'button--primary', 'js-add-page-trigger'],
                        'data-action' => 'add-page',
                    ],
                ],
            ];
        }

        return $build;
    }

    /**
     * Página de configuración del sitio.
     *
     * Carga o crea la entidad SiteConfig del tenant actual
     * y devuelve el formulario de edición.
     */
    public function siteConfig(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;

        if (!$tenantId) {
            return [
                '#markup' => $this->t('No hay un tenant seleccionado.'),
            ];
        }

        // Buscar configuración existente para este tenant.
        $storage = $this->entityTypeManager()->getStorage('site_config');
        $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
        $entity = reset($entities);

        // Si no existe, crear una nueva.
        if (!$entity) {
            $entity = $storage->create([
                'tenant_id' => $tenantId,
            ]);
        }

        // Construir el formulario de edición.
        $form = $this->entityFormBuilder()->getForm($entity, 'default');

        return $form;
    }

    /**
     * Página de redirects.
     */
    public function redirects(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $stats = $this->redirectService->getStats($tenantId);
        $topRedirects = $this->redirectService->getTopRedirects($tenantId, 20);

        // Cargar todas las entidades redirect del tenant.
        $storage = $this->entityTypeManager()->getStorage('site_redirect');
        $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);

        $rows = [];
        foreach ($entities as $redirect) {
            $rows[] = [
                'source' => $redirect->get('source_path')->value,
                'destination' => $redirect->get('destination_path')->value,
                'type' => $redirect->get('redirect_type')->value,
                'hits' => $redirect->get('hit_count')->value ?? 0,
                'status' => $redirect->isActive() ? $this->t('Activo') : $this->t('Inactivo'),
                'auto' => $redirect->get('is_auto_generated')->value ? $this->t('Sí') : $this->t('No'),
                'operations' => [
                    'data' => [
                        '#type' => 'operations',
                        '#links' => [
                            'edit' => [
                                'title' => $this->t('Editar'),
                                'url' => Url::fromRoute('jaraba_site_builder.redirect_edit', [
                                    'site_redirect' => $redirect->id(),
                                ]),
                            ],
                            'delete' => [
                                'title' => $this->t('Eliminar'),
                                'url' => Url::fromRoute('entity.site_redirect.delete_form', [
                                    'site_redirect' => $redirect->id(),
                                ]),
                            ],
                        ],
                    ],
                ],
            ];
        }

        $build = [
            '#attached' => [
                'library' => ['jaraba_site_builder/site-config'],
            ],
        ];

        // Estadísticas.
        $build['stats'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['redirect-stats']],
            'content' => [
                '#theme' => 'item_list',
                '#items' => [
                    $this->t('Total: @count', ['@count' => $stats['total']]),
                    $this->t('Activos: @count', ['@count' => $stats['active']]),
                    $this->t('Auto-generados: @count', ['@count' => $stats['auto_generated']]),
                    $this->t('Hits totales: @count', ['@count' => $stats['total_hits']]),
                ],
                '#attributes' => ['class' => ['redirect-stats-list']],
            ],
        ];

        // Botón añadir.
        $build['actions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['site-builder-actions']],
            'add' => [
                '#type' => 'link',
                '#title' => $this->t('Añadir redirect'),
                '#url' => Url::fromRoute('jaraba_site_builder.redirect_add'),
                '#attributes' => ['class' => ['button', 'button--primary']],
            ],
        ];

        // Tabla de redirects.
        $build['table'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('Origen'),
                $this->t('Destino'),
                $this->t('Tipo'),
                $this->t('Hits'),
                $this->t('Estado'),
                $this->t('Auto'),
                $this->t('Operaciones'),
            ],
            '#rows' => $rows,
            '#empty' => $this->t('No hay redirects configurados.'),
            '#attributes' => ['class' => ['redirects-table']],
        ];

        return $build;
    }

    /**
     * Página de visualización del sitemap.
     */
    public function sitemap(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $data = $this->sitemapService->getVisualData($tenantId);

        $build = [
            '#attached' => [
                'library' => ['jaraba_site_builder/site-config'],
            ],
        ];

        // Información general.
        $build['info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['sitemap-info']],
            'content' => [
                '#markup' => $this->t('<p>Total de páginas en sitemap: <strong>@count</strong></p><p>Última generación: @date</p>', [
                    '@count' => $data['total_pages'],
                    '@date' => $data['last_generated'],
                ]),
            ],
        ];

        // Link al XML.
        $build['xml_link'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['sitemap-actions']],
            'link' => [
                '#type' => 'link',
                '#title' => $this->t('Ver sitemap.xml'),
                '#url' => Url::fromRoute('jaraba_site_builder.sitemap_xml'),
                '#attributes' => [
                    'class' => ['button'],
                    'target' => '_blank',
                ],
            ],
        ];

        // Vista previa del árbol.
        $build['preview'] = [
            '#type' => 'details',
            '#title' => $this->t('Vista previa del sitemap'),
            '#open' => TRUE,
            'tree' => [
                '#theme' => 'item_list',
                '#items' => $this->buildSitemapPreview($data['pages']),
                '#attributes' => ['class' => ['sitemap-preview']],
            ],
        ];

        return $build;
    }

    /**
     * Construye la vista previa del sitemap como lista anidada.
     */
    protected function buildSitemapPreview(array $pages): array
    {
        $items = [];

        foreach ($pages as $page) {
            $item = [
                '#markup' => '<span class="sitemap-page-title">' . $page['nav_title'] . '</span> <small>' . $page['page_url'] . '</small>',
            ];

            if (!empty($page['children'])) {
                $item['children'] = $this->buildSitemapPreview($page['children']);
            }

            $items[] = $item;
        }

        return $items;
    }

    // =========================================================================
    // FRONTEND ROUTES - Sin tema de administración
    // El tenant accede a /site-builder/* con layout limpio
    // =========================================================================

    /**
     * Dashboard frontend del Site Builder.
     *
     * Muestra el árbol de páginas con navegación por tabs sin tema de admin.
     */
    public function dashboardFrontend(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $tree = $this->structureService->getTree($tenantId);
        $kpis = $this->analyticsService->getStats($tenantId);

        return [
            '#theme' => 'site_builder_dashboard_frontend',
            '#tree' => $tree,
            '#tabs' => $this->getFrontendTabs('dashboard'),
            '#kpis' => $kpis,
            '#can_edit' => $this->currentUser()->hasPermission('administer site structure'),
            '#attached' => [
                'library' => [
                    'jaraba_site_builder/site-tree-manager',
                    'jaraba_site_builder/site-builder-dashboard',
                ],
                'drupalSettings' => [
                    'jaraba_site_builder' => [
                        'tree' => $tree,
                        'kpis' => $kpis,
                        'apiBase' => '/api/v1/site',
                        'canEdit' => $this->currentUser()->hasPermission('administer site structure'),
                        'labels' => [
                            'addPage' => $this->t('Añadir página'),
                            'editPage' => $this->t('Editar'),
                            'removePage' => $this->t('Quitar del árbol'),
                            'movePage' => $this->t('Mover'),
                            'confirmRemove' => $this->t('¿Eliminar esta página del árbol?'),
                            'draft' => $this->t('Borrador'),
                            'archived' => $this->t('Archivado'),
                            'hidden' => $this->t('Oculto en navegación'),
                            'external' => $this->t('Enlace externo'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Árbol de páginas frontend.
     */
    public function treeFrontend(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $tree = $this->structureService->getTree($tenantId);

        return [
            '#theme' => 'site_builder_tree_frontend',
            '#tree' => $tree,
            '#tabs' => $this->getFrontendTabs('tree'),
            '#can_edit' => $this->currentUser()->hasPermission('administer site structure'),
            '#attached' => [
                'library' => ['jaraba_site_builder/site-tree-manager'],
                'drupalSettings' => [
                    'jaraba_site_builder' => [
                        'tree' => $tree,
                        'apiBase' => '/api/v1/site',
                        'canEdit' => $this->currentUser()->hasPermission('administer site structure'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Configuración del sitio frontend.
     */
    public function configFrontend(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;

        if (!$tenantId) {
            return [
                '#theme' => 'site_builder_error_frontend',
                '#message' => $this->t('No hay un tenant seleccionado.'),
                '#tabs' => $this->getFrontendTabs('config'),
            ];
        }

        // Buscar configuración existente para este tenant.
        $storage = $this->entityTypeManager()->getStorage('site_config');
        $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
        $entity = reset($entities);

        // Si no existe, crear una nueva.
        if (!$entity) {
            $entity = $storage->create([
                'tenant_id' => $tenantId,
            ]);
        }

        // Construir el formulario de edición.
        $form = $this->entityFormBuilder()->getForm($entity, 'default');

        return [
            '#theme' => 'site_builder_config_frontend',
            '#form' => $form,
            '#tabs' => $this->getFrontendTabs('config'),
            '#attached' => [
                'library' => ['jaraba_site_builder/site-config'],
            ],
        ];
    }

    /**
     * Página de redirects frontend.
     */
    public function redirectsFrontend(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $stats = $this->redirectService->getStats($tenantId);

        // Cargar todas las entidades redirect del tenant.
        $storage = $this->entityTypeManager()->getStorage('site_redirect');
        $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);

        $redirects = [];
        foreach ($entities as $redirect) {
            $redirects[] = [
                'id' => $redirect->id(),
                'source' => $redirect->get('source_path')->value,
                'destination' => $redirect->get('destination_path')->value,
                'type' => $redirect->get('redirect_type')->value,
                'hits' => $redirect->get('hit_count')->value ?? 0,
                'active' => $redirect->isActive(),
                'auto' => (bool) $redirect->get('is_auto_generated')->value,
            ];
        }

        return [
            '#theme' => 'site_builder_redirects_frontend',
            '#redirects' => $redirects,
            '#stats' => $stats,
            '#tabs' => $this->getFrontendTabs('redirects'),
            '#attached' => [
                'library' => ['jaraba_site_builder/site-config'],
            ],
        ];
    }

    /**
     * Página de sitemap frontend.
     */
    public function sitemapFrontend(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (int) $tenant->id() : null;
        $data = $this->sitemapService->getVisualData($tenantId);

        return [
            '#theme' => 'site_builder_sitemap_frontend',
            '#data' => $data,
            '#pages' => $data['pages'] ?? [],
            '#tabs' => $this->getFrontendTabs('sitemap'),
            '#attached' => [
                'library' => ['jaraba_site_builder/site-config'],
            ],
        ];
    }

    /**
     * Obtiene los tabs para la navegación frontend.
     *
     * @param string $activeTab
     *   El tab activo actual.
     *
     * @return array
     *   Array de tabs con url, title y active.
     */
    protected function getFrontendTabs(string $activeTab): array
    {
        return [
            [
                'id' => 'dashboard',
                'title' => $this->t('Dashboard'),
                'url' => Url::fromRoute('jaraba_site_builder.frontend.dashboard')->toString(),
                'active' => $activeTab === 'dashboard',
            ],
            [
                'id' => 'tree',
                'title' => $this->t('Árbol'),
                'url' => Url::fromRoute('jaraba_site_builder.frontend.tree')->toString(),
                'active' => $activeTab === 'tree',
            ],
            [
                'id' => 'config',
                'title' => $this->t('Configuración'),
                'url' => Url::fromRoute('jaraba_site_builder.frontend.config')->toString(),
                'active' => $activeTab === 'config',
            ],
            [
                'id' => 'redirects',
                'title' => $this->t('Redirects'),
                'url' => Url::fromRoute('jaraba_site_builder.frontend.redirects')->toString(),
                'active' => $activeTab === 'redirects',
            ],
            [
                'id' => 'sitemap',
                'title' => $this->t('Sitemap'),
                'url' => Url::fromRoute('jaraba_site_builder.frontend.sitemap')->toString(),
                'active' => $activeTab === 'sitemap',
            ],
        ];
    }

}
