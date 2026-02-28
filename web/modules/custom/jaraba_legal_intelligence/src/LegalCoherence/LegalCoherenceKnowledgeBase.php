<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

/**
 * Fuente de verdad inmutable para coherencia juridica.
 *
 * Codifica los principios generales del derecho espanol y europeo
 * como estructuras PHP constantes. Usado por LegalCoherenceValidatorService
 * y LegalCoherenceVerifierService para validar respuestas de IA.
 *
 * Fuentes juridicas:
 * - Constitucion Espanola de 1978, Arts. 9.3, 81, 82, 86, 148, 149, 150, 161
 * - Codigo Civil Art. 1.1-1.7 (fuentes del derecho)
 * - TFUE Arts. 288, 291
 * - TJUE: Costa v. ENEL (6/64), Van Gend en Loos (26/62), Simmenthal (106/77)
 * - Ley 39/2015 LPACAP
 * - Ley 40/2015 LRJSP
 *
 * LEGAL-COHERENCE-KB-001: Esta clase SOLO contiene conocimiento juridico
 * estructural (jerarquia, competencias, principios). NUNCA contenido
 * sustantivo de normas concretas (eso es dominio del RAG).
 */
final class LegalCoherenceKnowledgeBase {

  /**
   * Jerarquia normativa del ordenamiento juridico espanol.
   *
   * Art. 9.3 CE: "La Constitucion garantiza el principio de legalidad,
   * la jerarquia normativa..."
   *
   * Cada entrada: [rank, label, description, examples[]]
   * Rank menor = rango superior.
   */
  public const NORMATIVE_HIERARCHY = [
    'derecho_ue_primario' => [
      'rank' => 1,
      'label' => 'Derecho UE Primario',
      'description' => 'Tratados constitutivos (TUE, TFUE, CDFUE). Primacia absoluta sobre derecho interno.',
      'examples' => ['TFUE', 'TUE', 'Carta de Derechos Fundamentales', 'Tratado de Lisboa', 'Tratado de Funcionamiento'],
    ],
    'derecho_ue_derivado' => [
      'rank' => 2,
      'label' => 'Derecho UE Derivado',
      'description' => 'Reglamentos (efecto directo), Directivas (transposicion), Decisiones. Art. 288 TFUE.',
      'examples' => ['Reglamento UE', 'Reglamento (UE)', 'Directiva UE', 'Directiva (UE)', 'Decision UE'],
    ],
    'constitucion' => [
      'rank' => 3,
      'label' => 'Constitucion Espanola',
      'description' => 'Norma suprema del ordenamiento interno. Art. 9.1 CE: vincula ciudadanos y poderes publicos.',
      'examples' => ['Constitucion', 'CE', 'Constitucion Espanola'],
    ],
    'ley_organica' => [
      'rank' => 4,
      'label' => 'Ley Organica',
      'description' => 'Art. 81 CE: derechos fundamentales, Estatutos de Autonomia, regimen electoral, instituciones del Estado. Mayoria absoluta del Congreso.',
      'examples' => ['Ley Organica', 'LO ', 'L.O.'],
    ],
    'ley_ordinaria' => [
      'rank' => 5,
      'label' => 'Ley Ordinaria / Decreto-Ley / Decreto Legislativo',
      'description' => 'Leyes ordinarias (mayoria simple), RDL (Art. 86 CE, urgencia), RDLeg (Art. 82-85 CE, delegacion legislativa).',
      'examples' => ['Ley ', 'Real Decreto-ley', 'Real Decreto Legislativo', 'RDL ', 'RDLeg'],
    ],
    'reglamento_estatal' => [
      'rank' => 6,
      'label' => 'Reglamento Estatal',
      'description' => 'Real Decreto (Consejo de Ministros), Orden Ministerial, Resolucion, Instruccion, Circular.',
      'examples' => ['Real Decreto ', 'Orden ', 'Orden Ministerial', 'Resolucion de ', 'Instruccion de ', 'Circular '],
    ],
    'ley_autonomica' => [
      'rank' => 7,
      'label' => 'Ley Autonomica',
      'description' => 'Leyes aprobadas por los Parlamentos autonomicos en materias de competencia autonomica.',
      'examples' => ['Ley de Andalucia', 'Ley de Cataluna', 'Ley de Madrid', 'Ley del Pais Vasco', 'Ley de la Comunidad'],
    ],
    'reglamento_autonomico' => [
      'rank' => 8,
      'label' => 'Reglamento Autonomico',
      'description' => 'Decretos y ordenes de los Gobiernos autonomicos.',
      'examples' => ['Decreto de la Junta', 'Decreto del Gobierno', 'Orden de la Consejeria'],
    ],
    'normativa_local' => [
      'rank' => 9,
      'label' => 'Normativa Local',
      'description' => 'Ordenanzas municipales, bandos, reglamentos locales. Potestad reglamentaria local (Art. 137, 140 CE).',
      'examples' => ['Ordenanza municipal', 'Ordenanza de ', 'Reglamento municipal', 'Bando'],
    ],
  ];

