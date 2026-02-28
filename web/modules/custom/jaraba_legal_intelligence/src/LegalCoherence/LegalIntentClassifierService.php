<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Psr\Log\LoggerInterface;

/**
 * Clasificador de intent legal para activacion del pipeline LCIS.
 *
 * Capa 2 del LCIS (Legal Coherence Intelligence System).
 * Clasificacion hibrida en 2 fases:
 * 1. Keywords (coste cero, instantaneo) — resuelve ~80% de queries.
 * 2. LLM fast tier — solo para zona gris (score 0.15-0.85). [v2]
 *
 * Integrado como gate en SmartBaseAgent::applyLegalCoherence().
 * Si classify() retorna NON_LEGAL, todo el pipeline LCIS se bypasea.
 *
 * LEGAL-COHERENCE-INTENT-001: Layer 2 acts as gate. If NON_LEGAL,
 * entire pipeline bypassed. Zero overhead for non-legal queries.
 *
 * @see LegalCoherencePromptRule::requiresCoherence() (complementario)
 */
final class LegalIntentClassifierService {

  /**
   * Intent legal directo — consulta explicitamente juridica.
   */
  public const INTENT_LEGAL_DIRECT = 'LEGAL_DIRECT';

  /**
   * Intent legal implicito — implicaciones legales no explicitas.
   */
  public const INTENT_LEGAL_IMPLICIT = 'LEGAL_IMPLICIT';

  /**
   * Referencia legal tangencial — mencion tangencial a normativa.
   */
  public const INTENT_LEGAL_REFERENCE = 'LEGAL_REFERENCE';

  /**
   * Verificacion de cumplimiento — compliance check.
   */
  public const INTENT_COMPLIANCE_CHECK = 'COMPLIANCE_CHECK';

  /**
   * Sin componente juridico.
   */
  public const INTENT_NON_LEGAL = 'NON_LEGAL';

  /**
   * Keywords que indican intent legal directo.
   *
   * Score > 0.85 = LEGAL_DIRECT sin necesidad de LLM.
   */
  private const LEGAL_KEYWORDS = [
    // Normativa.
    'ley', 'decreto', 'reglamento', 'normativa', 'regulacion', 'legislacion',
    'articulo', 'disposicion', 'BOE', 'BOJA', 'ordenanza', 'real decreto',
    'orden ministerial', 'directiva', 'reglamento europeo',
    // Procedimientos.
    'recurso', 'demanda', 'denuncia', 'sancion', 'multa', 'infraccion',
    'plazo legal', 'prescripcion', 'caducidad', 'procedimiento administrativo',
    'recurso contencioso', 'recurso de alzada', 'via administrativa',
    // Derechos y obligaciones.
    'derecho', 'obligacion', 'responsabilidad', 'indemnizacion', 'despido',
    'contrato', 'clausula', 'garantia legal', 'proteccion de datos', 'RGPD',
    'LOPD', 'tutela judicial', 'derecho fundamental',
    // Instituciones.
    'tribunal', 'juzgado', 'inspeccion', 'hacienda', 'seguridad social',
    'registro mercantil', 'notario', 'procurador', 'abogado', 'letrado',
    'tribunal constitucional', 'tribunal supremo', 'TJUE',
    // Compliance.
    'cumplimiento', 'requisitos legales', 'licencia', 'permiso', 'autorizacion',
    'homologacion', 'certificacion obligatoria', 'compliance',
    // Fiscal.
    'impuesto', 'IRPF', 'IVA', 'tributo', 'hacienda publica',
    'declaracion de la renta', 'obligacion tributaria',
  ];

  /**
   * Keywords que indican intent NO legal.
   *
   * Score < 0.15 = NON_LEGAL sin necesidad de LLM.
   */
  private const NON_LEGAL_KEYWORDS = [
    'precio', 'mejor', 'receta', 'opinion', 'recomendacion personal',
    'tutorial', 'como funciona la app', 'horario', 'direccion', 'telefono',
    'que opinas', 'comparativa de productos', 'receta de cocina',
  ];

  /**
   * Keywords de compliance/cumplimiento.
   *
   * Detectan COMPLIANCE_CHECK con score elevado.
   */
  private const COMPLIANCE_KEYWORDS = [
    'cumple', 'cumplir', 'cumplimiento', 'compliance', 'conforme',
    'legal', 'obligatorio', 'requisito', 'exigible', 'normativo',
    'RGPD', 'LOPD', 'LSSI', 'PRL', 'prevencion de riesgos',
  ];

