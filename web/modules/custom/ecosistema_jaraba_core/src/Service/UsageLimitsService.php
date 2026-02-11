<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio de detección de límites de uso y sugerencias de upgrade.
 *
 * PROPÓSITO:
 * Monitorea el uso de recursos por tenant y detecta cuándo se acercan
 * a los límites de su plan para sugerir upgrades oportunos.
 *
 * Q2 2026 - Sprint 7-8: Expansion Loops
 */
class UsageLimitsService
{

    /**
     * Umbrales de alerta (% del límite).
     */
    protected const THRESHOLD_WARNING = 75;
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
    ) {
    }

    /**
     * Obtiene el resumen de uso de un tenant.
     */
    public function getUsageSummary(string $tenantId, string $planId = 'starter'): array
    {
        $limits = self::PLAN_LIMITS[$planId] ?? self::PLAN_LIMITS['starter'];
        $usage = $this->getCurrentUsage($tenantId);

        $summary = [
            'tenant_id' => $tenantId,
            'plan' => $planId,
            'resources' => [],
            'alerts' => [],
            'upgrade_suggestions' => [],
        ];

        foreach ($limits as $resource => $limit) {
            $currentUsage = $usage[$resource] ?? 0;

            // Si el límite es -1, es ilimitado.
            if ($limit === -1) {
                $summary['resources'][$resource] = [
                    'current' => $currentUsage,
                    'limit' => 'unlimited',
                    'percentage' => 0,
                    'status' => 'ok',
                ];
                continue;
            }

            $percentage = $limit > 0 ? round(($currentUsage / $limit) * 100, 1) : 0;
            $status = $this->getStatusFromPercentage($percentage);

            $summary['resources'][$resource] = [
                'current' => $currentUsage,
                'limit' => $limit,
                'percentage' => $percentage,
                'status' => $status,
            ];

            // Generar alertas si es necesario.
            if ($status !== 'ok') {
                $summary['alerts'][] = [
                    'resource' => $resource,
                    'status' => $status,
                    'message' => $this->getAlertMessage($resource, $status, $percentage),
                    'cta' => $this->getAlertCTA($resource, $status),
                ];
            }
        }

        // Generar sugerencias de upgrade.
        if (!empty($summary['alerts'])) {
            $summary['upgrade_suggestions'] = $this->generateUpgradeSuggestions($planId, $summary['alerts']);
        }

        return $summary;
    }

    /**
     * Obtiene el uso actual de un tenant.
     */
    protected function getCurrentUsage(string $tenantId): array
    {
        // En producción, esto consultaría las tablas reales.
        // Por ahora, simulamos datos realistas.
        return [
            'products' => rand(15, 30),
            'orders_month' => rand(50, 120),
            'storage_mb' => rand(200, 600),
            'api_calls_day' => rand(500, 1500),
            'team_members' => rand(1, 3),
        ];
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
     * Detecta accesos desde múltiples IPs.
     */
    protected function detectMultipleIPs(string $tenantId): bool
    {
        // Simulación - en producción consultaría logs de acceso.
        return rand(0, 1) === 1;
    }

    /**
     * Detecta credenciales compartidas.
     */
    protected function detectSharedCredentials(string $tenantId): bool
    {
        return FALSE; // Por defecto asumimos que no.
    }

    /**
     * Detecta sesiones concurrentes.
     */
    protected function detectConcurrentSessions(string $tenantId): bool
    {
        return rand(0, 2) === 2;
    }

}
