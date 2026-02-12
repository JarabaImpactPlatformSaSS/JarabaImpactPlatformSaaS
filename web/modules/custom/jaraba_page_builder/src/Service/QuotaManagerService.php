<?php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\jaraba_billing\Service\PlanValidator;

/**
 * Servicio para gestionar cuotas y límites por plan SaaS.
 *
 * PROPÓSITO:
 * Centraliza la lógica de verificación de límites para:
 * - Páginas por tenant
 * - Acceso a plantillas premium
 * - Bloques premium disponibles
 * - Funcionalidades avanzadas (A/B testing, SEO, etc.)
 */
class QuotaManagerService
{

    use StringTranslationTrait;

    /**
     * Servicio de resolucion de tenant.
     *
     * @var \Drupal\jaraba_page_builder\Service\TenantResolverService
     */
    protected TenantResolverService $tenantResolver;

    /**
     * Factoria de configuracion.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Validador central de limites de planes SaaS.
     *
     * PROPOSITO:
     * Delega la validacion de limites al servicio centralizado de billing
     * para mantener coherencia con el resto del ecosistema.
     * Inyeccion opcional (@?) para evitar dependencia dura si billing
     * no esta habilitado.
     *
     * @var \Drupal\jaraba_billing\Service\PlanValidator|null
     */
    protected ?PlanValidator $planValidator;

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_page_builder\Service\TenantResolverService $tenant_resolver
     *   Resolver de tenant.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   Factoria de configuracion.
     * @param \Drupal\jaraba_billing\Service\PlanValidator|null $plan_validator
     *   Validador de planes (opcional, inyectado si jaraba_billing esta activo).
     */
    public function __construct(
        TenantResolverService $tenant_resolver,
        ConfigFactoryInterface $config_factory,
        ?PlanValidator $plan_validator = NULL,
    ) {
        $this->tenantResolver = $tenant_resolver;
        $this->configFactory = $config_factory;
        $this->planValidator = $plan_validator;
    }

    /**
     * Verifica si el tenant actual puede crear una nueva pagina.
     *
     * LOGICA:
     * 1. Si PlanValidator esta disponible y el tenant implementa
     *    TenantInterface, delega la validacion al servicio centralizado
     *    de billing para mantener coherencia con el ecosistema.
     * 2. Si no, usa la logica local basada en TenantResolverService
     *    como fallback (para entornos donde billing no esta activo).
     *
     * @return array
     *   Array con 'allowed' (bool), 'message' (string si denegado),
     *   'remaining' (int paginas restantes si permitido).
     */
    public function checkCanCreatePage(): array
    {
        $current_count = $this->tenantResolver->getCurrentTenantPageCount();

        // Delegacion a PlanValidator centralizado (P1-01).
        if ($this->planValidator) {
            $tenant = $this->tenantResolver->getCurrentTenant();
            if ($tenant instanceof TenantInterface) {
                $result = $this->planValidator->enforceLimit($tenant, 'create_page', [
                    'current' => $current_count,
                ]);

                if (!$result['allowed']) {
                    return [
                        'allowed' => FALSE,
                        'message' => $this->t('Has alcanzado el limite de @count paginas para tu plan. <a href="@upgrade">Mejora tu plan</a> para crear mas.', [
                            '@count' => $result['usage']['limit'] ?? 0,
                            '@upgrade' => '/upgrade-plan',
                        ]),
                    ];
                }

                $limit = $result['usage']['limit'] ?? -1;
                return [
                    'allowed' => TRUE,
                    'message' => '',
                    'remaining' => $limit === -1 ? -1 : $limit - $current_count,
                ];
            }
        }

        // Fallback: logica local via TenantResolverService.
        $limit = $this->tenantResolver->getPageLimit();

        if ($limit === -1) {
            return ['allowed' => TRUE, 'message' => '', 'remaining' => -1];
        }

        if ($current_count >= $limit) {
            return [
                'allowed' => FALSE,
                'message' => $this->t('Has alcanzado el limite de @count paginas para tu plan. <a href="@upgrade">Mejora tu plan</a> para crear mas.', [
                    '@count' => $limit,
                    '@upgrade' => '/upgrade-plan',
                ]),
            ];
        }

        return [
            'allowed' => TRUE,
            'message' => '',
            'remaining' => $limit - $current_count,
        ];
    }

