<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de biblioteca de experimentos de validación.
 *
 * Proporciona acceso a los 44 experimentos de validación basados
 * en "Testing Business Ideas" de Strategyzer, adaptados al contexto
 * del programa Andalucía +ei.
 *
 * @see docs/tecnicos/20260121a-experiment_library_catalog.json
 */
class ExperimentLibraryService
{

    /**
     * Categorías de experimentos.
     */
    const CATEGORIES = [
        'DISCOVERY' => [
            'label' => 'Descubrimiento',
            'description' => 'No sabes si el problema existe',
            'evidence_strength' => 'Débil a Media',
            'count' => 10,
            'unlock_week' => 4,
        ],
        'INTEREST' => [
            'label' => 'Interés',
            'description' => 'Quieres medir interés sin producto',
            'evidence_strength' => 'Media',
            'count' => 12,
            'unlock_week' => 7,
        ],
        'PREFERENCE' => [
            'label' => 'Preferencia',
            'description' => 'Quieres validar la solución específica',
            'evidence_strength' => 'Media a Fuerte',
            'count' => 12,
            'unlock_week' => 10,
        ],
        'COMMITMENT' => [
            'label' => 'Compromiso',
            'description' => 'Quieres evidencia de pago real',
            'evidence_strength' => 'Fuerte',
            'count' => 10,
            'unlock_week' => 12,
        ],
    ];

    /**
     * Los 10 experimentos más recomendados para Andalucía +ei.
     * Seleccionados por facilidad de ejecución, coste cero y relevancia.
     */
    const TOP_RECOMMENDED = [1, 11, 16, 23, 24, 35, 38, 2, 3, 5];

    /**
     * Obtiene el mapeo de experimentos a carriles.
     *
     * @return array
     *   Mapeo por carril.
     */
    public static function getCarrilExperiments(): array
    {
        return [
            'IMPULSO' => range(1, 15),  // Experimentos más simples
            'ACELERA' => range(1, 44),  // Todo el catálogo
        ];
    }

    /**
     * Feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Catálogo de experimentos cargado.
     */
    protected ?array $catalog = NULL;

    /**
     * Constructor.
     */
    public function __construct(
        FeatureUnlockService $featureUnlock,
        LoggerInterface $logger
    ) {
        $this->featureUnlock = $featureUnlock;
        $this->logger = $logger;
    }

    /**
     * Obtiene el catálogo completo de experimentos.
     *
     * @return array
     *   Array de experimentos.
     */
    public function getCatalog(): array
    {
        if ($this->catalog === NULL) {
            $this->loadCatalog();
        }
        return $this->catalog;
    }

    /**
     * Obtiene experimentos disponibles para un perfil.
     *
     * @param object|null $profile
     *   Perfil del emprendedor (opcional).
     * @param string|null $category
     *   Filtrar por categoría (opcional).
     *
     * @return array
     *   Experimentos disponibles.
     */
    public function getAvailableExperiments(?object $profile = NULL, ?string $category = NULL): array
    {
        $catalog = $this->getCatalog();

        // Si no hay perfil, devolver todo el catálogo (admin)
        if ($profile === NULL) {
            if ($category !== NULL) {
                return array_filter($catalog, fn($e) => ($e['category'] ?? '') === $category);
            }
            return $catalog;
        }

        $carril = $this->getProfileCarril($profile);
        $allowedIds = self::getCarrilExperiments()[$carril] ?? [];

        $available = [];
        foreach ($catalog as $experiment) {
            // Verificar si el experimento está disponible para el carril
            if (!in_array($experiment['id'], $allowedIds)) {
                continue;
            }

            // Verificar si la categoría está desbloqueada
            $expCategory = $experiment['category'] ?? 'DISCOVERY';
            if (!$this->featureUnlock->isExperimentTypeAvailable($expCategory, $profile)) {
                continue;
            }

            // Filtrar por categoría si se especificó
            if ($category !== NULL && $expCategory !== $category) {
                continue;
            }

            $available[] = $experiment;
        }

        return $available;
    }

