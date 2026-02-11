<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Servicio para verificar límites de uso de IA por tenant/plan.
 *
 * RESPONSABILIDADES:
 * - Verificar si un tenant ha excedido o está cerca de su límite de tokens
 * - Calcular uso actual vs límite del plan
 * - Determinar si bloquear o advertir
 * - Proporcionar mensaje de upgrade cuando corresponda
 */
class AIUsageLimitService
{

    /**
     * Estado del uso de IA.
     */
    const STATUS_NORMAL = 'normal';
    const STATUS_WARNING = 'warning';
    const STATUS_BLOCKED = 'blocked';

    /**
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * @var \Drupal\Core\State\StateInterface
     */
    protected StateInterface $state;

    /**
     * Constructor.
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        StateInterface $state
    ) {
        $this->configFactory = $config_factory;
        $this->state = $state;
    }

    /**
     * Verifica el estado del límite de IA para un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a verificar.
     *
     * @return array
     *   Array con:
     *   - status: 'normal', 'warning', 'blocked'
     *   - usage_percent: Porcentaje de uso (0-100+)
     *   - tokens_used: Tokens usados este mes
     *   - tokens_limit: Límite de tokens del plan
     *   - tokens_remaining: Tokens restantes
     *   - message: Mensaje para mostrar al usuario (si aplica)
     *   - can_use_ai: Boolean si puede usar IA
     */
    public function checkLimit(TenantInterface $tenant): array
    {
        $config = $this->getConfig();

        // Obtener plan del tenant
        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return $this->buildResponse(self::STATUS_NORMAL, 0, 0, 0, TRUE);
        }

        // Determinar límite de tokens según el tier del plan
        $planTier = $this->getPlanTier($plan);
        $tokensLimit = $this->getTokensLimitForTier($planTier, $plan);

        // Si límite es 0, es ilimitado
        if ($tokensLimit <= 0) {
            return $this->buildResponse(self::STATUS_NORMAL, 0, 0, 0, TRUE);
        }

        // Obtener uso actual del tenant
        $tokensUsed = $this->getMonthlyTokenUsage($tenant);

        // Calcular porcentaje
        $usagePercent = ($tokensUsed / $tokensLimit) * 100;
        $tokensRemaining = max(0, $tokensLimit - $tokensUsed);

        // Determinar estado
        $warningThreshold = $this->getWarningThreshold($planTier);
        $blockOnLimit = $config->get('ai.block_on_limit') ?? TRUE;

        if ($usagePercent >= 100) {
            $status = $blockOnLimit ? self::STATUS_BLOCKED : self::STATUS_WARNING;
            $canUseAi = !$blockOnLimit;
            $message = $this->getUpgradeMessage($planTier);
        } elseif ($usagePercent >= $warningThreshold) {
            $status = self::STATUS_WARNING;
            $canUseAi = TRUE;
            $message = $this->getWarningMessage($tokensRemaining, $usagePercent);
        } else {
            $status = self::STATUS_NORMAL;
            $canUseAi = TRUE;
            $message = NULL;
        }

