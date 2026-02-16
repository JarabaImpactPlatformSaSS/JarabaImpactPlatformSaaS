<?php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio centralizado para gestión de templates del Page Builder.
 *
 * PROPÓSITO (SSoT - Single Source of Truth):
 * Este servicio es la fuente única de verdad para todos los templates
 * del Page Builder. Proporciona una API unificada para:
 * - Listar templates disponibles (con filtros por plan/categoría)
 * - Obtener un template específico por ID
 * - Validar acceso según plan de suscripción del tenant
 * - Generar datos para GrapesJS blocks y galería de templates
 *
 * ARQUITECTURA:
 * - Lee templates desde config/install/*.yml
 * - Cachea resultados para performance
 * - Filtra por plan del tenant actual
 * - Expone API para múltiples consumidores
 *
 * @see docs/arquitectura/2026-02-06_arquitectura_unificada_templates_bloques.md
 */
class TemplateRegistryService
{

    /**
     * Cache ID prefix.
     */
    const CACHE_PREFIX = 'jaraba_page_builder:templates:';

    /**
     * Cache TTL en segundos (1 hora).
     */
    const CACHE_TTL = 3600;

    /**
     * Categorías de templates ordenadas.
     */
    const CATEGORIES = [
        'hero' => [
            'label' => 'Hero',
            'icon' => 'star',
            'weight' => 0,
        ],
        'content' => [
            'label' => 'Contenido',
            'icon' => 'document-text',
            'weight' => 10,
        ],
        'cta' => [
            'label' => 'Llamada a Acción',
            'icon' => 'cursor-click',
            'weight' => 20,
        ],
        'features' => [
            'label' => 'Características',
            'icon' => 'sparkles',
            'weight' => 30,
        ],
        'testimonials' => [
            'label' => 'Testimonios',
            'icon' => 'chat-bubble',
            'weight' => 40,
        ],
        'pricing' => [
            'label' => 'Precios',
            'icon' => 'currency-euro',
            'weight' => 50,
        ],
        'contact' => [
            'label' => 'Contacto',
            'icon' => 'mail',
            'weight' => 60,
        ],
        'gallery' => [
            'label' => 'Galería',
            'icon' => 'photograph',
            'weight' => 70,
        ],
        'commerce' => [
            'label' => 'Comercio',
            'icon' => 'shopping-cart',
            'weight' => 80,
        ],
        'social' => [
            'label' => 'Social',
            'icon' => 'users',
            'weight' => 90,
        ],
        'advanced' => [
            'label' => 'Avanzado',
            'icon' => 'cog',
            'weight' => 100,
        ],
        'premium' => [
            'label' => 'Premium',
            'icon' => 'lightning-bolt',
            'weight' => 110,
        ],
        // Categorias verticales — P2-01: 55 templates verticales.
        'agroconecta' => [
            'label' => 'AgroConecta',
            'icon' => 'agriculture',
            'weight' => 200,
        ],
        'comercioconecta' => [
            'label' => 'ComercioConecta',
            'icon' => 'shopping-bag',
            'weight' => 210,
        ],
        'serviciosconecta' => [
            'label' => 'ServiciosConecta',
            'icon' => 'briefcase',
            'weight' => 220,
        ],
        'empleabilidad' => [
            'label' => 'Empleabilidad',
            'icon' => 'user-group',
            'weight' => 230,
        ],
        'emprendimiento' => [
            'label' => 'Emprendimiento',
            'icon' => 'rocket',
            'weight' => 240,
        ],
    ];

    /**
     * El backend de caché.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected CacheBackendInterface $cache;

    /**
     * El config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El module handler.
     *
     * @var \Drupal\Core\Extension\ModuleHandlerInterface
     */
    protected ModuleHandlerInterface $moduleHandler;

    /**
     * El servicio de contexto de tenant.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected AccountInterface $currentUser;

    /**
     * El logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Templates cargados en memoria.
     *
     * @var array|null
     */
    protected ?array $loadedTemplates = null;

    /**
     * Constructor.
     */
    public function __construct(
        CacheBackendInterface $cache,
        ConfigFactoryInterface $config_factory,
        ModuleHandlerInterface $module_handler,
        TenantContextService $tenant_context,
        AccountInterface $current_user,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->cache = $cache;
        $this->configFactory = $config_factory;
        $this->moduleHandler = $module_handler;
        $this->tenantContext = $tenant_context;
        $this->currentUser = $current_user;
        $this->logger = $logger_factory->get('jaraba_page_builder');
    }

