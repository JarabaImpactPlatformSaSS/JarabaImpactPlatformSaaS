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

        // TODO: Obtener lista de tenants activos.
        // Por ahora, verificamos solo los tenants en state.
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

        // TODO: Obtener datos reales de revenue.
        // Por ahora, datos simulados.
        $startMRR = $this->state->get("mrr_start_{$period}", 10000);
        $expansionMRR = $this->state->get("expansion_mrr_{$period}", 1200);
        $churnMRR = $this->state->get("churn_mrr_{$period}", 500);
        $contractionMRR = $this->state->get("contraction_mrr_{$period}", 200);

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
    // HELPER METHODS (simulated data for now)
    // =========================================================================

    protected function getUsagePercent(int $tenantId): int
    {
        return rand(50, 95);
    }

    protected function getFeaturesDiscovered(int $tenantId): int
    {
        return rand(0, 10);
    }

    protected function getTeamGrowth(int $tenantId): int
    {
        return rand(0, 5);
    }

    protected function getApiCallsThisMonth(int $tenantId): int
    {
        return rand(0, 50);
    }

    protected function getActiveSessions(int $tenantId): int
    {
        return rand(5, 50);
    }

    protected function getActiveTenantIds(): array
    {
        // TODO: Obtener de la base de datos.
        return [1, 2, 3];
    }

}
