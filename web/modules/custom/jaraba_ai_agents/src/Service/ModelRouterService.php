<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de enrutamiento inteligente de modelos basado en complejidad.
 *
 * PROPÓSITO:
 * Enruta tareas al modelo IA más costo-efectivo que pueda manejarlas,
 * reduciendo costos hasta un 40% para tareas simples. Implementa el
 * patrón "Model Routing" para optimización de recursos IA.
 *
 * TIERS DISPONIBLES:
 * - 'fast': Claude Haiku - Tareas simples (clasificación, extracción)
 * - 'balanced': Claude Sonnet - Complejidad media (contenido, resúmenes)
 * - 'premium': Claude Sonnet 4 - Razonamiento complejo (análisis, creatividad)
 *
 * ALGORITMO:
 * 1. Evalúa complejidad de la tarea (tipo + características del prompt)
 * 2. Aplica modificadores según requisitos (velocidad vs calidad)
 * 3. Selecciona el tier mínimo capaz de manejar la complejidad
 * 4. Estima costo para transparencia
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class ModelRouterService
{

    /**
     * El gestor de proveedores IA.
     *
     * @var \Drupal\ai\AiProviderPluginManager
     */
    protected AiProviderPluginManager $aiProvider;

    /**
     * La factoría de configuración.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Configuración de tiers de modelos.
     *
     * Define proveedor, modelo, costos y límites de complejidad
     * para cada tier disponible.
     *
     * @var array
     */
    protected array $modelTiers = [
        // Tier 1: Rápido y económico - Tareas simples.
        'fast' => [
            'provider' => 'anthropic',
            'model' => 'claude-3-haiku-20240307',
            'cost_per_1k_input' => 0.00025,
            'cost_per_1k_output' => 0.00125,
            'max_complexity' => 0.3,
            'use_cases' => ['classification', 'simple_extraction', 'formatting'],
        ],
        // Tier 2: Balanceado - Complejidad media.
        'balanced' => [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
            'cost_per_1k_input' => 0.003,
            'cost_per_1k_output' => 0.015,
            'max_complexity' => 0.7,
            'use_cases' => ['content_generation', 'summarization', 'translation'],
        ],
        // Tier 3: Premium - Razonamiento complejo.
        'premium' => [
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-20250514',
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.075,
            'max_complexity' => 1.0,
            'use_cases' => ['complex_reasoning', 'creative_writing', 'analysis'],
        ],
    ];

    /**
     * Construye un ModelRouterService.
     *
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   El gestor de proveedores IA.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoría de configuración.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
    ) {
        $this->aiProvider = $aiProvider;
        $this->configFactory = $configFactory;
        $this->logger = $logger;

        // Cargar configuración personalizada si existe.
        $this->loadCustomConfig();
    }

    /**
     * Enruta una tarea al modelo óptimo.
     *
     * Analiza el tipo de tarea y prompt para determinar el tier
     * más costo-efectivo que pueda manejar la solicitud.
     *
     * @param string $taskType
     *   El tipo de tarea (ej: 'social_post', 'faq_answer').
     * @param string $prompt
     *   El prompt a analizar para estimar complejidad.
     * @param array $options
     *   Opciones adicionales:
     *   - 'force_tier': string (fast|balanced|premium) - Forzar tier específico.
     *   - 'require_speed': bool - Priorizar velocidad sobre calidad.
     *   - 'require_quality': bool - Priorizar calidad sobre costo.
     *
     * @return array
     *   Configuración del proveedor con:
     *   - 'provider_id': string - ID del proveedor IA.
     *   - 'model_id': string - ID del modelo a usar.
     *   - 'tier': string - Nombre del tier seleccionado.
     *   - 'estimated_cost': float - Costo estimado en USD.
     */
    public function route(string $taskType, string $prompt, array $options = []): array
    {
        // Verificar si hay tier forzado.
        if (!empty($options['force_tier']) && isset($this->modelTiers[$options['force_tier']])) {
            return $this->buildProviderConfig($options['force_tier'], $prompt);
        }

        // Evaluar complejidad de la tarea.
        $complexity = $this->assessComplexity($taskType, $prompt);

        // Modificador de velocidad: reducir complejidad para usar tier más rápido.
        if (!empty($options['require_speed'])) {
            $complexity = min($complexity, 0.3);
        }

        // Modificador de calidad: aumentar complejidad para usar tier premium.
        if (!empty($options['require_quality'])) {
            $complexity = max($complexity, 0.8);
        }

        // Seleccionar tier basado en complejidad calculada.
        $tier = $this->selectTier($complexity);

        $this->logger->info('Model routing: @task -> @tier (complejidad: @complexity)', [
            '@task' => $taskType,
            '@tier' => $tier,
            '@complexity' => round($complexity, 2),
        ]);

        return $this->buildProviderConfig($tier, $prompt);
    }

    /**
     * Evalúa la complejidad de una tarea.
     *
     * Combina el tipo de tarea predefinido con análisis heurístico
     * del prompt para determinar un score de complejidad.
     *
     * Factores analizados:
     * - Tipo de tarea (mapping predefinido)
     * - Longitud del prompt (>2000 chars aumenta complejidad)
     * - Palabras clave de análisis (analyze, compare, evaluate)
     * - Requisitos de creatividad (creative, innovative, unique)
     * - Estructuración del output (JSON, structured reduce complejidad)
     *
     * @param string $taskType
     *   El tipo de tarea.
     * @param string $prompt
     *   El texto del prompt.
     *
     * @return float
     *   Score de complejidad de 0.0 a 1.0.
     */
    public function assessComplexity(string $taskType, string $prompt): float
    {
        $complexity = 0.5; // Complejidad base.

        // Mapeo de complejidad por tipo de tarea.
        $taskComplexity = [
            // Tareas simples (Tier 1).
            'faq_answer' => 0.2,
            'product_description' => 0.3,
            'social_post' => 0.35,

            // Tareas medias (Tier 2).
            'email_promo' => 0.5,
            'review_response' => 0.5,
            'followup_email' => 0.45,
            'ticket_response' => 0.4,
            'help_article' => 0.55,

            // Tareas complejas (Tier 3).
            'brand_story' => 0.7,
            'product_story' => 0.65,
            'about_page' => 0.6,
            'ad_copy' => 0.6,
            'complaint_response' => 0.7,
        ];

        if (isset($taskComplexity[$taskType])) {
            $complexity = $taskComplexity[$taskType];
        }

        // Ajustar según longitud del prompt.
        $promptLength = strlen($prompt);
        if ($promptLength > 2000) {
            $complexity += 0.15;
        } elseif ($promptLength < 200) {
            $complexity -= 0.1;
        }

        // Ajustar según requisitos especiales detectados en el prompt.
        if (preg_match('/\b(analyze|compare|evaluate|critique|synthesize)\b/i', $prompt)) {
            $complexity += 0.2;
        }
        if (preg_match('/\b(JSON|structured|format)\b/i', $prompt)) {
            $complexity -= 0.05; // Output estructurado es más fácil.
        }
        if (preg_match('/\b(creative|innovative|unique|original)\b/i', $prompt)) {
            $complexity += 0.15;
        }
        if (preg_match('/\b(translate|summarize|extract)\b/i', $prompt)) {
            $complexity -= 0.1;
        }

        return max(0.0, min(1.0, $complexity));
    }

    /**
     * Selecciona el tier apropiado basado en complejidad.
     *
     * Itera los tiers en orden de costo ascendente y selecciona
     * el primero cuyo max_complexity >= complejidad calculada.
     *
     * @param float $complexity
     *   El score de complejidad (0.0 a 1.0).
     *
     * @return string
     *   El nombre del tier (fast|balanced|premium).
     */
    protected function selectTier(float $complexity): string
    {
        foreach ($this->modelTiers as $tierName => $config) {
            if ($complexity <= $config['max_complexity']) {
                return $tierName;
            }
        }
        return 'premium';
    }

    /**
     * Construye la configuración del proveedor para un tier.
     *
     * Incluye estimación de costo basada en tokens aproximados.
     *
     * @param string $tier
     *   El nombre del tier.
     * @param string $prompt
     *   El prompt (para estimación de costos).
     *
     * @return array
     *   Configuración del proveedor lista para usar.
     */
    protected function buildProviderConfig(string $tier, string $prompt): array
    {
        $config = $this->modelTiers[$tier];

        // Estimar tokens (aproximación: 1 token ≈ 4 caracteres).
        $inputTokens = (int) (strlen($prompt) / 4);
        $outputTokens = 500; // Promedio estimado de output.

        $estimatedCost = ($inputTokens / 1000 * $config['cost_per_1k_input']) +
            ($outputTokens / 1000 * $config['cost_per_1k_output']);

        return [
            'provider_id' => $config['provider'],
            'model_id' => $config['model'],
            'tier' => $tier,
            'estimated_cost' => round($estimatedCost, 6),
            'cost_per_1k_input' => $config['cost_per_1k_input'],
            'cost_per_1k_output' => $config['cost_per_1k_output'],
        ];
    }

    /**
     * Carga configuración personalizada desde config.
     *
     * Permite sobrescribir modelos y costos via configuración Drupal.
     */
    protected function loadCustomConfig(): void
    {
        $config = $this->configFactory->get('jaraba_ai_agents.model_routing');

        if (!$config->isNew()) {
            $customTiers = $config->get('tiers');
            if (!empty($customTiers)) {
                $this->modelTiers = array_merge($this->modelTiers, $customTiers);
            }
        }
    }

    /**
     * Calcula estadísticas de ahorro de costos.
     *
     * Compara el costo real con el costo hipotético de usar
     * siempre el tier premium para mostrar el ahorro del routing.
     *
     * @param array $usageLog
     *   Array de registros de uso con 'cost' y 'tokens'.
     *
     * @return array
     *   Estadísticas de ahorro:
     *   - 'actual_cost': Costo real total.
     *   - 'premium_equivalent': Lo que habría costado con premium.
     *   - 'savings': Ahorro absoluto en USD.
     *   - 'savings_percent': Porcentaje de ahorro.
     */
    public function calculateSavings(array $usageLog): array
    {
        $actualCost = 0;
        $premiumCost = 0;

        foreach ($usageLog as $usage) {
            $actualCost += $usage['cost'];
            // Calcular lo que habría costado con tier premium.
            $premiumCost += $usage['tokens'] / 1000 *
                ($this->modelTiers['premium']['cost_per_1k_input'] +
                    $this->modelTiers['premium']['cost_per_1k_output']) / 2;
        }

        $savings = $premiumCost - $actualCost;
        $savingsPercent = $premiumCost > 0 ? ($savings / $premiumCost) * 100 : 0;

        return [
            'actual_cost' => round($actualCost, 4),
            'premium_equivalent' => round($premiumCost, 4),
            'savings' => round($savings, 4),
            'savings_percent' => round($savingsPercent, 1),
        ];
    }

    /**
     * Obtiene todos los tiers disponibles.
     *
     * @return array
     *   Configuración de tiers de modelos.
     */
    public function getTiers(): array
    {
        return $this->modelTiers;
    }

}
