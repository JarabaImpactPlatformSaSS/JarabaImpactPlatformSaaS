<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de scoring para el diagnostico express de empleabilidad.
 *
 * PROPOSITO:
 * Calcula el score (0-10) y el perfil de empleabilidad basado en
 * las respuestas a 3 preguntas: LinkedIn, CV ATS, Estrategia.
 *
 * ESTRATEGIA DE PESOS:
 * - LinkedIn: 40% (presencia digital clave en el mercado actual)
 * - CV ATS: 35% (el CV es el primer filtro en procesos de seleccion)
 * - Estrategia: 25% (la proactividad y enfoque en la busqueda)
 *
 * UMBRALES DE PERFIL:
 * - <2 = Invisible (no tiene presencia profesional)
 * - <4 = Desconectado (existe pero sin estrategia)
 * - <6 = En Construccion (ha empezado a trabajar su marca)
 * - <8 = Competitivo (perfil solido con areas de mejora)
 * - >=8 = Magnetico (perfil optimizado que atrae oportunidades)
 *
 * SPEC: 20260120b S3
 */
class EmployabilityScoringService {

  /**
   * Pesos de cada dimension.
   */
  protected const WEIGHTS = [
    'linkedin' => 0.40,
    'cv_ats' => 0.35,
    'estrategia' => 0.25,
  ];

  /**
   * Umbrales de perfil (limite superior exclusivo).
   */
  protected const PROFILE_THRESHOLDS = [
    ['max' => 2.0, 'type' => 'invisible'],
    ['max' => 4.0, 'type' => 'desconectado'],
    ['max' => 6.0, 'type' => 'construccion'],
    ['max' => 8.0, 'type' => 'competitivo'],
    ['max' => 10.1, 'type' => 'magnetico'],
  ];

  /**
   * Etiquetas legibles de cada perfil.
   */
  protected const PROFILE_LABELS = [
    'invisible' => 'Invisible',
    'desconectado' => 'Desconectado',
    'construccion' => 'En Construccion',
    'competitivo' => 'Competitivo',
    'magnetico' => 'Magnetico',
  ];

  /**
   * Descripciones de cada perfil.
   */
  protected const PROFILE_DESCRIPTIONS = [
    'invisible' => 'Tu presencia profesional es practicamente inexistente. Los reclutadores no pueden encontrarte.',
    'desconectado' => 'Existes en el mercado laboral pero sin una estrategia clara. Tus esfuerzos no estan dando frutos.',
    'construccion' => 'Has empezado a trabajar tu marca profesional. Tienes bases pero necesitas optimizar.',
    'competitivo' => 'Tu perfil es solido y compites bien. Hay areas especificas que pueden elevarte al siguiente nivel.',
    'magnetico' => 'Tu presencia profesional atrae oportunidades de forma natural. Eres referente en tu sector.',
  ];

  /**
   * El logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Calcula el score y perfil basado en las 3 respuestas.
   *
   * @param int $qLinkedin
   *   Respuesta LinkedIn (1-5).
   * @param int $qCvAts
   *   Respuesta CV ATS (1-5).
   * @param int $qEstrategia
   *   Respuesta Estrategia (1-5).
   *
   * @return array
   *   Array con: score, profile_type, profile_label, profile_description,
   *   primary_gap, dimension_scores, recommendations.
   */
  public function calculate(int $qLinkedin, int $qCvAts, int $qEstrategia): array {
    // Normalizar respuestas 1-5 a escala 0-10.
    $scores = [
      'linkedin' => ($qLinkedin - 1) * 2.5,
      'cv_ats' => ($qCvAts - 1) * 2.5,
      'estrategia' => ($qEstrategia - 1) * 2.5,
    ];

    // Calcular score ponderado.
    $totalScore = 0;
    foreach ($scores as $dimension => $score) {
      $totalScore += $score * self::WEIGHTS[$dimension];
    }
    $totalScore = round($totalScore, 2);

    // Determinar perfil.
    $profileType = $this->resolveProfile($totalScore);
    $primaryGap = $this->resolvePrimaryGap($scores);

    $this->logger->info('Diagnostico empleabilidad: score=@score, perfil=@profile, gap=@gap', [
      '@score' => $totalScore,
      '@profile' => $profileType,
      '@gap' => $primaryGap,
    ]);

    return [
      'score' => $totalScore,
      'profile_type' => $profileType,
      'profile_label' => self::PROFILE_LABELS[$profileType] ?? $profileType,
      'profile_description' => self::PROFILE_DESCRIPTIONS[$profileType] ?? '',
      'primary_gap' => $primaryGap,
      'dimension_scores' => $scores,
      'recommendations' => $this->generateRecommendations($profileType, $primaryGap, $scores),
    ];
  }

