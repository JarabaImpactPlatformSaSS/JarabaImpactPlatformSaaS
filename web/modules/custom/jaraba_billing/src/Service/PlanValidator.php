<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService;
use Psr\Log\LoggerInterface;

/**
 * Servicio para validar límites de planes SaaS.
 *
 * Verifica que los tenants no excedan los límites de su plan
 * (productores, storage, queries de IA, etc.)
 *
 * F2 Integration: Consulta FreemiumVerticalLimit via UpgradeTriggerService
 * para aplicar limites especificos por vertical y plan. Cuando existe un
 * FreemiumVerticalLimit configurado, este tiene prioridad sobre el limite
 * generico del SaasPlan. Cuando no existe, se usa el limite del plan como
 * fallback (backwards compatible).
 */
class PlanValidator
{

    use StringTranslationTrait;

    /**
     * El entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Servicio de triggers de upgrade (F2 freemium model).
     *
     * Nullable para backwards compatibility: cuando ecosistema_jaraba_core
     * no esta instalado o el servicio no esta disponible, PlanValidator
     * funciona sin consultar FreemiumVerticalLimit.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService|null
     */
    protected ?UpgradeTriggerService $upgradeTriggerService;

    /**
     * Servicio de resolucion de planes (Precios Configurables v2.1).
     *
     * Nullable para backwards compatibility: cuando no disponible,
     * PlanValidator sigue usando FreemiumVerticalLimit como fuente.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\PlanResolverService|null
     */
    protected ?PlanResolverService $planResolver;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   El entity type manager.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger.
     * @param \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService|null $upgrade_trigger_service
     *   (Optional) Servicio de triggers de upgrade para consultar limites
     *   FreemiumVerticalLimit por vertical+plan. NULL si no disponible.
     * @param \Drupal\ecosistema_jaraba_core\Service\PlanResolverService|null $plan_resolver
     *   (Optional) Servicio de resolucion de planes para consultar
     *   SaasPlanFeatures como fuente adicional de limites.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        LoggerInterface $logger,
        ?UpgradeTriggerService $upgrade_trigger_service = null,
        ?PlanResolverService $plan_resolver = null,
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger;
        $this->upgradeTriggerService = $upgrade_trigger_service;
        $this->planResolver = $plan_resolver;
    }

    // =========================================================================
    // F2 INTEGRATION: FreemiumVerticalLimit via UpgradeTriggerService.
    // =========================================================================

    /**
     * Enforces a vertical-aware limit for a specific feature.
     *
     * Consults FreemiumVerticalLimit ConfigEntity (via UpgradeTriggerService)
     * to determine the effective limit for the given vertical+plan+featureKey
     * combination. If no FreemiumVerticalLimit exists, falls back to the
     * provided $fallbackLimit (typically from SaasPlan::getLimit()).
     *
     * When currentUsage >= effectiveLimit, fires a 'limit_reached' upgrade
     * trigger for PLG conversion analytics.
     *
     * @param string $vertical
     *   Machine name of the vertical (e.g., 'agroconecta', 'comercioconecta').
     * @param string $plan
     *   Machine name of the plan (e.g., 'free', 'starter', 'profesional').
     * @param string $featureKey
     *   Key of the limited resource (e.g., 'productores', 'ai_queries').
     * @param int $currentUsage
     *   Current usage count for the resource.
     * @param int $fallbackLimit
     *   Limit value to use when no FreemiumVerticalLimit is configured.
     *   Typically the value from SaasPlan::getLimit().
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null $tenant
     *   (Optional) The tenant, required for firing upgrade triggers.
     *   When NULL, triggers are not fired (useful for dry-run checks).
     *
     * @return array{allowed: bool, trigger: array|null, effective_limit: int}
     *   - allowed: TRUE if currentUsage < effectiveLimit (or unlimited).
     *   - trigger: Upgrade trigger data array if limit was reached, NULL otherwise.
     *   - effective_limit: The resolved limit value (-1=unlimited, 0=disabled).
     */
    public function enforceVerticalLimit(
        string $vertical,
        string $plan,
        string $featureKey,
        int $currentUsage,
        int $fallbackLimit = -1,
        ?TenantInterface $tenant = null,
    ): array {
        // Resolve effective limit: FreemiumVerticalLimit > SaasPlan fallback.
        $effectiveLimit = $this->resolveEffectiveLimit($vertical, $plan, $featureKey, $fallbackLimit);

        // -1 = unlimited, always allowed.
        if ($effectiveLimit === -1) {
            return [
                'allowed' => TRUE,
                'trigger' => NULL,
                'effective_limit' => -1,
            ];
        }

        // 0 = not included, always blocked.
        if ($effectiveLimit === 0) {
            $trigger = NULL;
            if ($tenant !== null && $this->upgradeTriggerService !== null) {
                $trigger = $this->upgradeTriggerService->fire('feature_blocked', $tenant, [
                    'feature_key' => $featureKey,
                    'current_usage' => $currentUsage,
                ]);
            }
            return [
                'allowed' => FALSE,
                'trigger' => $trigger,
                'effective_limit' => 0,
            ];
        }

        // Numeric limit: check usage against it.
        $allowed = $currentUsage < $effectiveLimit;
        $trigger = NULL;

        if (!$allowed && $tenant !== null && $this->upgradeTriggerService !== null) {
            $trigger = $this->upgradeTriggerService->fire('limit_reached', $tenant, [
                'feature_key' => $featureKey,
                'current_usage' => $currentUsage,
                'limit_value' => $effectiveLimit,
            ]);
        }

        return [
            'allowed' => $allowed,
            'trigger' => $trigger,
            'effective_limit' => $effectiveLimit,
        ];
    }

    /**
     * Resolves the effective limit for a vertical+plan+feature combination.
     *
     * Checks FreemiumVerticalLimit first (via UpgradeTriggerService); if none
     * exists, returns the fallback value from the SaasPlan.
     *
     * @param string $vertical
     *   Machine name of the vertical.
     * @param string $plan
     *   Machine name of the plan.
     * @param string $featureKey
     *   Key of the limited resource.
     * @param int $fallback
     *   Value to return when no FreemiumVerticalLimit is configured.
     *
     * @return int
     *   The effective limit (-1=unlimited, 0=disabled, >0=max count).
     */
    protected function resolveEffectiveLimit(string $vertical, string $plan, string $featureKey, int $fallback): int
    {
        // 1. FreemiumVerticalLimit via UpgradeTriggerService (highest priority).
        if ($this->upgradeTriggerService !== null && $vertical !== '' && $plan !== '') {
            $fvlLimit = $this->upgradeTriggerService->getLimitValue($vertical, $plan, $featureKey, -999);
            if ($fvlLimit !== -999) {
                return $fvlLimit;
            }
        }

        // 2. SaasPlanFeatures via PlanResolverService (Precios Configurables v2.1).
        if ($this->planResolver !== null && $vertical !== '' && $plan !== '') {
            $tier = $this->planResolver->normalize($plan);
            $resolvedLimit = $this->planResolver->checkLimit($vertical, $tier, $featureKey, -999);
            if ($resolvedLimit !== -999) {
                return $resolvedLimit;
            }
        }

        // 3. SaasPlan fallback.
        return $fallback;
    }

    /**
     * Extracts vertical and plan IDs from a tenant.
     *
     * Helper method for the vertical-aware can* methods. Returns empty strings
     * when the tenant has no vertical or plan assigned (causing fallback to
     * SaasPlan limits).
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   The tenant.
     *
     * @return array{string, string}
     *   [verticalId, planId].
     */
    protected function extractVerticalAndPlan(TenantInterface $tenant): array
    {
        $vertical = $tenant->getVertical();
        $plan = $tenant->getSubscriptionPlan();

        $verticalId = $vertical?->id() ?? '';
        $planId = $plan?->id() ?? '';

        return [(string) $verticalId, (string) $planId];
    }

    // =========================================================================
    // CORE LIMIT CHECKS (updated with vertical-aware logic).
    // =========================================================================

    /**
     * Valida si un tenant puede añadir más productores.
     *
     * F2: When a FreemiumVerticalLimit exists for the tenant's
     * vertical+plan+'productores', that limit takes precedence over the
     * SaasPlan limit. Fires 'limit_reached' trigger when blocked.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     *
     * @return bool
     *   TRUE si puede añadir más productores.
     */
    public function canAddProducer(TenantInterface $tenant): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('productores', 0);
        $current = $this->countProducers($tenant);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $result = $this->enforceVerticalLimit(
            $verticalId,
            $planId,
            'productores',
            $current,
            $planLimit,
            $tenant,
        );

        return $result['allowed'];
    }

    /**
     * Cuenta los productores de un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Número de productores.
     */
    public function countProducers(TenantInterface $tenant): int
    {
        // Asumiendo que productores son usuarios con rol específico en el Group del tenant.
        // Esta implementación se adaptará al Group Module.
        $query = $this->entityTypeManager
            ->getStorage('user')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('field_tenant', $tenant->id());

        return (int) $query->count()->execute();
    }

    /**
     * Valida si un tenant puede usar más storage.
     *
     * F2: Consults FreemiumVerticalLimit for 'storage_gb' when available.
     * The vertical limit value is in GB (same unit as SaasPlan).
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     * @param int $additional_bytes
     *   Bytes adicionales que se quieren usar.
     *
     * @return bool
     *   TRUE si hay espacio disponible.
     */
    public function canUseStorage(TenantInterface $tenant, int $additional_bytes = 0): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimitGb = $plan->getLimit('storage_gb', 0);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        // Resolve effective limit in GB via FreemiumVerticalLimit.
        $effectiveLimitGb = $this->resolveEffectiveLimit($verticalId, $planId, 'storage_gb', $planLimitGb);

        // -1 = unlimited.
        if ($effectiveLimitGb === -1) {
            return TRUE;
        }

        $limitBytes = $effectiveLimitGb * 1024 * 1024 * 1024;
        $current = $this->calculateStorageUsage($tenant);

        $allowed = ($current + $additional_bytes) <= $limitBytes;

        // Fire trigger when limit reached.
        if (!$allowed && $this->upgradeTriggerService !== null) {
            $this->upgradeTriggerService->fire('limit_reached', $tenant, [
                'feature_key' => 'storage_gb',
                'current_usage' => (int) round($current / (1024 * 1024 * 1024)),
                'limit_value' => $effectiveLimitGb,
            ]);
        }

        return $allowed;
    }

    /**
     * Calcula el uso de almacenamiento de un tenant.
     *
     * Cuenta el tamaño total de archivos gestionados (file_managed)
     * asociados al tenant vía el campo field_tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Bytes usados.
     */
    public function calculateStorageUsage(TenantInterface $tenant): int
    {
        try {
            // Obtener todos los archivos asociados a nodos del tenant.
            $nodeStorage = $this->entityTypeManager->getStorage('node');
            $nodeIds = $nodeStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('field_tenant', $tenant->id())
                ->execute();

            if (empty($nodeIds)) {
                return 0;
            }

            $totalBytes = 0;
            $fileStorage = $this->entityTypeManager->getStorage('file');

            // Procesar en lotes para evitar problemas de memoria.
            foreach (array_chunk($nodeIds, 50) as $batch) {
                $nodes = $nodeStorage->loadMultiple($batch);
                foreach ($nodes as $node) {
                    // Recorrer todos los campos de tipo archivo/imagen del nodo.
                    foreach ($node->getFieldDefinitions() as $fieldName => $definition) {
                        $fieldType = $definition->getType();
                        if (in_array($fieldType, ['file', 'image'], TRUE) && !$node->get($fieldName)->isEmpty()) {
                            foreach ($node->get($fieldName) as $item) {
                                $fileId = $item->target_id;
                                if ($fileId) {
                                    $file = $fileStorage->load($fileId);
                                    if ($file) {
                                        $totalBytes += (int) $file->getSize();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $totalBytes;
        } catch (\Exception $e) {
            $this->logger->error('Error calculating storage for tenant @id: @error', [
                '@id' => $tenant->id(),
                '@error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Valida si un tenant puede realizar más queries de IA.
     *
     * F2: Consults FreemiumVerticalLimit for 'ai_queries' when available.
     * This enables per-vertical AI query caps (e.g., agroconecta free plan
     * gets 20 queries/month vs comercioconecta free plan gets 50).
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     *
     * @return bool
     *   TRUE si puede realizar más queries.
     */
    public function canUseAiQuery(TenantInterface $tenant): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('ai_queries', 0);
        $current = $this->countAiQueriesThisMonth($tenant);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $result = $this->enforceVerticalLimit(
            $verticalId,
            $planId,
            'ai_queries',
            $current,
            $planLimit,
            $tenant,
        );

        return $result['allowed'];
    }

    /**
     * Cuenta las queries de IA del mes actual.
     *
     * Usa el state API de Drupal donde el AIUsageLimitService
     * registra los contadores mensuales por tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Número de queries este mes.
     */
    public function countAiQueriesThisMonth(TenantInterface $tenant): int
    {
        try {
            $month = date('Y-m');
            $stateKey = "ai_usage_queries_{$tenant->id()}_{$month}";
            return (int) \Drupal::state()->get($stateKey, 0);
        } catch (\Exception $e) {
            $this->logger->error('Error counting AI queries for tenant @id: @error', [
                '@id' => $tenant->id(),
                '@error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Verifica si un tenant tiene acceso a una feature.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param string $feature
     *   El identificador de la feature.
     *
     * @return bool
     *   TRUE si la feature está disponible.
     */
    /**
     * Mapeo de features a códigos de add-on para verificación en PlanValidator.
     *
     * Se usa query directa a TenantAddon para evitar dependencia circular
     * con FeatureAccessService.
     */
    protected const FEATURE_ADDON_MAP = [
        // CRM.
        'crm_pipeline' => 'jaraba_crm',
        'crm_contacts' => 'jaraba_crm',
        'lead_scoring' => 'jaraba_crm',
        // Email Marketing.
        'email_campaigns' => 'jaraba_email',
        'email_sequences' => 'jaraba_email',
        'email_templates' => 'jaraba_email',
        // Social Media.
        'social_calendar' => 'jaraba_social',
        'social_posts' => 'jaraba_social',
        // Paid Ads.
        'ads_sync' => 'paid_ads_sync',
        'roas_tracking' => 'paid_ads_sync',
        // Retargeting.
        'pixels_manager' => 'retargeting_pixels',
        'server_tracking' => 'retargeting_pixels',
        // Events.
        'events_create' => 'events_webinars',
        'webinar_integration' => 'events_webinars',
        // A/B Testing.
        'experiments' => 'ab_testing',
        'ab_variants' => 'ab_testing',
        // Referral.
        'referral_codes' => 'referral_program',
        'rewards' => 'referral_program',
        // Page Builder — P1-01: Límites universales.
        'premium_blocks' => 'page_builder_premium',
        'page_builder_seo' => 'page_builder_seo',
        'page_builder_analytics' => 'page_builder_analytics',
        'page_builder_schema_org' => 'page_builder_seo',
        // Credentials — P1-02: Límites de credenciales.
        'credential_stacks' => 'credentials_advanced',
        'credential_portability' => 'credentials_advanced',
    ];

    public function hasFeature(TenantInterface $tenant, string $feature): bool
    {
        // Primero verificar si el tenant está activo.
        if (!$tenant->isActive()) {
            return FALSE;
        }

        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        // Verificar si la feature está en el plan.
        if ($plan->hasFeature($feature)) {
            // Verificar si la vertical del tenant tiene la feature habilitada.
            $vertical = $tenant->getVertical();
            if ($vertical && !$vertical->hasFeature($feature)) {
                return FALSE;
            }
            return TRUE;
        }

        // Feature not in base plan: check active add-ons.
        $addonCode = self::FEATURE_ADDON_MAP[$feature] ?? NULL;
        if ($addonCode) {
            try {
                $addonStorage = $this->entityTypeManager->getStorage('tenant_addon');
                $addons = $addonStorage->loadByProperties([
                    'tenant_id' => $tenant->id(),
                    'addon_code' => $addonCode,
                    'status' => 'active',
                ]);
                if (!empty($addons)) {
                    return TRUE;
                }
            }
            catch (\Exception $e) {
                $this->logger->warning('Error checking add-on @code for tenant @id: @error', [
                    '@code' => $addonCode,
                    '@id' => $tenant->id(),
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return FALSE;
    }

    /**
     * Obtiene las features disponibles para un tenant por su ID.
     *
     * @param string $tenant_id
     *   ID del grupo tenant.
     *
     * @return array
     *   Lista de features disponibles en el plan del tenant.
     */
    public function getAvailableFeatures(string $tenant_id): array
    {
        try {
            $tenants = $this->entityTypeManager
                ->getStorage('tenant')
                ->loadByProperties(['id' => $tenant_id]);

            if (empty($tenants)) {
                return [];
            }

            /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant */
            $tenant = reset($tenants);

            if (!$tenant->isActive()) {
                return [];
            }

            $plan = $tenant->getSubscriptionPlan();
            if (!$plan) {
                return [];
            }

            return $plan->getFeatures();
        } catch (\Exception $e) {
            $this->logger->warning('Error getting available features for tenant @id: @message', [
                '@id' => $tenant_id,
                '@message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtiene un resumen del uso actual vs límites.
     *
     * F2: Limits shown reflect the effective vertical-aware values
     * (FreemiumVerticalLimit when configured, SaasPlan otherwise).
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return array
     *   Array con uso actual y límites.
     */
    public function getUsageSummary(TenantInterface $tenant): array
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return [];
        }

        $limits = $plan->getLimits();
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $effectiveProductores = $this->resolveEffectiveLimit($verticalId, $planId, 'productores', $limits['productores'] ?? 0);
        $effectiveStorageGb = $this->resolveEffectiveLimit($verticalId, $planId, 'storage_gb', $limits['storage_gb'] ?? 0);
        $effectiveAiQueries = $this->resolveEffectiveLimit($verticalId, $planId, 'ai_queries', $limits['ai_queries'] ?? 0);

        return [
            'productores' => [
                'current' => $this->countProducers($tenant),
                'limit' => $effectiveProductores,
                'unlimited' => $effectiveProductores === -1,
            ],
            'storage_gb' => [
                'current' => round($this->calculateStorageUsage($tenant) / (1024 * 1024 * 1024), 2),
                'limit' => $effectiveStorageGb,
                'unlimited' => $effectiveStorageGb === -1,
            ],
            'ai_queries' => [
                'current' => $this->countAiQueriesThisMonth($tenant),
                'limit' => $effectiveAiQueries,
                'unlimited' => $effectiveAiQueries === -1,
                'included' => $effectiveAiQueries !== 0,
            ],
        ];
    }

    // =========================================================================
    // P1-01: Límites universales Page Builder.
    // =========================================================================

    /**
     * Verifica si un tenant puede crear una nueva pagina del Page Builder.
     *
     * F2: Consults FreemiumVerticalLimit for 'page_builder_pages'.
     *
     * LOGICA:
     * Consulta el campo 'page_builder_pages' del SaasPlan del tenant,
     * overridden by FreemiumVerticalLimit when configured.
     * -1 = ilimitado, 0 = no incluido, >0 = limite maximo.
     * El conteo actual se recibe como parametro porque la entidad
     * page_content pertenece al modulo jaraba_page_builder (evita
     * dependencia cruzada entre modulos).
     *
     * LIMITES POR DEFECTO:
     * - Starter: 5 paginas
     * - Professional: 25 paginas
     * - Enterprise: ilimitado (-1)
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     * @param int $current_page_count
     *   Numero actual de paginas del tenant (proporcionado por QuotaManagerService).
     *
     * @return bool
     *   TRUE si puede crear mas paginas.
     */
    public function canCreatePage(TenantInterface $tenant, int $current_page_count): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('page_builder_pages', 5);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $result = $this->enforceVerticalLimit(
            $verticalId, $planId, 'page_builder_pages', $current_page_count, $planLimit, $tenant,
        );

        return $result['allowed'];
    }

    /**
     * Verifica si un tenant puede usar bloques premium del Page Builder.
     *
     * F2: Consults FreemiumVerticalLimit for 'page_builder_premium_blocks'.
     *
     * LOGICA:
     * Los bloques premium (Aceternity UI, Magic UI) solo estan disponibles
     * en planes Professional y Enterprise. Se verifica via el campo
     * 'page_builder_premium_blocks' del plan, overridden by
     * FreemiumVerticalLimit when configured:
     * - 0 = no disponible (Starter)
     * - >0 = disponible (cantidad maxima de tipos accesibles)
     * - -1 = todos los bloques premium disponibles
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     *
     * @return bool
     *   TRUE si puede usar bloques premium.
     */
    public function canUsePremiumBlock(TenantInterface $tenant): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('page_builder_premium_blocks', 0);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);
        $effectiveLimit = $this->resolveEffectiveLimit($verticalId, $planId, 'page_builder_premium_blocks', $planLimit);

        return $effectiveLimit !== 0;
    }

    /**
     * Verifica si un tenant puede crear un nuevo experimento A/B.
     *
     * F2: Consults FreemiumVerticalLimit for 'page_builder_experiments'.
     *
     * LOGICA:
     * Los experimentos A/B tienen limite por plan, overridden by
     * FreemiumVerticalLimit when configured:
     * - -1 = ilimitado (Enterprise)
     * - 0 = no incluido (Starter)
     * - >0 = limite maximo activo (Professional: 3)
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     * @param int $current_experiment_count
     *   Numero actual de experimentos activos del tenant.
     *
     * @return bool
     *   TRUE si puede crear mas experimentos.
     */
    public function canCreateExperiment(TenantInterface $tenant, int $current_experiment_count): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('page_builder_experiments', 0);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $result = $this->enforceVerticalLimit(
            $verticalId, $planId, 'page_builder_experiments', $current_experiment_count, $planLimit, $tenant,
        );

        return $result['allowed'];
    }

    // =========================================================================
    // P1-02: Límites para Credentials.
    // =========================================================================

    /**
     * Verifica si un tenant puede emitir una nueva credencial.
     *
     * F2: Consults FreemiumVerticalLimit for 'credentials_per_month'.
     *
     * LOGICA:
     * El limite de credenciales se aplica mensualmente para controlar
     * el volumen de emision por plan, overridden by FreemiumVerticalLimit
     * when configured:
     * - Starter: 10/mes (suficiente para micro-academias)
     * - Professional: 100/mes (academias medianas, pymes formativas)
     * - Enterprise: ilimitado (-1) (universidades, grandes organizaciones)
     *
     * El conteo actual lo proporciona el modulo jaraba_credentials
     * para evitar dependencia cruzada.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     * @param int $current_monthly_count
     *   Numero de credenciales emitidas este mes por el tenant.
     *
     * @return bool
     *   TRUE si puede emitir mas credenciales.
     */
    public function canIssueCredential(TenantInterface $tenant, int $current_monthly_count): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('credentials_per_month', 10);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $result = $this->enforceVerticalLimit(
            $verticalId, $planId, 'credentials_per_month', $current_monthly_count, $planLimit, $tenant,
        );

        return $result['allowed'];
    }

    /**
     * Verifica si un tenant puede crear un nuevo stack de credenciales.
     *
     * F2: Consults FreemiumVerticalLimit for 'credential_stacks'.
     *
     * LOGICA:
     * Los stacks permiten agrupar credenciales en rutas formativas,
     * limit overridden by FreemiumVerticalLimit when configured.
     * - Starter: 0 stacks (feature no disponible)
     * - Professional: -1 (ilimitado)
     * - Enterprise: -1 (ilimitado)
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     * @param int $current_stack_count
     *   Numero actual de stacks del tenant.
     *
     * @return bool
     *   TRUE si puede crear mas stacks.
     */
    public function canCreateStack(TenantInterface $tenant, int $current_stack_count): bool
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        $planLimit = $plan->getLimit('credential_stacks', 0);
        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        $result = $this->enforceVerticalLimit(
            $verticalId, $planId, 'credential_stacks', $current_stack_count, $planLimit, $tenant,
        );

        return $result['allowed'];
    }

    // =========================================================================
    // ENFORCEMENT: Metodo universal de validacion de limites.
    // =========================================================================

    /**
     * BIZ-01: Enforces plan limits for a given action.
     *
     * Returns a structured result indicating whether the action is allowed,
     * with user-facing messages when limits are reached.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   The tenant.
     * @param string $action
     *   The action to validate: 'add_producer', 'use_storage', 'ai_query', 'feature:NAME'.
     * @param array $params
     *   Additional params (e.g., 'bytes' for storage, 'feature' name).
     *
     * @return array
     *   ['allowed' => bool, 'reason' => string|null, 'usage' => array]
     */
    public function enforceLimit(TenantInterface $tenant, string $action, array $params = []): array
    {
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return ['allowed' => FALSE, 'reason' => 'No subscription plan assigned.', 'usage' => []];
        }

        if (!$tenant->isActive() && $tenant->getSubscriptionStatus() !== TenantInterface::STATUS_TRIAL) {
            return ['allowed' => FALSE, 'reason' => 'Tenant subscription is not active.', 'usage' => []];
        }

        [$verticalId, $planId] = $this->extractVerticalAndPlan($tenant);

        switch ($action) {
            case 'add_producer':
                $planLimit = $plan->getLimit('productores', 0);
                $current = $this->countProducers($tenant);
                $verticalResult = $this->enforceVerticalLimit(
                    $verticalId, $planId, 'productores', $current, $planLimit, $tenant,
                );
                $effectiveLimit = $verticalResult['effective_limit'];
                $allowed = $verticalResult['allowed'];
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : "Producer limit reached ({$current}/{$effectiveLimit}).",
                    'usage' => ['current' => $current, 'limit' => $effectiveLimit],
                    'trigger' => $verticalResult['trigger'],
                ];

            case 'use_storage':
                $bytes = $params['bytes'] ?? 0;
                $allowed = $this->canUseStorage($tenant, $bytes);
                $planLimitGb = $plan->getLimit('storage_gb', 0);
                $effectiveLimitGb = $this->resolveEffectiveLimit($verticalId, $planId, 'storage_gb', $planLimitGb);
                $currentGb = round($this->calculateStorageUsage($tenant) / (1024 * 1024 * 1024), 2);
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : "Storage limit reached ({$currentGb}/{$effectiveLimitGb} GB).",
                    'usage' => ['current_gb' => $currentGb, 'limit_gb' => $effectiveLimitGb],
                ];

            case 'ai_query':
                $planLimit = $plan->getLimit('ai_queries', 0);
                $current = $this->countAiQueriesThisMonth($tenant);
                $verticalResult = $this->enforceVerticalLimit(
                    $verticalId, $planId, 'ai_queries', $current, $planLimit, $tenant,
                );
                $effectiveLimit = $verticalResult['effective_limit'];
                $allowed = $verticalResult['allowed'];
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : "AI query limit reached ({$current}/{$effectiveLimit} this month).",
                    'usage' => ['current' => $current, 'limit' => $effectiveLimit],
                    'trigger' => $verticalResult['trigger'],
                ];

            // P1-01: Page Builder limits (F2 vertical-aware).
            case 'create_page':
                return $this->checkResourceLimit(
                    $plan, 'page_builder_pages', $params['current'] ?? 0, 5,
                    $this->t('Paginas'), $verticalId, $planId, $tenant,
                );

            case 'use_premium_block':
                $planLimit = $plan->getLimit('page_builder_premium_blocks', 0);
                $effectiveLimit = $this->resolveEffectiveLimit($verticalId, $planId, 'page_builder_premium_blocks', $planLimit);
                $allowed = $effectiveLimit !== 0;
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : (string) $this->t('Los bloques premium no estan incluidos en tu plan actual.'),
                    'usage' => ['limit' => $effectiveLimit],
                ];

            case 'create_experiment':
                return $this->checkResourceLimit(
                    $plan, 'page_builder_experiments', $params['current'] ?? 0, 0,
                    $this->t('Experimentos A/B'), $verticalId, $planId, $tenant,
                );

            // P1-02: Credentials limits (F2 vertical-aware).
            case 'issue_credential':
                return $this->checkResourceLimit(
                    $plan, 'credentials_per_month', $params['current'] ?? 0, 10,
                    $this->t('Credenciales este mes'), $verticalId, $planId, $tenant,
                );

            case 'create_stack':
                return $this->checkResourceLimit(
                    $plan, 'credential_stacks', $params['current'] ?? 0, 0,
                    $this->t('Stacks de credenciales'), $verticalId, $planId, $tenant,
                );

            default:
                // Feature-based check: 'feature:firma_digital'.
                if (str_starts_with($action, 'feature:')) {
                    $feature = substr($action, 8);
                    $allowed = $this->hasFeature($tenant, $feature);
                    return [
                        'allowed' => $allowed,
                        'reason' => $allowed ? NULL : "Feature '{$feature}' not available in current plan.",
                        'usage' => [],
                    ];
                }

                return ['allowed' => TRUE, 'reason' => NULL, 'usage' => []];
        }
    }

    /**
     * Verifica un limite de recurso generico contra el plan del tenant.
     *
     * PROPOSITO:
     * Metodo auxiliar que centraliza la logica de verificacion de limites
     * para cualquier recurso medible (paginas, experimentos, credenciales).
     * Utilizado internamente por enforceLimit() para los cases de P1-01/P1-02.
     *
     * F2: When $verticalId and $planId are provided, resolves the effective
     * limit from FreemiumVerticalLimit (falling back to SaasPlan limit).
     * Fires upgrade triggers when limits are reached.
     *
     * SEMANTICA DE VALORES DEL LIMITE:
     * - -1 = ilimitado (sin restriccion, plan Enterprise)
     * -  0 = no incluido en el plan (bloqueado, requiere upgrade o add-on)
     * - >0 = limite maximo permitido
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
     *   El plan SaaS del tenant.
     * @param string $limit_key
     *   Clave del limite en el plan (ej: 'page_builder_pages').
     * @param int $current_count
     *   Uso actual del recurso (proporcionado por el modulo que llama).
     * @param int $default_limit
     *   Limite por defecto si la clave no existe en el plan.
     * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $label
     *   Etiqueta legible del recurso para mensajes de error.
     * @param string $verticalId
     *   (Optional) Vertical ID for FreemiumVerticalLimit lookup.
     * @param string $planId
     *   (Optional) Plan ID for FreemiumVerticalLimit lookup.
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null $tenant
     *   (Optional) Tenant for firing upgrade triggers.
     *
     * @return array
     *   Array estructurado con 'allowed', 'reason', 'usage' y opcionalmente 'trigger'.
     */
    protected function checkResourceLimit(
        SaasPlanInterface $plan,
        string $limit_key,
        int $current_count,
        int $default_limit,
        $label,
        string $verticalId = '',
        string $planId = '',
        ?TenantInterface $tenant = null,
    ): array {
        $planLimit = $plan->getLimit($limit_key, $default_limit);

        // Resolve effective limit via FreemiumVerticalLimit when available.
        $limit = $this->resolveEffectiveLimit($verticalId, $planId, $limit_key, $planLimit);

        // Ilimitado.
        if ($limit === -1) {
            return [
                'allowed' => TRUE,
                'reason' => NULL,
                'usage' => ['current' => $current_count, 'limit' => -1],
            ];
        }

        // No incluido en el plan.
        if ($limit === 0) {
            $trigger = NULL;
            if ($tenant !== null && $this->upgradeTriggerService !== null) {
                $trigger = $this->upgradeTriggerService->fire('feature_blocked', $tenant, [
                    'feature_key' => $limit_key,
                    'current_usage' => $current_count,
                ]);
            }
            return [
                'allowed' => FALSE,
                'reason' => (string) $this->t('@label no esta incluido en tu plan actual.', ['@label' => $label]),
                'usage' => ['current' => $current_count, 'limit' => 0],
                'trigger' => $trigger,
            ];
        }

        // Verificar contra limite numerico.
        $allowed = $current_count < $limit;
        $trigger = NULL;
        if (!$allowed && $tenant !== null && $this->upgradeTriggerService !== null) {
            $trigger = $this->upgradeTriggerService->fire('limit_reached', $tenant, [
                'feature_key' => $limit_key,
                'current_usage' => $current_count,
                'limit_value' => $limit,
            ]);
        }

        return [
            'allowed' => $allowed,
            'reason' => $allowed ? NULL : (string) $this->t('Limite de @label alcanzado (@current/@limit).', [
                '@label' => $label,
                '@current' => $current_count,
                '@limit' => $limit,
            ]),
            'usage' => ['current' => $current_count, 'limit' => $limit],
            'trigger' => $trigger,
        ];
    }

    /**
     * Verifica si un cambio de plan es válido.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $new_plan
     *   El nuevo plan deseado.
     *
     * @return array
     *   Array con 'valid' => bool y 'errors' => array.
     */
    public function validatePlanChange(TenantInterface $tenant, SaasPlanInterface $new_plan): array
    {
        $errors = [];

        // Verificar límite de productores.
        $current_producers = $this->countProducers($tenant);
        $new_limit = $new_plan->getLimit('productores', 0);

        if ($new_limit !== -1 && $current_producers > $new_limit) {
            $errors[] = $this->t('El plan @plan permite maximo @limit productores y tienes @current.', [
                '@plan' => $new_plan->getName(),
                '@limit' => $new_limit,
                '@current' => $current_producers,
            ]);
        }

        // Verificar límite de storage.
        $current_storage = $this->calculateStorageUsage($tenant);
        $new_storage_limit = $new_plan->getLimit('storage_gb', 0) * 1024 * 1024 * 1024;

        if ($new_plan->getLimit('storage_gb', 0) !== -1 && $current_storage > $new_storage_limit) {
            $errors[] = $this->t('El plan @plan permite maximo @limit GB y usas @current GB.', [
                '@plan' => $new_plan->getName(),
                '@limit' => $new_plan->getLimit('storage_gb', 0),
                '@current' => round($current_storage / (1024 * 1024 * 1024), 2),
            ]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

}