  /**
   * Pesos decimales para Authority-Aware Ranking.
   *
   * NOTA: ley_autonomica (0.83) < ley_ordinaria (0.88) en general,
   * PERO recibe bonus +0.12 en competencia exclusiva CCAA = 0.95.
   */
  public const HIERARCHY_WEIGHTS = [
    'derecho_ue_primario' => 1.00,
    'derecho_ue_derivado' => 0.95,
    'constitucion' => 0.98,
    'ley_organica' => 0.93,
    'ley_ordinaria' => 0.88,
    'reglamento_estatal' => 0.78,
    'ley_autonomica' => 0.83,
    'reglamento_autonomico' => 0.68,
    'normativa_local' => 0.58,
  ];

  /**
   * Competencias exclusivas del Estado (Art. 149.1 CE).
   */
  public const STATE_EXCLUSIVE_COMPETENCES = [
    'nacionalidad_inmigracion_extranjeria' => [
      'article' => '149.1.2',
      'label' => 'Nacionalidad, inmigracion, extranjeria y asilo',
      'keywords' => ['nacionalidad', 'inmigracion', 'extranjeria', 'asilo', 'refugiado'],
    ],
    'relaciones_internacionales' => [
      'article' => '149.1.3',
      'label' => 'Relaciones internacionales',
      'keywords' => ['relaciones internacionales', 'tratados internacionales', 'politica exterior'],
    ],
    'defensa_fuerzas_armadas' => [
      'article' => '149.1.4',
      'label' => 'Defensa y Fuerzas Armadas',
      'keywords' => ['defensa nacional', 'fuerzas armadas', 'ejercito'],
    ],
    'administracion_justicia' => [
      'article' => '149.1.5',
      'label' => 'Administracion de Justicia',
      'keywords' => ['administracion de justicia', 'poder judicial', 'planta judicial'],
    ],
    'legislacion_mercantil_penal_penitenciaria' => [
      'article' => '149.1.6',
      'label' => 'Legislacion mercantil, penal, penitenciaria y procesal',
      'keywords' => ['codigo penal', 'ley de enjuiciamiento', 'legislacion mercantil', 'legislacion penal', 'derecho procesal', 'legislacion penitenciaria'],
    ],
    'legislacion_laboral' => [
      'article' => '149.1.7',
      'label' => 'Legislacion laboral (sin perjuicio de ejecucion por CCAA)',
      'keywords' => ['estatuto de los trabajadores', 'legislacion laboral', 'despido', 'convenio colectivo', 'salario minimo'],
    ],
    'legislacion_civil' => [
      'article' => '149.1.8',
      'label' => 'Legislacion civil (sin perjuicio de derechos forales)',
      'keywords' => ['codigo civil', 'legislacion civil'],
    ],
    'propiedad_intelectual' => [
      'article' => '149.1.9',
      'label' => 'Legislacion sobre propiedad intelectual e industrial',
      'keywords' => ['propiedad intelectual', 'propiedad industrial', 'patentes', 'marcas'],
    ],
    'hacienda_general' => [
      'article' => '149.1.14',
      'label' => 'Hacienda general y Deuda del Estado',
      'keywords' => ['hacienda publica', 'deuda del estado', 'presupuestos generales'],
    ],
    'seguridad_social' => [
      'article' => '149.1.17',
      'label' => 'Legislacion basica y regimen economico de la Seguridad Social',
      'keywords' => ['seguridad social', 'pension', 'prestacion por desempleo', 'jubilacion'],
    ],
    'bases_regimen_juridico_aapp' => [
      'article' => '149.1.18',
      'label' => 'Bases del regimen juridico de las AAPP y procedimiento administrativo comun',
      'keywords' => ['procedimiento administrativo', 'LPACAP', 'LRJSP', 'regimen juridico administraciones publicas'],
    ],
    'legislacion_basica_medio_ambiente' => [
      'article' => '149.1.23',
      'label' => 'Legislacion basica sobre medio ambiente',
      'keywords' => ['legislacion medioambiental', 'proteccion medioambiente', 'evaluacion impacto ambiental'],
    ],
    'bases_regimen_minero_energetico' => [
      'article' => '149.1.25',
      'label' => 'Bases del regimen minero y energetico',
      'keywords' => ['regimen minero', 'legislacion energetica'],
    ],
  ];

