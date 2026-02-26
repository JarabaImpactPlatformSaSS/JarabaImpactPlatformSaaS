<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enhanced admin/structure page with categorized sections and search.
 *
 * Replaces the default SystemController::systemAdminMenuBlockPage with a
 * version that groups items into logical sections (SaaS Platform,
 * AgroConecta, Site Builder, Drupal Core, etc.), sorts them alphabetically,
 * and provides client-side search/filter capabilities.
 */
class AdminStructureController extends ControllerBase
{

    /**
     * Category definitions: label, icon, and module prefixes.
     *
     * Order determines display order on the page.
     */
    protected const CATEGORIES = [
        'saas_platform' => [
            'label' => 'Plataforma SaaS',
            'icon' => '🏢',
            'description' => 'Entidades y configuración del núcleo SaaS multi-tenant',
            'providers' => ['ecosistema_jaraba_core'],
        ],
        'agroconecta' => [
            'label' => 'AgroConecta',
            'icon' => '🌾',
            'description' => 'Marketplace agroalimentario — productos, pedidos, trazabilidad',
            'providers' => ['jaraba_agroconecta_core'],
        ],
        'site_builder' => [
            'label' => 'Site Builder',
            'icon' => '🌐',
            'description' => 'Estructura del sitio — menús, headers, footers, SEO',
            'providers' => ['jaraba_site_builder'],
        ],
        // FIX-027: Canonical vertical names (no underscores).
        'comercioconecta' => [
            'label' => 'ComercioConecta',
            'icon' => '🛍️',
            'description' => 'Marketplace de comercio local',
            'providers' => ['jaraba_comercio_conecta'],
        ],
        'serviciosconecta' => [
            'label' => 'ServiciosConecta',
            'icon' => '🔧',
            'description' => 'Marketplace de servicios profesionales',
            'providers' => ['jaraba_servicios_conecta'],
        ],
        'crm' => [
            'label' => 'CRM & Candidatos',
            'icon' => '👥',
            'description' => 'Gestión de relaciones y candidatos',
            'providers' => ['jaraba_crm', 'jaraba_candidate'],
        ],
        'ia_agents' => [
            'label' => 'IA & Agentes',
            'icon' => '🤖',
            'description' => 'Agentes IA, flujos automatizados y RAG',
            'providers' => ['jaraba_ai_agents', 'jaraba_agent_flows', 'jaraba_rag', 'jaraba_copilot_v2'],
        ],
        'content_media' => [
            'label' => 'Contenido & Medios',
            'icon' => '📝',
            'description' => 'Blog, eventos, LMS, contenido multimedia',
            'providers' => ['jaraba_blog', 'jaraba_events', 'jaraba_lms', 'jaraba_content_hub', 'jaraba_page_builder'],
        ],
        'billing_commerce' => [
            'label' => 'Facturación & Comercio',
            'icon' => '💰',
            'description' => 'Billing, planes de precios, addons, ads',
            'providers' => ['jaraba_billing', 'jaraba_usage_billing', 'jaraba_commerce', 'jaraba_addons', 'jaraba_ads', 'jaraba_funding'],
        ],
        'drupal_core' => [
            'label' => 'Estructura Drupal',
            'icon' => '🧩',
            'description' => 'Componentes del core de Drupal — tipos de contenido, taxonomía, vistas',
            'providers' => [
                'block',
                'block_content',
                'node',
                'taxonomy',
                'views_ui',
                'contact',
                'comment',
                'media',
                'menu_ui',
                'field_ui',
            ],
        ],
        'other' => [
            'label' => 'Otros Módulos',
            'icon' => '📦',
            'description' => 'Módulos adicionales de la plataforma',
            'providers' => [],
        ],
    ];

    /**
     * Constructs an AdminStructureController.
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
     * Renders the enhanced admin/structure overview page.
     *
     * @return array
     *   A render array for the page.
     */
    public function overview(): array
    {
        $items = $this->getStructureItems();
        $categorized = $this->categorizeItems($items);

        // Sort items alphabetically within each category.
        foreach ($categorized as &$category) {
            usort($category['items'], function ($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
        }

        // Remove empty categories (except 'other' which we always show if it has items).
        $categorized = array_filter($categorized, function ($cat) {
            return !empty($cat['items']);
        });

        return [
            '#theme' => 'admin_structure_overview',
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
     * Gets all menu link items that are children of system.admin_structure.
     *
     * @return array
     *   Array of items with title, description, url, and provider.
     */
    protected function getStructureItems(): array
    {
        $parameters = new MenuTreeParameters();
        $parameters->setRoot('system.admin_structure');
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
                // Skip items with broken routes.
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
     *
     * @param array $items
     *   Array of menu link items.
     *
     * @return array
     *   Categorized items keyed by category ID.
     */
    protected function categorizeItems(array $items): array
    {
        // Build a lookup map: provider => category key.
        $providerMap = [];
        foreach (self::CATEGORIES as $catKey => $catDef) {
            foreach ($catDef['providers'] as $provider) {
                $providerMap[$provider] = $catKey;
            }
        }

        // Initialize categories with metadata.
        $result = [];
        foreach (self::CATEGORIES as $catKey => $catDef) {
            $result[$catKey] = [
                'label' => $catDef['label'],
                'icon' => $catDef['icon'],
                'description' => $catDef['description'],
                'items' => [],
            ];
        }

        // Distribute items.
        foreach ($items as $item) {
            $provider = $item['provider'];
            $catKey = $providerMap[$provider] ?? 'other';
            $result[$catKey]['items'][] = $item;
        }

        return $result;
    }

}