  /**
   * Mapa vertical → areas juridicas tipicas.
   *
   * Reduce la zona gris para queries especificas de vertical.
   */
  private const VERTICAL_LEGAL_AREAS = [
    'empleabilidad' => [
      'laboral', 'seguridad social', 'formacion', 'empleo',
      'despido', 'convenio', 'estatuto de los trabajadores',
      'contrato de trabajo', 'nomina', 'cotizacion',
    ],
    'emprendimiento' => [
      'mercantil', 'fiscal', 'tributario', 'subvenciones',
      'autonomo', 'sociedad', 'constitucion de empresa',
      'alta censal', 'licencia de actividad',
    ],
    'agroconecta' => [
      'agroalimentario', 'etiquetado', 'denominacion origen',
      'PAC', 'fitosanitario', 'trazabilidad', 'sanidad animal',
      'explotacion agraria', 'registro ganadero',
    ],
    'comercioconecta' => [
      'consumo', 'comercio electronico', 'LSSI', 'devolucion',
      'garantia', 'consumidor', 'desistimiento',
      'condiciones generales', 'publicidad enganosa',
    ],
    'serviciosconecta' => [
      'colegio profesional', 'deontologia', 'eIDAS',
      'firma electronica', 'facturacion electronica',
      'proteccion de datos profesional',
    ],
    'jarabalex' => [],
  ];

  /**
   * Umbral superior para clasificacion rapida como LEGAL_DIRECT.
   */
  private const THRESHOLD_HIGH = 0.85;

  /**
   * Umbral inferior para clasificacion rapida como NON_LEGAL.
   */
  private const THRESHOLD_LOW = 0.15;

