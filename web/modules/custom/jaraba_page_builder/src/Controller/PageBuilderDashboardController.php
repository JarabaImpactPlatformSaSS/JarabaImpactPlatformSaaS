<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para el dashboard principal del Page Builder.
 *
 * Este dashboard actúa como hub central que conecta todas las funcionalidades
 * del Page Builder:
 * - Plantillas: Elegir y crear páginas desde templates
 * - Mis Páginas: Ver y gestionar páginas creadas
 * - Experimentos A/B: Gestionar tests de conversión
 * - Estructura: Gestionar árbol de navegación (Site Builder)
 *
 * SETUP-WIZARD-DAILY-001: Integra Setup Wizard y Daily Actions (L1-L2).
 * PIPELINE-E2E-001: L1=Service injection, L2=Render array binding.
 */
class PageBuilderDashboardController extends ControllerBase
{

    /**
     * Setup Wizard registry (optional cross-module dependency).
     *
     * @var mixed|null
     */
    protected $wizardRegistry;

    /**
     * Daily Actions registry (optional cross-module dependency).
     *
     * @var mixed|null
     */
    protected $dailyActionsRegistry;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static {
        $instance = parent::create($container);
        // OPTIONAL-CROSSMODULE-001: Graceful degradation si modulos no instalados.
        try {
            $instance->wizardRegistry = $container->get('ecosistema_jaraba_core.setup_wizard_registry');
        }
        catch (\Throwable) {
            // Module not installed.
        }
        try {
            $instance->dailyActionsRegistry = $container->get('ecosistema_jaraba_core.daily_actions_registry');
        }
        catch (\Throwable) {
            // Module not installed.
        }
        return $instance;
    }

    /**
     * Resuelve el contextId para wizard/daily actions (tenant-scoped).
     */
    protected function resolveContextId(): int {
        try {
            /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext */
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $tenantId = $tenantContext->getCurrentTenantId();
            if ($tenantId !== NULL && $tenantId > 0) {
                return $tenantId;
            }
        }
        catch (\Throwable) {
            // Service not available.
        }
        return $this->currentUser()->id();
    }

    /**
     * Renderiza el dashboard principal del Page Builder.
     *
     * @return array
     *   Render array con el dashboard.
     */
    public function dashboard(): array
    {
        // Estadísticas básicas.
        $stats = $this->getStats();

        // SETUP-WIZARD-DAILY-001: L2 — Resolver wizard y daily actions.
        $contextId = $this->resolveContextId();
        $setupWizard = NULL;
        $dailyActions = [];

        if ($this->wizardRegistry !== NULL) {
            try {
                $hasWizard = method_exists($this->wizardRegistry, 'hasWizard')
                    ? $this->wizardRegistry->hasWizard('page_builder')
                    : TRUE;
                if ($hasWizard) {
                    $setupWizard = $this->wizardRegistry->getStepsForWizard('page_builder', $contextId);
                }
            }
            catch (\Throwable) {
                // Graceful degradation.
            }
        }

        if ($this->dailyActionsRegistry !== NULL) {
            try {
                $dailyActions = $this->dailyActionsRegistry->getActionsForDashboard('page_builder', $contextId);
            }
            catch (\Throwable) {
                $dailyActions = [];
            }
        }

        // Acciones rápidas del dashboard.
        $quick_actions = [
            [
                'title' => $this->t('Plantillas'),
                'description' => $this->t('Elige una plantilla para crear una nueva página'),
                'icon' => 'layout-grid',
                'url' => Url::fromRoute('jaraba_page_builder.template_picker')->toString(),
                'color' => 'impulse',
            ],
            [
                'title' => $this->t('Mis Páginas'),
                'description' => $this->t('Ver y gestionar las páginas de tu organización'),
                'icon' => 'file-text',
                'url' => Url::fromRoute('jaraba_page_builder.my_pages')->toString(),
                'color' => 'innovation',
            ],
            [
                'title' => $this->t('Experimentos A/B'),
                'description' => $this->t('Optimiza páginas con tests de conversión'),
                'icon' => 'experiment',
                'url' => Url::fromRoute('jaraba_page_builder.experiments_dashboard')->toString(),
                'color' => 'corporate',
            ],
            [
                'title' => $this->t('Analytics'),
                'description' => $this->t('Métricas de rendimiento de páginas'),
                'icon' => 'gauge',
                'url' => Url::fromRoute('jaraba_page_builder.analytics_dashboard')->toString(),
                'color' => 'success',
            ],
            [
                'title' => $this->t('Estructura del Sitio'),
                'description' => $this->t('Organiza la navegación y menús del sitio'),
                'icon' => 'sitemap',
                'url' => Url::fromRoute('jaraba_site_builder.dashboard')->toString(),
                'color' => 'agro',
            ],
            [
                'title' => $this->t('Configuración'),
                'description' => $this->t('Ajustes del Page Builder'),
                'icon' => 'settings',
                'url' => Url::fromRoute('jaraba_page_builder.settings_ajax')->toString(),
                'color' => 'neutral',
                'data_attrs' => [
                    'slide-panel' => 'pb-settings',
                    'slide-panel-title' => $this->t('Configuración del Page Builder'),
                ],
            ],
        ];

        // S5-01: Quota info para upgrade CTA contextual.
        $quota = $this->getQuotaInfo();

        // S3-04: Cross-link al Content Hub (graceful degradation).
        $contentHubAvailable = FALSE;
        try {
            $contentHubAvailable = \Drupal::moduleHandler()->moduleExists('jaraba_content_hub');
        }
        catch (\Throwable) {
            // Graceful degradation.
        }

        return [
            '#theme' => 'page_builder_dashboard',
            '#stats' => $stats,
            '#quick_actions' => $quick_actions,
            '#setup_wizard' => $setupWizard,
            '#daily_actions' => $dailyActions,
            '#quota' => $quota,
            '#content_hub_available' => $contentHubAvailable,
            '#attached' => [
                'library' => [
                    'jaraba_page_builder/page-builder-dashboard',
                    'ecosistema_jaraba_theme/bundle-page-builder-dashboard',
                    'ecosistema_jaraba_theme/slide-panel',
                ],
            ],
        ];
    }

