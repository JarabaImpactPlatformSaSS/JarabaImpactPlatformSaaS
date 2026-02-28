<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Psr\Log\LoggerInterface;

/**
 * Validacion determinista de coherencia juridica en outputs de IA.
 *
 * Capa 6 del LCIS (Legal Coherence Intelligence System).
 * Post-procesamiento sin LLM. Analiza el texto generado buscando
 * patrones que violan principios juridicos estructurales.
 *
 * Capas de analisis:
 * 1. Deteccion de violaciones de jerarquia normativa via regex.
 * 2. Deteccion de violaciones de competencia territorial.
 * 3. Deteccion de reserva de ley organica.
 * 4. Deteccion de normas derogadas citadas como vigentes.
 * 5. Deteccion de contradicciones internas en la respuesta.
 * 6. Deteccion de sycophancy (premisas falsas no corregidas).
 * 7. Deteccion de antinomias (normas del mismo rango en conflicto).
 *
 * Resultado: array con violations[], warnings[], score (0.0-1.0).
 * Score < 0.5 = bloqueo o regeneracion.
 * Score 0.5-0.7 = warning (se entrega con advertencia).
 * Score > 0.7 = pass.
 *
 * LEGAL-COHERENCE-FAILOPEN-001: Este servicio es fail-open.
 * LEGAL-COHERENCE-REGEN-001: Puede solicitar regeneracion (max 2 reintentos).
 *
 * @see LegalCoherenceKnowledgeBase
 */
final class LegalCoherenceValidatorService {

  /**
   * Score threshold para bloqueo.
   */
  private const THRESHOLD_BLOCK = 0.5;

  /**
   * Score threshold para warning.
   */
  private const THRESHOLD_WARN = 0.7;

  /**
   * Maximo de reintentos de regeneracion.
   */
  private const MAX_RETRIES = 2;

