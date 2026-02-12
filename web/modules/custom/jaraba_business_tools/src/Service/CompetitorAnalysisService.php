<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\ClientInterface;

/**
 * Service for competitive analysis and market research.
 *
 * Provides tools for entrepreneurs to analyze competitors,
 * identify market positioning, and discover opportunities.
 */
class CompetitorAnalysisService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Analysis dimensions.
     */
    protected const ANALYSIS_DIMENSIONS = [
        'digital_presence' => [
            'website' => 'Presencia web',
            'social_media' => 'Redes sociales',
            'reviews' => 'Reseñas online',
            'seo' => 'Posicionamiento SEO',
        ],
        'product_offering' => [
            'range' => 'Gama de productos',
            'pricing' => 'Estrategia de precios',
            'quality' => 'Calidad percibida',
            'differentiation' => 'Diferenciación',
        ],
        'market_position' => [
            'target' => 'Público objetivo',
            'value_proposition' => 'Propuesta de valor',
            'brand_strength' => 'Fortaleza de marca',
            'reach' => 'Alcance geográfico',
        ],
    ];

    /**
     * Constructs a new CompetitorAnalysisService.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        ClientFactory $httpClientFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->httpClient = $httpClientFactory->fromOptions([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'JarabaImpact/1.0 Competitive Analysis',
            ],
        ]);
    }

    /**
     * Creates a new competitive analysis.
     *
     * @param int $canvasId
     *   The Business Model Canvas ID
     * @param array $competitors
     *   Array of competitor data
     *
     * @return array
     *   Analysis results
     */
    public function createAnalysis(int $canvasId, array $competitors): array
    {
        $canvas = $this->entityTypeManager
            ->getStorage('business_model_canvas')
            ->load($canvasId);

        if (!$canvas) {
            throw new \InvalidArgumentException('Canvas not found');
        }

        $analysis = [
            'canvas_id' => $canvasId,
            'business_name' => $canvas->label(),
            'sector' => $canvas->get('sector')->value ?? 'otros',
            'competitors' => [],
            'swot' => [],
            'positioning_matrix' => [],
            'opportunities' => [],
            'created_at' => date('Y-m-d\TH:i:s'),
        ];

        // Analyze each competitor
        foreach ($competitors as $competitor) {
            $competitorAnalysis = $this->analyzeCompetitor($competitor);
            $analysis['competitors'][] = $competitorAnalysis;
        }

        // Generate positioning matrix
        $analysis['positioning_matrix'] = $this->generatePositioningMatrix($analysis['competitors']);

        // Identify opportunities
        $analysis['opportunities'] = $this->identifyOpportunities($analysis['competitors']);

        // Generate SWOT
        $analysis['swot'] = $this->generateSwot($canvas, $analysis['competitors']);

        return $analysis;
    }

    /**
     * Analyzes a single competitor.
     */
    protected function analyzeCompetitor(array $competitor): array
    {
        $scores = [];

        // Digital presence score
        $digitalScore = 0;
        if (!empty($competitor['website'])) {
            $digitalScore += 25;
            $websiteAnalysis = $this->analyzeWebsite($competitor['website']);
            $digitalScore += $websiteAnalysis['score'];
        }
        if (!empty($competitor['social_media'])) {
            $digitalScore += count($competitor['social_media']) * 10;
        }
        $scores['digital_presence'] = min(100, $digitalScore);

        // Product offering score (from user input)
        $scores['product_offering'] = $competitor['product_score'] ?? 50;

        // Market position score (from user input)
        $scores['market_position'] = $competitor['market_score'] ?? 50;

        // Calculate overall threat level
        $avgScore = array_sum($scores) / count($scores);
        $threatLevel = $this->calculateThreatLevel($avgScore, $competitor);

        return [
            'name' => $competitor['name'],
            'website' => $competitor['website'] ?? null,
            'category' => $competitor['category'] ?? 'direct',
            'scores' => $scores,
            'average_score' => round($avgScore),
            'threat_level' => $threatLevel,
            'strengths' => $competitor['strengths'] ?? [],
            'weaknesses' => $competitor['weaknesses'] ?? [],
        ];
    }

    /**
     * Analyzes a competitor's website.
     */
    protected function analyzeWebsite(string $url): array
    {
        $score = 0;
        $findings = [];

        try {
            // Check if website is accessible
            $response = $this->httpClient->request('HEAD', $url, [
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() === 200) {
                $score += 25;
                $findings[] = 'Sitio web accesible';

                // Check for HTTPS
                if (str_starts_with($url, 'https://')) {
                    $score += 10;
                    $findings[] = 'Usa HTTPS';
                }
            }
        } catch (\Exception $e) {
            $findings[] = 'Sitio web no accesible';
        }

        return [
            'score' => $score,
            'findings' => $findings,
        ];
    }

    /**
     * Calculates threat level.
     */
    protected function calculateThreatLevel(float $score, array $competitor): string
    {
        $category = $competitor['category'] ?? 'direct';

        // Direct competitors are more threatening
        $multiplier = match ($category) {
            'direct' => 1.2,
            'indirect' => 0.8,
            'potential' => 0.6,
            default => 1.0,
        };

        $adjustedScore = $score * $multiplier;

        if ($adjustedScore >= 80) {
            return 'high';
        } elseif ($adjustedScore >= 50) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Generates positioning matrix data.
     */
    protected function generatePositioningMatrix(array $competitors): array
    {
        $matrix = [
            'axes' => [
                'x' => 'price_level',      // 1-5 scale
                'y' => 'quality_level',    // 1-5 scale
            ],
            'positions' => [],
        ];

        foreach ($competitors as $competitor) {
            $matrix['positions'][] = [
                'name' => $competitor['name'],
                'x' => ($competitor['scores']['product_offering'] ?? 50) / 20, // Convert to 1-5
                'y' => ($competitor['scores']['market_position'] ?? 50) / 20,
                'bubble_size' => $competitor['average_score'],
                'threat_level' => $competitor['threat_level'],
            ];
        }

        return $matrix;
    }

    /**
     * Identifies market opportunities.
     */
    protected function identifyOpportunities(array $competitors): array
    {
        $opportunities = [];

        // Find gaps in digital presence
        $lowDigitalCount = 0;
        foreach ($competitors as $competitor) {
            if ($competitor['scores']['digital_presence'] < 50) {
                $lowDigitalCount++;
            }
        }

        if ($lowDigitalCount > count($competitors) / 2) {
            $opportunities[] = [
                'type' => 'digital_gap',
                'title' => 'Brecha digital en el sector',
                'description' => 'La mayoría de competidores tienen baja presencia digital. Oportunidad de liderazgo online.',
                'priority' => 'high',
            ];
        }

        // Find service/quality gaps
        $avgProductScore = 0;
        foreach ($competitors as $competitor) {
            $avgProductScore += $competitor['scores']['product_offering'];
        }
        $avgProductScore /= max(1, count($competitors));

        if ($avgProductScore < 60) {
            $opportunities[] = [
                'type' => 'quality_gap',
                'title' => 'Oportunidad de diferenciación por calidad',
                'description' => 'El nivel de producto/servicio del mercado es moderado. Puedes destacar con calidad superior.',
                'priority' => 'medium',
            ];
        }

        // Default opportunity
        if (empty($opportunities)) {
            $opportunities[] = [
                'type' => 'innovation',
                'title' => 'Mercado competitivo - innova',
                'description' => 'El mercado está bien servido. Busca innovación en modelo de negocio o nicho específico.',
                'priority' => 'medium',
            ];
        }

        return $opportunities;
    }

    /**
     * Generates SWOT analysis.
     */
    protected function generateSwot($canvas, array $competitors): array
    {
        $swot = [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

        // Extract from canvas value propositions
        $valueProps = $canvas->get('value_propositions')->value ?? [];
        if (!empty($valueProps)) {
            $swot['strengths'][] = 'Propuesta de valor definida';
        } else {
            $swot['weaknesses'][] = 'Propuesta de valor por definir';
        }

        // Analyze threats from competitors
        $highThreats = 0;
        foreach ($competitors as $competitor) {
            if ($competitor['threat_level'] === 'high') {
                $highThreats++;
                $swot['threats'][] = 'Competidor fuerte: ' . $competitor['name'];
            }
        }

        if ($highThreats === 0) {
            $swot['opportunities'][] = 'Baja competencia directa fuerte';
        }

        // Digital opportunity
        $swot['opportunities'][] = 'Digitalización del negocio';

        return $swot;
    }

    /**
     * Gets analysis dimensions for UI.
     */
    public function getAnalysisDimensions(): array
    {
        return self::ANALYSIS_DIMENSIONS;
    }

}