    /**
     * Obtiene un experimento por ID.
     *
     * @param int $experimentId
     *   ID del experimento.
     *
     * @return array|null
     *   Datos del experimento o NULL.
     */
    public function getExperiment(int $experimentId): ?array
    {
        $catalog = $this->getCatalog();
        foreach ($catalog as $experiment) {
            if ((int) $experiment['id'] === $experimentId) {
                return $experiment;
            }
        }
        return NULL;
    }

    /**
     * Obtiene un experimento por ID (alias para compatibilidad con controladores).
     *
     * @param string $experimentId
     *   ID del experimento.
     *
     * @return array|null
     *   Datos del experimento o NULL.
     */
    public function getExperimentById(string $experimentId): ?array
    {
        return $this->getExperiment((int) $experimentId);
    }

    /**
     * Obtiene todas las categorías.
     *
     * @return array
     *   Categorías.
     */
    public function getCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Sugiere experimentos para una hipótesis.
     *
     * @param array $hypothesis
     *   Datos de la hipótesis con type (DESIRABILITY, FEASIBILITY, VIABILITY).
     * @param object $profile
     *   Perfil del emprendedor.
     * @param int $limit
     *   Número máximo de sugerencias.
     *
     * @return array
     *   Experimentos sugeridos ordenados por relevancia.
     */
    public function suggestExperiments(array $hypothesis, object $profile, int $limit = 5): array
    {
        $available = $this->getAvailableExperiments($profile);
        $hypothesisType = $hypothesis['type'] ?? 'DESIRABILITY';
        $carril = $this->getProfileCarril($profile);

        // Puntuar cada experimento
        $scored = [];
        foreach ($available as $experiment) {
            $score = 0;

            // +20 si coincide el tipo de hipótesis
            if (($experiment['hypothesis_type'] ?? '') === $hypothesisType) {
                $score += 20;
            }

            // +15 si está en los top recomendados
            if (in_array($experiment['id'], self::TOP_RECOMMENDED)) {
                $score += 15;
            }

            // +10 por coste gratuito para carril IMPULSO
            if ($carril === 'IMPULSO' && ($experiment['cost_level'] ?? '') === 'FREE') {
                $score += 10;
            }

            // +10 por tiempo en horas (más rápido)
            if (($experiment['time_required'] ?? '') === 'HOURS') {
                $score += 10;
            }

            // +5 por fuerza de evidencia fuerte
            if (($experiment['evidence_strength'] ?? '') === 'STRONG') {
                $score += 5;
            }

            $scored[] = [
                'experiment' => $experiment,
                'score' => $score,
                'match_reason' => $this->getMatchReason($experiment, $hypothesisType, $carril),
            ];
        }

        // Ordenar por score descendente
        usort($scored, fn($a, $b) => $b['score'] - $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Obtiene experimentos por categoría.
     *
     * @param string $category
     *   Categoría (DISCOVERY, INTEREST, PREFERENCE, COMMITMENT).
     *
     * @return array
     *   Experimentos de la categoría.
     */
    public function getByCategory(string $category): array
    {
        $catalog = $this->getCatalog();
        return array_filter($catalog, fn($e) => ($e['category'] ?? '') === $category);
    }

    /**
     * Genera la razón de coincidencia para una sugerencia.
     */
    protected function getMatchReason(array $experiment, string $hypothesisType, string $carril): string
    {
        $reasons = [];

        if (($experiment['hypothesis_type'] ?? '') === $hypothesisType) {
            $reasons[] = 'coincide con tu tipo de hipótesis';
        }

        if (in_array($experiment['id'], self::TOP_RECOMMENDED)) {
            $reasons[] = 'recomendado para Andalucía +ei';
        }

        if ($carril === 'IMPULSO' && ($experiment['cost_level'] ?? '') === 'FREE') {
            $reasons[] = 'sin coste';
        }

        if (($experiment['time_required'] ?? '') === 'HOURS') {
            $reasons[] = 'rápido de ejecutar';
        }

        return $reasons ? implode(', ', $reasons) : 'relevante para tu situación';
    }

    /**
     * Obtiene el carril del perfil.
     */
    protected function getProfileCarril(object $profile): string
    {
        if (method_exists($profile, 'getCarril')) {
            return $profile->getCarril();
        }
        if (method_exists($profile, 'get') && $profile->hasField('carril')) {
            return $profile->get('carril')->value ?? 'IMPULSO';
        }
        return 'IMPULSO';
    }

    /**
     * Carga el catálogo desde el archivo JSON.
     */
    protected function loadCatalog(): void
    {
        $modulePath = \Drupal::service('extension.list.module')
            ->getPath('jaraba_copilot_v2');
        $jsonPath = $modulePath . '/data/experiment_library_catalog.json';

        if (!file_exists($jsonPath)) {
            $this->logger->warning('Catálogo de experimentos no encontrado: @path', [
                '@path' => $jsonPath,
            ]);
            $this->catalog = $this->getDefaultCatalog();
            return;
        }

        $content = file_get_contents($jsonPath);
        $data = json_decode($content, TRUE);
        $this->catalog = $data['experiments'] ?? [];
    }

    /**
     * Catálogo por defecto con los 10 experimentos top.
     */
    protected function getDefaultCatalog(): array
    {
        return [
            [
                'id' => 1,
                'name_es' => 'Entrevista de Descubrimiento',
                'name_en' => 'Customer Discovery Interview',
                'category' => 'DISCOVERY',
                'hypothesis_type' => 'DESIRABILITY',
                'time_required' => 'HOURS',
                'cost_level' => 'FREE',
                'evidence_strength' => 'WEAK',
                'carril_recommended' => 'BOTH',
                'description' => 'Conversaciones abiertas con clientes potenciales para entender sus problemas reales.',
            ],
            [
                'id' => 11,
                'name_es' => 'Landing Page Simple',
                'name_en' => 'Simple Landing Page',
                'category' => 'INTEREST',
                'hypothesis_type' => 'DESIRABILITY',
                'time_required' => 'HOURS',
                'cost_level' => 'FREE',
                'evidence_strength' => 'MEDIUM',
                'carril_recommended' => 'BOTH',
                'description' => 'Página web de una pantalla que captura emails de interesados antes de tener producto.',
            ],
            [
                'id' => 23,
                'name_es' => 'MVP Concierge',
                'name_en' => 'Concierge MVP',
                'category' => 'PREFERENCE',
                'hypothesis_type' => 'FEASIBILITY',
                'time_required' => 'DAYS',
                'cost_level' => 'FREE',
                'evidence_strength' => 'STRONG',
                'carril_recommended' => 'BOTH',
                'description' => 'Entregar servicio manualmente como si fuera automático para aprender qué funciona.',
            ],
            [
                'id' => 35,
                'name_es' => 'Preventa',
                'name_en' => 'Pre-sale',
                'category' => 'COMMITMENT',
                'hypothesis_type' => 'VIABILITY',
                'time_required' => 'DAYS',
                'cost_level' => 'FREE',
                'evidence_strength' => 'STRONG',
                'carril_recommended' => 'BOTH',
                'description' => 'Ofrecer producto/servicio para compra ANTES de que exista completamente.',
            ],
        ];
    }

    /**
     * Obtiene información de categoría.
     *
     * @param string $category
     *   Nombre de la categoría.
     *
     * @return array|null
     *   Información de la categoría.
     */
    public function getCategoryInfo(string $category): ?array
    {
        return self::CATEGORIES[$category] ?? NULL;
    }

    /**
     * Obtiene todas las categorías con su estado de desbloqueo.
     *
     * @param object|null $profile
     *   Perfil del emprendedor.
     *
     * @return array
     *   Categorías con estado.
     */
    public function getCategoriesWithStatus(?object $profile = NULL): array
    {
        $result = [];
        foreach (self::CATEGORIES as $key => $info) {
            $result[$key] = $info;
            $result[$key]['unlocked'] = $this->featureUnlock->isExperimentTypeAvailable($key, $profile);
        }
        return $result;
    }

}