  /**
   * Principios generales de aplicacion del derecho.
   */
  public const LEGAL_PRINCIPLES = [
    'hierarchy' => [
      'latin' => 'Lex superior derogat legi inferiori',
      'description' => 'La norma de rango superior prevalece sobre la de rango inferior.',
      'article' => 'Art. 9.3 CE',
    ],
    'lex_posterior' => [
      'latin' => 'Lex posterior derogat legi priori',
      'description' => 'La norma posterior del mismo rango deroga la anterior.',
      'article' => 'Art. 2.2 CC',
    ],
    'lex_specialis' => [
      'latin' => 'Lex specialis derogat legi generali',
      'description' => 'La norma especial prevalece sobre la general del mismo rango en su ambito especifico.',
      'article' => 'Principio general del derecho',
    ],
    'irretroactividad_sancionadora' => [
      'latin' => 'Irretroactividad de disposiciones sancionadoras no favorables',
      'description' => 'Art. 9.3 CE: las disposiciones sancionadoras no favorables no tienen efecto retroactivo.',
      'article' => 'Art. 9.3 CE, Art. 26 Ley 40/2015',
    ],
    'reserva_ley_organica' => [
      'latin' => 'Reserva de Ley Organica',
      'description' => 'Art. 81 CE: desarrollo de DDFF y libertades publicas, Estatutos de Autonomia, LOREG.',
      'article' => 'Art. 81 CE',
    ],
    'primacia_ue' => [
      'latin' => 'Primacia del Derecho de la Union Europea',
      'description' => 'El Derecho UE prevalece sobre cualquier norma interna contraria. TJUE: Costa v. ENEL (6/64), Simmenthal (106/77).',
      'article' => 'Declaracion 1/2004 TC, TJUE 6/64',
    ],
    'efecto_directo' => [
      'latin' => 'Efecto directo del Derecho UE',
      'description' => 'Los Reglamentos UE tienen efecto directo. Directivas no transpuestas con disposiciones claras generan derechos invocables. TJUE: Van Gend en Loos (26/62).',
      'article' => 'Art. 288 TFUE, TJUE 26/62',
    ],
    'competencia_territorial' => [
      'latin' => 'Principio de competencia territorial',
      'description' => 'Las CCAA solo pueden legislar en materias de su competencia. En caso de conflicto, prevalece norma estatal (Art. 149.3 CE).',
      'article' => 'Arts. 148, 149 CE',
    ],
  ];

