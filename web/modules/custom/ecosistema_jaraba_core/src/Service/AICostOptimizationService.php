<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * AI Cost Optimization Service.
 *
 * Implementa FinOps para IA:
 * - Token budgets por tenant
 * - Smart model routing (GPT-3.5 para simple, GPT-4 para complejo)
 * - Response caching
 */
class AICostOptimizationService
{

    /**
     * Thresholds para routing de modelos.
     */
    protected const MODEL_ROUTING = [
        'simple' => [
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 500,
            'cost_per_1k' => 0.002,
        ],
        'standard' => [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 1000,
            'cost_per_1k' => 0.01,
        ],
        'complex' => [
            'model' => 'gpt-4o',
            'max_tokens' => 2000,
            'cost_per_1k' => 0.03,
        ],
    ];

    /**
     * Palabras clave para detectar complejidad.
     */
    protected const COMPLEXITY_KEYWORDS = [
        'complex' => ['analiza', 'compara', 'estrategia', 'planifica', 'evalúa', 'optimiza'],
        'simple' => ['lista', 'resume', 'traduce', 'formatea', 'extrae'],
    ];

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
        StateInterface $state,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->state = $state;
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * Determina el modelo óptimo basado en la complejidad del prompt.
     *
     * @param string $prompt
     *   El prompt a analizar.
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Configuración del modelo a usar.
     */
    public function routeToOptimalModel(string $prompt, int $tenantId): array
    {
        $complexity = $this->detectComplexity($prompt);

        // Verificar budget del tenant.
        // FIX-005: getTenantBudget() retorna centavos, getTenantUsage() retorna dólares.
        // Convertir budget a dólares antes de comparar.
        $budgetCents = $this->getTenantBudget($tenantId);
        $budget = $budgetCents / 100;
        $usage = $this->getTenantUsage($tenantId);

        // Si está cerca del límite (90%), degradar a modelo más barato.
        if ($usage >= $budget * 0.9) {
            $complexity = 'simple';
            $this->loggerFactory->get('ai_cost')->warning(
                '⚠️ Tenant @tenant cerca del límite de budget. Degradando a modelo simple.',
                ['@tenant' => $tenantId]
            );
        }

        $routing = self::MODEL_ROUTING[$complexity];

        $this->loggerFactory->get('ai_cost')->info(
            '🎯 Routing prompt to @model (complexity: @complexity)',
            ['@model' => $routing['model'], '@complexity' => $complexity]
        );

        return $routing;
    }

    /**
     * Detecta la complejidad de un prompt.
     *
     * @param string $prompt
     *   El prompt a analizar.
     *
     * @return string
     *   Nivel de complejidad: simple, standard, complex.
     */
    public function detectComplexity(string $prompt): string
    {
        $promptLower = strtolower($prompt);
        $length = strlen($prompt);

        // Detectar por palabras clave.
        foreach (self::COMPLEXITY_KEYWORDS['complex'] as $keyword) {
            if (str_contains($promptLower, $keyword)) {
                return 'complex';
            }
        }

        foreach (self::COMPLEXITY_KEYWORDS['simple'] as $keyword) {
            if (str_contains($promptLower, $keyword)) {
                return 'simple';
            }
        }

        // Detectar por longitud.
        if ($length > 2000) {
            return 'complex';
        }
        if ($length < 200) {
            return 'simple';
        }

        return 'standard';
    }

    /**
     * Obtiene el budget de tokens para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return int
     *   Budget en tokens.
     */
    public function getTenantBudget(int $tenantId): int
    {
        // Budgets por plan (en dólares equivalentes).
        $planBudgets = [
            'starter' => 5.0,    // $5/mes
            'professional' => 25.0, // $25/mes
            'enterprise' => 100.0,  // $100/mes
        ];

        $plan = $this->state->get("tenant_{$tenantId}_plan", 'professional');
        return (int) (($planBudgets[$plan] ?? 25.0) * 100); // Convertir a centavos.
    }

    /**
     * Obtiene el uso actual del tenant en el período.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return float
     *   Uso en dólares.
     */
    public function getTenantUsage(int $tenantId): float
    {
        $period = date('Y-m');
        $key = "tenant_ai_usage_{$tenantId}_{$period}";
        return (float) $this->state->get($key, 0.0);
    }

    /**
     * Registra uso de tokens y actualiza el tracking.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param int $inputTokens
     *   Tokens de entrada.
     * @param int $outputTokens
     *   Tokens de salida.
     * @param string $model
     *   Modelo usado.
     */
    public function trackUsage(int $tenantId, int $inputTokens, int $outputTokens, string $model): void
    {
        $period = date('Y-m');
        $key = "tenant_ai_usage_{$tenantId}_{$period}";

        // Calcular costo.
        $costPer1k = $this->getModelCost($model);
        $totalTokens = $inputTokens + $outputTokens;
        $cost = ($totalTokens / 1000) * $costPer1k;

        // Actualizar uso.
        $currentUsage = $this->state->get($key, 0.0);
        $this->state->set($key, $currentUsage + $cost);

        // Tracking detallado.
        $detailKey = "tenant_ai_usage_detail_{$tenantId}_{$period}";
        $details = $this->state->get($detailKey, []);
        $details[] = [
            'timestamp' => time(),
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost' => $cost,
        ];

        // Mantener solo últimos 1000 registros.
        if (count($details) > 1000) {
            $details = array_slice($details, -1000);
        }

        $this->state->set($detailKey, $details);

        $this->loggerFactory->get('ai_cost')->debug(
            '📊 Tracked AI usage: @tokens tokens, $@cost',
            ['@tokens' => $totalTokens, '@cost' => number_format($cost, 4)]
        );
    }

