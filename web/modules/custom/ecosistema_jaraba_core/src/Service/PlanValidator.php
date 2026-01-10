<?php

namespace Drupal\ecosistema_jaraba_core\Service;

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
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Bytes usados.
     */
    public function calculateStorageUsage(TenantInterface $tenant): int
    {
        // TODO: Implementar cálculo real basado en archivos del tenant.
        // Por ahora retornamos 0.
        return 0;
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
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Número de queries este mes.
     */
    public function countAiQueriesThisMonth(TenantInterface $tenant): int
    {
        // TODO: Implementar contador real.
        // Por ahora retornamos 0.
        return 0;
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
        if (!$plan->hasFeature($feature)) {
            return FALSE;
        }

        // Verificar si la vertical del tenant tiene la feature habilitada.
        $vertical = $tenant->getVertical();
        if ($vertical && !$vertical->hasFeature($feature)) {
            return FALSE;
        }

        return TRUE;
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