  /**
   * Reservas de Ley Organica — materias que REQUIEREN LO.
   */
  public const ORGANIC_LAW_MATTERS = [
    'derechos_fundamentales' => 'Desarrollo de derechos fundamentales y libertades publicas (Seccion 1a, Cap. II, Titulo I CE)',
    'estatutos_autonomia' => 'Aprobacion y reforma de Estatutos de Autonomia',
    'regimen_electoral' => 'Regimen electoral general (LOREG)',
    'defensor_pueblo' => 'Defensor del Pueblo',
    'tribunal_constitucional' => 'Tribunal Constitucional',
    'tribunal_cuentas' => 'Tribunal de Cuentas',
    'consejo_estado' => 'Consejo de Estado',
    'poder_judicial' => 'Poder Judicial (LOPJ)',
    'fuerzas_seguridad' => 'Fuerzas y Cuerpos de Seguridad',
    'estados_alarma_excepcion_sitio' => 'Estados de alarma, excepcion y sitio',
    'habeas_corpus' => 'Habeas Corpus',
    'iniciativa_legislativa_popular' => 'Regulacion de la Iniciativa Legislativa Popular',
  ];

  /**
   * Patrones regex para detectar tipo normativo en texto.
   */
  public const NORM_TYPE_PATTERNS = [
    '/\bReglamento\s*\(UE\)/i' => 'derecho_ue_derivado',
    '/\bDirectiva\s*\(UE\)/i' => 'derecho_ue_derivado',
    '/\bDirectiva\s+\d{4}\/\d+/i' => 'derecho_ue_derivado',
    '/\bReglamento\s*\(CE\)/i' => 'derecho_ue_derivado',
    '/\bTFUE\b/' => 'derecho_ue_primario',
    '/\bTUE\b/' => 'derecho_ue_primario',
    '/\bCarta de Derechos Fundamentales de la UE/i' => 'derecho_ue_primario',
    '/\bConstitucion\s+Espanola\b/i' => 'constitucion',
    '/\bArt(?:iculo)?\.?\s*\d+(?:\.\d+)?\s*(?:de la\s+)?CE\b/' => 'constitucion',
    '/\bLey\s+Organica\b/i' => 'ley_organica',
    '/\bL\.?O\.?\s+\d+/i' => 'ley_organica',
    '/\bLOPJ\b/' => 'ley_organica',
    '/\bLORE[GC]\b/' => 'ley_organica',
    '/\bLOPD(?:GDD)?\b/' => 'ley_organica',
    '/\bLey\s+\d+\/\d{4}\b/i' => 'ley_ordinaria',
    '/\bReal\s+Decreto[- ]ley\b/i' => 'ley_ordinaria',
    '/\bReal\s+Decreto\s+Legislativo\b/i' => 'ley_ordinaria',
    '/\bRDL\s+\d+/i' => 'ley_ordinaria',
    '/\bRDLeg\s+\d+/i' => 'ley_ordinaria',
    '/\bLPACAP\b/' => 'ley_ordinaria',
    '/\bLRJSP\b/' => 'ley_ordinaria',
    '/\bLEC\b/' => 'ley_ordinaria',
    '/\bLECrim\b/' => 'ley_ordinaria',
    '/\bReal\s+Decreto\s+\d+\/\d{4}\b/i' => 'reglamento_estatal',
    '/\bOrden\s+(?:Ministerial|[A-Z]{3})\b/i' => 'reglamento_estatal',
    '/\bResolucion\s+de\s+\d+\s+de\b/i' => 'reglamento_estatal',
    '/\bLey\s+(?:de|del)\s+(?:Andaluc[ií]a|Catalu[nñ]a|Madrid|Galicia|Pa[ií]s Vasco|Comunidad|Arag[oó]n|Asturias|Baleares|Canarias|Cantabria|Castilla|Extremadura|La Rioja|Murcia|Navarra|Valencian?a?)/i' => 'ley_autonomica',
    '/\bDecreto\s+(?:de la Junta|del Govern|del Gobierno de)\b/i' => 'reglamento_autonomico',
    '/\bOrdenanza\s+municipal\b/i' => 'normativa_local',
    '/\bOrdenanza\s+de\s+/i' => 'normativa_local',
  ];

