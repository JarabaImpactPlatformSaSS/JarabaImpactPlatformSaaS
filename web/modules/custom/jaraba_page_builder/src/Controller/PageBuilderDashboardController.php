<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controlador para el dashboard principal del Page Builder.
 *
 * Este dashboard actúa como hub central que conecta todas las funcionalidades
 * del Page Builder:
 * - Plantillas: Elegir y crear páginas desde templates
 * - Mis Páginas: Ver y gestionar páginas creadas
 * - Experimentos A/B: Gestionar tests de conversión
 * - Estructura: Gestionar árbol de navegación (Site Builder)
 */
class PageBuilderDashboardController extends ControllerBase
{

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

        return [
            '#theme' => 'page_builder_dashboard',
            '#stats' => $stats,
            '#quick_actions' => $quick_actions,
            '#attached' => [
                'library' => [
                    'jaraba_page_builder/page-builder-dashboard',
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

}
