<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de enrutamiento inteligente de conversaciones a agentes.
 *
 * ESTRUCTURA:
 *   Router central que clasifica intenciones del usuario mediante
 *   analisis de keywords y asigna la conversacion al agente mas
 *   adecuado. Mantiene mapas de keywords por tipo de agente.
 *
 * LOGICA:
 *   Implementa clasificacion basada en keywords para determinar
 *   el agente mas apropiado:
 *   - Enrollment keywords -> enrollment agent (matricula, inscripcion...).
 *   - Planning keywords -> planning agent (planificacion, horario...).
 *   - Support keywords -> support agent (ayuda, problema, soporte...).
 *   La confianza se calcula como proporcion de keywords encontradas.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentRouterService {

  /**
   * Mapa de keywords por tipo de agente para clasificacion de intenciones.
   *
   * Cada clave es el tipo de agente y su valor es un array de keywords
   * en espanol que indican esa intencion.
   */
  protected const KEYWORD_MAP = [
    'enrollment' => [
      'matricula', 'inscripcion', 'inscribir', 'matricular', 'registro',
      'registrar', 'admision', 'admisiones', 'plaza', 'plazas',
      'solicitud', 'solicitar', 'candidatura', 'postulacion', 'beca',
      'becas', 'convocatoria', 'preinscripcion', 'enrollment',
    ],
    'planning' => [
      'planificacion', 'planificar', 'horario', 'horarios', 'calendario',
      'agenda', 'programacion', 'programar', 'clase', 'clases',
      'asignatura', 'asignaturas', 'creditos', 'semestre', 'trimestre',
      'periodo', 'plan', 'estudios', 'itinerario', 'trayectoria',
    ],
    'support' => [
      'ayuda', 'problema', 'soporte', 'incidencia', 'error', 'fallo',
      'consulta', 'duda', 'pregunta', 'queja', 'reclamacion',
      'sugerencia', 'contacto', 'atencion', 'asistencia', 'tecnico',
      'urgente', 'urgencia', 'resolucion', 'ticket',
    ],
  ];

  /**
   * Ultima confianza calculada por el router.
   *
   * @var float
   */
  protected float $lastConfidence = 0.0;

  /**
   * Construye el servicio de enrutamiento de agentes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   * @param object $tenantContext
   *   Servicio de contexto de tenant para aislamiento multi-tenant.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly object $tenantContext,
  ) {}

  /**
   * Enruta un mensaje a un agente dentro de una conversacion.
   *
   * Clasifica la intencion del mensaje mediante analisis de keywords,
   * busca el agente correspondiente y devuelve el resultado del routing.
   *
   * @param int $conversationId
   *   ID de la conversacion activa.
   * @param string $message
   *   Mensaje del usuario a clasificar.
   *
   * @return array
   *   Array con ['agent_id' => int, 'confidence' => float, 'reasoning' => string].
   */
  public function route(int $conversationId, string $message): array {
    try {
      // Clasificar la intencion del mensaje.
      $classification = $this->classify($message);
      $intent = $classification['intent'];
      $confidence = $classification['confidence'];

      $this->lastConfidence = $confidence;

      // Buscar agente que coincida con la intencion clasificada.
      $agentStorage = $this->entityTypeManager->getStorage('autonomous_agent');
      $query = $agentStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->range(0, 50);

      $agentIds = $query->execute();

      if (empty($agentIds)) {
        $this->logger->warning('No hay agentes activos disponibles para routing de conversacion @id.', [
          '@id' => $conversationId,
        ]);
        return [
          'agent_id' => 0,
          'confidence' => 0.0,
          'reasoning' => (string) new TranslatableMarkup('No hay agentes activos disponibles.'),
        ];
      }

      $agents = $agentStorage->loadMultiple($agentIds);
      $matchedAgentId = 0;

      // Buscar agente cuyo nombre o configuracion coincida con la intencion.
      foreach ($agents as $agent) {
        $agentName = strtolower($agent->get('name')->value ?? '');
        if (str_contains($agentName, $intent)) {
          $matchedAgentId = (int) $agent->id();
          break;
        }
      }

      // Fallback: asignar el primer agente activo disponible.
      if ($matchedAgentId === 0) {
        $firstAgent = reset($agents);
        $matchedAgentId = (int) $firstAgent->id();
        $confidence = max(0.3, $confidence * 0.5);
        $this->lastConfidence = $confidence;
      }

      $reasoning = (string) new TranslatableMarkup(
        'Intencion detectada: @intent con confianza @confidence. Agente asignado: @agent_id.',
        [
          '@intent' => $intent,
          '@confidence' => number_format($confidence, 2),
          '@agent_id' => $matchedAgentId,
        ]
      );

      $this->logger->info('Routing de conversacion @conv a agente @agent (intent: @intent, confidence: @conf).', [
        '@conv' => $conversationId,
        '@agent' => $matchedAgentId,
        '@intent' => $intent,
        '@conf' => number_format($confidence, 2),
      ]);

      return [
        'agent_id' => $matchedAgentId,
        'confidence' => $confidence,
        'reasoning' => $reasoning,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en routing de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'agent_id' => 0,
        'confidence' => 0.0,
        'reasoning' => (string) new TranslatableMarkup('Error interno durante el routing.'),
      ];
    }
  }

  /**
   * Clasifica la intencion de un mensaje mediante analisis de keywords.
   *
   * Busca coincidencias de keywords en el texto del mensaje para
   * determinar la intencion mas probable y su confianza.
   *
   * @param string $message
   *   Texto del mensaje a clasificar.
   *
   * @return array
   *   Array con ['intent' => string, 'entities' => array, 'confidence' => float].
   */
  public function classify(string $message): array {
    $normalizedMessage = mb_strtolower(trim($message));
    $scores = [];
    $matchedEntities = [];

    foreach (self::KEYWORD_MAP as $intentType => $keywords) {
      $matchCount = 0;
      $matched = [];

      foreach ($keywords as $keyword) {
        if (str_contains($normalizedMessage, $keyword)) {
          $matchCount++;
          $matched[] = $keyword;
        }
      }

      if ($matchCount > 0) {
        // Calcular confianza como proporcion de keywords encontradas.
        $scores[$intentType] = $matchCount / count($keywords);
        $matchedEntities[$intentType] = $matched;
      }
    }

    if (empty($scores)) {
      $this->lastConfidence = 0.1;
      return [
        'intent' => 'support',
        'entities' => [],
        'confidence' => 0.1,
      ];
    }

    // Seleccionar la intencion con mayor puntuacion.
    arsort($scores);
    $bestIntent = array_key_first($scores);
    $bestConfidence = min(1.0, $scores[$bestIntent] * 3.0);

    $this->lastConfidence = $bestConfidence;

    return [
      'intent' => $bestIntent,
      'entities' => $matchedEntities[$bestIntent] ?? [],
      'confidence' => $bestConfidence,
    ];
  }

  /**
   * Devuelve la ultima confianza de routing calculada.
   *
   * @return float
   *   Valor de confianza entre 0.0 y 1.0.
   */
  public function getConfidence(): float {
    return $this->lastConfidence;
  }

}
