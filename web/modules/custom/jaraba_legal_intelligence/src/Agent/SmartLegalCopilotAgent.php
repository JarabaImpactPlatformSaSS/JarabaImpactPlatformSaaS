<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Agent\SmartBaseAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * Smart Legal Copilot Agent — Gen 2 migration of LegalCopilotAgent.
 *
 * Extiende SmartBaseAgent con model routing inteligente y 8 modos
 * especializados para el vertical JarabaLex:
 * - legal_search: Busqueda guiada de jurisprudencia
 * - legal_analysis: Analisis de resoluciones y doctrina
 * - legal_alerts: Configuracion de alertas inteligentes
 * - legal_citations: Insercion de citas en expedientes
 * - legal_eu: Derecho europeo y primacia UE
 * - case_assistant: Asistente contextual de expedientes
 * - document_drafter: Redaccion de escritos juridicos
 * - faq: Preguntas frecuentes sobre la plataforma
 *
 * Model routing por complejidad:
 * - Fast tier: faq, legal_alerts, legal_citations
 * - Balanced tier (default): legal_search, case_assistant
 * - Premium tier: legal_analysis, legal_eu, document_drafter
 *
 * AGENT-GEN2-PATTERN-001: Extends SmartBaseAgent, overrides doExecute().
 * SMART-AGENT-CONSTRUCTOR-001: 10-arg constructor with optional services.
 *
 * @see \Drupal\jaraba_ai_agents\Agent\SmartBaseAgent
 * @see \Drupal\jaraba_legal_intelligence\Agent\LegalCopilotAgent
 * @see \Drupal\jaraba_legal_intelligence\Service\LegalCopilotBridgeService
 */
class SmartLegalCopilotAgent extends SmartBaseAgent
{

    /**
     * Modos soportados con metadatos y keywords de deteccion.
     */
    protected const MODES = [
        'legal_search' => [
            'label' => 'Busqueda Juridica',
            'description' => 'Busqueda guiada de jurisprudencia, normativa y doctrina',
            'keywords' => ['buscar', 'encontrar', 'jurisprudencia', 'sentencia', 'resolucion', 'normativa', 'consulta', 'busqueda'],
            'icon_category' => 'legal',
            'icon_name' => 'search-legal',
        ],
        'legal_analysis' => [
            'label' => 'Analisis Legal',
            'description' => 'Analisis de resoluciones, doctrina y lineas jurisprudenciales',
            'keywords' => ['analizar', 'interpretar', 'doctrina', 'criterio', 'linea jurisprudencial', 'evolucion', 'contradiccion'],
            'icon_category' => 'legal',
            'icon_name' => 'gavel',
        ],
        'legal_alerts' => [
            'label' => 'Alertas Juridicas',
            'description' => 'Configuracion y gestion de alertas inteligentes',
            'keywords' => ['alerta', 'notificar', 'avisar', 'vigilar', 'monitorizar', 'cambio normativo'],
            'icon_category' => 'legal',
            'icon_name' => 'alert-bell',
        ],
        'legal_citations' => [
            'label' => 'Citas Legales',
            'description' => 'Generacion e insercion de citas en expedientes',
            'keywords' => ['citar', 'cita', 'expediente', 'referencia', 'bibliografica', 'nota al pie', 'insertar'],
            'icon_category' => 'legal',
            'icon_name' => 'citation',
        ],
        'legal_eu' => [
            'label' => 'Derecho Europeo',
            'description' => 'Consultas sobre derecho europeo, primacia UE y TEDH',
            'keywords' => ['europeo', 'EUR-Lex', 'TJUE', 'TEDH', 'CURIA', 'primacia', 'efecto directo', 'directiva', 'reglamento', 'EDPB'],
            'icon_category' => 'legal',
            'icon_name' => 'eu-flag',
        ],
        'case_assistant' => [
            'label' => 'Asistente de Expedientes',
            'description' => 'Asistente contextual para gestion de expedientes y casos',
            'keywords' => ['expediente', 'caso', 'estado', 'resumen del caso', 'que falta', 'plazos', 'pendiente', 'actuaciones', 'historial'],
            'icon_category' => 'legal',
            'icon_name' => 'briefcase',
        ],
        'document_drafter' => [
            'label' => 'Redaccion de Escritos',
            'description' => 'Redaccion asistida de escritos y documentos juridicos',
            'keywords' => ['redactar', 'escrito', 'demanda', 'contestacion', 'recurso', 'borrador', 'plantilla', 'documento', 'modelo'],
            'icon_category' => 'legal',
            'icon_name' => 'document-legal',
        ],
        'faq' => [
            'label' => 'Ayuda',
            'description' => 'Preguntas frecuentes sobre la plataforma',
            'keywords' => ['ayuda', 'como', 'funciona', 'plan', 'precio', 'limite', 'cuenta'],
            'icon_category' => 'ui',
            'icon_name' => 'help-circle',
        ],
    ];

