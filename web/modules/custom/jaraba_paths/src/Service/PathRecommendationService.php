<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_diagnostic\Service\DiagnosticScoringService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de recomendación de itinerarios.
 *
 * Recomienda paths basándose en los resultados del diagnóstico.
 */
class PathRecommendationService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected DiagnosticScoringService $scoringService;
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        DiagnosticScoringService $scoringService,
        LoggerInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->scoringService = $scoringService;
        $this->logger = $loggerFactory;
    }

    /**
     * Recomienda paths basándose en diagnóstico.
     *
     * @param int $diagnosticId
     *   ID del diagnóstico.
     *
     * @return array
     *   Array de paths recomendados con score de matching.
     */
    public function getRecommendationsFromDiagnostic(int $diagnosticId): array
    {
        $diagnostic = $this->entityTypeManager->getStorage('business_diagnostic')
            ->load($diagnosticId);

        if (!$diagnostic) {
            return [];
        }

        $sector = $diagnostic->get('business_sector')->value;
        $maturityLevel = $diagnostic->get('maturity_level')->value;

        return $this->findMatchingPaths($sector, $maturityLevel);
    }

    /**
     * Encuentra paths que coinciden con sector y nivel.
     */
    public function findMatchingPaths(string $sector, ?string $maturityLevel): array
    {
        $storage = $this->entityTypeManager->getStorage('digitalization_path');

        // Buscar paths publicados
        $query = $storage->getQuery()
            ->condition('status', TRUE)
            ->accessCheck(TRUE);

        $ids = $query->execute();
        $paths = $storage->loadMultiple($ids);

        $recommendations = [];

        foreach ($paths as $path) {
            /** @var \Drupal\jaraba_paths\Entity\DigitalizationPathInterface $path */
            $score = $this->calculateMatchScore($path, $sector, $maturityLevel);

            $recommendations[] = [
                'path_id' => $path->id(),
                'uuid' => $path->uuid(),
                'title' => $path->getTitle(),
                'sector' => $path->getTargetSector(),
                'estimated_weeks' => $path->getEstimatedWeeks(),
                'is_featured' => $path->isFeatured(),
                'match_score' => $score,
            ];
        }

        // Ordenar por score descendente
        usort($recommendations, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return $recommendations;
    }

    /**
     * Calcula el score de matching entre path y perfil.
     */
    protected function calculateMatchScore($path, string $sector, ?string $maturityLevel): int
    {
        $score = 0;

        // Match por sector (+50 puntos)
        $pathSector = $path->getTargetSector();
        if ($pathSector === $sector) {
            $score += 50;
        } elseif ($pathSector === 'general') {
            $score += 25;
        }

        // Match por nivel de madurez (+40 puntos)
        $pathMaturity = $path->getTargetMaturityLevel();
        if ($pathMaturity && $pathMaturity === $maturityLevel) {
            $score += 40;
        } elseif (!$pathMaturity) {
            $score += 20; // Path genérico
        }

        // Bonus por destacado (+10 puntos)
        if ($path->isFeatured()) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Obtiene el path más recomendado para un diagnóstico.
     */
    public function getBestMatch(int $diagnosticId): ?array
    {
        $recommendations = $this->getRecommendationsFromDiagnostic($diagnosticId);

        return !empty($recommendations) ? $recommendations[0] : NULL;
    }

}