    /**
     * Obtiene las capacidades del plan actual.
     *
     * @return array
     *   Capacidades del plan.
     */
    public function getPlanCapabilities(): array
    {
        $plan = $this->tenantResolver->getCurrentTenantPlan();
        $config = $this->configFactory->get('jaraba_page_builder.settings');

        // Definiciones por defecto.
        $capabilities = [
            'starter' => [
                'max_pages' => 5,
                'basic_templates' => 10,
                'premium_templates' => 0,
                'basic_blocks' => 15,
                'premium_blocks' => 0,
                'seo_advanced' => FALSE,
                'ab_testing' => FALSE,
                'ab_test_limit' => 0,
                'schema_org' => FALSE,
                'analytics' => FALSE,
            ],
            'professional' => [
                'max_pages' => 25,
                'basic_templates' => 25,
                'premium_templates' => 8,
                'basic_blocks' => 35,
                'premium_blocks' => 10,
                'seo_advanced' => TRUE,
                'ab_testing' => TRUE,
                'ab_test_limit' => 3,
                'schema_org' => FALSE,
                'analytics' => TRUE,
            ],
            'enterprise' => [
                'max_pages' => -1,
                'basic_templates' => 55,
                'premium_templates' => 22,
                'basic_blocks' => 45,
                'premium_blocks' => 22,
                'seo_advanced' => TRUE,
                'ab_testing' => TRUE,
                'ab_test_limit' => -1,
                'schema_org' => TRUE,
                'analytics' => TRUE,
            ],
        ];

        // Obtener configuración personalizada si existe.
        $custom_limits = $config->get('page_limits') ?? [];

        $plan_capabilities = $capabilities[$plan] ?? $capabilities['starter'];

        // Sobrescribir límite de páginas si está configurado.
        if (isset($custom_limits[$plan])) {
            $plan_capabilities['max_pages'] = $custom_limits[$plan];
        }

        return $plan_capabilities;
    }

    /**
     * Obtiene el resumen de uso actual del tenant.
     *
     * @return array
     *   Resumen de uso con 'used' y 'limit' por recurso.
     */
    public function getUsageSummary(): array
    {
        $capabilities = $this->getPlanCapabilities();
        $current_pages = $this->tenantResolver->getCurrentTenantPageCount();

        return [
            'pages' => [
                'used' => $current_pages,
                'limit' => $capabilities['max_pages'],
                'unlimited' => $capabilities['max_pages'] === -1,
                'percentage' => $capabilities['max_pages'] > 0
                    ? round(($current_pages / $capabilities['max_pages']) * 100, 1)
                    : 0,
            ],
            'features' => [
                'seo_advanced' => $capabilities['seo_advanced'],
                'ab_testing' => $capabilities['ab_testing'],
                'schema_org' => $capabilities['schema_org'],
                'analytics' => $capabilities['analytics'],
            ],
            'plan' => $this->tenantResolver->getCurrentTenantPlan(),
        ];
    }

    /**
     * Verifica si una funcionalidad especifica esta disponible.
     *
     * LOGICA:
     * Si PlanValidator esta disponible, verifica contra el plan del tenant
     * y sus add-ons activos (via enforceLimit con prefijo 'feature:').
     * Como fallback, consulta las capacidades locales del plan.
     *
     * @param string $feature
     *   Nombre de la funcionalidad (ej: 'seo_advanced', 'ab_testing',
     *   'premium_blocks').
     *
     * @return bool
     *   TRUE si esta disponible en el plan actual o via add-on.
     */
    public function hasFeature(string $feature): bool
    {
        // Delegacion a PlanValidator para verificacion plan + add-ons (P1-01).
        if ($this->planValidator) {
            $tenant = $this->tenantResolver->getCurrentTenant();
            if ($tenant instanceof TenantInterface) {
                return $this->planValidator->hasFeature($tenant, $feature);
            }
        }

        // Fallback: capacidades locales hardcodeadas.
        $capabilities = $this->getPlanCapabilities();
        return !empty($capabilities[$feature]);
    }

    /**
     * Filtra plantillas disponibles según el plan actual.
     *
     * @param array $templates
     *   Array de plantillas PageTemplate.
     *
     * @return array
     *   Plantillas filtradas según acceso.
     */
    public function filterAccessibleTemplates(array $templates): array
    {
        return array_filter($templates, function ($template) {
            return $this->tenantResolver->hasAccessToTemplate($template);
        });
    }

    /**
     * Marca plantillas con su estado de acceso.
     *
     * @param array $templates
     *   Array de plantillas PageTemplate.
     *
     * @return array
     *   Plantillas con '_accessible' y '_upgrade_required' añadidos.
     */
    public function markTemplatesAccessibility(array $templates): array
    {
        $plan = $this->tenantResolver->getCurrentTenantPlan();

        return array_map(function ($template) use ($plan) {
            $has_access = $this->tenantResolver->hasAccessToTemplate($template);
            return [
                'template' => $template,
                'accessible' => $has_access,
                'upgrade_required' => !$has_access,
                'required_plan' => $has_access ? NULL : $this->getMinimumRequiredPlan($template),
            ];
        }, $templates);
    }

    /**
     * Obtiene el plan mínimo requerido para una plantilla.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La plantilla.
     *
     * @return string|null
     *   ID del plan mínimo o NULL.
     */
    protected function getMinimumRequiredPlan($template): ?string
    {
        $required = $template->getPlansRequired();

        $plan_order = ['starter', 'professional', 'enterprise'];

        foreach ($plan_order as $plan) {
            if (in_array($plan, $required, TRUE)) {
                return $plan;
            }
        }

        return NULL;
    }

}