  /**
   * Patrones de violacion de jerarquia normativa.
   *
   * Cada patron: [pattern, type, severity, description]
   */
  public const HIERARCHY_VIOLATION_PATTERNS = [
    [
      'pattern' => '/(?:Real\s+Decreto|Orden\s+Ministerial|Resolucion)\s+[^.]*(?:deroga|anula|modifica|prevalece\s+sobre|sustituye)\s+[^.]*(?:Ley\s+Org[aá]nica|Ley\s+\d|Constituci[oó]n)/i',
      'type' => 'hierarchy_inversion',
      'severity' => 'critical',
      'description' => 'Norma reglamentaria no puede derogar/prevalecer sobre norma con rango de ley',
    ],
    [
      'pattern' => '/(?:(?:L|l)ey\s+(?:ordinaria|\d+\/\d{4}))\s+[^.]*(?:regula|desarrolla|establece)\s+[^.]*(?:derechos?\s+fundamentales?|habeas\s+corpus|r[eé]gimen\s+electoral)/i',
      'type' => 'organic_law_violation',
      'severity' => 'high',
      'description' => 'Materia reservada a Ley Organica no puede regularse por ley ordinaria',
    ],
    [
      'pattern' => '/(?:Ley\s+(?:de|del)\s+(?:Andaluc[ií]a|Catalu[nñ]a|Madrid|Galicia|Pa[ií]s\s+Vasco|Comunidad|Arag[oó]n))\s+[^.]*(?:regula|establece|modifica)\s+[^.]*(?:c[oó]digo\s+penal|legislaci[oó]n\s+mercantil|legislaci[oó]n\s+laboral\s+b[aá]sica|seguridad\s+social|nacionalidad|extranjer[ií]a)/i',
      'type' => 'competence_violation',
      'severity' => 'critical',
      'description' => 'Legislacion autonomica no puede regular competencia exclusiva del Estado (Art. 149.1 CE)',
    ],
    [
      'pattern' => '/(?:Constituci[oó]n|Ley\s+Org[aá]nica|Ley\s+\d)\s+[^.]*(?:prevalece|prima|se\s+aplica\s+preferentemente|deroga)\s+[^.]*(?:Reglamento\s+\(UE\)|Directiva\s+\(UE\)|Derecho\s+(?:de\s+la\s+)?(?:Uni[oó]n\s+Europea|UE|comunitario))/i',
      'type' => 'eu_primacy_violation',
      'severity' => 'critical',
      'description' => 'El Derecho interno no prevalece sobre el Derecho UE (primacia, Costa v. ENEL)',
    ],
    [
      'pattern' => '/(?:sanci[oó]n|multa|pena|castigo)\s+[^.]*(?:retroactiv|con\s+efecto\s+retroactivo|se\s+aplica\s+retroactivamente)\s+[^.]*(?:desfavorable|perjudicial|restrictiv)/i',
      'type' => 'retroactivity_violation',
      'severity' => 'high',
      'description' => 'Disposiciones sancionadoras desfavorables no tienen efecto retroactivo (Art. 9.3 CE)',
    ],
  ];

