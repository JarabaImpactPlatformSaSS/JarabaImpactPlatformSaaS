<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;

/**
 * Servicio de medición de valor generado por IA.
 *
 * PROPÓSITO:
 * Rastrea y cuantifica el valor que la IA genera para cada tenant,
 * permitiendo pricing basado en outcomes/resultados.
 *
 * Q4 2026 - Sprint 13-14: Outcome-Based Pricing
 */
class AIValueDashboardService
{

    /**
     * Tipos de valor medible.
     */
    public const VALUE_SALES_GENERATED = 'sales_generated';
    public const VALUE_TIME_SAVED = 'time_saved';
    public const VALUE_LEADS_GENERATED = 'leads_generated';
    public const VALUE_CONTENT_CREATED = 'content_created';
    public const VALUE_CUSTOMER_RETENTION = 'customer_retention';
    public const VALUE_SUPPORT_DEFLECTED = 'support_deflected';

    /**
     * Factores de conversión a valor monetario.
     */
    protected const VALUE_MULTIPLIERS = [
        self::VALUE_SALES_GENERATED => 1.0,         // Valor directo.
        self::VALUE_TIME_SAVED => 25.0,             // €25/hora ahorrada.
        self::VALUE_LEADS_GENERATED => 15.0,        // €15/lead.
        self::VALUE_CONTENT_CREATED => 50.0,        // €50/contenido.
        self::VALUE_CUSTOMER_RETENTION => 100.0,    // €100/cliente retenido.
        self::VALUE_SUPPORT_DEFLECTED => 5.0,       // €5/ticket evitado.
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
    ) {
    }