    /**
     * Prompts de sistema por modo.
     */
    protected const MODE_PROMPTS = [
        'legal_search' => 'Eres un asistente juridico especializado en busqueda de jurisprudencia, normativa y doctrina administrativa. '
            . 'Guia al usuario para formular busquedas efectivas. Sugiere filtros facetados (fuente, jurisdiccion, fecha, tipo). '
            . 'Explica los resultados de forma clara y cita siempre la referencia oficial. '
            . 'LEGAL-RAG-001: Toda respuesta basada en resoluciones debe incluir disclaimer y citas verificables.',

        'legal_analysis' => 'Eres un analista juridico experto. Analiza resoluciones, identifica lineas jurisprudenciales, '
            . 'detecta contradicciones doctrinales y explica la evolucion del criterio judicial. '
            . 'Cita siempre las fuentes. No inventes resoluciones. Si no tienes datos suficientes, indicalo.',

        'legal_alerts' => 'Eres un asistente de configuracion de alertas juridicas inteligentes. Ayuda al usuario a definir '
            . 'criterios de alerta efectivos: temas, fuentes, jurisdicciones y tipos de resolucion. '
            . 'Explica como funciona el sistema de alertas y como optimizar las notificaciones.',

        'legal_citations' => 'Eres un asistente de citacion legal. Ayuda al usuario a insertar citas de resoluciones en sus '
            . 'expedientes. Soportas 4 formatos: formal, resumida, bibliografica y nota al pie. '
            . 'Explica las diferencias entre formatos y cuando usar cada uno.',

        'legal_eu' => 'Eres un especialista en derecho europeo. Dominas EUR-Lex, CURIA (TJUE), HUDOC (TEDH) y EDPB. '
            . 'Explica primacia del derecho UE, efecto directo, transposicion de directivas y su impacto en el ordenamiento espanol. '
            . 'Cita siempre ECLI, numeros CELEX y asuntos.',

        'case_assistant' => 'Eres un asistente contextual de expedientes juridicos. Analizas el estado completo del caso: '
            . 'hechos, partes, plazos, documentos, citas y actividad reciente. '
            . 'Sugiere acciones pendientes, detecta plazos proximos y documentos faltantes. '
            . 'Resume el estado del expediente de forma estructurada. '
            . 'Si hay jurisprudencia vinculada, indicala. No inventes datos: trabaja solo con lo proporcionado.',

        'document_drafter' => 'Eres un redactor juridico experto. Generas borradores de escritos procesales '
            . '(demandas, contestaciones, recursos, escritos) profesionales y bien estructurados. '
            . 'Insertas citas de jurisprudencia donde proceda, usando formato legal formal. '
            . 'Adaptas el formato al tipo de procedimiento y jurisdiccion. '
            . 'IMPORTANTE: Incluye disclaimer indicando que el borrador requiere revision profesional antes de presentacion.',

        'faq' => 'Eres el asistente de ayuda de JarabaLex. Responde preguntas sobre la plataforma, planes, funcionalidades '
            . 'y limites. Se conciso y util. Si el usuario pregunta por algo fuera de la plataforma, redirige amablemente.',
    ];