  /**
   * Regimenes de Derecho Civil especial / Foral.
   *
   * Art. 149.1.8 CE: "sin perjuicio de la conservacion, modificacion
   * y desarrollo por las CCAA de los derechos civiles, forales o
   * especiales, alli donde existan".
   */
  public const FORAL_LAW_REGIMES = [
    'cataluna' => [
      'corpus' => 'Codi Civil de Catalunya (Ley 29/2002 + reformas)',
      'materias' => ['sucesiones', 'regimen economico matrimonial', 'derechos reales', 'obligaciones', 'familia'],
      'ccaa' => 'Cataluna',
    ],
    'aragon' => [
      'corpus' => 'Codigo del Derecho Foral de Aragon (DL 1/2011)',
      'materias' => ['sucesiones', 'regimen economico matrimonial', 'derecho de la persona'],
      'ccaa' => 'Aragon',
    ],
    'navarra' => [
      'corpus' => 'Fuero Nuevo de Navarra (Ley 1/1973, compilacion 2019)',
      'materias' => ['sucesiones', 'regimen economico matrimonial', 'obligaciones', 'derechos reales'],
      'ccaa' => 'Navarra',
    ],
    'pais_vasco' => [
      'corpus' => 'Ley 5/2015 de Derecho Civil Vasco',
      'materias' => ['sucesiones', 'vecindad civil', 'regimen economico matrimonial'],
      'ccaa' => 'Pais Vasco',
    ],
    'baleares' => [
      'corpus' => 'Compilacion de Derecho Civil de Baleares (DL 79/1990)',
      'materias' => ['sucesiones', 'regimen economico matrimonial'],
      'ccaa' => 'Islas Baleares',
    ],
    'galicia' => [
      'corpus' => 'Ley 2/2006 de Derecho Civil de Galicia',
      'materias' => ['comunidad de bienes', 'derecho de familia', 'sucesiones', 'servidumbres'],
      'ccaa' => 'Galicia',
    ],
  ];

  /**
   * Verifica si un rango normativo es superior a otro.
   */
  public static function isHigherRank(string $normTypeA, string $normTypeB): bool {
    $rankA = self::NORMATIVE_HIERARCHY[$normTypeA]['rank'] ?? 99;
    $rankB = self::NORMATIVE_HIERARCHY[$normTypeB]['rank'] ?? 99;
    return $rankA < $rankB;
  }

  /**
   * Obtiene el rango de una norma a partir de su texto.
   */
  public static function detectNormRank(string $normText): ?string {
    foreach (self::NORM_TYPE_PATTERNS as $pattern => $hierarchyKey) {
      if (preg_match($pattern, $normText)) {
        return $hierarchyKey;
      }
    }
    return NULL;
  }

  /**
   * Verifica si una materia es competencia exclusiva del Estado.
   */
  public static function isStateExclusiveCompetence(string $text): ?array {
    $textLower = mb_strtolower($text);
    foreach (self::STATE_EXCLUSIVE_COMPETENCES as $id => $competence) {
      foreach ($competence['keywords'] as $keyword) {
        if (str_contains($textLower, mb_strtolower($keyword))) {
          return ['id' => $id, ...$competence];
        }
      }
    }
    return NULL;
  }

  /**
   * Verifica si una materia requiere Ley Organica.
   */
  public static function requiresOrganicLaw(string $text): ?string {
    $textLower = mb_strtolower($text);
    foreach (self::ORGANIC_LAW_MATTERS as $key => $description) {
      $keywords = explode(' ', str_replace('_', ' ', $key));
      $matchCount = 0;
      foreach ($keywords as $kw) {
        if (mb_strlen($kw) > 2 && str_contains($textLower, $kw)) {
          $matchCount++;
        }
      }
      if ($matchCount >= 2) {
        return $description;
      }
    }
    return NULL;
  }

  /**
   * Obtiene el peso jerarquico para Authority-Aware Ranking.
   */
  public static function getHierarchyWeight(string $normType): float {
    return self::HIERARCHY_WEIGHTS[$normType] ?? 0.50;
  }

  /**
   * Verifica si una materia tiene Derecho Foral aplicable en una CCAA.
   */
  public static function getForalRegime(string $matter, string $territory): ?array {
    $matterLower = mb_strtolower($matter);
    $territoryLower = mb_strtolower($territory);

    foreach (self::FORAL_LAW_REGIMES as $id => $regime) {
      if (mb_strtolower($regime['ccaa']) === $territoryLower
        || str_contains($territoryLower, $id)) {
        foreach ($regime['materias'] as $m) {
          if (str_contains($matterLower, $m) || str_contains($m, $matterLower)) {
            return ['id' => $id, ...$regime];
          }
        }
      }
    }

    return NULL;
  }

}