  /**
   * Penalty points por severidad de violacion.
   */
  private const PENALTIES = [
    'critical' => 0.40,
    'high' => 0.25,
    'medium' => 0.15,
    'low' => 0.05,
  ];

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Valida la coherencia juridica de un texto generado por IA.
   *
   * @param string $output
   *   Texto generado por el agente.
   * @param array $context
   *   Contexto de la validacion:
   *   - agent_id: ID del agente.
   *   - action: Accion ejecutada.
   *   - mode: Modo del copilot.
   *   - tenant_id: ID del tenant.
   *   - vertical: Vertical activo.
   *   - user_query: (opcional) Query original del usuario (para anti-sycophancy).
   *   - retry_count: (opcional) Numero de reintentos de regeneracion.
   *
   * @return array{
   *   passed: bool,
   *   score: float,
   *   action: string,
   *   violations: array,
   *   warnings: array,
   *   sanitized_output: string,
   *   regeneration_constraints: string[],
   *   retry_count: int,
   *   metadata: array,
   * }
   */
  public function validate(string $output, array $context = []): array {
    $violations = [];
    $warnings = [];
    $score = 1.0;
    $retryCount = (int) ($context['retry_count'] ?? 0);

    // 1. Deteccion de violaciones de jerarquia (HIERARCHY_VIOLATION_PATTERNS).
    $hierarchyResult = $this->checkHierarchyViolations($output);
    $violations = array_merge($violations, $hierarchyResult['violations']);

    // 2. Deteccion de violaciones de competencia territorial.
    $competenceResult = $this->checkCompetenceViolations($output);
    $violations = array_merge($violations, $competenceResult['violations']);

    // 3. Deteccion de reserva de ley organica.
    $organicResult = $this->checkOrganicLawViolations($output);
    $violations = array_merge($violations, $organicResult['violations']);

    // 4. Deteccion de normas citadas sin advertencia de vigencia.
    $vigenciaResult = $this->checkVigenciaWarnings($output);
    $warnings = array_merge($warnings, $vigenciaResult['warnings']);

    // 5. Deteccion de contradicciones internas.
    $contradictionResult = $this->checkInternalContradictions($output);
    $warnings = array_merge($warnings, $contradictionResult['warnings']);

    // 6. Anti-sycophancy: premisas falsas no corregidas.
    $userQuery = $context['user_query'] ?? '';
    if ($userQuery) {
      $sycophancyResult = $this->checkSycophancyPatterns($output, $userQuery);
      $warnings = array_merge($warnings, $sycophancyResult['warnings']);
    }

    // 7. Deteccion de antinomias (normas del mismo rango en conflicto).
    $antinomyResult = $this->checkAntinomies($output);
    $warnings = array_merge($warnings, $antinomyResult['warnings']);

    // Calcular score.
    foreach ($violations as $v) {
      $score -= self::PENALTIES[$v['severity']] ?? 0.10;
    }
    foreach ($warnings as $w) {
      $score -= self::PENALTIES[$w['severity'] ?? 'low'] ?? 0.05;
    }
    $score = max(0.0, round($score, 3));

    // Determinar accion.
    $action = 'allow';
    $sanitizedOutput = $output;
    $regenerationConstraints = [];

    if ($score < self::THRESHOLD_BLOCK) {
      if ($retryCount < self::MAX_RETRIES) {
        // LEGAL-COHERENCE-REGEN-001: solicitar regeneracion.
        $action = 'regenerate';
        $regenerationConstraints = $this->buildRegenerationConstraints($violations);
      }
      else {
        $action = 'block';
        $sanitizedOutput = $this->buildBlockedResponse($violations, $context);
      }
    }
    elseif ($score < self::THRESHOLD_WARN) {
      $action = 'warn';
      $sanitizedOutput = $this->appendWarnings($output, $violations, $warnings);
    }

    $passed = $action !== 'block';

    if ($action === 'block' || $action === 'regenerate') {
      $this->logger->warning('Legal coherence @action for @agent/@agentAction: @violations (score: @score, retry: @retry)', [
        '@action' => $action,
        '@agent' => $context['agent_id'] ?? 'unknown',
        '@agentAction' => $context['action'] ?? 'unknown',
        '@violations' => json_encode(array_column($violations, 'type')),
        '@score' => $score,
        '@retry' => $retryCount,
      ]);
    }

    return [
      'passed' => $passed,
      'score' => $score,
      'action' => $action,
      'violations' => $violations,
      'warnings' => $warnings,
      'sanitized_output' => $sanitizedOutput,
      'regeneration_constraints' => $regenerationConstraints,
      'retry_count' => $retryCount,
      'metadata' => [
        'hierarchy_checks' => $hierarchyResult['checks_run'] ?? 0,
        'competence_checks' => $competenceResult['checks_run'] ?? 0,
        'norms_detected' => $hierarchyResult['norms_detected'] ?? [],
      ],
    ];
  }

