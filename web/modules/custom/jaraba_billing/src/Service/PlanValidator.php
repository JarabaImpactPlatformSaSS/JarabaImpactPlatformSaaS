<?php

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para validar límites de planes SaaS.
 *
 * Verifica que los tenants no excedan los límites de su plan
 * (productores, storage, queries de IA, etc.)
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
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   El entity type manager.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger;
    }

    /**
     * Valida si un tenant puede añadir más productores.
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

        $limit = $plan->getLimit('productores', 0);

        // -1 significa ilimitado.
        if ($limit === -1) {
            return TRUE;
        }

        $current = $this->countProducers($tenant);
        return $current < $limit;
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

        $limit_gb = $plan->getLimit('storage_gb', 0);

        // -1 significa ilimitado.
        if ($limit_gb === -1) {
            return TRUE;
        }

        $limit_bytes = $limit_gb * 1024 * 1024 * 1024;
        $current = $this->calculateStorageUsage($tenant);

        return ($current + $additional_bytes) <= $limit_bytes;
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

        $limit = $plan->getLimit('ai_queries', 0);

        // 0 significa no incluido en el plan.
        if ($limit === 0) {
            return FALSE;
        }

        // -1 significa ilimitado.
        if ($limit === -1) {
            return TRUE;
        }

        $current = $this->countAiQueriesThisMonth($tenant);
        return $current < $limit;
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
        'crm_pipeline' => 'jaraba_crm',
        'crm_contacts' => 'jaraba_crm',
        'lead_scoring' => 'jaraba_crm',
        'email_campaigns' => 'jaraba_email',
        'email_sequences' => 'jaraba_email',
        'email_templates' => 'jaraba_email',
        'social_calendar' => 'jaraba_social',
        'social_posts' => 'jaraba_social',
        'ads_sync' => 'paid_ads_sync',
        'roas_tracking' => 'paid_ads_sync',
        'pixels_manager' => 'retargeting_pixels',
        'server_tracking' => 'retargeting_pixels',
        'events_create' => 'events_webinars',
        'webinar_integration' => 'events_webinars',
        'experiments' => 'ab_testing',
        'ab_variants' => 'ab_testing',
        'referral_codes' => 'referral_program',
        'rewards' => 'referral_program',
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

        return [
            'productores' => [
                'current' => $this->countProducers($tenant),
                'limit' => $limits['productores'] ?? 0,
                'unlimited' => ($limits['productores'] ?? 0) === -1,
            ],
            'storage_gb' => [
                'current' => round($this->calculateStorageUsage($tenant) / (1024 * 1024 * 1024), 2),
                'limit' => $limits['storage_gb'] ?? 0,
                'unlimited' => ($limits['storage_gb'] ?? 0) === -1,
            ],
            'ai_queries' => [
                'current' => $this->countAiQueriesThisMonth($tenant),
                'limit' => $limits['ai_queries'] ?? 0,
                'unlimited' => ($limits['ai_queries'] ?? 0) === -1,
                'included' => ($limits['ai_queries'] ?? 0) !== 0,
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

        switch ($action) {
            case 'add_producer':
                $allowed = $this->canAddProducer($tenant);
                $limit = $plan->getLimit('productores', 0);
                $current = $this->countProducers($tenant);
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : "Producer limit reached ({$current}/{$limit}).",
                    'usage' => ['current' => $current, 'limit' => $limit],
                ];

            case 'use_storage':
                $bytes = $params['bytes'] ?? 0;
                $allowed = $this->canUseStorage($tenant, $bytes);
                $limitGb = $plan->getLimit('storage_gb', 0);
                $currentGb = round($this->calculateStorageUsage($tenant) / (1024 * 1024 * 1024), 2);
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : "Storage limit reached ({$currentGb}/{$limitGb} GB).",
                    'usage' => ['current_gb' => $currentGb, 'limit_gb' => $limitGb],
                ];

            case 'ai_query':
                $allowed = $this->canUseAiQuery($tenant);
                $limit = $plan->getLimit('ai_queries', 0);
                $current = $this->countAiQueriesThisMonth($tenant);
                return [
                    'allowed' => $allowed,
                    'reason' => $allowed ? NULL : "AI query limit reached ({$current}/{$limit} this month).",
                    'usage' => ['current' => $current, 'limit' => $limit],
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
            $errors[] = t('El plan @plan permite máximo @limit productores y tienes @current.', [
                '@plan' => $new_plan->getName(),
                '@limit' => $new_limit,
                '@current' => $current_producers,
            ]);
        }

        // Verificar límite de storage.
        $current_storage = $this->calculateStorageUsage($tenant);
        $new_storage_limit = $new_plan->getLimit('storage_gb', 0) * 1024 * 1024 * 1024;

        if ($new_plan->getLimit('storage_gb', 0) !== -1 && $current_storage > $new_storage_limit) {
            $errors[] = t('El plan @plan permite máximo @limit GB y usas @current GB.', [
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