        return $this->buildResponse(
            $status,
            round($usagePercent, 1),
            $tokensUsed,
            $tokensLimit,
            $canUseAi,
            $tokensRemaining,
            $message,
            $planTier
        );
    }

    /**
     * Registra uso de tokens para un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param int $tokensIn
     *   Tokens de entrada usados.
     * @param int $tokensOut
     *   Tokens de salida usados.
     */
    public function recordUsage(TenantInterface $tenant, int $tokensIn, int $tokensOut): void
    {
        $tenantId = $tenant->id();
        $monthKey = 'ai_usage_' . $tenantId . '_' . date('Y-m');

        $currentUsage = $this->state->get($monthKey, [
            'tokens_in' => 0,
            'tokens_out' => 0,
            'total' => 0,
            'calls' => 0,
        ]);

        $currentUsage['tokens_in'] += $tokensIn;
        $currentUsage['tokens_out'] += $tokensOut;
        $currentUsage['total'] += ($tokensIn + $tokensOut);
        $currentUsage['calls']++;
        $currentUsage['last_updated'] = time();

        $this->state->set($monthKey, $currentUsage);
    }

    /**
     * Obtiene el uso de tokens del mes actual para un tenant.
     */
    public function getMonthlyTokenUsage(TenantInterface $tenant): int
    {
        $tenantId = $tenant->id();
        $monthKey = 'ai_usage_' . $tenantId . '_' . date('Y-m');
        $usage = $this->state->get($monthKey, ['total' => 0]);
        return (int) ($usage['total'] ?? 0);
    }

    /**
     * Obtiene el porcentaje de uso para un tenant.
     */
    public function getUsagePercent(TenantInterface $tenant): float
    {
        $result = $this->checkLimit($tenant);
        return $result['usage_percent'];
    }

    /**
     * Obtiene tokens restantes para un tenant.
     */
    public function getRemainingTokens(TenantInterface $tenant): int
    {
        $result = $this->checkLimit($tenant);
        return $result['tokens_remaining'];
    }

    /**
     * Determina el tier del plan basándose en su nombre.
     */
    protected function getPlanTier($plan): string
    {
        if (!method_exists($plan, 'getName')) {
            return 'basic';
        }

        $planName = strtolower($plan->getName());

        if (str_contains($planName, 'enterprise') || str_contains($planName, 'empresarial')) {
            return 'enterprise';
        }
        if (str_contains($planName, 'professional') || str_contains($planName, 'profesional')) {
            return 'professional';
        }

        return 'basic';
    }

    /**
     * Obtiene el límite de tokens para un tier.
     * 
     * Primero intenta obtener del JSON de límites del plan,
     * luego de la configuración global de FinOps.
     */
    protected function getTokensLimitForTier(string $tier, $plan): int
    {
        // Primero intentar desde los límites del plan (ai_queries en JSON)
        if (method_exists($plan, 'getLimit')) {
            $planLimit = $plan->getLimit('ai_tokens', 0);
            if ($planLimit > 0) {
                return $planLimit;
            }
            // Fallback a ai_queries (legacy)
            $queries = $plan->getLimit('ai_queries', 0);
            if ($queries > 0) {
                // Convertir queries a tokens estimados (1 query ≈ 2000 tokens)
                return $queries * 2000;
            }
        }

        // Fallback a configuración global de FinOps
        $config = $this->getConfig();
        return match ($tier) {
            'enterprise' => (int) ($config->get('ai.enterprise.tokens_monthly') ?: 0),
            'professional' => (int) ($config->get('ai.professional.tokens_monthly') ?: 200000),
            default => (int) ($config->get('ai.basic.tokens_monthly') ?: 50000),
        };
    }

    /**
     * Obtiene el umbral de advertencia para un tier.
     */
    protected function getWarningThreshold(string $tier): int
    {
        $config = $this->getConfig();
        return match ($tier) {
            'enterprise' => (int) ($config->get('ai.enterprise.warning_percent') ?: 80),
            'professional' => (int) ($config->get('ai.professional.warning_percent') ?: 80),
            default => (int) ($config->get('ai.basic.warning_percent') ?: 80),
        };
    }

    /**
     * Obtiene el mensaje de upgrade.
     */
    protected function getUpgradeMessage(string $currentTier): string
    {
        $config = $this->getConfig();
        $message = $config->get('ai.upgrade_message') ?:
            'Has alcanzado el límite de uso de IA de tu plan @plan. Actualiza para acceder a más funciones de IA.';

        $planNames = [
            'basic' => 'Básico',
            'professional' => 'Profesional',
            'enterprise' => 'Enterprise',
        ];

        return str_replace('@plan', $planNames[$currentTier] ?? $currentTier, $message);
    }

    /**
     * Obtiene el mensaje de advertencia.
     */
    protected function getWarningMessage(int $remaining, float $percent): string
    {
        return sprintf(
            'Te quedan %s tokens de IA este mes (%d%% usado).',
            number_format($remaining),
            round($percent)
        );
    }

    /**
     * Construye la respuesta de verificación.
     */
    protected function buildResponse(
        string $status,
        float $usagePercent,
        int $tokensUsed,
        int $tokensLimit,
        bool $canUseAi,
        int $tokensRemaining = 0,
        ?string $message = NULL,
        ?string $planTier = NULL
    ): array {
        return [
            'status' => $status,
            'usage_percent' => $usagePercent,
            'tokens_used' => $tokensUsed,
            'tokens_limit' => $tokensLimit,
            'tokens_remaining' => $tokensRemaining,
            'can_use_ai' => $canUseAi,
            'message' => $message,
            'plan_tier' => $planTier,
        ];
    }

    /**
     * Obtiene la configuración de FinOps.
     */
    protected function getConfig()
    {
        return $this->configFactory->get('ecosistema_jaraba_core.finops');
    }

}
