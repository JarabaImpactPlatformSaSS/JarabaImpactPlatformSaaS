<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Expansion Revenue Service.
 *
 * Implementa tracking de expansion revenue y Product Qualified Accounts (PQA):
 * - Expansion signals automáticos
 * - PQA scoring
 * - Revenue expansion alerts
 */
class ExpansionRevenueService
{

    /**
     * Señales de expansión y sus pesos.
     */
    protected const EXPANSION_SIGNALS = [
        'usage_near_limit' => ['weight' => 30, 'threshold' => 80],
        'feature_discovery' => ['weight' => 15, 'threshold' => 5],
        'team_growth' => ['weight' => 25, 'threshold' => 2],
        'api_adoption' => ['weight' => 20, 'threshold' => 10],
        'engagement_high' => ['weight' => 10, 'threshold' => 20],
    ];

    /**
     * Thresholds para clasificación PQA.
     */
    protected const PQA_THRESHOLDS = [
        'hot' => 80,      // Muy caliente - contactar inmediatamente
        'warm' => 50,     // Tibio - nurturing activo
        'cold' => 20,     // Frío - nurturing pasivo
    ];

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * State service.
     */
    protected StateInterface $state;

    /**
     * Logger.
     */
    protected LoggerChannelFactoryInterface $loggerFactory;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        StateInterface $state,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->state = $state;
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * Calcula el score PQA para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Score PQA con desglose.
     */
    public function calculatePQAScore(int $tenantId): array
    {
        $signals = $this->detectExpansionSignals($tenantId);
        $totalScore = 0;
        $breakdown = [];

        foreach ($signals as $signal => $data) {
            if ($data['triggered']) {
                $weight = self::EXPANSION_SIGNALS[$signal]['weight'] ?? 0;
                $totalScore += $weight;
                $breakdown[$signal] = [
                    'score' => $weight,
                    'value' => $data['value'],
                    'reason' => $data['reason'],
                ];
            }
        }

        // Clasificar PQA.
        $classification = 'cold';
        if ($totalScore >= self::PQA_THRESHOLDS['hot']) {
            $classification = 'hot';
        } elseif ($totalScore >= self::PQA_THRESHOLDS['warm']) {
            $classification = 'warm';
        }

        $result = [
            'tenant_id' => $tenantId,
            'score' => $totalScore,
            'max_score' => 100,
            'classification' => $classification,
            'breakdown' => $breakdown,
            'signals_triggered' => count($breakdown),
            'calculated_at' => date('c'),
        ];

        // Guardar score.
        $this->state->set("pqa_score_{$tenantId}", $result);

        // Log si es hot.
        if ($classification === 'hot') {
            $this->loggerFactory->get('expansion_revenue')->notice(
                '🔥 HOT PQA detected for tenant @tenant (score: @score)',
                ['@tenant' => $tenantId, '@score' => $totalScore]
            );
        }

        return $result;
    }

    /**
     * Detecta señales de expansión para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Señales detectadas.
     */
    public function detectExpansionSignals(int $tenantId): array
    {
        $signals = [];

        // 1. Usage near limit.
        $usagePercent = $this->getUsagePercent($tenantId);
        $signals['usage_near_limit'] = [
            'triggered' => $usagePercent >= self::EXPANSION_SIGNALS['usage_near_limit']['threshold'],
            'value' => $usagePercent,
            'reason' => "Uso al {$usagePercent}% del límite del plan",
        ];

        // 2. Feature discovery (features nuevas usadas).
        $featuresDiscovered = $this->getFeaturesDiscovered($tenantId);
        $signals['feature_discovery'] = [
            'triggered' => $featuresDiscovered >= self::EXPANSION_SIGNALS['feature_discovery']['threshold'],
            'value' => $featuresDiscovered,
            'reason' => "Descubrió {$featuresDiscovered} features recientemente",
        ];

        // 3. Team growth.
        $teamGrowth = $this->getTeamGrowth($tenantId);
        $signals['team_growth'] = [
            'triggered' => $teamGrowth >= self::EXPANSION_SIGNALS['team_growth']['threshold'],
            'value' => $teamGrowth,
            'reason' => "Añadió {$teamGrowth} usuarios este mes",
        ];

        // 4. API adoption.
        $apiCalls = $this->getApiCallsThisMonth($tenantId);
        $signals['api_adoption'] = [
            'triggered' => $apiCalls >= self::EXPANSION_SIGNALS['api_adoption']['threshold'],
            'value' => $apiCalls,
            'reason' => "Realizó {$apiCalls} llamadas API",
        ];

        // 5. High engagement.
        $sessions = $this->getActiveSessions($tenantId);
        $signals['engagement_high'] = [
            'triggered' => $sessions >= self::EXPANSION_SIGNALS['engagement_high']['threshold'],
            'value' => $sessions,
            'reason' => "{$sessions} sesiones activas este mes",
        ];

        return $signals;
    }

