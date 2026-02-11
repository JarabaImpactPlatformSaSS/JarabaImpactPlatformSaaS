<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Servicio para resolver el contexto del Tenant actual.
 *
 * PROPÓSITO:
 * En una arquitectura multi-tenant, necesitamos identificar a qué tenant
 * pertenece el usuario actual. Este servicio proporciona métodos para:
 *
 * 1. Obtener el Tenant asociado al usuario logueado
 * 2. Calcular métricas de uso del tenant (productores, contenido, etc.)
 * 3. Verificar permisos específicos del tenant
 *
 * ESTRATEGIA DE RESOLUCIÓN:
 * El tenant se resuelve buscando al usuario actual como admin_user_id
 * de un Tenant. En el futuro, se podría extender para resolver por:
 * - Dominio actual (Domain Access)
 * - Membresía en Group
 * - Campo personalizado en el usuario
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\Tenant
 * @see \Drupal\ecosistema_jaraba_core\Controller\TenantDashboardController
 */
class TenantContextService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El proxy de cuenta actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * El canal de logger.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     * Cache del tenant actual para evitar consultas repetidas.
     *
     * @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null|false
     */
    protected $cachedTenant = FALSE;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   El proxy de cuenta actual.
     * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
     *   El canal de logger.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Obtiene el Tenant asociado al usuario actual.
     *
     * ESTRATEGIA DE BÚSQUEDA:
     * 1. Primero busca por admin_user_id (el usuario es admin del tenant)
     * 2. Si no encuentra, busca por membresía en Group (futuro)
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
     *   El tenant del usuario actual, o NULL si no tiene uno asociado.
     */
    public function getCurrentTenant(): ?TenantInterface
    {
        // Usar cache para evitar consultas repetidas en el mismo request
        if ($this->cachedTenant !== FALSE) {
            return $this->cachedTenant;
        }

        $uid = $this->currentUser->id();

        // Usuarios anónimos no tienen tenant
        if (!$uid) {
            $this->cachedTenant = NULL;
            return NULL;
        }

        try {
            $tenantStorage = $this->entityTypeManager->getStorage('tenant');

            // =========================================================
            // MÉTODO 1: Buscar por admin_user
            // El usuario es el administrador principal del tenant
            // =========================================================
            $tenants = $tenantStorage->loadByProperties([
                'admin_user' => $uid,
            ]);

            if (!empty($tenants)) {
                $this->cachedTenant = reset($tenants);
                return $this->cachedTenant;
            }

            // =========================================================
            // MÉTODO 2: Buscar por membresía en Group (futuro)
            // Descomentar cuando se implemente la resolución por Group
            // =========================================================
            // $this->cachedTenant = $this->findTenantByGroupMembership($uid);
            // if ($this->cachedTenant) {
            //     return $this->cachedTenant;
            // }

            $this->cachedTenant = NULL;
            return NULL;

        } catch (\Exception $e) {
            $this->logger->error(
                '🚫 Error resolviendo tenant para usuario @uid: @error',
                [
                    '@uid' => $uid,
                    '@error' => $e->getMessage(),
                ]
            );
            $this->cachedTenant = NULL;
            return NULL;
        }
    }

    /**
     * Calcula las métricas de uso del tenant.
     *
     * Devuelve un array con información sobre el consumo actual
     * del tenant comparado con los límites de su plan.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant del que calcular métricas.
     *
     * @return array
     *   Array asociativo con métricas:
     *   - 'productores': ['count' => N, 'limit' => M, 'percentage' => P]
     *   - 'almacenamiento': ['used' => X, 'limit' => Y, 'percentage' => P]
     *   - 'contenido': ['count' => N]
     */
    public function getUsageMetrics(TenantInterface $tenant): array
    {
        // BE-04: Cargar plan y grupo UNA sola vez para evitar N+1 queries.
        $plan = $tenant->getSubscriptionPlan();
        $group = $tenant->getGroup();

        // Decodificar límites del plan una sola vez.
        $planLimits = [];
        if ($plan) {
            $limitsRaw = $plan->get('limits')->value ?? '';
            if ($limitsRaw) {
                $planLimits = json_decode($limitsRaw, TRUE) ?? [];
            }
        }

        return [
            'productores' => $this->calculateMemberMetrics($group, $planLimits),
            'almacenamiento' => $this->calculateStorageMetrics($planLimits),
            'contenido' => $this->calculateContentMetrics($group),
        ];
    }

    /**
     * Calcula las métricas de miembros/productores del tenant.
     *
     * @param \Drupal\group\Entity\GroupInterface|null $group
     *   El grupo asociado al tenant.
     * @param array $planLimits
     *   Límites del plan ya decodificados.
     *
     * @return array
     *   Métricas de miembros.
     */
    protected function calculateMemberMetrics(?\Drupal\group\Entity\GroupInterface $group, array $planLimits): array
    {
        $count = 0;
        $limit = $planLimits['max_productores'] ?? 0;

        // BE-04: Usar countQuery en vez de cargar todas las membresías.
        if ($group) {
            try {
                $count = (int) $this->entityTypeManager
                    ->getStorage('group_relationship')
                    ->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('gid', $group->id())
                    ->condition('plugin_id', 'group_membership')
                    ->count()
                    ->execute();
            } catch (\Exception $e) {
                // Si hay error, el count queda en 0.
            }
        }

        $percentage = ($limit > 0) ? min(100, round(($count / $limit) * 100)) : 0;

        return [
            'count' => $count,
            'limit' => $limit,
            'percentage' => $percentage,
        ];
    }

    /**
     * Calcula las métricas de almacenamiento del tenant.
     *
     * @param array $planLimits
     *   Límites del plan ya decodificados.
     *
     * @return array
     *   Métricas de almacenamiento.
     */
    protected function calculateStorageMetrics(array $planLimits): array
    {
        // Límite en MB
        $limit = $planLimits['max_storage_mb'] ?? 1024;

        // TODO: Calcular uso real mediante file_managed o directorio físico.
        // Por ahora, simular un 30% de uso.
        $used = round($limit * 0.3);

        $percentage = ($limit > 0) ? min(100, round(($used / $limit) * 100)) : 0;

        return [
            'used_mb' => $used,
            'limit_mb' => $limit,
            'percentage' => $percentage,
            'used_formatted' => $this->formatBytes($used * 1024 * 1024),
            'limit_formatted' => $this->formatBytes($limit * 1024 * 1024),
        ];
    }

    /**
     * Calcula las métricas de contenido del tenant.
     *
     * @param \Drupal\group\Entity\GroupInterface|null $group
     *   El grupo asociado al tenant.
     *
     * @return array
     *   Métricas de contenido.
     */
    protected function calculateContentMetrics(?\Drupal\group\Entity\GroupInterface $group): array
    {
        $count = 0;

        if ($group) {
            try {
                $nodeStorage = $this->entityTypeManager->getStorage('node');
                $query = $nodeStorage->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('type', ['article', 'producto', 'productor'], 'IN');

                // TODO: Filtrar por grupo cuando gnode esté completamente configurado.
                $count = $query->count()->execute();

            } catch (\Exception $e) {
                // Si hay error, el count queda en 0.
            }
        }

        return [
            'count' => $count,
        ];
    }

    /**
     * Formatea bytes a una cadena legible (KB, MB, GB).
     *
     * @param int $bytes
     *   Número de bytes.
     *
     * @return string
     *   Cadena formateada.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Verifica si el usuario actual tiene acceso al tenant especificado.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a verificar.
     *
     * @return bool
     *   TRUE si el usuario tiene acceso.
     */
    public function hasAccessToTenant(TenantInterface $tenant): bool
    {
        $currentTenant = $this->getCurrentTenant();

        if (!$currentTenant) {
            return FALSE;
        }

        return $currentTenant->id() === $tenant->id();
    }

}
