<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

/**
 * JarabaLex Copilot Agent — Asistente juridico IA clase mundial.
 *
 * Plan Elevacion JarabaLex Clase Mundial v1 — Fase 11.
 * Extiende BaseAgent con 6 modos especializados en derecho espanol y europeo.
 *
 * Modos:
 * - legal_search: Busqueda semantica en jurisprudencia y normativa.
 * - legal_analysis: Analisis de resoluciones con citas cruzadas.
 * - legal_alerts: Configuracion y gestion de alertas normativas.
 * - case_assistant: Asistente contextual de expedientes.
 * - document_drafter: Redactor de escritos procesales.
 * - legal_advisor: Asesoramiento juridico general.
 *
 * Directrices:
 * - LEGAL-RAG-001: Siempre citar fuente (ECLI/BOE/TEAC/EU).
 * - Soft upsell solo para plan free + fase >= 3.
 * - Temperaturas individuales por modo.
 */
class JarabaLexCopilotAgent extends BaseAgent {

  /**
   * Modos del agente con sus configuraciones.
   */
  protected const MODES = [
    'legal_alerts' => [
      'label' => 'Alertas Normativas',
      'description' => 'Configuracion y gestion de alertas sobre cambios normativos.',
      'temperature' => 0.2,
      'keywords' => ['alerta', 'notificar', 'vigilar', 'cambio normativo', 'derogacion'],
    ],
    'legal_search' => [
      'label' => 'Busqueda Juridica',
      'description' => 'Busqueda semantica en jurisprudencia, legislacion y doctrina administrativa.',
      'temperature' => 0.3,
      'keywords' => ['buscar', 'jurisprudencia', 'sentencia', 'ley', 'normativa', 'STS', 'BOE', 'CENDOJ'],
    ],
    'legal_analysis' => [
      'label' => 'Analisis Legal',
      'description' => 'Analisis de resoluciones judiciales con citas cruzadas y linea jurisprudencial.',
      'temperature' => 0.4,
      'keywords' => ['analizar', 'analisis', 'fundamentar', 'citar', 'doctrina', 'linea jurisprudencial'],
    ],
    'case_assistant' => [
      'label' => 'Asistente del Expediente',
      'description' => 'Analiza el estado del caso y sugiere acciones procesales.',
      'temperature' => 0.4,
      'keywords' => ['expediente', 'caso', 'plazo', 'actuacion', 'estado', 'partes'],
    ],
    'document_drafter' => [
      'label' => 'Redactor de Escritos',
      'description' => 'Genera borradores de escritos procesales profesionales.',
      'temperature' => 0.3,
      'keywords' => ['redactar', 'escrito', 'demanda', 'recurso', 'contestacion', 'borrador'],
    ],
    'legal_advisor' => [
      'label' => 'Asesor Juridico',
      'description' => 'Asesoramiento juridico general con citacion de fuentes.',
      'temperature' => 0.5,
      'keywords' => ['consulta', 'consejo', 'recomendar', 'que hacer', 'obligacion', 'derecho'],
    ],
  ];