    /**
     * Obtiene todos los templates disponibles.
     *
     * @param array $options
     *   Opciones de filtrado:
     *   - category: Filtrar por categoría.
     *   - is_premium: Filtrar por estado premium.
     *   - plan: Filtrar por plan requerido.
     *   - include_restricted: Incluir templates restringidos (default: FALSE).
     *
     * @return array
     *   Array de templates indexados por ID.
     */
    public function getAll(array $options = []): array
    {
        $templates = $this->loadAllTemplates();

        // Aplicar filtros.
        if (!empty($options['category'])) {
            $templates = array_filter($templates, fn($t) => ($t['category'] ?? '') === $options['category']);
        }

        if (isset($options['is_premium'])) {
            $templates = array_filter($templates, fn($t) => ($t['is_premium'] ?? FALSE) === $options['is_premium']);
        }

        // Filtrar por plan del tenant actual si no se incluyen restringidos.
        if (empty($options['include_restricted'])) {
            $tenantPlan = $this->getTenantPlan();
            $templates = array_filter($templates, fn($t) => $this->isTemplateAccessible($t, $tenantPlan));
        }

        // Ordenar por weight y luego por label.
        uasort($templates, function ($a, $b) {
            $weightA = $a['weight'] ?? 0;
            $weightB = $b['weight'] ?? 0;
            if ($weightA !== $weightB) {
                return $weightA <=> $weightB;
            }
            return ($a['label'] ?? '') <=> ($b['label'] ?? '');
        });

        return $templates;
    }

    /**
     * Obtiene un template por su ID.
     *
     * @param string $id
     *   El ID del template.
     *
     * @return array|null
     *   El template o NULL si no existe.
     */
    public function get(string $id): ?array
    {
        $templates = $this->loadAllTemplates();
        return $templates[$id] ?? NULL;
    }

    /**
     * Obtiene templates agrupados por categoría.
     *
     * @param array $options
     *   Opciones de filtrado (ver getAll()).
     *
     * @return array
     *   Array de categorías con sus templates.
     */
    public function getGroupedByCategory(array $options = []): array
    {
        $templates = $this->getAll($options);
        $grouped = [];

        foreach (self::CATEGORIES as $categoryId => $categoryInfo) {
            $categoryTemplates = array_filter(
                $templates,
                fn($t) => ($t['category'] ?? 'content') === $categoryId
            );

            if (!empty($categoryTemplates)) {
                $grouped[$categoryId] = [
                    'id' => $categoryId,
                    'label' => $categoryInfo['label'],
                    'icon' => $categoryInfo['icon'],
                    'weight' => $categoryInfo['weight'],
                    'templates' => $categoryTemplates,
                    'count' => count($categoryTemplates),
                ];
            }
        }

        // Ordenar categorías por weight.
        uasort($grouped, fn($a, $b) => $a['weight'] <=> $b['weight']);

        return $grouped;
    }

    /**
     * Genera datos para bloques GrapesJS.
     *
     * Transforma templates en formato compatible con el Block Manager de GrapesJS.
     * Incluye información de accesibilidad (isLocked) basada en el plan del tenant.
     *
     * @param array $options
     *   Opciones de filtrado.
     *
     * @return array
     *   Array de bloques para GrapesJS.
     */
    public function getAsGrapesJSBlocks(array $options = []): array
    {
        $templates = $this->getAll($options);
        $blocks = [];
        $currentPlan = $this->getTenantPlan();

        foreach ($templates as $id => $template) {
            $isPremium = $template['is_premium'] ?? FALSE;
            $isLocked = $isPremium && !$this->isTemplateAccessible($template, $currentPlan);

            $blocks[] = [
                'id' => 'template-' . $id,
                'label' => $template['label'] ?? $id,
                'category' => $template['category'] ?? 'content',
                'content' => $this->renderTemplatePreview($template),
                'media' => $this->getTemplateIcon($template),
                'isLocked' => $isLocked,
                'isPremium' => $isPremium,
                'requiredPlan' => $template['required_plan'] ?? 'free',
                'attributes' => [
                    'class' => 'gjs-block-template' . ($isLocked ? ' gjs-block--locked' : ''),
                    'data-template-id' => $id,
                    'data-is-premium' => $isPremium ? 'true' : 'false',
                    'data-is-locked' => $isLocked ? 'true' : 'false',
                    'data-required-plan' => $template['required_plan'] ?? 'free',
                ],
            ];
        }

        return $blocks;
    }

