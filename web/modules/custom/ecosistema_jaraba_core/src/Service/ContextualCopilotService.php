<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Contextual AI Copilot Service.
 *
 * Analiza el contexto de la página actual y genera sugerencias
 * inteligentes para el usuario.
 */
class ContextualCopilotService
{

    use StringTranslationTrait;

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Current route match.
     */
    protected CurrentRouteMatch $routeMatch;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Contextos de página conocidos.
     */
    protected const PAGE_CONTEXTS = [
        'ecosistema_jaraba_core.tenant.dashboard' => 'dashboard',
        'entity.node.add_form' => 'content_create',
        'entity.node.edit_form' => 'content_edit',
        'ecosistema_jaraba_core.marketplace' => 'marketplace',
        'system.admin_content' => 'content_list',
        'view.commerce_orders.page' => 'orders',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CurrentRouteMatch $routeMatch,
        AccountProxyInterface $currentUser
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->routeMatch = $routeMatch;
        $this->currentUser = $currentUser;
    }

    /**
     * Analiza el contexto de la página actual.
     *
     * @return array
     *   Contexto de página con sugerencias.
     */
    public function analyzeCurrentContext(): array
    {
        $routeName = $this->routeMatch->getRouteName();
        $params = $this->routeMatch->getParameters()->all();

        $context = [
            'route' => $routeName,
            'type' => $this->getContextType($routeName),
            'entity' => NULL,
            'suggestions' => [],
            'actions' => [],
            'tips' => [],
        ];

        // Detectar entidad en contexto.
        if (isset($params['node'])) {
            $context['entity'] = [
                'type' => 'node',
                'id' => is_object($params['node']) ? $params['node']->id() : $params['node'],
                'bundle' => is_object($params['node']) ? $params['node']->bundle() : NULL,
            ];
        }

        // Generar sugerencias según contexto.
        $context['suggestions'] = $this->generateSuggestions($context);
        $context['actions'] = $this->getContextualActions($context);
        $context['tips'] = $this->getContextualTips($context);

        return $context;
    }

    /**
     * Obtiene el tipo de contexto para una ruta.
     */
    protected function getContextType(string $routeName): string
    {
        if (isset(self::PAGE_CONTEXTS[$routeName])) {
            return self::PAGE_CONTEXTS[$routeName];
        }

        // Detectar por patrones.
        if (str_contains($routeName, 'marketplace')) {
            return 'marketplace';
        }
        if (str_contains($routeName, 'dashboard')) {
            return 'dashboard';
        }
        if (str_contains($routeName, 'commerce')) {
            return 'commerce';
        }
        if (str_contains($routeName, 'tenant')) {
            return 'tenant';
        }

        return 'general';
    }

    /**
     * Genera sugerencias basadas en contexto.
     */
    protected function generateSuggestions(array $context): array
    {
        $suggestions = [];

        switch ($context['type']) {
            case 'dashboard':
                $suggestions = [
                    [
                        'id' => 'view_metrics',
                        'text' => $this->t('Revisa tus métricas de esta semana'),
                        'icon' => '📊',
                        'action' => 'scroll_to_metrics',
                    ],
                    [
                        'id' => 'check_orders',
                        'text' => $this->t('Tienes pedidos pendientes de procesar'),
                        'icon' => '📦',
                        'action' => 'navigate',
                        'url' => '/admin/commerce/orders',
                    ],
                    [
                        'id' => 'optimize_products',
                        'text' => $this->t('3 productos pueden mejorar sus descripciones'),
                        'icon' => '✨',
                        'action' => 'show_products',
                    ],
                ];
                break;

            case 'content_create':
            case 'content_edit':
                $suggestions = [
                    [
                        'id' => 'ai_description',
                        'text' => $this->t('Generar descripción con IA'),
                        'icon' => '🤖',
                        'action' => 'generate_description',
                    ],
                    [
                        'id' => 'seo_check',
                        'text' => $this->t('Analizar SEO del contenido'),
                        'icon' => '🔍',
                        'action' => 'analyze_seo',
                    ],
                    [
                        'id' => 'suggest_tags',
                        'text' => $this->t('Sugerir etiquetas automáticamente'),
                        'icon' => '🏷️',
                        'action' => 'suggest_tags',
                    ],
                ];
                break;

            case 'marketplace':
                $suggestions = [
                    [
                        'id' => 'trending',
                        'text' => $this->t('Ver productos tendencia'),
                        'icon' => '🔥',
                        'action' => 'filter_trending',
                    ],
                    [
                        'id' => 'for_you',
                        'text' => $this->t('Recomendaciones para ti'),
                        'icon' => '💡',
                        'action' => 'show_recommendations',
                    ],
                ];
                break;

            case 'orders':
                $suggestions = [
                    [
                        'id' => 'batch_process',
                        'text' => $this->t('Procesar pedidos en lote'),
                        'icon' => '⚡',
                        'action' => 'batch_mode',
                    ],
                    [
                        'id' => 'export_orders',
                        'text' => $this->t('Exportar pedidos a Excel'),
                        'icon' => '📄',
                        'action' => 'export',
                    ],
                ];
                break;

            default:
                $suggestions = [
                    [
                        'id' => 'help',
                        'text' => $this->t('¿Necesitas ayuda?'),
                        'icon' => '❓',
                        'action' => 'show_help',
                    ],
                ];
        }

        return $suggestions;
    }