  /**
   * System prompts por modo.
   */
  protected const MODE_PROMPTS = [
    'legal_search' => 'Eres un motor de busqueda juridica especializado. Buscas en 8 fuentes: '
      . 'CENDOJ, BOE, DGT, TEAC, EUR-Lex, CURIA, HUDOC y EDPB. '
      . 'LEGAL-RAG-001: Siempre cita la fuente con ECLI, numero de disposicion o referencia oficial. '
      . 'Presenta resultados ordenados por relevancia con abstract y metadatos.',
    'legal_analysis' => 'Eres un analista juridico experto en derecho espanol y europeo. '
      . 'Analizas resoluciones judiciales, identificas ratio decidendi, obiter dicta y lineas jurisprudenciales. '
      . 'LEGAL-RAG-001: Siempre referencia la fuente con ECLI o numero oficial. '
      . 'Detectas contradicciones doctrinales y cambios de criterio.',
    'legal_alerts' => 'Eres un sistema de vigilancia normativa. Monitorizas cambios en '
      . 'legislacion, jurisprudencia y doctrina administrativa. Configuras alertas personalizadas '
      . 'por jurisdiccion, materia y tipo de fuente. Notificas de derogaciones, anulaciones y nueva doctrina.',
    'case_assistant' => 'Eres un asistente contextual de expedientes juridicos. Analizas el estado completo del caso: '
      . 'hechos, partes, plazos, documentos, citas y actividad reciente. '
      . 'Sugieres acciones procesales concretas basadas en el estado del expediente y los plazos vigentes. '
      . 'LEGAL-RAG-001: Referencia articulos y jurisprudencia aplicable.',
    'document_drafter' => 'Eres un redactor juridico experto. Generas borradores de escritos procesales '
      . '(demandas, contestaciones, recursos, escritos) profesionales y bien estructurados. '
      . 'Usas terminologia juridica precisa, citas de articulos y jurisprudencia. '
      . 'Estructura: encabezamiento, hechos, fundamentos de derecho, suplico/petitum.',
    'legal_advisor' => 'Eres un asesor juridico con amplio conocimiento del ordenamiento espanol y europeo. '
      . 'Proporcionas orientacion juridica clara y fundamentada, siempre citando las fuentes legales. '
      . 'LEGAL-RAG-001: Referencia articulos, sentencias y normativa aplicable. '
      . 'Adviertes cuando una cuestion requiere asistencia letrada presencial.',
  ];

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return 'jarabalex_copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'JarabaLex Copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Copiloto juridico IA para profesionales del derecho. Busqueda semantica, analisis, alertas, expedientes y redaccion de escritos.';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableModes(): array {
    $modes = [];
    foreach (self::MODES as $key => $config) {
      $modes[$key] = [
        'label' => $config['label'],
        'description' => $config['description'],
      ];
    }
    return $modes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    $actions = [];
    foreach (self::MODES as $key => $config) {
      $actions[$key] = [
        'label' => $config['label'],
        'description' => $config['description'],
        'requires' => ['message'],
        'optional' => [],
        'tier' => 'balanced',
      ];
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(string $action, array $context): array {
    $this->setCurrentAction($action);
    $message = $context['message'] ?? '';
    if (empty($message)) {
      return [
        'success' => FALSE,
        'error' => 'No message provided in context.',
      ];
    }

    $mode = isset(self::MODES[$action]) ? $action : $this->detectMode($message);
    $options = [
      'temperature' => $this->getTemperature($mode),
    ];

    return $this->callAiApi($message, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function detectMode(string $message): string {
    $lowerMessage = mb_strtolower($message);

    foreach (self::MODES as $mode => $config) {
      foreach ($config['keywords'] as $keyword) {
        if (str_contains($lowerMessage, $keyword)) {
          return $mode;
        }
      }
    }

    return 'legal_advisor';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemperature(string $mode): float {
    return self::MODES[$mode]['temperature'] ?? 0.4;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModeSystemPrompt(string $mode): string {
    $prompt = self::MODE_PROMPTS[$mode] ?? self::MODE_PROMPTS['legal_advisor'];

    if ($this->shouldShowSoftUpsell()) {
      $prompt .= "\n\nNOTA: El usuario tiene plan gratuito. Si la consulta requiere funcionalidades premium "
        . "(busquedas ilimitadas, alertas avanzadas, redaccion IA), menciona sutilmente los beneficios del plan Starter o Profesional "
        . "sin ser insistente. Maximo una vez por conversacion.";
    }

    return $prompt;
  }

  /**
   * Determina si debe mostrar soft upsell.
   *
   * Solo para usuarios con plan free y fase >= 3 del journey.
   */
  protected function shouldShowSoftUpsell(): bool {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.jarabalex_feature_gate')) {
        return FALSE;
      }
      /** @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService $featureGate */
      $featureGate = \Drupal::service('ecosistema_jaraba_core.jarabalex_feature_gate');
      $result = $featureGate->check('legal_search');
      return isset($result['plan']) && $result['plan'] === 'free';
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return 'Eres un profesional juridico con anos de experiencia. '
      . 'Tu comunicacion es precisa, formal pero accesible. '
      . 'Utilizas terminologia juridica correcta y siempre citas las fuentes legales. '
      . 'Tu tono inspira confianza y profesionalidad.';
  }

}