    /**
     * Obtiene estadísticas básicas para el dashboard.
     *
     * @return array
     *   Array con estadísticas.
     */
    protected function getStats(): array
    {
        $page_storage = $this->entityTypeManager()->getStorage('page_content');
        $template_storage = $this->entityTypeManager()->getStorage('page_template');

        // Contar páginas publicadas.
        $pages_count = $page_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', TRUE)
            ->count()
            ->execute();

        // Contar plantillas disponibles.
        $templates_count = $template_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', TRUE)
            ->count()
            ->execute();

        // Contar experimentos activos.
        $experiment_storage = $this->entityTypeManager()->getStorage('page_experiment');
        $experiments_count = $experiment_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 'running')
            ->count()
            ->execute();

        return [
            'pages' => [
                'value' => $pages_count,
                'label' => $this->t('Páginas Publicadas'),
                'icon' => 'file-text',
            ],
            'templates' => [
                'value' => $templates_count,
                'label' => $this->t('Plantillas Disponibles'),
                'icon' => 'layout-grid',
            ],
            'experiments' => [
                'value' => $experiments_count,
                'label' => $this->t('Experimentos Activos'),
                'icon' => 'chart-line',
            ],
        ];
    }

    /**
     * Obtiene informacion de cuota para el upgrade CTA contextual.
     *
     * S5-01: Muestra indicador de uso y CTA de upgrade cuando
     * el tenant se acerca al limite de paginas del plan.
     *
     * @return array{current: int, limit: int, percentage: int, plan_label: \Drupal\Core\StringTranslation\TranslatableMarkup, can_upgrade: bool, upgrade_route: string|null}
     *   Datos de cuota con current, limit, percentage, can_upgrade.
     */
    protected function getQuotaInfo(): array {
        $quota = [
            'current' => 0,
            'limit' => 5,
            'percentage' => 0,
            'plan_label' => $this->t('Free'),
            'can_upgrade' => FALSE,
            'upgrade_route' => NULL,
        ];

        if (!\Drupal::hasService('jaraba_page_builder.quota_manager')) {
            return $quota;
        }

        try {
            $quotaManager = \Drupal::service('jaraba_page_builder.quota_manager');
            $check = $quotaManager->checkCanCreatePage();

            // Extraer datos de la respuesta del QuotaManager.
            $remaining = $check['remaining'] ?? NULL;
            $limit = NULL;

            // El remaining + current = limit.
            $page_storage = $this->entityTypeManager()->getStorage('page_content');
            /** @var int $current */
            $current = $page_storage->getQuery()
                ->accessCheck(TRUE)
                ->count()
                ->execute();

            if ($remaining !== NULL && $remaining >= 0) {
                $limit = $current + $remaining;
            }

            $quota['current'] = $current;
            $quota['limit'] = $limit ?? 5;
            $quota['percentage'] = $quota['limit'] > 0
                ? min(100, (int) round(($current / $quota['limit']) * 100))
                : 0;

            // Unlimited plan (-1) = no upgrade needed.
            if ($limit === -1 || $limit === NULL) {
                $quota['limit'] = -1;
                $quota['percentage'] = 0;
                $quota['can_upgrade'] = FALSE;
            } else {
                $quota['can_upgrade'] = TRUE;
            }

            // Resolver ruta de upgrade.
            try {
                $quota['upgrade_route'] = Url::fromRoute('jaraba_billing.plan_upgrade')
                    ->toString();
            }
            catch (\Throwable) {
                $quota['upgrade_route'] = '/planes';
            }
        }
        catch (\Throwable) {
            // Graceful degradation.
        }

        return $quota;
    }

}
