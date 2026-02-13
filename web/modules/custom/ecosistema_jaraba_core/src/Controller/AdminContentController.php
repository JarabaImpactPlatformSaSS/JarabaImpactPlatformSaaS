<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enhanced admin/content page with categorized sections and search.
 *
 * Replaces the default SystemController::systemAdminMenuBlockPage with a
 * version that groups content items into logical sections, sorts them
 * alphabetically, and provides client-side search/filter with tab navigation.
 *
 * Categories cover ALL platform verticals so that every menu item from the
 * Drupal sidebar under "Contenido" is represented on this page.
 */
class AdminContentController extends ControllerBase
{

    /**
     * Category definitions for content items.
     *
     * Order determines display order on the page. Every jaraba_* module that
     * registers menu links under system.admin_content MUST appear here.
     */
    protected const CATEGORIES = [
        'lms_training' => [
            'label' => 'Formación & LMS',
            'icon' => '🎓',
            'description' => 'Cursos, rutas de aprendizaje, certificados, gamificación',
            'providers' => [
                'jaraba_lms',
                'jaraba_training',
                'jaraba_interactive',
                'jaraba_sepe_teleformacion',
                'jaraba_paths',
            ],
        ],
        'mentoring' => [
            'label' => 'Mentoría',
            'icon' => '🤝',
            'description' => 'Sesiones de mentoría, mentores, programas',
            'providers' => ['jaraba_mentoring'],
        ],
        'knowledge_base' => [
            'label' => 'Base de Conocimiento',
            'icon' => '📚',
            'description' => 'Conocimiento del tenant, documentación legal, artículos',
            'providers' => ['jaraba_tenant_knowledge', 'jaraba_legal_knowledge'],
        ],
        'employability' => [
            'label' => 'Empleabilidad & Candidatos',
            'icon' => '🎯',
            'description' => 'Empleos, candidaturas, skills, competencias IA',
            'providers' => [
                'jaraba_job_board',
                'jaraba_candidate',
                'jaraba_skills',
                'jaraba_matching',
            ],
        ],
        'crm_sales' => [
            'label' => 'CRM & Ventas',
            'icon' => '📇',
            'description' => 'Empresas, contactos, oportunidades, actividades comerciales',
            'providers' => ['jaraba_crm'],
        ],
        'agroconecta' => [
            'label' => 'AgroConecta',
            'icon' => '🌾',
            'description' => 'Productos agro, productores, pedidos, trazabilidad, certificaciones',
            'providers' => ['jaraba_agroconecta_core'],
        ],
        'comercio_marketplace' => [
            'label' => 'Comercio & Marketplace',
            'icon' => '🛒',
            'description' => 'Productos, variaciones, pedidos, comerciantes, social commerce',
            'providers' => [
                'jaraba_commerce',
                'jaraba_comercio_conecta',
                'jaraba_social_commerce',
            ],
        ],
        'servicios' => [
            'label' => 'Servicios & Reservas',
            'icon' => '🗓️',
            'description' => 'Paquetes de servicios, reservas, disponibilidad',
            'providers' => ['jaraba_servicios_conecta'],
        ],
        'content_media' => [
            'label' => 'Contenido & Blog',
            'icon' => '📝',
            'description' => 'Artículos, noticias, categorías de contenido',
            'providers' => ['jaraba_content_hub', 'jaraba_blog'],
        ],
        'resources' => [
            'label' => 'Recursos',
            'icon' => '📁',
            'description' => 'Recursos descargables, kits digitales, documentos',
            'providers' => ['jaraba_resources'],
        ],
        'groups_community' => [
            'label' => 'Grupos & Comunidad',
            'icon' => '👥',
            'description' => 'Grupos, foros, comunidades de práctica, social',
            'providers' => ['jaraba_groups', 'jaraba_social', 'jaraba_referral'],
        ],
        'events' => [
            'label' => 'Eventos',
            'icon' => '📅',
            'description' => 'Eventos, sesiones, inscripciones',
            'providers' => ['jaraba_events'],
        ],
        'onboarding' => [
            'label' => 'Onboarding',
            'icon' => '🚀',
            'description' => 'Flujos de bienvenida, guías de inicio, journeys',
            'providers' => ['jaraba_onboarding', 'jaraba_journey', 'jaraba_customer_success'],
        ],
        'business_tools' => [
            'label' => 'Herramientas de Negocio',
            'icon' => '🧰',
            'description' => 'Canvas, DAFO, business plans, emprendimiento',
            'providers' => [
                'jaraba_business_tools',
                'jaraba_copilot_v2',
                'jaraba_foc',
            ],
        ],
        'ai_agents' => [
            'label' => 'IA & Agentes',
            'icon' => '🤖',
            'description' => 'Agentes IA, flujos automatizados, experimentos A/B',
            'providers' => [
                'jaraba_ai_agents',
                'jaraba_agent_flows',
                'jaraba_ab_testing',
            ],
        ],
        'billing_usage' => [
            'label' => 'Facturación & Uso',
            'icon' => '💰',
            'description' => 'Facturas, planes, consumo de servicios, add-ons',
            'providers' => [
                'jaraba_billing',
                'jaraba_usage_billing',
                'jaraba_funding',
                'jaraba_addons',
            ],
        ],
        'analytics' => [
            'label' => 'Analítica & Datos',
            'icon' => '📊',
            'description' => 'Heatmaps, insights, dashboards, analítica avanzada',
            'providers' => [
                'jaraba_heatmap',
                'jaraba_insights_hub',
                'jaraba_analytics',
                'jaraba_pixels',
            ],
        ],
        'andalucia_ei' => [
            'label' => 'Andalucía +ei',
            'icon' => '🏛️',
            'description' => 'Programa Andalucía Emprende e Innova',
            'providers' => ['jaraba_andalucia_ei'],
        ],
        'site_builder' => [
            'label' => 'Site Builder',
            'icon' => '🌐',
            'description' => 'Páginas, bloques de contenido, SEO, theming',
            'providers' => [
                'jaraba_site_builder',
                'jaraba_page_builder',
                'jaraba_theming',
                'jaraba_i18n',
                'jaraba_ads',
                'jaraba_email',
            ],
        ],
        'security' => [
            'label' => 'Seguridad & Compliance',
            'icon' => '🔒',
            'description' => 'Auditoría, seguridad, cumplimiento normativo',
            'providers' => ['jaraba_security_compliance'],
        ],
        'pwa_mobile' => [
            'label' => 'PWA & Notificaciones',
            'icon' => '📱',
            'description' => 'Push notifications, configuración PWA, geolocalización',
            'providers' => ['jaraba_pwa', 'jaraba_geo'],
        ],
        'performance' => [
            'label' => 'Rendimiento',
            'icon' => '⚡',
            'description' => 'Optimización, caché, rendimiento del sistema',
            'providers' => ['jaraba_performance'],
        ],
        'drupal_core' => [
            'label' => 'Contenido Drupal',
            'icon' => '⚙️',
            'description' => 'Nodos, comentarios, archivos, medios, bloques',
            'providers' => [
                'node',
                'comment',
                'file',
                'media',
                'media_library',
                'views',
                'system',
                'user',
                'block_content',
                'paragraphs_library',
                'domain_content',
                'ecosistema_jaraba_core',
            ],
        ],
        'other' => [
            'label' => 'Otros',
            'icon' => '📦',
            'description' => 'Módulos adicionales de la plataforma',
            'providers' => [],
        ],
    ];