    /**
     * Genera datos para la galería de templates.
     *
     * @param array $options
     *   Opciones de filtrado.
     *
     * @return array
     *   Datos estructurados para la galería UI.
     */
    public function getForGallery(array $options = []): array
    {
        $grouped = $this->getGroupedByCategory($options);
        $tenantPlan = $this->getTenantPlan();

        $gallery = [
            'categories' => [],
            'total_count' => 0,
            'available_count' => 0,
            'premium_count' => 0,
        ];

        foreach ($grouped as $categoryId => $category) {
            $categoryData = [
                'id' => $categoryId,
                'label' => $category['label'],
                'icon' => $category['icon'],
                'templates' => [],
            ];

            foreach ($category['templates'] as $templateId => $template) {
                $isAccessible = $this->isTemplateAccessible($template, $tenantPlan);
                $isPremium = $template['is_premium'] ?? FALSE;

                $categoryData['templates'][] = [
                    'id' => $templateId,
                    'label' => $template['label'] ?? $templateId,
                    'description' => $template['description'] ?? '',
                    'preview_image' => $template['preview_image'] ?? '',
                    'is_premium' => $isPremium,
                    'is_accessible' => $isAccessible,
                    'plans_required' => $template['plans_required'] ?? ['starter'],
                ];

                $gallery['total_count']++;
                if ($isAccessible) {
                    $gallery['available_count']++;
                }
                if ($isPremium) {
                    $gallery['premium_count']++;
                }
            }

            $gallery['categories'][] = $categoryData;
        }

        return $gallery;
    }

    /**
     * Verifica si un template es accesible para un plan dado.
     *
     * @param array $template
     *   El template a verificar.
     * @param string $plan
     *   El plan del tenant.
     *
     * @return bool
     *   TRUE si es accesible.
     */
    public function isTemplateAccessible(array $template, string $plan): bool
    {
        $requiredPlans = $template['plans_required'] ?? ['starter'];

        // Admin tiene acceso a todo.
        if ($this->currentUser->hasPermission('administer page_builder')) {
            return TRUE;
        }

        // Jerarquía de planes.
        $planHierarchy = [
            'free' => 0,
            'starter' => 1,
            'professional' => 2,
            'enterprise' => 3,
        ];

        $tenantLevel = $planHierarchy[$plan] ?? 0;

        // El template es accesible si el tenant tiene un plan igual o superior
        // al mínimo requerido.
        $minRequired = PHP_INT_MAX;
        foreach ($requiredPlans as $requiredPlan) {
            $level = $planHierarchy[$requiredPlan] ?? 0;
            $minRequired = min($minRequired, $level);
        }

        return $tenantLevel >= $minRequired;
    }

    /**
     * Invalida el caché de templates.
     */
    public function invalidateCache(): void
    {
        $this->cache->deleteAll();
        $this->loadedTemplates = NULL;
        $this->logger->info('Template registry cache invalidated.');
    }

