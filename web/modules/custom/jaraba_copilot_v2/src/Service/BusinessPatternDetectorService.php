<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

/**
 * Servicio para detectar patrones de negocio según Business Model Generation.
 *
 * Implementa los 10 patrones de modelo de negocio de Osterwalder:
 * 1. Unbundling - Separar modelos
 * 2. Long Tail - Cola larga
 * 3. Multi-sided Platforms - Plataformas multilaterales
 * 4. Free as Business Model - Freemium
 * 5. Open Business Models - Modelos abiertos
 * 6. Bait and Hook - Navaja y cuchilla
 * 7. Subscription - Suscripción
 * 8. Leasing - Arrendamiento
 * 9. Licensing - Licencias
 * 10. White Label - Marca blanca
 *
 * @see Business Model Generation (Osterwalder & Pigneur)
 */
class BusinessPatternDetectorService
{

    /**
     * Los 10 patrones de Business Model Generation.
     */
    const PATTERNS = [
        'unbundling' => [
            'name' => 'Unbundling',
            'name_es' => 'Desagregación',
            'description' => 'Separar las actividades de relación con clientes, innovación de producto e infraestructura en entidades independientes.',
            'triggers' => ['separar', 'desagregar', 'spin-off', 'core business', 'externalizar'],
            'examples' => ['Bancos (banca minorista vs inversión)', 'Telcos (red vs servicio)'],
            'when_to_use' => 'Cuando tienes actividades con economías de escala y alcance muy diferentes.',
            'bmc_affected' => ['key_activities', 'key_partners', 'cost_structure'],
        ],
        'long_tail' => [
            'name' => 'Long Tail',
            'name_es' => 'Cola Larga',
            'description' => 'Vender menos de más cosas: ofrecer un gran número de productos nicho que individualmente venden poco.',
            'triggers' => ['nicho', 'catálogo amplio', 'productos especializados', 'cola larga', 'long tail'],
            'examples' => ['Amazon', 'Netflix', 'iTunes', 'Etsy'],
            'when_to_use' => 'Cuando puedes agregar demanda de nichos pequeños a bajo coste.',
            'bmc_affected' => ['value_proposition', 'customer_segments', 'channels'],
        ],
        'multi_sided' => [
            'name' => 'Multi-sided Platform',
            'name_es' => 'Plataforma Multilateral',
            'description' => 'Conectar dos o más grupos de clientes interdependientes creando valor de red.',
            'triggers' => ['plataforma', 'marketplace', 'dos lados', 'conectar', 'intermediario', 'efecto red'],
            'examples' => ['Visa', 'Google', 'Airbnb', 'Uber'],
            'when_to_use' => 'Cuando puedes crear valor conectando grupos que se benefician mutuamente.',
            'bmc_affected' => ['customer_segments', 'value_proposition', 'revenue_streams'],
        ],
        'freemium' => [
            'name' => 'Freemium',
            'name_es' => 'Freemium',
            'description' => 'Ofrecer un servicio básico gratuito y cobrar por funciones premium.',
            'triggers' => ['gratis', 'gratuito', 'premium', 'freemium', 'versión free', 'conversión'],
            'examples' => ['Spotify', 'Dropbox', 'LinkedIn', 'Zoom'],
            'when_to_use' => 'Cuando el coste marginal es bajo y puedes convertir un % a premium.',
            'bmc_affected' => ['revenue_streams', 'customer_segments', 'value_proposition'],
            'conversion_rate' => '2-5% típico',
        ],
        'open_business' => [
            'name' => 'Open Business Model',
            'name_es' => 'Modelo de Negocio Abierto',
            'description' => 'Crear y capturar valor colaborando sistemáticamente con partners externos.',
            'triggers' => ['open source', 'colaboración', 'ecosistema', 'API abierta', 'co-creación'],
            'examples' => ['Red Hat', 'Procter & Gamble Connect+Develop', 'LEGO Ideas'],
            'when_to_use' => 'Cuando la innovación externa puede acelerar tu desarrollo.',
            'bmc_affected' => ['key_partners', 'key_activities', 'value_proposition'],
        ],
        'bait_hook' => [
            'name' => 'Bait and Hook (Razor & Blade)',
            'name_es' => 'Navaja y Cuchilla',
            'description' => 'Ofrecer un producto inicial barato o gratis y obtener ingresos de consumibles recurrentes.',
            'triggers' => ['consumibles', 'recambios', 'recurrente', 'lock-in', 'razor blade', 'cuchilla'],
            'examples' => ['Gillette', 'Nespresso', 'HP (impresoras)', 'PlayStation'],
            'when_to_use' => 'Cuando puedes crear dependencia de consumibles con márgenes altos.',
            'bmc_affected' => ['revenue_streams', 'customer_relationships', 'value_proposition'],
        ],
        'subscription' => [
            'name' => 'Subscription',
            'name_es' => 'Suscripción',
            'description' => 'Cobrar una cuota periódica por acceso continuo a un producto o servicio.',
            'triggers' => ['suscripción', 'mensual', 'anual', 'membresía', 'subscription', 'recurrente'],
            'examples' => ['Netflix', 'Salesforce', 'Amazon Prime', 'Gym'],
            'when_to_use' => 'Cuando el valor del cliente aumenta con el uso continuado.',
            'bmc_affected' => ['revenue_streams', 'customer_relationships'],
            'metrics' => ['MRR', 'Churn', 'LTV', 'CAC'],
        ],
        'leasing' => [
            'name' => 'Leasing',
            'name_es' => 'Arrendamiento',
            'description' => 'Permitir el uso temporal de un activo a cambio de una cuota periódica.',
            'triggers' => ['alquiler', 'leasing', 'renting', 'uso temporal', 'no propiedad'],
            'examples' => ['Hertz', 'WeWork', 'Caterpillar Financial'],
            'when_to_use' => 'Cuando el cliente prefiere acceso sobre propiedad.',
            'bmc_affected' => ['revenue_streams', 'key_resources'],
        ],
        'licensing' => [
            'name' => 'Licensing',
            'name_es' => 'Licencias',
            'description' => 'Monetizar propiedad intelectual permitiendo su uso a terceros.',
            'triggers' => ['licencia', 'patente', 'marca', 'franquicia', 'royalty', 'IP'],
            'examples' => ['Disney', 'Microsoft', 'ARM Holdings'],
            'when_to_use' => 'Cuando tienes IP valiosa que otros pueden comercializar.',
            'bmc_affected' => ['revenue_streams', 'key_resources', 'key_partners'],
        ],
        'white_label' => [
            'name' => 'White Label',
            'name_es' => 'Marca Blanca',
            'description' => 'Producir productos o servicios que otros venden bajo su propia marca.',
            'triggers' => ['marca blanca', 'white label', 'B2B', 'OEM', 'private label'],
            'examples' => ['Foxconn', 'Marca blanca supermercado'],
            'when_to_use' => 'Cuando tienes capacidad productiva pero no marca/distribución.',
            'bmc_affected' => ['customer_segments', 'channels', 'revenue_streams'],
        ],
    ];