    /**
     * Obtiene tenants con señales de expansión activas.
     *
     * @return array
     *   Array de tenant_id => PQA data.
     */
    public function getExpansionOpportunities(): array
    {
        $opportunities = [];

        $tenantIds = $this->getActiveTenantIds();

        foreach ($tenantIds as $tenantId) {
            $pqa = $this->calculatePQAScore($tenantId);
            if ($pqa['classification'] !== 'cold') {
                $opportunities[$tenantId] = $pqa;
            }
        }

        // Ordenar por score descendente.
        uasort($opportunities, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $opportunities;
    }

    /**
     * Genera alertas de expansión para el equipo de ventas.
     *
     * @return array
     *   Alertas generadas.
     */
    public function generateExpansionAlerts(): array
    {
        $alerts = [];
        $opportunities = $this->getExpansionOpportunities();

        foreach ($opportunities as $tenantId => $pqa) {
            if ($pqa['classification'] === 'hot') {
                $alerts[] = [
                    'type' => 'expansion_hot',
                    'tenant_id' => $tenantId,
                    'priority' => 'high',
                    'message' => "Tenant #{$tenantId} listo para upgrade (PQA: {$pqa['score']})",
                    'action' => 'contact_immediately',
                    'created_at' => date('c'),
                ];
            } elseif ($pqa['classification'] === 'warm') {
                $alerts[] = [
                    'type' => 'expansion_warm',
                    'tenant_id' => $tenantId,
                    'priority' => 'medium',
                    'message' => "Tenant #{$tenantId} mostrando interés (PQA: {$pqa['score']})",
                    'action' => 'schedule_nurturing',
                    'created_at' => date('c'),
                ];
            }
        }

        // Log alertas generadas.
        if (!empty($alerts)) {
            $this->loggerFactory->get('expansion_revenue')->info(
                '📊 Generated @count expansion alerts',
                ['@count' => count($alerts)]
            );
        }

        return $alerts;
    }

    /**
     * Calcula NRR (Net Revenue Retention) del período.
     *
     * @param string $period
     *   Período (formato Y-m).
     *
     * @return array
     *   Métricas de NRR.
     */
    public function calculateNRR(string $period = ''): array
    {
        if (empty($period)) {
            $period = date('Y-m');
        }

        // Obtener datos de revenue desde entidades de subscripción/billing.
        $startMRR = 0;
        $expansionMRR = 0;
        $churnMRR = 0;
        $contractionMRR = 0;

        try {
            // Calcular MRR sumando precios de planes activos.
            $activeTenants = $this->entityTypeManager
                ->getStorage('tenant')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('status', TRUE)
                ->execute();

            if (!empty($activeTenants)) {
                $tenants = $this->entityTypeManager->getStorage('tenant')
                    ->loadMultiple($activeTenants);
                foreach ($tenants as $tenant) {
                    $plan = $tenant->getSubscriptionPlan();
                    if ($plan && method_exists($plan, 'getPriceMonthly')) {
                        $startMRR += (float) $plan->getPriceMonthly();
                    }
                }
            }

            // Leer métricas de expansión/churn desde state (actualizadas por webhooks).
            $expansionMRR = $this->state->get("expansion_mrr_{$period}", 0);
            $churnMRR = $this->state->get("churn_mrr_{$period}", 0);
            $contractionMRR = $this->state->get("contraction_mrr_{$period}", 0);
        } catch (\Exception $e) {
            // Fallback a state para todos los valores.
            $startMRR = $this->state->get("mrr_start_{$period}", 0);
            $expansionMRR = $this->state->get("expansion_mrr_{$period}", 0);
            $churnMRR = $this->state->get("churn_mrr_{$period}", 0);
            $contractionMRR = $this->state->get("contraction_mrr_{$period}", 0);
        }

        $endMRR = $startMRR + $expansionMRR - $churnMRR - $contractionMRR;
        $nrr = $startMRR > 0 ? ($endMRR / $startMRR) * 100 : 100;

        return [
            'period' => $period,
            'start_mrr' => $startMRR,
            'end_mrr' => $endMRR,
            'expansion_mrr' => $expansionMRR,
            'churn_mrr' => $churnMRR,
            'contraction_mrr' => $contractionMRR,
            'nrr' => round($nrr, 1),
            'target_nrr' => 120,
            'status' => $nrr >= 120 ? 'on_track' : 'below_target',
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Calcula el porcentaje de uso del plan del tenant.
     */
    protected function getUsagePercent(int $tenantId): int
    {
        try {
            $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
            if (!$tenant) {
                return 0;
            }
            $plan = $tenant->getSubscriptionPlan();
            if (!$plan) {
                return 0;
            }
            $maxUsers = (int) $plan->getLimit('max_users', 0);
            if ($maxUsers <= 0) {
                return 0;
            }
            $group = $tenant->getGroup();
            if (!$group) {
                return 0;
            }
            $memberCount = (int) $this->entityTypeManager
                ->getStorage('group_relationship')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('gid', $group->id())
                ->condition('plugin_id', 'group_membership')
                ->count()
                ->execute();

            return $maxUsers > 0 ? (int) round(($memberCount / $maxUsers) * 100) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Cuenta features distintas usadas por el tenant en los últimos 30 días.
     */
    protected function getFeaturesDiscovered(int $tenantId): int
    {
        $features = $this->state->get("tenant_features_{$tenantId}", []);
        $thirtyDaysAgo = time() - (30 * 86400);
        $recentFeatures = array_filter($features, fn($ts) => $ts >= $thirtyDaysAgo);
        return count($recentFeatures);
    }

    /**
     * Cuenta nuevos miembros del grupo del tenant este mes.
     */
    protected function getTeamGrowth(int $tenantId): int
    {
        try {
            $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
            if (!$tenant) {
                return 0;
            }
            $group = $tenant->getGroup();
            if (!$group) {
                return 0;
            }
            $firstOfMonth = strtotime(date('Y-m-01'));
            return (int) $this->entityTypeManager
                ->getStorage('group_relationship')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('gid', $group->id())
                ->condition('plugin_id', 'group_membership')
                ->condition('created', $firstOfMonth, '>=')
                ->count()
                ->execute();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Cuenta llamadas API del tenant este mes desde state.
     */
    protected function getApiCallsThisMonth(int $tenantId): int
    {
        $period = date('Y-m');
        return (int) $this->state->get("api_calls_{$tenantId}_{$period}", 0);
    }

    /**
     * Cuenta sesiones activas del tenant este mes.
     */
    protected function getActiveSessions(int $tenantId): int
    {
        try {
            $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
            if (!$tenant) {
                return 0;
            }
            $group = $tenant->getGroup();
            if (!$group) {
                return 0;
            }
            $thirtyDaysAgo = time() - (30 * 86400);
            $memberRelIds = $this->entityTypeManager
                ->getStorage('group_relationship')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('gid', $group->id())
                ->condition('plugin_id', 'group_membership')
                ->execute();
            if (empty($memberRelIds)) {
                return 0;
            }
            $members = $this->entityTypeManager->getStorage('group_relationship')
                ->loadMultiple($memberRelIds);
            $uids = [];
            foreach ($members as $rel) {
                $uids[] = (int) $rel->get('entity_id')->target_id;
            }
            if (empty($uids)) {
                return 0;
            }
            return (int) $this->entityTypeManager
                ->getStorage('user')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('uid', $uids, 'IN')
                ->condition('access', $thirtyDaysAgo, '>=')
                ->count()
                ->execute();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene IDs de tenants activos.
     */
    protected function getActiveTenantIds(): array
    {
        try {
            return $this->entityTypeManager
                ->getStorage('tenant')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('status', TRUE)
                ->execute();
        } catch (\Exception $e) {
            return [];
        }
    }

}