    /**
     * Obtiene el costo por 1K tokens de un modelo.
     */
    protected function getModelCost(string $model): float
    {
        $costs = [
            'gpt-3.5-turbo' => 0.002,
            'gpt-4o-mini' => 0.01,
            'gpt-4o' => 0.03,
            'gpt-4' => 0.06,
            'claude-3-haiku' => 0.001,
            'claude-3-sonnet' => 0.015,
            'claude-3-opus' => 0.075,
        ];

        return $costs[$model] ?? 0.01;
    }

    /**
     * Verifica respuesta en cache.
     *
     * @param string $prompt
     *   El prompt.
     * @param string $model
     *   El modelo.
     *
     * @return string|null
     *   Respuesta cacheada o NULL.
     */
    /**
     * FIX-004: Método helper para obtener el tenant actual del contexto.
     *
     * @return int|null
     *   ID del tenant actual o NULL si no hay contexto.
     */
    protected function getCurrentTenantId(): ?int
    {
        // Intentar obtener del TenantContextService si está disponible.
        try {
            if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
                $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
                $tenant = $tenantContext->getCurrentTenant();
                return $tenant ? (int) $tenant->id() : NULL;
            }
        }
        catch (\Exception $e) {
            // Silenciar — no bloquear por resolución de tenant.
        }
        return NULL;
    }

    public function getCachedResponse(string $prompt, string $model, ?int $tenantId = NULL): ?string
    {
        // FIX-004: Incluir tenant_id en la cache key para evitar cross-tenant leak.
        $tenantId = $tenantId ?? $this->getCurrentTenantId() ?? 0;
        $hash = md5($prompt . $model . (string) $tenantId);
        $key = "ai_cache_{$hash}";

        $cached = $this->state->get($key);

        if ($cached && ($cached['expires'] > time())) {
            $this->loggerFactory->get('ai_cost')->info(
                '💾 Cache hit for prompt (saved tokens!)',
                []
            );
            return $cached['response'];
        }

        return NULL;
    }

    /**
     * Guarda respuesta en cache.
     *
     * @param string $prompt
     *   El prompt.
     * @param string $model
     *   El modelo.
     * @param string $response
     *   La respuesta.
     * @param int $ttl
     *   Tiempo de vida en segundos (default: 1 hora).
     */
    public function cacheResponse(string $prompt, string $model, string $response, int $ttl = 3600, ?int $tenantId = NULL): void
    {
        // FIX-004: Incluir tenant_id en la cache key para evitar cross-tenant leak.
        $tenantId = $tenantId ?? $this->getCurrentTenantId() ?? 0;
        $hash = md5($prompt . $model . (string) $tenantId);
        $key = "ai_cache_{$hash}";

        $this->state->set($key, [
            'response' => $response,
            'expires' => time() + $ttl,
            'model' => $model,
        ]);
    }

    /**
     * Obtiene estadísticas de optimización para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Estadísticas.
     */
    public function getOptimizationStats(int $tenantId): array
    {
        $period = date('Y-m');
        $usage = $this->getTenantUsage($tenantId);
        $budget = $this->getTenantBudget($tenantId) / 100; // Convertir a dólares.

        $detailKey = "tenant_ai_usage_detail_{$tenantId}_{$period}";
        $details = $this->state->get($detailKey, []);

        // Calcular estadísticas por modelo.
        $byModel = [];
        foreach ($details as $detail) {
            $model = $detail['model'];
            if (!isset($byModel[$model])) {
                $byModel[$model] = ['calls' => 0, 'tokens' => 0, 'cost' => 0];
            }
            $byModel[$model]['calls']++;
            $byModel[$model]['tokens'] += $detail['input_tokens'] + $detail['output_tokens'];
            $byModel[$model]['cost'] += $detail['cost'];
        }

        return [
            'period' => $period,
            'budget' => $budget,
            'usage' => $usage,
            'usage_percent' => $budget > 0 ? ($usage / $budget) * 100 : 0,
            'remaining' => max(0, $budget - $usage),
            'total_calls' => count($details),
            'by_model' => $byModel,
            'estimated_savings' => $this->calculateSavings($details),
        ];
    }

    /**
     * Calcula ahorros estimados por routing inteligente.
     */
    protected function calculateSavings(array $details): float
    {
        $savingsIfAllGpt4 = 0;
        $actualCost = 0;

        foreach ($details as $detail) {
            $actualCost += $detail['cost'];
            $tokens = $detail['input_tokens'] + $detail['output_tokens'];
            $savingsIfAllGpt4 += ($tokens / 1000) * 0.06; // Costo si todo fuera GPT-4.
        }

        return max(0, $savingsIfAllGpt4 - $actualCost);
    }

}