    /**
     * Carga todos los templates desde config.
     *
     * @return array
     *   Array de templates indexados por ID.
     */
    protected function loadAllTemplates(): array
    {
        // Retornar de memoria si ya están cargados.
        if ($this->loadedTemplates !== NULL) {
            return $this->loadedTemplates;
        }

        // Intentar obtener de caché.
        $cacheId = self::CACHE_PREFIX . 'all';
        $cached = $this->cache->get($cacheId);

        if ($cached) {
            $this->loadedTemplates = $cached->data;
            return $this->loadedTemplates;
        }

        // Cargar desde archivos de configuración.
        $templates = [];
        $modulePath = $this->moduleHandler->getModule('jaraba_page_builder')->getPath();
        $configPath = $modulePath . '/config/install';

        if (is_dir($configPath)) {
            $files = glob($configPath . '/jaraba_page_builder.template.*.yml');

            foreach ($files as $file) {
                try {
                    $content = file_get_contents($file);
                    $data = Yaml::decode($content);

                    if (!empty($data['id'])) {
                        $templates[$data['id']] = $data;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error loading template from @file: @message', [
                        '@file' => $file,
                        '@message' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Permitir que otros módulos añadan templates.
        $this->moduleHandler->alter('jaraba_page_builder_templates', $templates);

        // Guardar en caché.
        $this->cache->set($cacheId, $templates, time() + self::CACHE_TTL);
        $this->loadedTemplates = $templates;

        return $templates;
    }

    /**
     * Obtiene el plan del tenant actual.
     *
     * @return string
     *   El ID del plan (free, starter, professional, enterprise).
     */
    protected function getTenantPlan(): string
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return 'free';
        }

        // Obtener plan del tenant.
        if ($tenant->hasField('subscription_plan') && !$tenant->get('subscription_plan')->isEmpty()) {
            return $tenant->get('subscription_plan')->value ?? 'starter';
        }

        return 'starter';
    }

    /**
     * Renderiza preview HTML de un template para GrapesJS.
     *
     * @param array $template
     *   El template.
     *
     * @return string
     *   HTML del preview.
     */
    protected function renderTemplatePreview(array $template): string
    {
        $twigPath = $template['twig_template'] ?? '';

        if (!empty($twigPath)) {
            try {
                /** @var \Twig\Environment $twig */
                $twig = \Drupal::service('twig');
                $loader = $twig->getLoader();

                if ($loader->exists($twigPath)) {
                    $previewData = $template['preview_data'] ?? [];
                    return $twig->render($twigPath, $previewData);
                }
            } catch (\Exception $e) {
                $this->logger->warning(
                    'Error rendering template preview @id: @error',
                    ['@id' => $template['id'] ?? 'unknown', '@error' => $e->getMessage()]
                );
            }
        }

        // Fallback: placeholder genérico.
        $id = $template['id'] ?? 'unknown';
        $label = $template['label'] ?? $id;

        return sprintf(
            '<section class="jaraba-template jaraba-template--%s" data-template-id="%s">
                <div class="jaraba-template__placeholder">
                    <span class="jaraba-template__label">%s</span>
                    <span class="jaraba-template__hint">Haz doble clic para editar</span>
                </div>
            </section>',
            htmlspecialchars($id),
            htmlspecialchars($id),
            htmlspecialchars($label)
        );
    }

    /**
     * Obtiene el icono SVG de un template.
     *
     * @param array $template
     *   El template.
     *
     * @return string
     *   SVG del icono.
     */
    protected function getTemplateIcon(array $template): string
    {
        // Preferir PNG preview_image cuando esté disponible.
        if (!empty($template['preview_image'])) {
            $label = htmlspecialchars($template['label'] ?? '', ENT_QUOTES, 'UTF-8');
            $src = htmlspecialchars($template['preview_image'], ENT_QUOTES, 'UTF-8');
            return '<img src="' . $src . '" alt="' . $label . '" '
                . 'width="120" height="80" loading="lazy" '
                . 'style="border-radius:6px; object-fit:cover; width:100%; height:auto;" />';
        }

        $category = $template['category'] ?? 'content';
        $iconName = self::CATEGORIES[$category]['icon'] ?? 'document';

        // Iconos SVG inline.
        $icons = [
            'star' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15,9 22,9 17,14 19,22 12,18 5,22 7,14 2,9 9,9"/></svg>',
            'document-text' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'cursor-click' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>',
            'sparkles' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
            'chat-bubble' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>',
            'currency-euro' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M14.5 9a3.5 3.5 0 100 6M7 10h6M7 14h6"/></svg>',
            'mail' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            'photograph' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
            'shopping-cart' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>',
            'users' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>',
            'cog' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
            'lightning-bolt' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        ];

        return $icons[$iconName] ?? $icons['document-text'];
    }

    /**
     * Obtiene estadísticas del registry.
     *
     * @return array
     *   Estadísticas.
     */
    public function getStats(): array
    {
        $templates = $this->loadAllTemplates();
        $tenantPlan = $this->getTenantPlan();

        $stats = [
            'total' => count($templates),
            'by_category' => [],
            'premium' => 0,
            'accessible' => 0,
        ];

        foreach ($templates as $template) {
            $category = $template['category'] ?? 'content';
            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;

            if ($template['is_premium'] ?? FALSE) {
                $stats['premium']++;
            }

            if ($this->isTemplateAccessible($template, $tenantPlan)) {
                $stats['accessible']++;
            }
        }

        return $stats;
    }

}