    /**
     * Detecta patrones de negocio en el Business Model Canvas del emprendedor.
     *
     * @param array $bmcData
     *   Datos del BMC del emprendedor.
     *
     * @return array
     *   Patrones detectados con puntuación.
     */
    public function detectPatterns(array $bmcData): array
    {
        $detected = [];

        foreach (self::PATTERNS as $patternId => $pattern) {
            $score = $this->scorePattern($patternId, $bmcData);
            if ($score > 0) {
                $detected[$patternId] = [
                    'pattern' => $pattern,
                    'score' => $score,
                    'fit' => $this->calculateFit($score),
                ];
            }
        }

        // Ordenar por puntuación
        uasort($detected, fn($a, $b) => $b['score'] <=> $a['score']);

        return $detected;
    }

    /**
     * Puntúa un patrón según los datos del BMC.
     */
    protected function scorePattern(string $patternId, array $bmcData): int
    {
        $score = 0;
        $pattern = self::PATTERNS[$patternId];

        // Buscar triggers en los bloques del BMC
        $textToAnalyze = implode(' ', array_map(
            fn($block) => is_array($block) ? implode(' ', $block) : $block,
            $bmcData
        ));
        $textLower = mb_strtolower($textToAnalyze);

        foreach ($pattern['triggers'] as $trigger) {
            if (mb_strpos($textLower, $trigger) !== FALSE) {
                $score += 10;
            }
        }

        return $score;
    }

    /**
     * Calcula el fit del patrón.
     */
    protected function calculateFit(int $score): string
    {
        if ($score >= 30) {
            return 'high';
        }
        if ($score >= 15) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Sugiere patrones basados en el mensaje del usuario.
     *
     * @param string $message
     *   Mensaje del usuario.
     *
     * @return array
     *   Patrones sugeridos.
     */
    public function suggestFromMessage(string $message): array
    {
        $suggested = [];
        $messageLower = mb_strtolower($message);

        foreach (self::PATTERNS as $patternId => $pattern) {
            $matchCount = 0;
            foreach ($pattern['triggers'] as $trigger) {
                if (mb_strpos($messageLower, $trigger) !== FALSE) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $suggested[$patternId] = [
                    'pattern' => $pattern,
                    'matches' => $matchCount,
                ];
            }
        }

        uasort($suggested, fn($a, $b) => $b['matches'] <=> $a['matches']);

        return array_slice($suggested, 0, 3);
    }

    /**
     * Obtiene todos los patrones disponibles.
     */
    public function getAllPatterns(): array
    {
        return self::PATTERNS;
    }

    /**
     * Obtiene un patrón específico.
     */
    public function getPattern(string $patternId): ?array
    {
        return self::PATTERNS[$patternId] ?? NULL;
    }

    /**
     * Genera resumen para prompt del Copiloto.
     */
    public function getPatternsSummaryForPrompt(array $bmcData): string
    {
        $detected = $this->detectPatterns($bmcData);

        if (empty($detected)) {
            return 'No se detectan patrones de negocio claros. Considera explorar: freemium, suscripción, o plataforma multilateral.';
        }

        $parts = [];
        $top3 = array_slice($detected, 0, 3, TRUE);

        foreach ($top3 as $patternId => $data) {
            $parts[] = "{$data['pattern']['name_es']} (fit: {$data['fit']})";
        }

        return 'Patrones detectados: ' . implode(', ', $parts);
    }

}