  /**
   * Resuelve el tipo de perfil basado en el score.
   */
  protected function resolveProfile(float $score): string {
    foreach (self::PROFILE_THRESHOLDS as $threshold) {
      if ($score < $threshold['max']) {
        return $threshold['type'];
      }
    }
    return 'magnetico';
  }

  /**
   * Identifica el gap principal (la dimension con menor puntuacion).
   */
  protected function resolvePrimaryGap(array $scores): string {
    $minDimension = 'linkedin';
    $minScore = PHP_FLOAT_MAX;

    foreach ($scores as $dimension => $score) {
      if ($score < $minScore) {
        $minScore = $score;
        $minDimension = $dimension;
      }
    }

    $gapLabels = [
      'linkedin' => 'linkedin',
      'cv_ats' => 'cv',
      'estrategia' => 'search_strategy',
    ];

    return $gapLabels[$minDimension] ?? 'linkedin';
  }

  /**
   * Genera recomendaciones contextualizadas por perfil y gap.
   */
  protected function generateRecommendations(string $profileType, string $primaryGap, array $scores): array {
    $recommendations = [];

    // Recomendacion principal segun gap.
    $gapRecommendations = [
      'linkedin' => [
        'title' => 'Optimiza tu perfil de LinkedIn',
        'description' => 'Tu presencia en LinkedIn es tu carta de presentacion digital. Un perfil optimizado multiplica x10 tus oportunidades.',
        'action' => 'Curso: LinkedIn para Profesionales',
        'icon' => 'linkedin',
      ],
      'cv' => [
        'title' => 'Moderniza tu CV para pasar filtros ATS',
        'description' => 'El 75% de los CVs son descartados por sistemas automaticos. Asegura que el tuyo pase el filtro.',
        'action' => 'Herramienta: CV Builder con IA',
        'icon' => 'cv',
      ],
      'search_strategy' => [
        'title' => 'Define tu estrategia de busqueda',
        'description' => 'Buscar empleo sin estrategia es como navegar sin mapa. Define tu plan de accion.',
        'action' => 'Ruta: Estrategia de Busqueda de Empleo',
        'icon' => 'strategy',
      ],
    ];

    if (isset($gapRecommendations[$primaryGap])) {
      $recommendations[] = $gapRecommendations[$primaryGap];
    }

    // Recomendaciones adicionales por perfil.
    if (in_array($profileType, ['invisible', 'desconectado'])) {
      $recommendations[] = [
        'title' => 'Ruta de Transformacion Digital',
        'description' => 'Programa completo para construir tu presencia profesional desde cero.',
        'action' => 'Inscribirme ahora',
        'icon' => 'rocket',
      ];
    }

    if ($profileType === 'competitivo') {
      $recommendations[] = [
        'title' => 'Preparacion para Entrevistas',
        'description' => 'Domina las entrevistas con simulaciones IA y feedback personalizado.',
        'action' => 'Practicar con Copilot',
        'icon' => 'interview',
      ];
    }

    return $recommendations;
  }

}