    /**
     * Temperaturas por modo.
     *
     * @var array<string, float>
     */
    protected const MODE_TEMPERATURES = [
        'legal_search' => 0.3,
        'legal_analysis' => 0.5,
        'legal_alerts' => 0.3,
        'legal_citations' => 0.2,
        'legal_eu' => 0.4,
        'case_assistant' => 0.4,
        'document_drafter' => 0.3,
        'faq' => 0.3,
    ];

    /**
     * Modos que requieren fast tier (require_speed).
     *
     * @var string[]
     */
    protected const FAST_MODES = ['faq', 'legal_alerts', 'legal_citations'];

    /**
     * Modos que requieren premium tier (require_quality).
     *
     * @var string[]
     */
    protected const PREMIUM_MODES = ['legal_analysis', 'legal_eu', 'document_drafter'];

    /**
     * Constructs a SmartLegalCopilotAgent.
     */
    public function __construct(
        ?AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        ?TenantBrandVoiceService $brandVoice,
        ?AIObservabilityService $observability,
        ?ModelRouterService $modelRouter = NULL,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
        ?ToolRegistry $toolRegistry = NULL,
        ?ProviderFallbackService $providerFallback = NULL,
        ?ContextWindowManager $contextWindowManager = NULL,
    ) {
        if ($aiProvider && $brandVoice && $observability) {
            parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        }
        if ($modelRouter) {
            $this->setModelRouter($modelRouter);
        }
        $this->setToolRegistry($toolRegistry);
        $this->setProviderFallback($providerFallback);
        $this->setContextWindowManager($contextWindowManager);
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'smart_legal_copilot';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Smart Copiloto Legal JarabaLex';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Asistente IA especializado en busqueda juridica, analisis de resoluciones, alertas inteligentes, citas legales, derecho europeo, asistencia de expedientes y redaccion de escritos. Gen 2 con model routing inteligente.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            'legal_search' => [
                'label' => 'Busqueda Juridica',
                'description' => 'Busqueda guiada de jurisprudencia, normativa y doctrina',
                'requires' => ['query'],
                'optional' => ['source', 'jurisdiction', 'date_range'],
                'complexity' => 'medium',
            ],
            'legal_analysis' => [
                'label' => 'Analisis Legal',
                'description' => 'Analisis de resoluciones y lineas jurisprudenciales',
                'requires' => ['resolution_id'],
                'optional' => ['analysis_type'],
                'complexity' => 'high',
            ],
            'legal_alerts' => [
                'label' => 'Configurar Alerta',
                'description' => 'Configuracion de alertas juridicas inteligentes',
                'requires' => ['topic'],
                'optional' => ['sources', 'jurisdictions'],
                'complexity' => 'low',
            ],
            'legal_citations' => [
                'label' => 'Insertar Cita',
                'description' => 'Generacion e insercion de citas en expedientes',
                'requires' => ['resolution_id', 'format'],
                'optional' => ['expediente_id'],
                'complexity' => 'low',
            ],
            'legal_eu' => [
                'label' => 'Consulta Derecho Europeo',
                'description' => 'Consultas sobre EUR-Lex, CURIA, HUDOC y EDPB',
                'requires' => ['query'],
                'optional' => ['eu_source', 'date_range'],
                'complexity' => 'high',
            ],
            'case_assistant' => [
                'label' => 'Asistente del Expediente',
                'description' => 'Analiza el estado del caso y sugiere acciones pendientes',
                'requires' => ['case_id'],
                'optional' => ['query'],
                'complexity' => 'medium',
            ],
            'document_drafter' => [
                'label' => 'Redactor de Escritos',
                'description' => 'Genera borradores de escritos procesales',
                'requires' => ['case_id', 'document_type'],
                'optional' => ['template_id', 'instructions'],
                'complexity' => 'high',
            ],
            'faq' => [
                'label' => 'Ayuda Plataforma',
                'description' => 'Preguntas frecuentes sobre JarabaLex',
                'requires' => ['question'],
                'optional' => [],
                'complexity' => 'low',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $action, array $context): array
    {
        // 1. Detect mode from action or user query.
        $mode = array_key_exists($action, self::MODES)
            ? $action
            : $this->detectMode($context['query'] ?? $action);

        $userMessage = $context['query'] ?? $context['question'] ?? '';

        // 2. Load case context for contextual modes.
        $caseContext = NULL;
        if (in_array($mode, ['case_assistant', 'document_drafter'], TRUE) && !empty($context['case_id'])) {
            $caseContext = $this->loadCaseContext((int) $context['case_id']);
        }

        // 3. Build mode prompt (mode prompt + brand voice + case context + RAG + vertical).
        $systemPrompt = $this->buildModePrompt($mode, $userMessage, $caseContext);
        $temperature = $this->getModeTemperature($mode);

        // 4. Enrich drafter message if document_drafter mode.
        if ($mode === 'document_drafter') {
            $userMessage = $this->enrichDrafterMessage($userMessage, $context, $caseContext);
        }

        // 5. Build routing options based on mode complexity.
        $routingOptions = [
            'temperature' => $temperature,
            'user_message' => $userMessage,
        ];

        if (in_array($mode, self::FAST_MODES, TRUE)) {
            $routingOptions['require_speed'] = TRUE;
        } elseif (in_array($mode, self::PREMIUM_MODES, TRUE)) {
            $routingOptions['require_quality'] = TRUE;
        }

        // 6. Call AI API with model routing.
        $result = $this->callAiApi($systemPrompt, $routingOptions);

        // 7. Return result with mode metadata.
        return [
            'success' => !empty($result['data']['text']),
            'mode' => $mode,
            'response' => $result['data']['text'] ?? '',
            'metadata' => [
                'mode_label' => self::MODES[$mode]['label'] ?? $mode,
                'temperature' => $temperature,
                'tokens_used' => $result['routing']['estimated_cost'] ?? 0,
                'case_id' => $context['case_id'] ?? NULL,
                'routing' => $result['routing'] ?? [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBrandVoice(): string
    {
        return 'Profesional juridico riguroso. Tono formal pero accesible. '
            . 'Cita siempre fuentes verificables. Incluye disclaimer en analisis legales. '
            . 'Nunca inventes resoluciones ni datos normativos.';
    }

    /**
     * Detecta el modo mas adecuado basado en el mensaje del usuario.
     *
     * Usa scoring por keywords: cada keyword encontrada en el mensaje
     * incrementa la puntuacion del modo. El modo con mayor puntuacion gana.
     *
     * @param string $message
     *   Mensaje del usuario.
     *
     * @return string
     *   Modo detectado (default: legal_search).
     */
    public function detectMode(string $message): string
    {
        $message = mb_strtolower($message);
        $scores = [];

        foreach (self::MODES as $mode => $meta) {
            $score = 0;
            foreach ($meta['keywords'] as $keyword) {
                if (str_contains($message, mb_strtolower($keyword))) {
                    $score++;
                }
            }
            $scores[$mode] = $score;
        }

        arsort($scores);
        $bestMode = array_key_first($scores);

        return $scores[$bestMode] > 0 ? $bestMode : 'legal_search';
    }

    /**
     * Construye el prompt completo para un modo.
     *
     * Combina: mode prompt + brand voice + case context + RAG + vertical.
     *
     * @param string $mode
     *   Modo del copiloto.
     * @param string|null $userMessage
     *   Mensaje del usuario para contexto RAG.
     * @param array|null $caseContext
     *   Datos del expediente para modos contextuales.
     *
     * @return string
     *   System prompt completo.
     */
    public function buildModePrompt(string $mode, ?string $userMessage = NULL, ?array $caseContext = NULL): string
    {
        $prompt = self::MODE_PROMPTS[$mode] ?? self::MODE_PROMPTS['legal_search'];

        // Brand voice del tenant.
        $brandVoice = $this->getBrandVoicePrompt();
        if ($brandVoice) {
            $prompt .= "\n\nVOZ DE MARCA: " . $brandVoice;
        }

        // Contexto del expediente para modos contextuales.
        if ($caseContext && in_array($mode, ['case_assistant', 'document_drafter'], TRUE)) {
            $prompt .= "\n\nDATOS DEL EXPEDIENTE:\n" . $this->formatCaseContext($caseContext);
        }

        // Contexto RAG legal.
        if ($userMessage) {
            $ragContext = $this->getUnifiedContext($userMessage);
            if ($ragContext) {
                $prompt .= "\n\nCONTEXTO LEGAL RAG:\n" . $ragContext;
            }
        }

        // Contexto vertical.
        $verticalContext = $this->getVerticalContext();
        if ($verticalContext) {
            $prompt .= "\n\n" . $verticalContext;
        }

        return $prompt;
    }

    /**
     * Obtiene la temperatura por modo.
     *
     * @param string $mode
     *   Modo del copiloto.
     *
     * @return float
     *   Temperatura (default: 0.3).
     */
    public function getModeTemperature(string $mode): float
    {
        return self::MODE_TEMPERATURES[$mode] ?? 0.3;
    }

    /**
     * Carga el contexto completo de un expediente para modos contextuales.
     *
     * Carga entidades client_case, legal_citation y legal_deadline de Drupal,
     * mas actividad reciente si el servicio activity_logger esta disponible.
     *
     * @param int $caseId
     *   ID del expediente.
     *
     * @return array|null
     *   Datos del expediente o NULL si no se encuentra.
     */
    protected function loadCaseContext(int $caseId): ?array
    {
        try {
            $entityTypeManager = \Drupal::entityTypeManager();
            $case = $entityTypeManager->getStorage('client_case')->load($caseId);
            if (!$case) {
                return NULL;
            }

            $context = [
                'id' => (int) $case->id(),
                'title' => $case->get('title')->value ?? '',
                'status' => $case->get('status')->value ?? '',
                'case_number' => $case->get('case_number')->value ?? '',
            ];

            // Cargar citas vinculadas.
            if ($entityTypeManager->hasDefinition('legal_citation')) {
                $citationIds = $entityTypeManager->getStorage('legal_citation')
                    ->getQuery()
                    ->condition('case_id', $caseId)
                    ->accessCheck(FALSE)
                    ->range(0, 20)
                    ->execute();

                $citations = [];
                foreach ($entityTypeManager->getStorage('legal_citation')->loadMultiple($citationIds) as $c) {
                    $citations[] = $c->get('citation_text')->value ?? '';
                }
                $context['citations'] = $citations;
            }

            // Cargar actividad reciente via activity_logger si existe.
            if (\Drupal::hasService('jaraba_legal_cases.activity_logger')) {
                $logger = \Drupal::service('jaraba_legal_cases.activity_logger');
                if (method_exists($logger, 'getRecentActivity')) {
                    $context['recent_activity'] = $logger->getRecentActivity($caseId, 10);
                }
            }

            // Cargar plazos del calendario vinculados.
            if ($entityTypeManager->hasDefinition('legal_deadline')) {
                $deadlineIds = $entityTypeManager->getStorage('legal_deadline')
                    ->getQuery()
                    ->condition('case_id', $caseId)
                    ->condition('status', 'completed', '<>')
                    ->accessCheck(FALSE)
                    ->sort('deadline_date', 'ASC')
                    ->range(0, 10)
                    ->execute();

                $deadlines = [];
                foreach ($entityTypeManager->getStorage('legal_deadline')->loadMultiple($deadlineIds) as $d) {
                    $deadlines[] = [
                        'title' => $d->get('title')->value ?? '',
                        'date' => $d->get('deadline_date')->value ?? '',
                        'type' => $d->get('deadline_type')->value ?? '',
                    ];
                }
                $context['deadlines'] = $deadlines;
            }

            return $context;
        }
        catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Formatea el contexto del expediente como texto para el prompt.
     *
     * @param array $caseContext
     *   Datos del expediente cargados por loadCaseContext().
     *
     * @return string
     *   Texto formateado con datos del expediente.
     */
    protected function formatCaseContext(array $caseContext): string
    {
        $lines = [];
        $lines[] = "Expediente: {$caseContext['title']} ({$caseContext['case_number']})";
        $lines[] = "Estado: {$caseContext['status']}";

        if (!empty($caseContext['deadlines'])) {
            $lines[] = "\nPLAZOS PENDIENTES:";
            foreach ($caseContext['deadlines'] as $d) {
                $lines[] = "- {$d['title']} ({$d['type']}) — Fecha: {$d['date']}";
            }
        }

        if (!empty($caseContext['citations'])) {
            $lines[] = "\nCITAS JURIDICAS VINCULADAS:";
            foreach ($caseContext['citations'] as $citation) {
                $lines[] = "- {$citation}";
            }
        }

        if (!empty($caseContext['recent_activity'])) {
            $lines[] = "\nACTIVIDAD RECIENTE:";
            foreach ($caseContext['recent_activity'] as $activity) {
                $label = is_array($activity) ? ($activity['description'] ?? json_encode($activity)) : (string) $activity;
                $lines[] = "- {$label}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Enriquece el mensaje del usuario para el modo document_drafter.
     *
     * Agrega tipo de documento, instrucciones adicionales, contenido de
     * plantilla y referencia al expediente vinculado.
     *
     * @param string $userMessage
     *   Mensaje original del usuario.
     * @param array $context
     *   Contexto de ejecucion con document_type, instructions, template_id.
     * @param array|null $caseContext
     *   Datos del expediente o NULL.
     *
     * @return string
     *   Mensaje enriquecido.
     */
    protected function enrichDrafterMessage(string $userMessage, array $context, ?array $caseContext): string
    {
        $parts = [$userMessage];

        if (!empty($context['document_type'])) {
            $parts[] = "Tipo de documento solicitado: {$context['document_type']}";
        }

        if (!empty($context['instructions'])) {
            $parts[] = "Instrucciones adicionales: {$context['instructions']}";
        }

        // Si hay template_id, cargar instrucciones de la plantilla.
        if (!empty($context['template_id'])) {
            try {
                $template = \Drupal::entityTypeManager()
                    ->getStorage('legal_template')
                    ->load((int) $context['template_id']);
                if ($template) {
                    $aiInstructions = $template->get('ai_instructions')->value ?? '';
                    if ($aiInstructions) {
                        $parts[] = "Instrucciones de la plantilla: {$aiInstructions}";
                    }
                    $templateBody = $template->get('template_body')->value ?? '';
                    if ($templateBody) {
                        $parts[] = "Plantilla base:\n{$templateBody}";
                    }
                }
            }
            catch (\Exception $e) {
                // Continue without template context.
            }
        }

        if ($caseContext) {
            $parts[] = "Expediente vinculado: {$caseContext['title']} (#{$caseContext['case_number']})";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Genera sugerencias contextuales para chips del FAB.
     *
     * Retorna sugerencias basadas en la ruta actual del usuario,
     * adaptadas al contexto de navegacion en JarabaLex.
     *
     * @param string $currentRoute
     *   Ruta actual del usuario.
     *
     * @return array
     *   Array de sugerencias con label y action.
     */
    public function getSuggestions(string $currentRoute): array
    {
        return match (TRUE) {
            str_contains($currentRoute, 'legal_cases.') => [
                ['label' => 'Resumen del expediente', 'action' => 'case_assistant'],
                ['label' => 'Plazos pendientes', 'action' => 'case_assistant'],
                ['label' => 'Redactar escrito', 'action' => 'document_drafter'],
            ],
            str_contains($currentRoute, 'legal_templates.') => [
                ['label' => 'Redactar desde plantilla', 'action' => 'document_drafter'],
                ['label' => 'Ver plantillas similares', 'action' => 'search'],
                ['label' => 'Ayuda', 'action' => 'help'],
            ],
            str_contains($currentRoute, 'legal.search') => [
                ['label' => 'Buscar jurisprudencia', 'action' => 'search'],
                ['label' => 'Filtrar por fuente', 'action' => 'filter'],
                ['label' => 'Buscar en fuentes UE', 'action' => 'eu_search'],
            ],
            str_contains($currentRoute, 'legal.resolution') => [
                ['label' => 'Analizar esta resolucion', 'action' => 'analyze'],
                ['label' => 'Citar en expediente', 'action' => 'cite'],
                ['label' => 'Buscar similares', 'action' => 'similar'],
            ],
            str_contains($currentRoute, 'legal.dashboard') => [
                ['label' => 'Configurar alerta', 'action' => 'alert'],
                ['label' => 'Revisar bookmarks', 'action' => 'bookmarks'],
                ['label' => 'Ver digest semanal', 'action' => 'digest'],
            ],
            default => [
                ['label' => 'Buscar jurisprudencia', 'action' => 'search'],
                ['label' => 'Configurar alertas', 'action' => 'alerts'],
                ['label' => 'Ayuda', 'action' => 'help'],
            ],
        };
    }

    /**
     * Genera sugerencia de upgrade contextual.
     *
     * Verifica el plan del usuario via JarabaLexFeatureGateService y
     * genera sugerencias de upgrade si el usuario es free con engagement
     * suficiente. Patron fire-and-forget con try-catch.
     *
     * @param array $context
     *   Contexto opcional con user_id.
     *
     * @return array|null
     *   Sugerencia de upgrade o NULL.
     */
    public function getSoftSuggestion(array $context = []): ?array
    {
        try {
            $userId = $context['user_id'] ?? (int) \Drupal::currentUser()->id();
            if (!$userId) {
                return NULL;
            }

            if (!\Drupal::hasService('ecosistema_jaraba_core.jarabalex_feature_gate')) {
                return NULL;
            }

            /** @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService $featureGate */
            $featureGate = \Drupal::service('ecosistema_jaraba_core.jarabalex_feature_gate');
            $plan = $featureGate->getUserPlan($userId);
            if ($plan !== 'free') {
                return NULL;
            }

            // Verificar engagement minimo.
            $result = $featureGate->check($userId, 'searches_per_month');
            $used = $result->used ?? 0;
            if ($used < 3) {
                return NULL;
            }

            if ($used >= 8) {
                return [
                    'type' => 'upgrade',
                    'message' => 'Estas usando la inteligencia legal de forma intensiva. Con el plan Starter tendras busquedas ilimitadas, alertas y digest semanal.',
                    'cta' => [
                        'label' => 'Ver plan Starter',
                        'url' => '/upgrade?vertical=jarabalex&source=copilot_agent',
                    ],
                    'trigger' => 'copilot_premium_upsell',
                ];
            }

            return [
                'type' => 'upgrade',
                'message' => 'Tu actividad legal crece. Con el plan Starter podrias buscar sin limites y configurar alertas ilimitadas.',
                'cta' => [
                    'label' => 'Ver plan Starter',
                    'url' => '/upgrade?vertical=jarabalex&source=copilot_agent',
                ],
                'trigger' => 'copilot_soft_upsell',
            ];
        }
        catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Devuelve los modos disponibles con sus metadatos.
     *
     * @return array
     *   Array asociativo de modos con label, description, keywords, icon.
     */
    public function getAvailableModes(): array
    {
        return self::MODES;
    }

}
