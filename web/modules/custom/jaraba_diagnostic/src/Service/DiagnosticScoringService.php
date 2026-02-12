<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface;

/**
 * Servicio de scoring para diagnósticos empresariales.
 *
 * Implementa el motor de scoring multidimensional según spec 25:
 * overall_score = Σ (section_score × section_weight × sector_modifier)
 *
 * Niveles de madurez:
 * - Analógico (0-20): Sin presencia digital
 * - Básico (21-40): Presencia mínima
 * - Conectado (41-60): Canales activos sin integrar
 * - Digitalizado (61-80): Procesos digitalizados
 * - Inteligente (81-100): Automatización avanzada + IA
 */
class DiagnosticScoringService
{

    /**
     * Pesos por sección.
     */
    const SECTION_WEIGHTS = [
        'online_presence' => 0.20,
        'digital_operations' => 0.20,
        'digital_sales' => 0.25,
        'digital_marketing' => 0.20,
        'automation_ai' => 0.15,
    ];

    /**
     * Modificadores por sector.
     */
    const SECTOR_MODIFIERS = [
        'comercio' => [
            'online_presence' => 1.0,
            'digital_operations' => 0.8,
            'digital_sales' => 1.3,
            'digital_marketing' => 1.1,
            'automation_ai' => 0.8,
        ],
        'servicios' => [
            'online_presence' => 1.2,
            'digital_operations' => 1.0,
            'digital_sales' => 0.9,
            'digital_marketing' => 1.1,
            'automation_ai' => 0.8,
        ],
        'agro' => [
            'online_presence' => 0.8,
            'digital_operations' => 1.2,
            'digital_sales' => 1.0,
            'digital_marketing' => 0.9,
            'automation_ai' => 1.1,
        ],
        'hosteleria' => [
            'online_presence' => 1.2,
            'digital_operations' => 1.0,
            'digital_sales' => 1.1,
            'digital_marketing' => 1.1,
            'automation_ai' => 0.6,
        ],
        'industria' => [
            'online_presence' => 0.7,
            'digital_operations' => 1.3,
            'digital_sales' => 0.8,
            'digital_marketing' => 0.8,
            'automation_ai' => 1.4,
        ],
        'tech' => [
            'online_presence' => 1.0,
            'digital_operations' => 1.0,
            'digital_sales' => 1.0,
            'digital_marketing' => 1.0,
            'automation_ai' => 1.0,
        ],
        'otros' => [
            'online_presence' => 1.0,
            'digital_operations' => 1.0,
            'digital_sales' => 1.0,
            'digital_marketing' => 1.0,
            'automation_ai' => 1.0,
        ],
    ];

    /**
     * Factores de pérdida por gap (% del revenue).
     * Fuente: McKinsey Digital, Statista, HubSpot (spec 25).
     */
    const GAP_LOSS_FACTORS = [
        'no_web' => 0.12,           // 8-15%
        'no_ecommerce' => 0.18,     // 12-25%
        'no_crm' => 0.08,           // 5-10%
        'no_marketing' => 0.15,     // 10-20%
        'no_automation' => 0.05,    // 3-8%
    ];