  /**
   * Umbral para COMPLIANCE_CHECK.
   */
  private const THRESHOLD_COMPLIANCE = 0.60;

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Clasifica el intent legal de una query.
   *
   * @param string $query
   *   La consulta del usuario.
   * @param string $vertical
   *   El vertical activo (empleabilidad, jarabalex, etc.).
   * @param string $action
   *   La accion del agente (si ya es legal_*, shortcircuit).
   *
   * @return array{intent: string, score: float, areas: string[]}
   *   Resultado de clasificacion:
   *   - intent: LEGAL_DIRECT|LEGAL_IMPLICIT|LEGAL_REFERENCE|COMPLIANCE_CHECK|NON_LEGAL
   *   - score: 0.0-1.0 confianza de clasificacion
   *   - areas: areas juridicas detectadas
   */
  public function classify(string $query, string $vertical = '', string $action = ''): array {
    // Shortcircuit 1: Accion ya clasificada como legal.
    if (str_starts_with($action, 'legal_') || $action === 'fiscal' || $action === 'laboral') {
      return [
        'intent' => self::INTENT_LEGAL_DIRECT,
        'score' => 1.0,
        'areas' => [$action],
      ];
    }

    // Shortcircuit 2: Vertical JarabaLex siempre es legal.
    if ($vertical === 'jarabalex') {
      return [
        'intent' => self::INTENT_LEGAL_DIRECT,
        'score' => 1.0,
        'areas' => ['jarabalex'],
      ];
    }

    // Fase 1: Keyword scoring (zero-cost).
    $queryLower = mb_strtolower($query);
    $legalScore = 0.0;
    $detectedAreas = [];
    $legalKeywordCount = 0;

    // Puntuacion por keywords legales.
    foreach (self::LEGAL_KEYWORDS as $kw) {
      if (str_contains($queryLower, mb_strtolower($kw))) {
        $legalScore += 0.2;
        $legalKeywordCount++;
      }
    }

    // Bonus por keywords de vertical.
    if (!empty(self::VERTICAL_LEGAL_AREAS[$vertical])) {
      foreach (self::VERTICAL_LEGAL_AREAS[$vertical] as $area) {
        if (str_contains($queryLower, mb_strtolower($area))) {
          $legalScore += 0.3;
          $detectedAreas[] = $area;
        }
      }
    }

    // Deteccion de compliance.
    $complianceScore = 0.0;
    foreach (self::COMPLIANCE_KEYWORDS as $kw) {
      if (str_contains($queryLower, mb_strtolower($kw))) {
        $complianceScore += 0.15;
      }
    }

    // Penalizacion por keywords no-legales.
    foreach (self::NON_LEGAL_KEYWORDS as $kw) {
      if (str_contains($queryLower, mb_strtolower($kw))) {
        $legalScore -= 0.15;
      }
    }

    $legalScore = max(0.0, min(1.0, $legalScore));
    $complianceScore = max(0.0, min(1.0, $complianceScore));

    // Resolucion rapida: LEGAL_DIRECT.
    if ($legalScore >= self::THRESHOLD_HIGH) {
      return [
        'intent' => self::INTENT_LEGAL_DIRECT,
        'score' => $legalScore,
        'areas' => $detectedAreas,
      ];
    }

    // Resolucion rapida: NON_LEGAL.
    if ($legalScore < self::THRESHOLD_LOW && $complianceScore < self::THRESHOLD_LOW) {
      return [
        'intent' => self::INTENT_NON_LEGAL,
        'score' => 1.0 - $legalScore,
        'areas' => [],
      ];
    }

    // Compliance check: alta puntuacion de compliance + alguna legal.
    if ($complianceScore >= self::THRESHOLD_COMPLIANCE && $legalScore >= self::THRESHOLD_LOW) {
      return [
        'intent' => self::INTENT_COMPLIANCE_CHECK,
        'score' => max($legalScore, $complianceScore),
        'areas' => $detectedAreas,
      ];
    }

    // Zona gris: clasificar segun granularidad.
    if ($legalKeywordCount >= 2) {
      return [
        'intent' => self::INTENT_LEGAL_IMPLICIT,
        'score' => $legalScore,
        'areas' => $detectedAreas,
      ];
    }

    if ($legalKeywordCount === 1 || !empty($detectedAreas)) {
      return [
        'intent' => self::INTENT_LEGAL_REFERENCE,
        'score' => $legalScore,
        'areas' => $detectedAreas,
      ];
    }

    // Compliance sin keywords legales fuertes.
    if ($complianceScore >= self::THRESHOLD_LOW) {
      return [
        'intent' => self::INTENT_COMPLIANCE_CHECK,
        'score' => $complianceScore,
        'areas' => $detectedAreas,
      ];
    }

    return [
      'intent' => self::INTENT_NON_LEGAL,
      'score' => 1.0 - $legalScore,
      'areas' => [],
    ];
  }

  /**
   * Verifica si el resultado requiere activacion completa del LCIS.
   *
   * Pipeline completo: Capas 1-9 (KB, Intent, NormGraph, Prompt,
   * Constitutional, Validator, Verifier, Disclaimer, Benchmark).
   *
   * @param array $classification
   *   Resultado de classify().
   *
   * @return bool
   *   TRUE si requiere pipeline completo.
   */
  public static function requiresFullPipeline(array $classification): bool {
    return in_array($classification['intent'], [
      self::INTENT_LEGAL_DIRECT,
      self::INTENT_LEGAL_IMPLICIT,
      self::INTENT_COMPLIANCE_CHECK,
    ], TRUE);
  }

  /**
   * Verifica si requiere al menos disclaimers (pipeline parcial).
   *
   * Para LEGAL_REFERENCE: solo Capas 4 (Prompt) + 8 (Disclaimer).
   *
   * @param array $classification
   *   Resultado de classify().
   *
   * @return bool
   *   TRUE si requiere disclaimer (todo excepto NON_LEGAL).
   */
  public static function requiresDisclaimer(array $classification): bool {
    return $classification['intent'] !== self::INTENT_NON_LEGAL;
  }

  /**
   * Determina si el intent requiere solo prompt + disclaimer (sin validacion).
   *
   * Para LEGAL_REFERENCE: aplicar Capa 4 (Prompt) + Capa 8 (Disclaimer)
   * pero NO Capas 6-7 (Validator/Verifier) por coste/latencia.
   *
   * @param array $classification
   *   Resultado de classify().
   *
   * @return bool
   *   TRUE si es referencia tangencial.
   */
  public static function isLightPipeline(array $classification): bool {
    return $classification['intent'] === self::INTENT_LEGAL_REFERENCE;
  }

}
