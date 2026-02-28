<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService;
use Psr\Log\LoggerInterface;

/**
 * Servicio puente entre el Legal Intelligence Hub y el sistema Copilot.
 *
 * ESTRUCTURA:
 * Bridge between Legal Intelligence Hub and the Copilot system. Provides
 * searchForCopilot() for conversational legal search, getRelevantKnowledge()
 * for RAG context injection, and attachResultToExpediente() for one-click
 * citation insertion from the copilot chat.
 *
 * LOGICA:
 * searchForCopilot delegates to LegalSearchService with a max of 5 results,
 * formats for the copilot UI with action buttons. getRelevantKnowledge uses
 * keyword detection to decide if legal context is relevant to the user
 * message, then performs a lightweight 3-result search for RAG injection.
 *
 * RELACIONES:
 * - LegalCopilotBridgeService -> LegalSearchService: semantic search in Qdrant.
 * - LegalCopilotBridgeService -> LegalCitationService: citation insertion
 *   into expedientes.
 * - LegalCopilotBridgeService -> EntityTypeManagerInterface: entity loading
 *   for enrichment.
 * - LegalCopilotBridgeService <- UnifiedPromptBuilder: called for RAG
 *   knowledge injection.
 * - LegalCopilotBridgeService <- LegalSearchController::apiCopilotSearch():
 *   API endpoint.
 *
 * SINTAXIS:
 * Servicio registrado como jaraba_legal_intelligence.copilot_bridge.
 * Inyecta search, citation, entity_type.manager y logger.
 */
class LegalCopilotBridgeService {

  /**
   * Palabras clave legales para deteccion de contexto juridico.
   *
   * Lista de terminos que, si aparecen en el mensaje del usuario,
   * indican que el contexto legal es relevante para la respuesta
   * del copilot y se debe inyectar conocimiento RAG.
   *
   * @var string[]
   */
  private const LEGAL_KEYWORDS = [
    'jurisprudencia',
    'resolucion',
    'sentencia',
    'consulta DGT',
    'doctrina',
    'normativa',
    'ley',
    'tribunal',
    'recurso',
    'casacion',
  ];

  /**
   * Mapeo de source_id a etiquetas legibles en espanol.
   *
   * @var array<string, string>
   */
  private const SOURCE_LABELS = [
    'cendoj' => 'CENDOJ',
    'boe' => 'BOE',
    'dgt' => 'DGT (Consultas Vinculantes)',
    'teac' => 'TEAC',
    'tjue' => 'TJUE (Tribunal de Justicia UE)',
    'eurlex' => 'EUR-Lex',
    'tedh' => 'TEDH (Tribunal Europeo DDHH)',
    'edpb' => 'EDPB',
  ];

