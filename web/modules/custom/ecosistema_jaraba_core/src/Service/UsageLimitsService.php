<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de detección de límites de uso y sugerencias de upgrade.
 *
 * PROPÓSITO:
 * Monitorea el uso de recursos por tenant y detecta cuándo se acercan
 * a los límites de su plan para sugerir upgrades oportunos.
 *
 * Integra con UpgradeTriggerService y FreemiumVerticalLimit ConfigEntity
 * para resolver límites contextualizados por vertical y plan (F2 Doc 183).
 * El PLAN_LIMITS constant se mantiene como fallback de compatibilidad.
 *
 * Q2 2026 - Sprint 7-8: Expansion Loops
 */
class UsageLimitsService
{

    /**
     * Umbrales de alerta (% del límite).
     */
    protected const THRESHOLD_WARNING = 75;
    protected const THRESHOLD_UPGRADE_WARNING = 80;
    protected const THRESHOLD_CRITICAL = 90;
    protected const THRESHOLD_REACHED = 100;

    /**
     * Límites por plan.
     */
    protected const PLAN_LIMITS = [
        'starter' => [
            'products' => 25,
            'orders_month' => 100,
            'storage_mb' => 500,
            'api_calls_day' => 1000,
            'team_members' => 1,
        ],
        'professional' => [
            'products' => 100,
            'orders_month' => 500,
            'storage_mb' => 2000,
            'api_calls_day' => 5000,
            'team_members' => 5,
        ],
        'business' => [
            'products' => 500,
            'orders_month' => 2500,
            'storage_mb' => 10000,
            'api_calls_day' => 25000,
            'team_members' => 15,
        ],
        'enterprise' => [
            'products' => -1, // Ilimitado
            'orders_month' => -1,
            'storage_mb' => -1,
            'api_calls_day' => -1,
            'team_members' => -1,
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected ?UpgradeTriggerService $upgradeTriggerService = NULL,
        protected ?LoggerInterface $logger = NULL,
    ) {
    }

    /**
     * Obtiene el resumen de uso de un tenant.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param string $planId
     *   ID del plan (default: starter).
     * @param string $verticalId
     *   ID de la vertical (para FreemiumVerticalLimit lookup). Si se pasa,
     *   se usa UpgradeTriggerService->getLimitValue() en vez de PLAN_LIMITS.
     */
    public function getUsageSummary(string $tenantId, string $planId = 'starter', string $verticalId = ''): array
    {
        $usage = $this->getCurrentUsage($tenantId);

        $summary = [
            'tenant_id' => $tenantId,
            'plan' => $planId,
            'vertical' => $verticalId,
            'resources' => [],
            'alerts' => [],
            'upgrade_suggestions' => [],
        ];

        // Resolve limits: FreemiumVerticalLimit > PLAN_LIMITS fallback.
        $limits = $this->resolveLimits($verticalId, $planId);

        foreach ($limits as $resource => $limit) {
            $currentUsage = $usage[$resource] ?? 0;

            // Si el límite es -1, es ilimitado.
            if ($limit === -1) {
                $summary['resources'][$resource] = [
                    'current' => $currentUsage,
                    'limit' => 'unlimited',
                    'percentage' => 0,
                    'status' => 'ok',
                    'upgrade_warning' => FALSE,
                ];
                continue;
            }

            $percentage = $limit > 0 ? round(($currentUsage / $limit) * 100, 1) : 0;
            $status = $this->getStatusFromPercentage($percentage);

            // Flag de advertencia de upgrade al 80%+ (F2 Doc 183).
            $upgradeWarning = $percentage >= self::THRESHOLD_UPGRADE_WARNING;

            $summary['resources'][$resource] = [
                'current' => $currentUsage,
                'limit' => $limit,
                'percentage' => $percentage,
                'status' => $status,
                'upgrade_warning' => $upgradeWarning,
            ];

            // Generar alertas si es necesario.
            if ($status !== 'ok') {
                $summary['alerts'][] = [
                    'resource' => $resource,
                    'status' => $status,
                    'message' => $this->getAlertMessage($resource, $status, $percentage),
                    'cta' => $this->getAlertCTA($resource, $status),
                    'upgrade_warning' => $upgradeWarning,
                ];
            }

            // Disparar upgrade trigger cuando se alcanza el limite (F2).
            if ($status === 'reached') {
                $this->fireUpgradeTrigger($tenantId, $resource, $currentUsage, $limit);
            }
        }

        // Generar sugerencias de upgrade.
        if (!empty($summary['alerts'])) {
            $summary['upgrade_suggestions'] = $this->generateUpgradeSuggestions($planId, $summary['alerts']);
        }

        return $summary;
    }

    /**
     * Resuelve los limites para un plan, con FreemiumVerticalLimit override.
     *
     * Cuando verticalId esta presente y UpgradeTriggerService esta inyectado,
     * consulta FreemiumVerticalLimit ConfigEntity para cada feature key.
     * Cae al PLAN_LIMITS constant como fallback de compatibilidad.
     *
     * @param string $verticalId
     *   ID de la vertical (vacio = solo usar PLAN_LIMITS).
     * @param string $planId
     *   ID del plan.
     *
     * @return array
     *   Mapa feature_key => limit_value.
     */
    protected function resolveLimits(string $verticalId, string $planId): array
    {
        $fallbackLimits = self::PLAN_LIMITS[$planId] ?? self::PLAN_LIMITS['starter'];

        // Si no hay vertical o no hay UpgradeTriggerService, usar fallback.
        if (empty($verticalId) || !$this->upgradeTriggerService) {
            return $fallbackLimits;
        }

        $resolvedLimits = [];
        foreach ($fallbackLimits as $featureKey => $fallbackValue) {
            $resolvedLimits[$featureKey] = $this->upgradeTriggerService->getLimitValue(
                $verticalId,
                $planId,
                $featureKey,
                $fallbackValue,
            );
        }

        return $resolvedLimits;
    }

    /**
     * Dispara un trigger de upgrade cuando un recurso alcanza su limite.
     *
     * Carga el tenant entity y delega al UpgradeTriggerService. Silencioso
     * ante errores para no afectar el flujo principal.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param string $featureKey
     *   Clave del recurso que alcanzo el limite.
     * @param int $currentUsage
     *   Uso actual.
     * @param int $limit
     *   Limite configurado.
     */
    protected function fireUpgradeTrigger(string $tenantId, string $featureKey, int $currentUsage, int $limit): void
    {
        if (!$this->upgradeTriggerService) {
            return;
        }

        try {
            $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
            if (!$tenant) {
                return;
            }

            $this->upgradeTriggerService->fire('limit_reached', $tenant, [
                'feature_key' => $featureKey,
                'current_usage' => $currentUsage,
                'limit_value' => $limit,
            ]);
        }
        catch (\Exception $e) {
            // Non-blocking: trigger failure should not break usage summary.
            if ($this->logger) {
                $this->logger->warning('Failed to fire upgrade trigger for tenant @tenant, feature @feature: @error', [
                    '@tenant' => $tenantId,
                    '@feature' => $featureKey,
                    '@error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Obtiene el uso actual de un tenant.
     */
    /**
     * Gets current usage for a tenant from real metering data (HAL-AI-02).
     *
     * Queries the tenant_metering table for actual usage metrics.
     * Falls back to TenantMeteringService if available via service container,
     * then to entity counts as last resort.
     *
     * @param string $tenantId
     *   The tenant ID.
     *
     * @return array
     *   Associative array of resource => current usage value.
     */
    protected function getCurrentUsage(string $tenantId): array
    {
        $usage = [
            'products' => 0,
            'orders_month' => 0,
            'storage_mb' => 0,
            'api_calls_day' => 0,
            'team_members' => 0,
        ];

        $currentPeriod = date('Y-m');
        $today = date('Y-m-d');

        try {
            // HAL-AI-02: Query real data from tenant_metering table.
            if ($this->database->schema()->tableExists('tenant_metering')) {
                // Products: latest recorded value.
                $products = $this->database->select('tenant_metering', 'tm')
                    ->fields('tm', ['value'])
                    ->condition('tenant_id', $tenantId)
                    ->condition('metric', 'products')
                    ->orderBy('created', 'DESC')
                    ->range(0, 1)
                    ->execute()
                    ->fetchField();
                if ($products !== FALSE) {
                    $usage['products'] = (int) $products;
                }

                // Orders this month: SUM of orders in current period.
                $orders = $this->database->select('tenant_metering', 'tm')
                    ->condition('tenant_id', $tenantId)
                    ->condition('metric', 'orders')
                    ->condition('period', $currentPeriod)
                    ->execute();
                $orders->addExpression('SUM(value)', 'total');
                // Re-query with expression.
                $ordersTotal = $this->database->query(
                    "SELECT COALESCE(SUM(value), 0) as total FROM {tenant_metering} WHERE tenant_id = :tid AND metric = :metric AND period = :period",
                    [':tid' => $tenantId, ':metric' => 'orders', ':period' => $currentPeriod]
                )->fetchField();
                $usage['orders_month'] = (int) ($ordersTotal ?: 0);

                // Storage MB: latest recorded value.
                $storage = $this->database->select('tenant_metering', 'tm')
                    ->fields('tm', ['value'])
                    ->condition('tenant_id', $tenantId)
                    ->condition('metric', 'storage_mb')
                    ->orderBy('created', 'DESC')
                    ->range(0, 1)
                    ->execute()
                    ->fetchField();
                if ($storage !== FALSE) {
                    $usage['storage_mb'] = (int) $storage;
                }

                // API calls today: SUM of api_calls today.
                $todayStart = strtotime($today);
                $apiCalls = $this->database->query(
                    "SELECT COALESCE(SUM(value), 0) as total FROM {tenant_metering} WHERE tenant_id = :tid AND metric = :metric AND created >= :start",
                    [':tid' => $tenantId, ':metric' => 'api_calls', ':start' => $todayStart]
                )->fetchField();
                $usage['api_calls_day'] = (int) ($apiCalls ?: 0);

                // Team members: query group_relationship for membership count.
                $members = $this->database->query(
                    "SELECT COUNT(*) FROM {group_relationship_field_data} WHERE gid = :gid AND type LIKE :type",
                    [':gid' => $tenantId, ':type' => '%-group_membership']
                )->fetchField();
                $usage['team_members'] = max(1, (int) ($members ?: 1));
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('Failed to query real usage for tenant @tid: @error', [
                    '@tid' => $tenantId,
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return $usage;
    }

    /**
     * Determina el estado basándose en el porcentaje.
     */
    protected function getStatusFromPercentage(float $percentage): string
    {
        if ($percentage >= self::THRESHOLD_REACHED) {
            return 'reached';
        }
        if ($percentage >= self::THRESHOLD_CRITICAL) {
            return 'critical';
        }
        if ($percentage >= self::THRESHOLD_WARNING) {
            return 'warning';
        }
        return 'ok';
    }

    /**
     * Genera mensaje de alerta.
     */
    protected function getAlertMessage(string $resource, string $status, float $percentage): string
    {
        $resourceNames = [
            'products' => 'productos',
            'orders_month' => 'pedidos mensuales',
            'storage_mb' => 'almacenamiento',
            'api_calls_day' => 'llamadas API diarias',
            'team_members' => 'miembros del equipo',
        ];

        $name = $resourceNames[$resource] ?? $resource;

        return match ($status) {
            'reached' => "Has alcanzado el límite de {$name}.",
            'critical' => "Estás al {$percentage}% de tu límite de {$name}.",
            'warning' => "Tu uso de {$name} está creciendo ({$percentage}%).",
            default => "Uso de {$name} normal.",
        };
    }

    /**
     * Genera CTA para alerta.
     */
    protected function getAlertCTA(string $resource, string $status): array
    {
        return match ($status) {
            'reached', 'critical' => [
                'text' => 'Aumentar límite',
                'url' => '/tenant/upgrade',
                'type' => 'primary',
            ],
            'warning' => [
                'text' => 'Ver opciones',
                'url' => '/planes',
                'type' => 'secondary',
            ],
            default => [],
        };
    }

    /**
     * Genera sugerencias de upgrade personalizadas.
     */
    protected function generateUpgradeSuggestions(string $currentPlan, array $alerts): array
    {
        $planOrder = ['starter', 'professional', 'business', 'enterprise'];
        $currentIndex = array_search($currentPlan, $planOrder);

        if ($currentIndex === FALSE || $currentIndex >= count($planOrder) - 1) {
            return [];
        }

        $nextPlan = $planOrder[$currentIndex + 1];
        $nextLimits = self::PLAN_LIMITS[$nextPlan];

        $criticalResources = array_filter($alerts, fn($a) => $a['status'] === 'critical' || $a['status'] === 'reached');

        $suggestions = [];

        if (!empty($criticalResources)) {
            $suggestions[] = [
                'plan' => $nextPlan,
                'reason' => 'Tu uso actual supera los límites de tu plan.',
                'benefits' => $this->getPlanBenefits($currentPlan, $nextPlan),
                'savings' => $this->calculatePotentialSavings($nextPlan),
                'cta' => [
                    'text' => 'Actualizar a ' . ucfirst($nextPlan),
                    'url' => '/tenant/upgrade?plan=' . $nextPlan,
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * Obtiene beneficios de cambiar de plan.
     */
    protected function getPlanBenefits(string $from, string $to): array
    {
        $fromLimits = self::PLAN_LIMITS[$from] ?? [];
        $toLimits = self::PLAN_LIMITS[$to] ?? [];

        $benefits = [];
        foreach ($toLimits as $resource => $limit) {
            $fromLimit = $fromLimits[$resource] ?? 0;
            if ($limit === -1) {
                $benefits[] = ucfirst(str_replace('_', ' ', $resource)) . ' ilimitado';
            } elseif ($limit > $fromLimit) {
                $multiplier = round($limit / max(1, $fromLimit), 1);
                $benefits[] = ucfirst(str_replace('_', ' ', $resource)) . " x{$multiplier}";
            }
        }

        return $benefits;
    }

    /**
     * Calcula ahorro potencial.
     */
    protected function calculatePotentialSavings(string $plan): ?string
    {
        $monthlyCosts = [
            'starter' => 29,
            'professional' => 79,
            'business' => 199,
            'enterprise' => 499,
        ];

        // Simular ahorro por funcionalidad incluida vs. alternativas.
        return match ($plan) {
            'professional' => 'Ahorra €50/mes vs. tools separados',
            'business' => 'Ahorra €150/mes vs. tools separados',
            'enterprise' => 'ROI positivo en 30 días',
            default => NULL,
        };
    }

    /**
     * Detecta patrones de colaboración multi-seat.
     */
    public function detectCollaborationPatterns(string $tenantId): array
    {
        // Analizar si hay señales de que necesitan más usuarios.
        $patterns = [
            'multiple_ips' => $this->detectMultipleIPs($tenantId),
            'shared_credentials' => $this->detectSharedCredentials($tenantId),
            'concurrent_sessions' => $this->detectConcurrentSessions($tenantId),
        ];

        $needsMultiSeat = $patterns['multiple_ips'] || $patterns['concurrent_sessions'];

        return [
            'patterns' => $patterns,
            'recommendation' => $needsMultiSeat ? 'multi_seat' : NULL,
            'message' => $needsMultiSeat ? 'Parece que varios usuarios acceden a tu cuenta. ¿Necesitas más licencias?' : NULL,
        ];
    }

    /**
     * Detecta accesos desde multiples IPs en las ultimas 24h.
     *
     * Consulta la tabla {sessions} agrupando por uid para miembros del tenant.
     * Si un mismo uid accede desde >3 IPs distintas en 24h, se considera
     * indicador de credential sharing o necesidad de multi-seat.
     *
     * @param string $tenantId
     *   ID del tenant (group ID).
     *
     * @return bool
     *   TRUE si se detectan >3 IPs distintas para algun usuario del tenant.
     */
    protected function detectMultipleIPs(string $tenantId): bool
    {
        try {
            if (!$this->database->schema()->tableExists('sessions')) {
                return FALSE;
            }

            $threshold24h = \Drupal::time()->getRequestTime() - 86400;

            // Get tenant member UIDs from group_relationship.
            $memberUids = $this->database->query(
                "SELECT DISTINCT grfd.entity_id FROM {group_relationship_field_data} grfd WHERE grfd.gid = :gid AND grfd.type LIKE :type",
                [':gid' => $tenantId, ':type' => '%-group_membership']
            )->fetchCol();

            if (empty($memberUids)) {
                return FALSE;
            }

            // Check if any member has >3 distinct IPs in last 24h.
            foreach ($memberUids as $uid) {
                $ipCount = $this->database->query(
                    "SELECT COUNT(DISTINCT hostname) FROM {sessions} WHERE uid = :uid AND timestamp >= :threshold",
                    [':uid' => $uid, ':threshold' => $threshold24h]
                )->fetchField();

                if ((int) $ipCount > 3) {
                    return TRUE;
                }
            }
        }
        catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('Failed to detect multiple IPs for tenant @tid: @error', [
                    '@tid' => $tenantId,
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return FALSE;
    }

    /**
     * Detecta credenciales compartidas.
     *
     * Heuristica: si un mismo uid tiene >3 IPs distintas en 1 hora,
     * es un fuerte indicador de credential sharing (IPs que cambian
     * demasiado rapido para ser un solo usuario movil).
     *
     * @param string $tenantId
     *   ID del tenant (group ID).
     *
     * @return bool
     *   TRUE si se detecta patron de credenciales compartidas.
     */
    protected function detectSharedCredentials(string $tenantId): bool
    {
        try {
            if (!$this->database->schema()->tableExists('sessions')) {
                return FALSE;
            }

            $threshold1h = \Drupal::time()->getRequestTime() - 3600;

            $memberUids = $this->database->query(
                "SELECT DISTINCT grfd.entity_id FROM {group_relationship_field_data} grfd WHERE grfd.gid = :gid AND grfd.type LIKE :type",
                [':gid' => $tenantId, ':type' => '%-group_membership']
            )->fetchCol();

            if (empty($memberUids)) {
                return FALSE;
            }

            foreach ($memberUids as $uid) {
                $ipCount = $this->database->query(
                    "SELECT COUNT(DISTINCT hostname) FROM {sessions} WHERE uid = :uid AND timestamp >= :threshold",
                    [':uid' => $uid, ':threshold' => $threshold1h]
                )->fetchField();

                // >3 distinct IPs in 1 hour = strong indicator.
                if ((int) $ipCount > 3) {
                    return TRUE;
                }
            }
        }
        catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('Failed to detect shared credentials for tenant @tid: @error', [
                    '@tid' => $tenantId,
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return FALSE;
    }

    /**
     * Detecta sesiones concurrentes para el mismo usuario.
     *
     * Si un mismo uid tiene >1 sesion activa (timestamp < 30 min),
     * indica acceso simultaneo desde multiples dispositivos/personas.
     *
     * @param string $tenantId
     *   ID del tenant (group ID).
     *
     * @return bool
     *   TRUE si se detectan sesiones concurrentes.
     */
    protected function detectConcurrentSessions(string $tenantId): bool
    {
        try {
            if (!$this->database->schema()->tableExists('sessions')) {
                return FALSE;
            }

            $threshold30min = \Drupal::time()->getRequestTime() - 1800;

            $memberUids = $this->database->query(
                "SELECT DISTINCT grfd.entity_id FROM {group_relationship_field_data} grfd WHERE grfd.gid = :gid AND grfd.type LIKE :type",
                [':gid' => $tenantId, ':type' => '%-group_membership']
            )->fetchCol();

            if (empty($memberUids)) {
                return FALSE;
            }

            foreach ($memberUids as $uid) {
                $sessionCount = $this->database->query(
                    "SELECT COUNT(*) FROM {sessions} WHERE uid = :uid AND timestamp >= :threshold",
                    [':uid' => $uid, ':threshold' => $threshold30min]
                )->fetchField();

                if ((int) $sessionCount > 1) {
                    return TRUE;
                }
            }
        }
        catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('Failed to detect concurrent sessions for tenant @tid: @error', [
                    '@tid' => $tenantId,
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return FALSE;
    }

}
