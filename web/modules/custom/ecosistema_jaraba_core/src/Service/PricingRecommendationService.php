<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;

/**
 * Servicio de recomendaciones de pricing basadas en uso.
 *
 * PROPÓSITO:
 * Analiza el uso de un tenant y recomienda el plan más adecuado
 * basándose en patrones de uso, ROI potencial y objetivos del negocio.
 *
 * Q2 2026 - Sprint 7-8: Expansion Loops
 */
class PricingRecommendationService
{

    /**
     * Planes disponibles con características.
     */
    protected const PLANS = [
        'starter' => [
            'id' => 'starter',
            'name' => 'Starter',
            'price' => 29,
            'currency' => 'EUR',
            'billing_period' => 'month',
            'features' => [
                'products' => 25,
                'orders_month' => 100,
                'team_members' => 1,
                'support' => 'email',
                'analytics' => 'basic',
                'api_access' => FALSE,
                'custom_domain' => FALSE,
            ],
        ],
        'professional' => [
            'id' => 'professional',
            'name' => 'Professional',
            'price' => 79,
            'currency' => 'EUR',
            'billing_period' => 'month',
            'features' => [
                'products' => 100,
                'orders_month' => 500,
                'team_members' => 5,
                'support' => 'priority',
                'analytics' => 'advanced',
                'api_access' => TRUE,
                'custom_domain' => TRUE,
            ],
        ],
        'business' => [
            'id' => 'business',
            'name' => 'Business',
            'price' => 199,
            'currency' => 'EUR',
            'billing_period' => 'month',
            'features' => [
                'products' => 500,
                'orders_month' => 2500,
                'team_members' => 15,
                'support' => 'dedicated',
                'analytics' => 'premium',
                'api_access' => TRUE,
                'custom_domain' => TRUE,
                'white_label' => TRUE,
            ],
        ],
        'enterprise' => [
            'id' => 'enterprise',
            'name' => 'Enterprise',
            'price' => 499,
            'currency' => 'EUR',
            'billing_period' => 'month',
            'features' => [
                'products' => -1,
                'orders_month' => -1,
                'team_members' => -1,
                'support' => 'dedicated_manager',
                'analytics' => 'enterprise',
                'api_access' => TRUE,
                'custom_domain' => TRUE,
                'white_label' => TRUE,
                'sla' => TRUE,
                'custom_integrations' => TRUE,
            ],
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
    ) {
    }

    /**
     * Genera recomendación de plan basada en uso actual.
     */
    public function getRecommendation(string $tenantId, string $currentPlan, array $usage): array
    {
        $scores = $this->calculatePlanScores($usage, $currentPlan);
        $recommendedPlan = $this->getBestPlan($scores, $currentPlan);

        $recommendation = [
            'current_plan' => $currentPlan,
            'recommended_plan' => $recommendedPlan,
            'scores' => $scores,
            'analysis' => $this->generateAnalysis($currentPlan, $recommendedPlan, $usage),
            'roi' => $this->calculateROI($currentPlan, $recommendedPlan, $usage),
        ];

        if ($recommendedPlan !== $currentPlan) {
            $recommendation['upgrade_benefits'] = $this->getUpgradeBenefits($currentPlan, $recommendedPlan);
            $recommendation['price_difference'] = $this->getPriceDifference($currentPlan, $recommendedPlan);
        }

        return $recommendation;
    }

    /**
     * Calcula puntuaciones de ajuste para cada plan.
     */
    protected function calculatePlanScores(array $usage, string $currentPlan): array
    {
        $scores = [];

        foreach (self::PLANS as $planId => $plan) {
            $score = 0;
            $maxScore = 0;

            foreach ($plan['features'] as $feature => $limit) {
                $usageValue = $usage[$feature] ?? 0;
                $maxScore += 10;

                if ($limit === -1) {
                    // Ilimitado: puntuación perfecta.
                    $score += 10;
                } elseif (is_bool($limit)) {
                    // Feature booleano: si lo necesita y lo tiene.
                    $needsFeature = $usage['needs_' . $feature] ?? FALSE;
                    if ($limit === TRUE && $needsFeature) {
                        $score += 10;
                    } elseif ($limit === FALSE && !$needsFeature) {
                        $score += 5;
                    }
                } elseif (is_numeric($limit) && $limit > 0) {
                    // Feature numérico: porcentaje de ajuste.
                    $percentage = min(100, ($usageValue / $limit) * 100);
                    if ($percentage <= 70) {
                        $score += 10; // Tiene margen.
                    } elseif ($percentage <= 90) {
                        $score += 7; // Ajustado pero funciona.
                    } elseif ($percentage <= 100) {
                        $score += 3; // Muy justo.
                    } else {
                        $score += 0; // Excede el límite.
                    }
                }
            }

            $scores[$planId] = [
                'score' => $score,
                'max_score' => $maxScore,
                'fit_percentage' => $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : 0,
                'price' => $plan['price'],
                'value_score' => $maxScore > 0 ? round($score / $plan['price'], 2) : 0,
            ];
        }

        return $scores;
    }

    /**
     * Determina el mejor plan basándose en scores.
     */
    protected function getBestPlan(array $scores, string $currentPlan): string
    {
        $currentIndex = array_search($currentPlan, array_keys(self::PLANS));
        $bestPlan = $currentPlan;
        $bestValueScore = $scores[$currentPlan]['value_score'] ?? 0;

        foreach ($scores as $planId => $score) {
            // Solo considerar si tiene buen ajuste.
            if ($score['fit_percentage'] >= 80) {
                // Preferir el plan con mejor relación valor/precio.
                if ($score['value_score'] > $bestValueScore * 1.1) {
                    $bestPlan = $planId;
                    $bestValueScore = $score['value_score'];
                }
            }
            // Si el plan actual no ajusta bien, recomendar upgrade.
            elseif ($planId === $currentPlan && $score['fit_percentage'] < 70) {
                // Buscar el siguiente plan.
                $planIds = array_keys(self::PLANS);
                $nextIndex = $currentIndex + 1;
                if (isset($planIds[$nextIndex])) {
                    $bestPlan = $planIds[$nextIndex];
                }
            }
        }

        return $bestPlan;
    }

    /**
     * Genera análisis del uso.
     */
    protected function generateAnalysis(string $current, string $recommended, array $usage): array
    {
        $analysis = [
            'summary' => '',
            'insights' => [],
        ];

        if ($current === $recommended) {
            $analysis['summary'] = 'Tu plan actual se ajusta bien a tu uso.';
            $analysis['insights'][] = 'Continúa monitoreando tu crecimiento.';
        } else {
            $currentPlan = self::PLANS[$current];
            $recommendedPlan = self::PLANS[$recommended];

            if ($recommendedPlan['price'] > $currentPlan['price']) {
                $analysis['summary'] = 'Tu uso sugiere que un plan superior te beneficiaría.';
                $analysis['insights'][] = 'Evita límites que pueden frenar tu crecimiento.';
                $analysis['insights'][] = 'Desbloquea funcionalidades premium.';
            } else {
                $analysis['summary'] = 'Podrías ahorrar con un plan más ajustado a tu uso.';
                $analysis['insights'][] = 'Tu uso actual no aprovecha todas las funcionalidades.';
            }
        }

        return $analysis;
    }

    /**
     * Calcula el ROI de cambiar de plan.
     */
    protected function calculateROI(string $from, string $to, array $usage): array
    {
        if ($from === $to) {
            return ['applicable' => FALSE];
        }

        $fromPlan = self::PLANS[$from];
        $toPlan = self::PLANS[$to];
        $priceDiff = $toPlan['price'] - $fromPlan['price'];

        // Simular beneficios potenciales.
        $avgOrderValue = $usage['avg_order_value'] ?? 50;
        $currentOrders = $usage['orders_month'] ?? 50;

        // Proyección: más límite = más oportunidad de venta.
        $potentialOrderIncrease = round($currentOrders * 0.2); // 20% más
        $potentialRevenue = $potentialOrderIncrease * $avgOrderValue;

        $roi = $priceDiff > 0 ? round(($potentialRevenue - $priceDiff) / $priceDiff * 100, 1) : 0;

        return [
            'applicable' => TRUE,
            'monthly_cost_change' => $priceDiff,
            'potential_revenue_increase' => $potentialRevenue,
            'roi_percentage' => $roi,
            'payback_months' => $potentialRevenue > 0 ? round(abs($priceDiff) / ($potentialRevenue / 12), 1) : NULL,
        ];
    }

    /**
     * Obtiene beneficios del upgrade.
     */
    protected function getUpgradeBenefits(string $from, string $to): array
    {
        $fromFeatures = self::PLANS[$from]['features'];
        $toFeatures = self::PLANS[$to]['features'];
        $benefits = [];

        foreach ($toFeatures as $feature => $value) {
            $fromValue = $fromFeatures[$feature] ?? NULL;

            if ($value === -1 && $fromValue !== -1) {
                $benefits[] = ucfirst(str_replace('_', ' ', $feature)) . ' ilimitado';
            } elseif ($value === TRUE && $fromValue !== TRUE) {
                $benefits[] = ucfirst(str_replace('_', ' ', $feature)) . ' incluido';
            } elseif (is_numeric($value) && is_numeric($fromValue) && $value > $fromValue) {
                $multiplier = round($value / max(1, $fromValue), 1);
                $benefits[] = ucfirst(str_replace('_', ' ', $feature)) . " x{$multiplier}";
            }
        }

        return $benefits;
    }

    /**
     * Obtiene diferencia de precio.
     */
    protected function getPriceDifference(string $from, string $to): array
    {
        $fromPlan = self::PLANS[$from];
        $toPlan = self::PLANS[$to];

        return [
            'from' => $fromPlan['price'],
            'to' => $toPlan['price'],
            'difference' => $toPlan['price'] - $fromPlan['price'],
            'currency' => 'EUR',
            'annual_savings' => ($fromPlan['price'] - $toPlan['price']) * 12,
        ];
    }

    /**
     * Obtiene todos los planes.
     */
    public function getAllPlans(): array
    {
        return self::PLANS;
    }

    /**
     * Obtiene un plan específico.
     */
    public function getPlan(string $planId): ?array
    {
        return self::PLANS[$planId] ?? NULL;
    }

}
