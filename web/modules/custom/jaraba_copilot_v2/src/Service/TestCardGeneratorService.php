<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio para generar Test Cards según Testing Business Ideas (Osterwalder).
 *
 * Una Test Card documenta el diseño de un experimento para validar hipótesis:
 * - Hipótesis: Lo que queremos probar
 * - Test: Cómo lo vamos a probar
 * - Métrica: Qué mediremos
 * - Criterio: Umbral de éxito/fracaso
 *
 * @see https://www.strategyzer.com/blog/test-cards-learning-cards
 */
class TestCardGeneratorService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountProxyInterface $currentUser;
    protected LoggerChannelInterface $logger;

    /**
     * Tipos de experimentos disponibles (del libro Testing Business Ideas).
     */
    const EXPERIMENT_TYPES = [
        'discovery' => [
            'name' => 'Discovery',
            'description' => 'Explorar si existe un problema real',
            'methods' => ['customer_interview', 'observation', 'survey'],
            'cost' => 'low',
            'evidence_strength' => 'weak',
        ],
        'validation' => [
            'name' => 'Validation',
            'description' => 'Confirmar que la solución resuelve el problema',
            'methods' => ['landing_page', 'mvp', 'presale', 'concierge'],
            'cost' => 'medium',
            'evidence_strength' => 'medium',
        ],
        'monetization' => [
            'name' => 'Monetization',
            'description' => 'Probar disposición a pagar',
            'methods' => ['pricing_test', 'crowdfunding', 'presale_order'],
            'cost' => 'medium',
            'evidence_strength' => 'strong',
        ],
    ];

    /**
     * Métodos de experimento con plantillas.
     */
    const EXPERIMENT_METHODS = [
        'customer_interview' => [
            'name' => 'Entrevista de Cliente',
            'template' => 'Realizar %d entrevistas siguiendo Mom Test',
            'default_sample' => 10,
            'time_days' => 7,
        ],
        'landing_page' => [
            'name' => 'Landing Page Test',
            'template' => 'Crear landing con CTA y medir conversión',
            'default_sample' => 100,
            'time_days' => 14,
        ],
        'mvp' => [
            'name' => 'MVP',
            'template' => 'Construir versión mínima y medir uso',
            'default_sample' => 20,
            'time_days' => 30,
        ],
        'presale' => [
            'name' => 'Pre-venta',
            'template' => 'Ofrecer producto antes de construirlo',
            'default_sample' => 10,
            'time_days' => 14,
        ],
        'concierge' => [
            'name' => 'Concierge',
            'template' => 'Servicio manual para simular solución',
            'default_sample' => 5,
            'time_days' => 21,
        ],
        'pricing_test' => [
            'name' => 'Test de Precio',
            'template' => 'Probar diferentes precios y medir conversión',
            'default_sample' => 50,
            'time_days' => 14,
        ],
    ];

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
     * Genera una Test Card basada en una hipótesis.
     *
     * @param string $hypothesis
     *   La hipótesis a probar.
     * @param string $experimentType
     *   Tipo: discovery, validation, monetization.
     * @param string $method
     *   Método específico (customer_interview, landing_page, etc.).
     *
     * @return array
     *   Test Card estructurada.
     */
    public function generateTestCard(
        string $hypothesis,
        string $experimentType = 'discovery',
        string $method = 'customer_interview'
    ): array {
        $typeConfig = self::EXPERIMENT_TYPES[$experimentType] ?? self::EXPERIMENT_TYPES['discovery'];
        $methodConfig = self::EXPERIMENT_METHODS[$method] ?? self::EXPERIMENT_METHODS['customer_interview'];

        $testCard = [
            'id' => uniqid('tc_'),
            'created' => date('Y-m-d H:i:s'),
            'user_id' => (int) $this->currentUser->id(),

            // 1. HIPÓTESIS
            'hypothesis' => [
                'statement' => $hypothesis,
                'type' => $experimentType,
                'falsifiable' => $this->isFalsifiable($hypothesis),
            ],

            // 2. TEST
            'test' => [
                'method' => $method,
                'method_name' => $methodConfig['name'],
                'description' => sprintf($methodConfig['template'], $methodConfig['default_sample']),
                'sample_size' => $methodConfig['default_sample'],
                'duration_days' => $methodConfig['time_days'],
                'cost_estimate' => $typeConfig['cost'],
            ],

            // 3. MÉTRICA
            'metric' => [
                'primary' => $this->suggestMetric($experimentType, $method),
                'how_to_measure' => $this->suggestMeasurement($method),
            ],

            // 4. CRITERIO
            'criteria' => [
                'success_threshold' => $this->suggestSuccessThreshold($experimentType, $method),
                'failure_threshold' => $this->suggestFailureThreshold($experimentType, $method),
                'decision' => 'Pivotar si no se alcanza el umbral de éxito tras el experimento.',
            ],

            // Metadata
            'status' => 'draft',
            'evidence_strength' => $typeConfig['evidence_strength'],
        ];

        return $testCard;
    }

    /**
     * Verifica si una hipótesis es falsificable.
     */
    protected function isFalsifiable(string $hypothesis): bool
    {
        // Hipótesis no falsificables típicas
        $nonFalsifiable = [
            'mejor',
            'único',
            'revolucionario',
            'todo el mundo',
            'nadie',
            'siempre',
            'nunca',
            'obviamente',
            'claramente',
        ];

        $lower = mb_strtolower($hypothesis);
        foreach ($nonFalsifiable as $word) {
            if (mb_strpos($lower, $word) !== FALSE) {
                return FALSE;
            }
        }

        // Debe contener elementos medibles
        $measurable = ['%', 'euros', 'clientes', 'usuarios', 'días', 'veces'];
        foreach ($measurable as $word) {
            if (mb_strpos($lower, $word) !== FALSE) {
                return TRUE;
            }
        }

        return TRUE; // Default optimista
    }

    /**
     * Sugiere métrica según tipo y método.
     */
    protected function suggestMetric(string $type, string $method): string
    {
        $metrics = [
            'customer_interview' => 'Número de clientes que confirman el problema',
            'landing_page' => 'Tasa de conversión (clicks/visitas)',
            'mvp' => 'Usuarios activos diarios (DAU)',
            'presale' => 'Número de pre-pedidos',
            'concierge' => 'NPS de clientes piloto',
            'pricing_test' => 'Conversión por punto de precio',
        ];

        return $metrics[$method] ?? 'Definir métrica específica';
    }

    /**
     * Sugiere cómo medir.
     */
    protected function suggestMeasurement(string $method): string
    {
        $measurements = [
            'customer_interview' => 'Registro en hoja de cálculo + notas de entrevista',
            'landing_page' => 'Google Analytics + Hotjar',
            'mvp' => 'Métricas in-app + Mixpanel/Amplitude',
            'presale' => 'Contador de pedidos en Stripe/PayPal',
            'concierge' => 'Encuesta NPS post-servicio',
            'pricing_test' => 'A/B test con herramienta de landing',
        ];

        return $measurements[$method] ?? 'Definir sistema de medición';
    }

    /**
     * Sugiere umbral de éxito.
     */
    protected function suggestSuccessThreshold(string $type, string $method): string
    {
        $thresholds = [
            'customer_interview' => '8 de 10 confirman el problema',
            'landing_page' => '>5% conversión en CTA principal',
            'mvp' => '>30% retención D7',
            'presale' => '>20 pre-pedidos en 2 semanas',
            'concierge' => 'NPS >50',
            'pricing_test' => '>3% conversión al precio target',
        ];

        return $thresholds[$method] ?? 'Definir umbral';
    }

    /**
     * Sugiere umbral de fracaso.
     */
    protected function suggestFailureThreshold(string $type, string $method): string
    {
        $thresholds = [
            'customer_interview' => '<5 de 10 confirman',
            'landing_page' => '<1% conversión',
            'mvp' => '<10% retención D7',
            'presale' => '<5 pre-pedidos',
            'concierge' => 'NPS <0',
            'pricing_test' => '<1% conversión',
        ];

        return $thresholds[$method] ?? 'Definir umbral';
    }

    /**
     * Obtiene tipos de experimento disponibles.
     */
    public function getExperimentTypes(): array
    {
        return self::EXPERIMENT_TYPES;
    }

    /**
     * Obtiene métodos disponibles para un tipo.
     */
    public function getMethodsForType(string $type): array
    {
        $typeConfig = self::EXPERIMENT_TYPES[$type] ?? NULL;
        if (!$typeConfig) {
            return [];
        }

        $methods = [];
        foreach ($typeConfig['methods'] as $methodKey) {
            if (isset(self::EXPERIMENT_METHODS[$methodKey])) {
                $methods[$methodKey] = self::EXPERIMENT_METHODS[$methodKey];
            }
        }

        return $methods;
    }

}