    protected EntityTypeManagerInterface $entityTypeManager;
    protected Connection $database;
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        LoggerInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->logger = $loggerFactory;
    }

    /**
     * Calcula y actualiza los scores de un diagnóstico.
     *
     * @param \Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface $diagnostic
     *   El diagnóstico a procesar.
     * @param array $sectionScores
     *   Array de scores por sección [section_name => score 0-100].
     *
     * @return array
     *   Resultados calculados.
     */
    public function calculateScores(BusinessDiagnosticInterface $diagnostic, array $sectionScores): array
    {
        $sector = $diagnostic->getBusinessSector();
        $modifiers = self::SECTOR_MODIFIERS[$sector] ?? self::SECTOR_MODIFIERS['otros'];

        $weightedSum = 0;
        $totalWeight = 0;

        foreach (self::SECTION_WEIGHTS as $section => $weight) {
            $sectionScore = $sectionScores[$section] ?? 0;
            $modifier = $modifiers[$section] ?? 1.0;

            $weightedSum += $sectionScore * $weight * $modifier;
            $totalWeight += $weight * $modifier;
        }

        // Normalizar el score
        $overallScore = $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0;
        $overallScore = min(100, max(0, $overallScore));

        // Determinar nivel de madurez
        $maturityLevel = $this->determineMaturityLevel($overallScore);

        // Calcular pérdida estimada
        $estimatedLoss = $this->calculateEstimatedLoss($diagnostic, $sectionScores);

        // Detectar gaps prioritarios
        $priorityGaps = $this->detectPriorityGaps($sectionScores, $sector);

        // Actualizar el diagnóstico
        $diagnostic->setOverallScore($overallScore);
        $diagnostic->setMaturityLevel($maturityLevel);
        $diagnostic->setEstimatedLoss($estimatedLoss);
        $diagnostic->set('priority_gaps', json_encode($priorityGaps));

        return [
            'overall_score' => $overallScore,
            'maturity_level' => $maturityLevel,
            'estimated_loss' => $estimatedLoss,
            'priority_gaps' => $priorityGaps,
            'section_scores' => $sectionScores,
        ];
    }

    /**
     * Determina el nivel de madurez según el score.
     */
    protected function determineMaturityLevel(float $score): string
    {
        return match (TRUE) {
            $score < 20 => 'analogico',
            $score < 40 => 'basico',
            $score < 60 => 'conectado',
            $score < 80 => 'digitalizado',
            default => 'inteligente',
        };
    }

    /**
     * Calcula la pérdida anual estimada basada en gaps.
     */
    protected function calculateEstimatedLoss(BusinessDiagnosticInterface $diagnostic, array $sectionScores): float
    {
        $annualRevenue = (float) ($diagnostic->get('annual_revenue')->value ?? 0);

        if ($annualRevenue <= 0) {
            return 0;
        }

        $totalLossPercent = 0;

        // Si presencia online es baja, hay pérdida por no tener web
        if (($sectionScores['online_presence'] ?? 0) < 30) {
            $totalLossPercent += self::GAP_LOSS_FACTORS['no_web'];
        }

        // Si ventas digitales son bajas, pérdida por no tener e-commerce
        if (($sectionScores['digital_sales'] ?? 0) < 25) {
            $totalLossPercent += self::GAP_LOSS_FACTORS['no_ecommerce'];
        }

        // Si operaciones digitales bajas, pérdida por no tener CRM
        if (($sectionScores['digital_operations'] ?? 0) < 30) {
            $totalLossPercent += self::GAP_LOSS_FACTORS['no_crm'];
        }

        // Si marketing digital bajo
        if (($sectionScores['digital_marketing'] ?? 0) < 30) {
            $totalLossPercent += self::GAP_LOSS_FACTORS['no_marketing'];
        }

        return round($annualRevenue * $totalLossPercent, 2);
    }

    /**
     * Detecta los gaps prioritarios.
     */
    protected function detectPriorityGaps(array $sectionScores, string $sector): array
    {
        $gaps = [];
        $modifiers = self::SECTOR_MODIFIERS[$sector] ?? self::SECTOR_MODIFIERS['otros'];

        foreach ($sectionScores as $section => $score) {
            $weight = self::SECTION_WEIGHTS[$section] ?? 0;
            $modifier = $modifiers[$section] ?? 1.0;

            // Un gap es prioritario si score < 40 y tiene peso significativo
            if ($score < 40 && ($weight * $modifier) > 0.15) {
                $gaps[] = [
                    'area' => $section,
                    'score' => $score,
                    'priority' => round((100 - $score) * $weight * $modifier, 2),
                ];
            }
        }

        // Ordenar por prioridad descendente
        usort($gaps, fn($a, $b) => $b['priority'] <=> $a['priority']);

        // Retornar top 3
        return array_slice($gaps, 0, 3);
    }

    /**
     * Recomienda un itinerario de digitalización según resultados.
     */
    public function recommendPath(BusinessDiagnosticInterface $diagnostic): ?int
    {
        $maturityLevel = $diagnostic->getMaturityLevel();
        $sector = $diagnostic->getBusinessSector();

        // Query digitalization_path entity matching maturity level and sector.
        $this->logger->info('Recommendation requested for diagnostic @id: level=@level, sector=@sector', [
            '@id' => $diagnostic->id(),
            '@level' => $maturityLevel,
            '@sector' => $sector,
        ]);

        try {
            $pathStorage = $this->entityTypeManager->getStorage('digitalization_path');
            $query = $pathStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('target_maturity_level', $maturityLevel)
                ->condition('sector', $sector)
                ->condition('status', TRUE)
                ->sort('weight', 'ASC')
                ->range(0, 1);

            $pathIds = $query->execute();

            if (!empty($pathIds)) {
                return (int) reset($pathIds);
            }

            // Fallback: search by maturity level only (any sector).
            $fallbackIds = $pathStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('target_maturity_level', $maturityLevel)
                ->condition('status', TRUE)
                ->sort('weight', 'ASC')
                ->range(0, 1)
                ->execute();

            if (!empty($fallbackIds)) {
                return (int) reset($fallbackIds);
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('digitalization_path entity not available: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return NULL;
    }

}