  /**
   * Verifica violaciones de jerarquia normativa.
   *
   * Dos fases:
   * A) Patrones predefinidos (HIERARCHY_VIOLATION_PATTERNS).
   * B) Analisis estructural: extraer normas y verificar prevalencia.
   */
  protected function checkHierarchyViolations(string $output): array {
    $violations = [];
    $normsDetected = [];
    $checksRun = 0;

    // Fase A: Deteccion mediante patrones predefinidos.
    foreach (LegalCoherenceKnowledgeBase::HIERARCHY_VIOLATION_PATTERNS as $rule) {
      $checksRun++;
      if (preg_match($rule['pattern'], $output, $matches)) {
        $violations[] = [
          'type' => $rule['type'],
          'severity' => $rule['severity'],
          'description' => $rule['description'],
          'match' => $matches[0],
          'source' => 'pattern',
        ];
      }
    }

    // Fase B: Deteccion estructural — extraer normas y verificar
    // que no se afirme prevalencia de inferior sobre superior.
    $sentences = preg_split('/[.;]\s+/', $output);
    $prevalenceKeywords = [
      'prevalece sobre', 'prima sobre', 'deroga', 'anula',
      'deja sin efecto', 'se aplica preferentemente', 'sustituye a',
      'invalida', 'se impone sobre',
    ];

    foreach ($sentences as $sentence) {
      $checksRun++;
      $sentenceLower = mb_strtolower($sentence);

      // Buscar si la frase contiene keyword de prevalencia.
      $hasPrevalence = FALSE;
      foreach ($prevalenceKeywords as $kw) {
        if (str_contains($sentenceLower, $kw)) {
          $hasPrevalence = TRUE;
          break;
        }
      }

      if (!$hasPrevalence) {
        continue;
      }

      // Detectar normas en la frase.
      $detectedNorms = [];
      foreach (LegalCoherenceKnowledgeBase::NORM_TYPE_PATTERNS as $pattern => $hierarchyKey) {
        if (preg_match($pattern, $sentence, $m)) {
          $detectedNorms[] = [
            'text' => $m[0],
            'hierarchy_key' => $hierarchyKey,
            'rank' => LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$hierarchyKey]['rank'] ?? 99,
          ];
        }
      }

      $normsDetected = array_merge($normsDetected, $detectedNorms);

      // Si hay 2+ normas de diferente rango, verificar que la de
      // mayor rango es la que prevalece (aparece DESPUES del keyword).
      if (count($detectedNorms) >= 2) {
        usort($detectedNorms, static fn(array $a, array $b): int => $a['rank'] <=> $b['rank']);
        $highest = $detectedNorms[0];
        $lowest = end($detectedNorms);

        foreach ($prevalenceKeywords as $kw) {
          $kwPos = mb_strpos($sentenceLower, $kw);
          if ($kwPos === FALSE) {
            continue;
          }
          $lowestPos = mb_stripos($sentence, $lowest['text']);
          $highestPos = mb_stripos($sentence, $highest['text']);

          if ($lowestPos !== FALSE && $highestPos !== FALSE
            && $lowestPos < $kwPos && $highestPos > $kwPos
            && $lowest['rank'] > $highest['rank']) {
            $violations[] = [
              'type' => 'hierarchy_inversion_structural',
              'severity' => 'critical',
              'description' => sprintf(
                'Se afirma que %s (%s, rango %d) prevalece sobre %s (%s, rango %d)',
                $lowest['text'],
                LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$lowest['hierarchy_key']]['label'] ?? '',
                $lowest['rank'],
                $highest['text'],
                LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$highest['hierarchy_key']]['label'] ?? '',
                $highest['rank'],
              ),
              'match' => mb_substr($sentence, 0, 200),
              'source' => 'structural',
            ];
          }
        }
      }
    }

    return [
      'violations' => $violations,
      'norms_detected' => array_unique(array_column($normsDetected, 'text')),
      'checks_run' => $checksRun,
    ];
  }

  /**
   * Verifica violaciones de competencia territorial.
   *
   * Detecta si se atribuye legislacion a CCAA en materia de
   * competencia exclusiva estatal (Art. 149.1 CE).
   */
  protected function checkCompetenceViolations(string $output): array {
    $violations = [];
    $checksRun = 0;

    $ccaaPatterns = [
      '/(?:Ley|Decreto|normativa)\s+(?:de\s+)?(?:la\s+)?(?:Comunidad\s+(?:Autonoma\s+)?de|de\s+Andalucia|de\s+Cataluna|del?\s+Pais\s+Vasco|de\s+Madrid|de\s+Galicia|de\s+Aragon|de\s+Valencia)\s+[^.]{0,100}/i',
    ];

    foreach ($ccaaPatterns as $ccaaPattern) {
      if (preg_match_all($ccaaPattern, $output, $matches)) {
        foreach ($matches[0] as $match) {
          $checksRun++;
          $competence = LegalCoherenceKnowledgeBase::isStateExclusiveCompetence($match);
          if ($competence) {
            $violations[] = [
              'type' => 'competence_violation',
              'severity' => 'high',
              'description' => sprintf(
                'Se atribuye a normativa autonomica la regulacion de "%s" (competencia exclusiva del Estado, Art. %s CE)',
                $competence['label'],
                $competence['article'],
              ),
              'match' => mb_substr($match, 0, 200),
              'source' => 'competence_check',
            ];
          }
        }
      }
    }

    return ['violations' => $violations, 'checks_run' => $checksRun];
  }

  /**
   * Verifica violaciones de reserva de ley organica.
   *
   * Detecta frases donde una norma NO organica (ley ordinaria,
   * RD, OM) pretende regular materia reservada a LO (Art. 81 CE).
   */
  protected function checkOrganicLawViolations(string $output): array {
    $violations = [];

    $nonOrganicPattern = '/(?:(?:L|l)ey\s+\d+\/\d{4}|Real\s+Decreto|Decreto[- ]ley|Orden\s+Ministerial)\s+[^.]{0,100}(?:regula|desarrolla|establece|aprueba)\s+[^.]{0,100}/i';

    if (preg_match_all($nonOrganicPattern, $output, $matches)) {
      foreach ($matches[0] as $match) {
        $loMatter = LegalCoherenceKnowledgeBase::requiresOrganicLaw($match);
        if ($loMatter) {
          $violations[] = [
            'type' => 'organic_law_violation',
            'severity' => 'high',
            'description' => sprintf(
              'Se afirma que una norma no organica regula materia reservada a LO: %s (Art. 81 CE)',
              $loMatter,
            ),
            'match' => mb_substr($match, 0, 200),
            'source' => 'organic_law_check',
          ];
        }
      }
    }

    return ['violations' => $violations];
  }

  /**
   * Genera advertencias sobre vigencia normativa.
   *
   * Detecta normas con fecha anterior a 2015 citadas sin mencion
   * de su estado de vigencia.
   */
  protected function checkVigenciaWarnings(string $output): array {
    $warnings = [];

    $oldNormPattern = '/(?:Ley|Real\s+Decreto|Ley\s+Organica)\s+\d+\/(?:19[0-9]{2}|200[0-9]|201[0-5])\b/i';
    $vigenciaKeywords = [
      'vigente', 'derogad', 'modificad', 'sustituida',
      'actualizada', 'consolidada', 'en vigor',
    ];

    if (preg_match_all($oldNormPattern, $output, $matches)) {
      foreach ($matches[0] as $match) {
        $sentence = $this->getSentenceContaining($output, $match);
        $hasVigencia = FALSE;
        foreach ($vigenciaKeywords as $kw) {
          if (stripos($sentence, $kw) !== FALSE) {
            $hasVigencia = TRUE;
            break;
          }
        }

        if (!$hasVigencia) {
          $warnings[] = [
            'type' => 'vigencia_not_mentioned',
            'severity' => 'low',
            'description' => sprintf(
              'Norma con fecha anterior a 2015 citada sin mencion de vigencia: %s. Recomendar verificacion.',
              $match,
            ),
            'match' => $match,
          ];
        }
      }
    }

    return ['warnings' => $warnings];
  }

  /**
   * Detecta contradicciones internas en la respuesta.
   *
   * Busca pares de afirmaciones mutuamente excluyentes en el
   * mismo texto generado por la IA.
   */
  protected function checkInternalContradictions(string $output): array {
    $warnings = [];

    $contradictionPairs = [
      [
        '/es\s+competencia\s+exclusiva\s+del\s+Estado/i',
        '/(?:las?\s+)?(?:CCAA|Comunidades?\s+Autonomas?)\s+(?:pueden|tiene[n]?\s+competencia\s+para)\s+legislar/i',
        'Competencia exclusiva del Estado vs legislacion autonomica',
      ],
      [
        '/no\s+tiene\s+efecto\s+retroactivo/i',
        '/se\s+aplica\s+retroactivamente/i',
        'Irretroactividad vs aplicacion retroactiva',
      ],
      [
        '/requiere\s+Ley\s+Organica/i',
        '/(?:se\s+)?regula\s+(?:por|mediante)\s+(?:ley\s+ordinaria|decreto|reglamento)/i',
        'Reserva de LO vs regulacion por norma inferior',
      ],
    ];

    foreach ($contradictionPairs as [$patternA, $patternB, $desc]) {
      if (preg_match($patternA, $output) && preg_match($patternB, $output)) {
        $warnings[] = [
          'type' => 'internal_contradiction',
          'severity' => 'medium',
          'description' => "Posible contradiccion interna: {$desc}. Verificar contexto.",
          'match' => $desc,
        ];
      }
    }

    return ['warnings' => $warnings];
  }

  /**
   * Detecta patrones de sycophancy en la respuesta.
   *
   * Busca respuestas que refuerzan premisas tipicamente falsas
   * del usuario sin corregirlas. Heuristica basada en patrones.
   *
   * LEGAL-COHERENCE-SYCOPHANCY-001: V8 Premise Validation.
   *
   * @param string $output
   *   Texto generado por el agente.
   * @param string $userQuery
   *   Query original del usuario.
   *
   * @return array{warnings: array}
   */
  protected function checkSycophancyPatterns(string $output, string $userQuery): array {
    $warnings = [];

    // Premisas falsas comunes que el LLM no deberia reforzar.
    $falsePremises = [
      '/autonomo.*no.*coti[cz]a.*desempleo/i' => 'Los autonomos SI pueden cotizar para desempleo (RETA cese de actividad)',
      '/ley.*(?:catalana|vasca|gallega|andaluza).*(?:penal|mercantil|laboral)/i' => 'La legislacion penal, mercantil y laboral es competencia exclusiva del Estado (Art. 149.1 CE)',
      '/municipio.*puede.*derogar.*ley/i' => 'Las ordenanzas municipales no pueden derogar leyes',
      '/(?:ccaa|comunidad\s+autonoma).*(?:legislar|aprobar).*(?:codigo\s+penal|legislacion\s+penal)/i' => 'La legislacion penal es competencia exclusiva estatal (Art. 149.1.6 CE)',
      '/directiva.*no.*(?:efecto|aplicable).*(?:no\s+transpuesta|sin\s+transponer)/i' => 'Directivas con disposiciones claras, precisas e incondicionales SI tienen efecto directo vertical (Van Gend en Loos)',
    ];

    $queryLower = mb_strtolower($userQuery);
    $outputLower = mb_strtolower($output);

    foreach ($falsePremises as $pattern => $correction) {
      if (preg_match($pattern, $queryLower)) {
        // La query tiene premisa potencialmente falsa.
        // Verificar si la respuesta la corrige o la refuerza.
        $correctionKeywords = [
          'sin embargo', 'no obstante', 'es incorrecto',
          'en realidad', 'conviene aclarar', 'premisa',
          'matizar', 'cabe senalar', 'aclaracion',
        ];
        $corrects = FALSE;
        foreach ($correctionKeywords as $kw) {
          if (str_contains($outputLower, $kw)) {
            $corrects = TRUE;
            break;
          }
        }

        if (!$corrects) {
          $warnings[] = [
            'type' => 'sycophancy_risk',
            'severity' => 'medium',
            'description' => sprintf(
              'La consulta puede contener premisa incorrecta no corregida: %s',
              $correction,
            ),
            'match' => mb_substr($userQuery, 0, 200),
          ];
        }
      }
    }

    return ['warnings' => $warnings];
  }

  /**
   * Detecta antinomias: normas del mismo rango en conflicto.
   *
   * LEGAL-COHERENCE-ANTINOMY-001: Cuando se citan 2+ normas del
   * mismo rango que regulan la misma materia con disposiciones
   * incompatibles, se debe aplicar lex posterior o lex specialis.
   *
   * @param string $output
   *   Texto generado por el agente.
   *
   * @return array{warnings: array}
   */
  protected function checkAntinomies(string $output): array {
    $warnings = [];

    // Extraer todas las normas mencionadas con su rango.
    $normsByRank = [];
    foreach (LegalCoherenceKnowledgeBase::NORM_TYPE_PATTERNS as $pattern => $hierarchyKey) {
      if (preg_match_all($pattern, $output, $matches)) {
        foreach ($matches[0] as $match) {
          $normsByRank[$hierarchyKey][] = $match;
        }
      }
    }

    // Para cada rango, verificar si hay multiples normas.
    foreach ($normsByRank as $rank => $norms) {
      $uniqueNorms = array_unique($norms);
      if (count($uniqueNorms) < 2) {
        continue;
      }

      // Buscar keywords de conflicto entre las normas del mismo rango.
      $conflictKeywords = [
        'contradice', 'conflicto', 'incompatible',
        'contrapone', 'colision', 'antinomia',
      ];

      $outputLower = mb_strtolower($output);
      foreach ($conflictKeywords as $kw) {
        if (str_contains($outputLower, $kw)) {
          // Verificar si se resuelve la antinomia.
          $resolutionKeywords = [
            'lex posterior', 'lex specialis', 'norma posterior',
            'norma especial', 'prevalece la', 'se aplica preferentemente',
          ];
          $resolved = FALSE;
          foreach ($resolutionKeywords as $rk) {
            if (str_contains($outputLower, $rk)) {
              $resolved = TRUE;
              break;
            }
          }

          if (!$resolved) {
            $warnings[] = [
              'type' => 'unresolved_antinomy',
              'severity' => 'medium',
              'description' => sprintf(
                'Se mencionan normas del mismo rango (%s) en posible conflicto sin criterio de resolucion (lex posterior/lex specialis): %s',
                LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$rank]['label'] ?? $rank,
                implode(', ', array_slice($uniqueNorms, 0, 3)),
              ),
              'match' => implode(' vs ', array_slice($uniqueNorms, 0, 2)),
            ];
          }
          break;
        }
      }
    }

    return ['warnings' => $warnings];
  }

  /**
   * Construye constraints de regeneracion a partir de violaciones.
   *
   * LEGAL-COHERENCE-REGEN-001: Se inyectan en el prompt del
   * siguiente intento para corregir las violaciones detectadas.
   *
   * @param array $violations
   *   Violaciones detectadas.
   *
   * @return string[]
   *   Constraints para inyectar en el prompt.
   */
  protected function buildRegenerationConstraints(array $violations): array {
    $constraints = [];

    foreach ($violations as $v) {
      $constraint = match ($v['type']) {
        'hierarchy_inversion', 'hierarchy_inversion_structural' =>
          'CRITICO: No afirmes que una norma de rango inferior deroga o prevalece sobre una de rango superior. Respeta la jerarquia: DUE > CE > LO > Ley > RD > Ley CCAA > Local.',
        'competence_violation' =>
          'CRITICO: La materia mencionada es competencia exclusiva del Estado (Art. 149.1 CE). No atribuyas esta regulacion a normativa autonomica.',
        'eu_primacy_violation' =>
          'CRITICO: El Derecho de la UE prevalece sobre el derecho interno. No afirmes lo contrario (Costa v. ENEL 6/64).',
        'organic_law_violation' =>
          'CRITICO: Esta materia requiere Ley Organica (Art. 81 CE). No la atribuyas a ley ordinaria ni reglamento.',
        'retroactivity_violation' =>
          'CRITICO: Las disposiciones sancionadoras desfavorables no tienen efecto retroactivo (Art. 9.3 CE).',
        default =>
          'Revisa la coherencia juridica de tu respuesta: ' . ($v['description'] ?? ''),
      };

      $constraints[] = $constraint;
    }

    return array_values(array_unique($constraints));
  }

  /**
   * Construye respuesta de bloqueo con explicacion al usuario.
   */
  protected function buildBlockedResponse(array $violations, array $context): string {
    $header = "La respuesta generada contiene inconsistencias juridicas que impiden su entrega. ";
    $header .= "Esto protege la calidad y fiabilidad de la informacion legal.\n\n";
    $header .= "**Problemas detectados:**\n";

    foreach ($violations as $v) {
      $header .= "- {$v['description']}\n";
    }

    $header .= "\n**Recomendacion:** Reformule su consulta o consulte directamente la normativa en las fuentes oficiales (BOE, EUR-Lex, CENDOJ).";

    return $header;
  }

  /**
   * Anade advertencias al output sin bloquearlo.
   */
  protected function appendWarnings(string $output, array $violations, array $warnings): string {
    $notice = "\n\n---\n**Aviso de coherencia juridica:** Se han detectado posibles imprecisiones en esta respuesta:\n";

    foreach ($violations as $v) {
      $notice .= "- [!] {$v['description']}\n";
    }
    foreach ($warnings as $w) {
      $notice .= "- [i] {$w['description']}\n";
    }

    $notice .= "\nSe recomienda verificar la informacion con las fuentes oficiales.";

    return $output . $notice;
  }

  /**
   * Obtiene la frase completa que contiene un texto dado.
   */
  protected function getSentenceContaining(string $text, string $needle): string {
    $pos = mb_strpos($text, $needle);
    if ($pos === FALSE) {
      return '';
    }

    $start = mb_strrpos(mb_substr($text, 0, $pos), '.') ?: 0;
    $end = mb_strpos($text, '.', $pos);
    if ($end === FALSE) {
      $end = mb_strlen($text);
    }

    return mb_substr($text, $start, $end - $start + 1);
  }

}