    /**
     * Registra valor generado por IA.
     */
    public function recordValue(
        string $tenantId,
        string $valueType,
        float $amount,
        string $agentId,
        array $context = []
    ): void {
        $monetaryValue = $amount * (self::VALUE_MULTIPLIERS[$valueType] ?? 1.0);

        $this->database->insert('ai_value_tracking')
            ->fields([
                    'tenant_id' => $tenantId,
                    'value_type' => $valueType,
                    'raw_amount' => $amount,
                    'monetary_value' => $monetaryValue,
                    'agent_id' => $agentId,
                    'context' => json_encode($context),
                    'period' => date('Y-m'),
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Obtiene el valor total generado por IA para un tenant.
     */
    public function getTotalValue(string $tenantId, ?string $period = NULL): array
    {
        $period = $period ?? date('Y-m');

        $query = $this->database->select('ai_value_tracking', 'avt')
            ->fields('avt', ['value_type'])
            ->condition('tenant_id', $tenantId)
            ->condition('period', $period)
            ->groupBy('value_type');
        $query->addExpression('SUM(raw_amount)', 'raw_total');
        $query->addExpression('SUM(monetary_value)', 'value_total');

        $results = $query->execute()->fetchAll();

        $breakdown = [];
        $totalValue = 0;

        foreach ($results as $row) {
            $breakdown[$row->value_type] = [
                'raw_amount' => (float) $row->raw_total,
                'monetary_value' => (float) $row->value_total,
                'label' => $this->getValueLabel($row->value_type),
            ];
            $totalValue += (float) $row->value_total;
        }

        return [
            'tenant_id' => $tenantId,
            'period' => $period,
            'total_value' => round($totalValue, 2),
            'breakdown' => $breakdown,
            'roi' => $this->calculateROI($tenantId, $totalValue),
        ];
    }

    /**
     * Obtiene valor generado por agente.
     */
    public function getValueByAgent(string $tenantId, ?string $period = NULL): array
    {
        $period = $period ?? date('Y-m');

        $query = $this->database->select('ai_value_tracking', 'avt')
            ->fields('avt', ['agent_id'])
            ->condition('tenant_id', $tenantId)
            ->condition('period', $period)
            ->groupBy('agent_id');
        $query->addExpression('SUM(monetary_value)', 'total_value');
        $query->addExpression('COUNT(*)', 'interactions');

        $results = $query->execute()->fetchAll();

        $agents = [];
        foreach ($results as $row) {
            $agents[$row->agent_id] = [
                'total_value' => round((float) $row->total_value, 2),
                'interactions' => (int) $row->interactions,
                'avg_value_per_interaction' => round((float) $row->total_value / max(1, (int) $row->interactions), 2),
            ];
        }

        // Ordenar por valor.
        uasort($agents, fn($a, $b) => $b['total_value'] <=> $a['total_value']);

        return $agents;
    }

    /**
     * Obtiene tendencias de valor histórico.
     */
    public function getValueTrends(string $tenantId, int $months = 6): array
    {
        $query = $this->database->select('ai_value_tracking', 'avt')
            ->fields('avt', ['period'])
            ->condition('tenant_id', $tenantId)
            ->groupBy('period')
            ->orderBy('period', 'ASC');
        $query->addExpression('SUM(monetary_value)', 'total_value');

        $results = $query->execute()->fetchAll();

        $trends = [];
        $previousValue = NULL;

        foreach (array_slice($results, -$months) as $row) {
            $value = (float) $row->total_value;
            $growth = $previousValue !== NULL && $previousValue > 0
                ? round((($value - $previousValue) / $previousValue) * 100, 1)
                : NULL;

            $trends[$row->period] = [
                'value' => round($value, 2),
                'growth_percent' => $growth,
            ];

            $previousValue = $value;
        }

        return $trends;
    }

    /**
     * Calcula ROI comparando valor generado vs coste del servicio.
     */
    protected function calculateROI(string $tenantId, float $valueGenerated): array
    {
        // Obtener coste del plan del tenant (simplificado).
        $monthlyCost = $this->getTenantMonthlyCost($tenantId);

        if ($monthlyCost <= 0) {
            return ['applicable' => FALSE];
        }

        $roi = (($valueGenerated - $monthlyCost) / $monthlyCost) * 100;

        return [
            'applicable' => TRUE,
            'value_generated' => round($valueGenerated, 2),
            'monthly_cost' => $monthlyCost,
            'net_value' => round($valueGenerated - $monthlyCost, 2),
            'roi_percent' => round($roi, 1),
            'multiplier' => round($valueGenerated / max(1, $monthlyCost), 1),
        ];
    }

    /**
     * Obtiene coste mensual del tenant.
     */
    protected function getTenantMonthlyCost(string $tenantId): float
    {
        // Simplificado - en producción consultaría el plan activo.
        return 79.0; // Plan Professional por defecto.
    }

    /**
     * Obtiene etiqueta de tipo de valor.
     */
    protected function getValueLabel(string $valueType): string
    {
        $labels = [
            self::VALUE_SALES_GENERATED => 'Ventas generadas',
            self::VALUE_TIME_SAVED => 'Tiempo ahorrado',
            self::VALUE_LEADS_GENERATED => 'Leads generados',
            self::VALUE_CONTENT_CREATED => 'Contenido creado',
            self::VALUE_CUSTOMER_RETENTION => 'Clientes retenidos',
            self::VALUE_SUPPORT_DEFLECTED => 'Tickets evitados',
        ];

        return $labels[$valueType] ?? $valueType;
    }

    /**
     * Genera resumen ejecutivo de valor IA.
     */
    public function getExecutiveSummary(string $tenantId): array
    {
        $currentValue = $this->getTotalValue($tenantId);
        $trends = $this->getValueTrends($tenantId, 3);
        $topAgents = array_slice($this->getValueByAgent($tenantId), 0, 3, TRUE);

        $trendValues = array_values($trends);
        $avgGrowth = count($trendValues) > 0
            ? round(array_sum(array_column($trendValues, 'growth_percent')) / count($trendValues), 1)
            : 0;

        return [
            'headline' => [
                'total_value' => $currentValue['total_value'],
                'roi' => $currentValue['roi'],
                'currency' => 'EUR',
            ],
            'insights' => $this->generateInsights($currentValue, $trends, $topAgents),
            'top_agents' => $topAgents,
            'avg_monthly_growth' => $avgGrowth,
            'recommendation' => $this->getRecommendation($currentValue, $avgGrowth),
        ];
    }

    /**
     * Genera insights automáticos.
     */
    protected function generateInsights(array $value, array $trends, array $agents): array
    {
        $insights = [];

        // ROI alto.
        if (isset($value['roi']['roi_percent']) && $value['roi']['roi_percent'] > 200) {
            $insights[] = [
                'type' => 'positive',
                'message' => 'Tu ROI de IA es excepcional: ' . $value['roi']['multiplier'] . 'x',
            ];
        }

        // Crecimiento consistente.
        $trendValues = array_values($trends);
        $allPositive = count($trendValues) > 1 && !array_filter(
            array_column($trendValues, 'growth_percent'),
            fn($g) => $g !== NULL && $g < 0
        );
        if ($allPositive) {
            $insights[] = [
                'type' => 'positive',
                'message' => 'Tu valor IA crece consistentemente mes a mes.',
            ];
        }

        // Agente top.
        $topAgent = array_key_first($agents);
        if ($topAgent && isset($agents[$topAgent])) {
            $insights[] = [
                'type' => 'info',
                'message' => "Tu agente más valioso es '{$topAgent}' con €" . $agents[$topAgent]['total_value'],
            ];
        }

        return $insights;
    }

    /**
     * Genera recomendación basada en datos.
     */
    protected function getRecommendation(array $value, float $avgGrowth): ?array
    {
        if (!isset($value['roi']['roi_percent'])) {
            return NULL;
        }

        $roi = $value['roi']['roi_percent'];

        if ($roi > 500 && $avgGrowth > 10) {
            return [
                'action' => 'scale_up',
                'message' => 'Tu ROI es excepcional. Considera escalar tu uso de IA.',
                'cta' => 'Ver planes enterprise',
            ];
        }

        if ($roi < 50) {
            return [
                'action' => 'optimize',
                'message' => 'Hay oportunidad de mejorar tu uso de IA.',
                'cta' => 'Ver guía de optimización',
            ];
        }

        return [
            'action' => 'maintain',
            'message' => 'Tu uso de IA es saludable. Sigue así.',
        ];
    }

}