  /**
   * Construye una nueva instancia de LegalCopilotBridgeService.
   *
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalSearchService $searchService
   *   Servicio de busqueda semantica para resoluciones juridicas.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalCitationService $citationService
   *   Servicio de gestion de citas y favoritos legales.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para carga y enriquecimiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    protected LegalSearchService $searchService,
    protected LegalCitationService $citationService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected JarabaLexFeatureGateService $featureGate,
  ) {}

  /**
   * Ejecuta busqueda legal desde el copilot conversacional.
   *
   * Punto de entrada principal para busquedas legales iniciadas desde la
   * interfaz del copilot. Extrae entidades legales del contexto de la
   * conversacion, construye filtros facetados y delega en LegalSearchService
   * con un maximo de 5 resultados formateados para la UI del copilot.
   *
   * @param string $query
   *   Texto de busqueda del usuario en lenguaje natural.
   * @param array $context
   *   Contexto de la conversacion del copilot. Claves opcionales:
   *   - expediente_id: int — ID del expediente activo.
   *   - source_ids: string[] — Fuentes a filtrar.
   *   - date_range: array — Rango de fechas con 'from' y 'to'.
   *
   * @return array
   *   Array asociativo con claves:
   *   - success: bool — TRUE si la busqueda se completo.
   *   - results: array — Resultados formateados para el copilot.
   *   - total: int — Numero total de resultados encontrados.
   *   - query: string — Query original del usuario.
   *   - suggested_response: string — Texto sugerido para el copilot.
   */
  public function searchForCopilot(string $query, array $context = []): array {
    $filters = [];

    // Extraer entidades legales del contexto de la conversacion.
    if (!empty($context['source_ids'])) {
      $filters['source_id'] = $context['source_ids'];
    }

    if (!empty($context['date_range']['from'])) {
      $filters['date_from'] = $context['date_range']['from'];
    }

    if (!empty($context['date_range']['to'])) {
      $filters['date_to'] = $context['date_range']['to'];
    }

    try {
      $searchResults = $this->searchService->search($query, $filters, 'all', 5);

      $formattedResults = [];
      if (!empty($searchResults['results'])) {
        foreach ($searchResults['results'] as $result) {
          $formattedResults[] = $this->formatResultForCopilot($result);
        }
      }

      $total = count($formattedResults);
      $suggestedResponse = sprintf(
        'He encontrado %d resoluciones relevantes sobre %s:',
        $total,
        $query
      );

      return [
        'success' => TRUE,
        'results' => $formattedResults,
        'total' => $total,
        'query' => $query,
        'suggested_response' => $suggestedResponse,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('CopilotBridge: Error en busqueda para copilot: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'results' => [],
        'total' => 0,
        'query' => $query,
        'suggested_response' => 'No se han podido obtener resultados legales en este momento.',
      ];
    }
  }

  /**
   * Genera contexto legal para inyeccion RAG en el prompt del copilot.
   *
   * Analiza el mensaje del usuario buscando palabras clave legales. Si se
   * detectan terminos juridicos, ejecuta una busqueda ligera (3 resultados)
   * y formatea los resultados como un bloque XML de contexto para inyeccion
   * en el prompt del copilot.
   *
   * @param string $userMessage
   *   Mensaje del usuario en la conversacion del copilot.
   *
   * @return string
   *   Bloque XML de contexto legal para RAG, o cadena vacia si el mensaje
   *   no contiene terminos juridicos relevantes.
   */
  public function getRelevantKnowledge(string $userMessage): string {
    if (!$this->containsLegalKeywords($userMessage)) {
      return '';
    }

    try {
      $searchResults = $this->searchService->search($userMessage, [], 'all', 3);

      if (empty($searchResults['results'])) {
        return '';
      }

      $xml = '<legal_context>';
      foreach ($searchResults['results'] as $result) {
        $title = htmlspecialchars($result['title'] ?? '', ENT_XML1, 'UTF-8');
        $source = htmlspecialchars($result['source_id'] ?? '', ENT_XML1, 'UTF-8');
        $ref = htmlspecialchars($result['external_ref'] ?? '', ENT_XML1, 'UTF-8');
        $date = htmlspecialchars($result['date_issued'] ?? '', ENT_XML1, 'UTF-8');
        $abstract = htmlspecialchars(
          mb_substr($result['abstract_ai'] ?? '', 0, 200),
          ENT_XML1,
          'UTF-8'
        );

        $xml .= sprintf(
          '<result title="%s" source="%s" ref="%s" date="%s" abstract="%s"/>',
          $title,
          $source,
          $ref,
          $date,
          $abstract
        );
      }
      $xml .= '</legal_context>';

      // LCIS Capa 4: Prepend coherencia juridica al contexto RAG legal.
      $xml = \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherencePromptRule::COHERENCE_PROMPT_SHORT . "\n\n" . $xml;

      return $xml;
    }
    catch (\Exception $e) {
      $this->logger->warning('CopilotBridge: Error generando contexto RAG: @message', [
        '@message' => $e->getMessage(),
      ]);

      return '';
    }
  }

  /**
   * Vincula un resultado de busqueda a un expediente desde el copilot.
   *
   * Metodo de conveniencia para que el copilot pueda vincular una
   * resolucion legal a un expediente con un solo clic desde el chat.
   * Obtiene el usuario actual y delega en LegalCitationService.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution a vincular.
   * @param int $expedienteId
   *   ID del expediente destino.
   * @param string $format
   *   Formato de cita: 'formal', 'resumida', 'bibliografica' o 'nota_al_pie'.
   *
   * @return array
   *   Resultado de la operacion de vinculacion delegado a
   *   LegalCitationService::attachToExpediente().
   */
  public function attachResultToExpediente(int $resolutionId, int $expedienteId, string $format = 'formal'): array {
    $userId = (int) \Drupal::currentUser()->id();

    return $this->citationService->attachToExpediente(
      $resolutionId,
      $expedienteId,
      $format,
      $userId
    );
  }

  /**
   * Devuelve etiquetas legibles para todos los source_id soportados.
   *
   * Proporciona el mapeo completo de identificadores internos de fuente
   * a nombres legibles en espanol para uso en la interfaz del copilot.
   *
   * @return array<string, string>
   *   Array asociativo con source_id como clave y etiqueta en espanol
   *   como valor.
   */
  public function getSourceLabels(): array {
    return self::SOURCE_LABELS;
  }

  /**
   * Genera una sugerencia de upgrade contextual para el copilot.
   *
   * Sigue el patron de EmployabilityCopilotAgent::getSoftSuggestion().
   * Solo sugiere a usuarios en plan free con actividad suficiente
   * (al menos 3 busquedas realizadas).
   *
   * Plan Elevacion JarabaLex v1 — Fase 5.
   *
   * @param array $context
   *   Contexto opcional: user_id, current_route.
   *
   * @return array|null
   *   Sugerencia de upgrade o NULL si no aplica.
   */
  public function getSoftSuggestion(array $context = []): ?array {
    try {
      $userId = $context['user_id'] ?? (int) \Drupal::currentUser()->id();
      if (!$userId) {
        return NULL;
      }

      $plan = $this->featureGate->getUserPlan($userId);
      if ($plan !== 'free') {
        return NULL;
      }

      // Verificar que el usuario tiene actividad suficiente (>= 3 busquedas).
      $searchResult = $this->featureGate->check($userId, 'searches_per_month');
      $used = $searchResult->used ?? 0;
      if ($used < 3) {
        return NULL;
      }

      // Determinar sugerencia segun nivel de uso.
      if ($used >= 8) {
        return [
          'type' => 'upgrade',
          'message' => 'Estas usando la inteligencia legal de forma intensiva. Con el plan Starter tendras busquedas ilimitadas, alertas juridicas y acceso al digest semanal.',
          'cta' => [
            'label' => 'Ver plan Starter',
            'url' => '/upgrade?vertical=jarabalex&source=copilot',
          ],
          'trigger' => 'copilot_premium_upsell',
        ];
      }

      return [
        'type' => 'upgrade',
        'message' => 'Tu actividad legal crece. Con el plan Starter podrias buscar jurisprudencia sin limites y configurar alertas para tus areas de practica.',
        'cta' => [
          'label' => 'Ver plan Starter',
          'url' => '/upgrade?vertical=jarabalex&source=copilot',
        ],
        'trigger' => 'copilot_soft_upsell',
      ];
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Formatea un resultado de busqueda para la interfaz del copilot.
   *
   * Transforma un resultado crudo de LegalSearchService en la estructura
   * esperada por la UI del copilot, incluyendo botones de accion para
   * citar, ver completo y buscar resoluciones similares.
   *
   * @param array $result
   *   Resultado crudo de LegalSearchService con metadatos de la resolucion.
   *
   * @return array
   *   Resultado formateado con claves: title, source_id, external_ref,
   *   abstract_ai, date_issued, resolution_type, importance_level, actions.
   */
  private function formatResultForCopilot(array $result): array {
    $id = $result['id'] ?? 0;
    $sourceId = $result['source_id'] ?? '';
    $externalRef = $result['external_ref'] ?? '';

    return [
      'title' => $result['title'] ?? '',
      'source_id' => $sourceId,
      'external_ref' => $externalRef,
      'abstract_ai' => $result['abstract_ai'] ?? '',
      'date_issued' => $result['date_issued'] ?? '',
      'resolution_type' => $result['resolution_type'] ?? '',
      'importance_level' => $result['importance_level'] ?? 0,
      'actions' => [
        'cite' => [
          'type' => 'cite',
          'label' => 'Insertar en expediente',
          'url' => "/legal/cite/{$id}/formal",
        ],
        'view' => [
          'type' => 'view',
          'label' => 'Ver completo',
          'url' => "/legal/{$sourceId}/{$externalRef}",
        ],
        'similar' => [
          'type' => 'similar',
          'label' => 'Buscar similares',
          'url' => "/legal/{$id}/similar",
        ],
      ],
    ];
  }

  /**
   * Verifica si un texto contiene palabras clave legales.
   *
   * Comprueba la presencia de terminos juridicos predefinidos en el texto
   * del usuario usando expresion regular case-insensitive con soporte
   * Unicode para determinar si el contexto legal es relevante.
   *
   * @param string $text
   *   Texto a analizar en busca de terminos legales.
   *
   * @return bool
   *   TRUE si el texto contiene al menos una palabra clave legal.
   */
  private function containsLegalKeywords(string $text): bool {
    $pattern = '/(' . implode('|', array_map(
      fn(string $keyword): string => preg_quote($keyword, '/'),
      self::LEGAL_KEYWORDS
    )) . ')/iu';

    return (bool) preg_match($pattern, $text);
  }

}
