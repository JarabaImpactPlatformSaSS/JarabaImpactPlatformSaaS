<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para validar limites de planes SaaS.
 *
 * Verifica que los tenants no excedan los limites de su plan
 * (productores, storage, queries de IA, etc.)
 *
 * F2 Integration: Consulta FreemiumVerticalLimit via UpgradeTriggerService
 * para aplicar limites especificos por vertical y plan. Cuando existe un
 * FreemiumVerticalLimit configurado, este tiene prioridad sobre el limite
 * generico del SaasPlan. Cuando no existe, se usa el limite del plan como
 * fallback (backwards compatible).
 *
 * NOTE: This is the ecosistema_jaraba_core copy. The canonical version
 * lives in jaraba_billing. This file is kept in sync for environments
 * where jaraba_billing is not installed.
 */
class PlanValidator
{

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
     * Nullable para backwards compatibility.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService|null
     */
    protected ?UpgradeTriggerService $upgradeTriggerService;

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
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        LoggerInterface $logger,
        ?UpgradeTriggerService $upgrade_trigger_service = null,
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger;
        $this->upgradeTriggerService = $upgrade_trigger_service;
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
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null $tenant
     *   (Optional) The tenant, required for firing upgrade triggers.
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
        if ($this->upgradeTriggerService === null || $vertical === '' || $plan === '') {
            return $fallback;
        }

        return $this->upgradeTriggerService->getLimitValue($vertical, $plan, $featureKey, $fallback);
    }

    /**
     * Extracts vertical and plan IDs from a tenant.
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
     * Valida si un tenant puede anadir mas productores.
     *
     * F2: Consults FreemiumVerticalLimit for 'productores' when available.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     *
     * @return bool
     *   TRUE si puede anadir mas productores.
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
            $verticalId, $planId, 'productores', $current, $planLimit, $tenant,
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
     *   Numero de productores.
     */
    public function countProducers(TenantInterface $tenant): int
    {
        $query = $this->entityTypeManager
            ->getStorage('user')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('field_tenant', $tenant->id());

        return (int) $query->count()->execute();
    }

    /**
     * Valida si un tenant puede usar mas storage.
     *
     * F2: Consults FreemiumVerticalLimit for 'storage_gb' when available.
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

        $effectiveLimitGb = $this->resolveEffectiveLimit($verticalId, $planId, 'storage_gb', $planLimitGb);

        if ($effectiveLimitGb === -1) {
            return TRUE;
        }

        $limitBytes = $effectiveLimitGb * 1024 * 1024 * 1024;
        $current = $this->calculateStorageUsage($tenant);

        $allowed = ($current + $additional_bytes) <= $limitBytes;

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
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Bytes usados.
     */
    public function calculateStorageUsage(TenantInterface $tenant): int
    {
        try {
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

            foreach (array_chunk($nodeIds, 50) as $batch) {
                $nodes = $nodeStorage->loadMultiple($batch);
                foreach ($nodes as $node) {
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
        }
        catch (\Exception $e) {
            $this->logger->error('Error calculating storage for tenant @id: @error', [
                '@id' => $tenant->id(),
                '@error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Valida si un tenant puede realizar mas queries de IA.
     *
     * F2: Consults FreemiumVerticalLimit for 'ai_queries' when available.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a validar.
     *
     * @return bool
     *   TRUE si puede realizar mas queries.
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
            $verticalId, $planId, 'ai_queries', $current, $planLimit, $tenant,
        );

        return $result['allowed'];
    }

    /**
     * Cuenta las queries de IA del mes actual.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Numero de queries este mes.
     */
    public function countAiQueriesThisMonth(TenantInterface $tenant): int
    {
        try {
            $month = date('Y-m');
            $stateKey = "ai_usage_queries_{$tenant->id()}_{$month}";
            return (int) \Drupal::state()->get($stateKey, 0);
        }
        catch (\Exception $e) {
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
     *   TRUE si la feature esta disponible.
     */
    public function hasFeature(TenantInterface $tenant, string $feature): bool
    {
        if (!$tenant->isActive()) {
            return FALSE;
        }

        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return FALSE;
        }

        if (!$plan->hasFeature($feature)) {
            return FALSE;
        }

        $vertical = $tenant->getVertical();
        if ($vertical && !$vertical->hasFeature($feature)) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Obtiene un resumen del uso actual vs limites.
     *
     * F2: Limits shown reflect the effective vertical-aware values.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return array
     *   Array con uso actual y limites.
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
     * Verifica si un cambio de plan es valido.
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

        $current_producers = $this->countProducers($tenant);
        $new_limit = $new_plan->getLimit('productores', 0);

        if ($new_limit !== -1 && $current_producers > $new_limit) {
            $errors[] = t('El plan @plan permite maximo @limit productores y tienes @current.', [
                '@plan' => $new_plan->getName(),
                '@limit' => $new_limit,
                '@current' => $current_producers,
            ]);
        }

        $current_storage = $this->calculateStorageUsage($tenant);
        $new_storage_limit = $new_plan->getLimit('storage_gb', 0) * 1024 * 1024 * 1024;

        if ($new_plan->getLimit('storage_gb', 0) !== -1 && $current_storage > $new_storage_limit) {
            $errors[] = t('El plan @plan permite maximo @limit GB y usas @current GB.', [
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