    /**
     * Constructs an AdminContentController.
     */
    public function __construct(
        protected MenuLinkTreeInterface $menuTree,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('menu.link_tree'),
        );
    }

    /**
     * Renders the enhanced admin/content overview page.
     *
     * @return array
     *   A render array for the page.
     */
    public function overview(): array
    {
        $items = $this->getContentItems();
        $categorized = $this->categorizeItems($items);

        // Sort items alphabetically within each category.
        foreach ($categorized as &$category) {
            usort($category['items'], function ($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
        }

        // Remove empty categories.
        $categorized = array_filter($categorized, function ($cat) {
            return !empty($cat['items']);
        });

        return [
            '#theme' => 'admin_content_overview',
            '#categories' => $categorized,
            '#total_items' => count($items),
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/admin-structure',
                ],
            ],
        ];
    }

    /**
     * Gets all menu link items under system.admin_content.
     *
     * @return array
     *   Array of items with title, description, url, and provider.
     */
    protected function getContentItems(): array
    {
        $parameters = new MenuTreeParameters();
        $parameters->setRoot('system.admin_content');
        $parameters->setMaxDepth(1);
        $parameters->excludeRoot();

        $tree = $this->menuTree->load('admin', $parameters);
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];
        $tree = $this->menuTree->transform($tree, $manipulators);

        $items = [];
        foreach ($tree as $element) {
            $link = $element->link;
            $definition = $link->getPluginDefinition();

            $url = NULL;
            try {
                $url = $link->getUrlObject();
            } catch (\Exception $e) {
                continue;
            }

            $items[] = [
                'title' => (string) $link->getTitle(),
                'description' => (string) ($definition['description'] ?? ''),
                'url' => $url instanceof Url ? $url->toString() : '',
                'provider' => $definition['provider'] ?? 'unknown',
                'route_name' => $definition['route_name'] ?? '',
            ];
        }

        return $items;
    }

    /**
     * Categorizes items into sections based on their provider module.
     */
    protected function categorizeItems(array $items): array
    {
        $providerMap = [];
        foreach (self::CATEGORIES as $catKey => $catDef) {
            foreach ($catDef['providers'] as $provider) {
                $providerMap[$provider] = $catKey;
            }
        }

        $result = [];
        foreach (self::CATEGORIES as $catKey => $catDef) {
            $result[$catKey] = [
                'label' => $catDef['label'],
                'icon' => $catDef['icon'],
                'description' => $catDef['description'],
                'items' => [],
            ];
        }

        foreach ($items as $item) {
            $provider = $item['provider'];
            $catKey = $providerMap[$provider] ?? 'other';
            $result[$catKey]['items'][] = $item;
        }

        return $result;
    }

}