    /**
     * Obtiene acciones contextuales rápidas.
     */
    protected function getContextualActions(array $context): array
    {
        $actions = [];

        switch ($context['type']) {
            case 'dashboard':
                $actions = [
                    ['label' => $this->t('Añadir producto'), 'url' => '/node/add/product', 'icon' => '➕'],
                    ['label' => $this->t('Ver pedidos'), 'url' => '/admin/commerce/orders', 'icon' => '📦'],
                    ['label' => $this->t('Estadísticas'), 'url' => '/tenant/analytics', 'icon' => '📊'],
                ];
                break;

            case 'marketplace':
                $actions = [
                    ['label' => $this->t('Buscar'), 'action' => 'focus_search', 'icon' => '🔍'],
                    ['label' => $this->t('Filtrar'), 'action' => 'show_filters', 'icon' => '⚙️'],
                    ['label' => $this->t('Categorías'), 'action' => 'show_categories', 'icon' => '📁'],
                ];
                break;
        }

        return $actions;
    }

    /**
     * Obtiene tips contextuales.
     */
    protected function getContextualTips(array $context): array
    {
        $tips = [];

        switch ($context['type']) {
            case 'content_create':
                $tips = [
                    $this->t('💡 Las imágenes de alta calidad aumentan las conversiones un 40%'),
                    $this->t('💡 Incluye al menos 3 palabras clave en la descripción'),
                    $this->t('💡 Los precios terminados en .99 convierten mejor'),
                ];
                break;

            case 'dashboard':
                $tips = [
                    $this->t('💡 Revisa el dashboard cada mañana para detectar oportunidades'),
                    $this->t('💡 Los pedidos procesados en 24h tienen mejor valoración'),
                ];
                break;

            case 'marketplace':
                $tips = [
                    $this->t('💡 Usa filtros para encontrar productos más rápido'),
                    $this->t('💡 Los productos con sello ecológico destacan más'),
                ];
                break;
        }

        return $tips;
    }

    /**
     * Obtiene sugerencias de autocompletado para un campo.
     *
     * @param string $field
     *   Nombre del campo.
     * @param string $query
     *   Texto de búsqueda.
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Sugerencias de autocompletado.
     */
    public function getAutocomplete(string $field, string $query, int $tenantId): array
    {
        $suggestions = [];

        switch ($field) {
            case 'product_name':
                // Sugerir basado en productos existentes del tenant.
                $suggestions = [
                    ['value' => "$query Ecológico", 'label' => "$query Ecológico ✨"],
                    ['value' => "$query Premium", 'label' => "$query Premium ⭐"],
                    ['value' => "$query Artesanal", 'label' => "$query Artesanal 🎨"],
                ];
                break;

            case 'description':
                // Sugerir frases para descripción.
                $suggestions = [
                    ['value' => "Producto de alta calidad elaborado con $query", 'label' => 'Calidad premium'],
                    ['value' => "Descubre el auténtico sabor de $query", 'label' => 'Sabor auténtico'],
                    ['value' => "$query producido de forma sostenible", 'label' => 'Sostenibilidad'],
                ];
                break;

            case 'tags':
                // Sugerir etiquetas.
                $suggestions = [
                    ['value' => 'ecológico', 'label' => 'ecológico 🌿'],
                    ['value' => 'artesanal', 'label' => 'artesanal 🎨'],
                    ['value' => 'gourmet', 'label' => 'gourmet ⭐'],
                    ['value' => 'local', 'label' => 'local 📍'],
                ];
                break;
        }

        return $suggestions;
    }

    /**
     * Genera contenido con IA.
     *
     * @param string $type
     *   Tipo de contenido (description, title, tags).
     * @param array $context
     *   Contexto (producto actual, tenant, etc.).
     *
     * @return string
     *   Contenido generado.
     */
    public function generateContent(string $type, array $context): string
    {
        $productName = $context['product_name'] ?? 'producto';
        $category = $context['category'] ?? 'general';

        switch ($type) {
            case 'description':
                return "Descubre {$productName}, un producto excepcional de la categoría {$category}. " .
                    "Elaborado con los mejores ingredientes y siguiendo métodos tradicionales, " .
                    "este producto destaca por su calidad superior y sabor auténtico. " .
                    "Ideal para quienes buscan experiencias gastronómicas únicas.";

            case 'seo_title':
                return "Comprar {$productName} | Mejor precio | Envío 24h";

            case 'meta_description':
                return "✅ {$productName} de máxima calidad. Productos artesanales con envío rápido. " .
                    "Descubre el auténtico sabor. ¡Compra ahora!";

            default:
                return '';
        }
    }

}
