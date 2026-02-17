<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de observabilidad y trazabilidad de conversaciones multi-agente.
 *
 * ESTRUCTURA:
 *   Proporciona trazas, metricas agregadas y visualizacion de cadenas
 *   de agentes para monitoreo de la orquestacion multi-agente.
 *
 * LOGICA:
 *   - trace(): Genera traza completa de una conversacion con handoffs.
 *   - getMetrics(): Agrega metricas globales de conversaciones.
 *   - getChainVisualization(): Genera nodos y aristas para visualizacion
 *     de grafos de la cadena de agentes.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentObserverService {

  /**
   * Construye el servicio de observabilidad de agentes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera una traza completa de una conversacion y sus handoffs.
   *
   * Carga la conversacion y todos sus handoffs ordenados por handoff_at
   * para proporcionar una vista cronologica completa.
   *
   * @param int $conversationId
   *   ID de la conversacion a trazar.
   *
   * @return array
   *   Array estructurado con datos de la conversacion y handoffs.
   */
  public function trace(int $conversationId): array {
    try {
      // Cargar la conversacion.
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->warning('Conversacion no encontrada para trace: @id', ['@id' => $conversationId]);
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Conversacion con ID @id no encontrada.', ['@id' => $conversationId]),
        ];
      }

      // Cargar todos los handoffs de esta conversacion ordenados por handoff_at.
      $handoffStorage = $this->entityTypeManager->getStorage('agent_handoff');
      $handoffIds = $handoffStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('conversation_id', $conversationId)
        ->sort('handoff_at', 'ASC')
        ->execute();

      $handoffs = [];
      if (!empty($handoffIds)) {
        $handoffEntities = $handoffStorage->loadMultiple($handoffIds);
        foreach ($handoffEntities as $handoff) {
          $handoffs[] = [
            'handoff_id' => (int) $handoff->id(),
            'from_agent_id' => (int) ($handoff->get('from_agent_id')->target_id ?? 0),
            'to_agent_id' => (int) ($handoff->get('to_agent_id')->target_id ?? 0),
            'reason' => $handoff->get('reason')->value ?? '',
            'confidence' => (float) ($handoff->get('confidence')->value ?? 0),
            'handoff_at' => $handoff->get('handoff_at')->value ?? '',
          ];
        }
      }

      $agentChainJson = $conversation->get('agent_chain')->value ?? '[]';

      return [
        'success' => TRUE,
        'conversation_id' => $conversationId,
        'user_id' => (int) ($conversation->get('user_id')->target_id ?? 0),
        'current_agent_id' => (int) ($conversation->get('current_agent_id')->target_id ?? 0),
        'status' => $conversation->get('status')->value ?? '',
        'agent_chain' => json_decode($agentChainJson, TRUE) ?: [],
        'handoff_count' => (int) ($conversation->get('handoff_count')->value ?? 0),
        'total_tokens' => (int) ($conversation->get('total_tokens')->value ?? 0),
        'satisfaction_score' => $conversation->get('satisfaction_score')->value !== NULL
          ? (int) $conversation->get('satisfaction_score')->value
          : NULL,
        'started_at' => $conversation->get('started_at')->value ?? '',
        'completed_at' => $conversation->get('completed_at')->value ?? '',
        'handoffs' => $handoffs,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar traza de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error interno al generar la traza.'),
      ];
    }
  }

  /**
   * Obtiene metricas agregadas de todas las conversaciones.
   *
   * Calcula: total_conversations, avg_handoffs, avg_satisfaction,
   * completion_rate y avg_tokens.
   *
   * @return array
   *   Array con metricas agregadas de orquestacion.
   */
  public function getMetrics(): array {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');

      // Total de conversaciones.
      $totalIds = $conversationStorage->getQuery()
        ->accessCheck(TRUE)
        ->execute();

      $totalConversations = count($totalIds);

      if ($totalConversations === 0) {
        return [
          'total_conversations' => 0,
          'avg_handoffs' => 0.0,
          'avg_satisfaction' => 0.0,
          'completion_rate' => 0.0,
          'avg_tokens' => 0.0,
        ];
      }

      // Cargar todas las conversaciones para calcular agregados.
      $conversations = $conversationStorage->loadMultiple($totalIds);

      $totalHandoffs = 0;
      $totalSatisfaction = 0;
      $satisfactionCount = 0;
      $completedCount = 0;
      $totalTokens = 0;

      foreach ($conversations as $conversation) {
        $totalHandoffs += (int) ($conversation->get('handoff_count')->value ?? 0);
        $totalTokens += (int) ($conversation->get('total_tokens')->value ?? 0);

        $satisfaction = $conversation->get('satisfaction_score')->value;
        if ($satisfaction !== NULL && $satisfaction !== '') {
          $totalSatisfaction += (int) $satisfaction;
          $satisfactionCount++;
        }

        $status = $conversation->get('status')->value;
        if ($status === 'completed') {
          $completedCount++;
        }
      }

      return [
        'total_conversations' => $totalConversations,
        'avg_handoffs' => round($totalHandoffs / $totalConversations, 2),
        'avg_satisfaction' => $satisfactionCount > 0
          ? round($totalSatisfaction / $satisfactionCount, 2)
          : 0.0,
        'completion_rate' => round(($completedCount / $totalConversations) * 100, 2),
        'avg_tokens' => round($totalTokens / $totalConversations, 2),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener metricas de orquestacion: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'total_conversations' => 0,
        'avg_handoffs' => 0.0,
        'avg_satisfaction' => 0.0,
        'completion_rate' => 0.0,
        'avg_tokens' => 0.0,
      ];
    }
  }

  /**
   * Genera nodos y aristas para visualizacion de cadena de agentes.
   *
   * Produce una estructura de grafo con nodos (agentes) y edges
   * (handoffs) para renderizar visualizaciones de la cadena.
   *
   * @param int $conversationId
   *   ID de la conversacion a visualizar.
   *
   * @return array
   *   Array con ['nodes' => array, 'edges' => array].
   */
  public function getChainVisualization(int $conversationId): array {
    try {
      // Cargar la conversacion para obtener agent_chain.
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        return ['nodes' => [], 'edges' => []];
      }

      $chainJson = $conversation->get('agent_chain')->value ?? '[]';
      $chain = json_decode($chainJson, TRUE) ?: [];

      // Cargar los handoffs para obtener datos de las aristas.
      $handoffStorage = $this->entityTypeManager->getStorage('agent_handoff');
      $handoffIds = $handoffStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('conversation_id', $conversationId)
        ->sort('handoff_at', 'ASC')
        ->execute();

      // Construir nodos (agentes unicos).
      $agentStorage = $this->entityTypeManager->getStorage('autonomous_agent');
      $uniqueAgentIds = array_unique($chain);
      $nodes = [];

      foreach ($uniqueAgentIds as $agentId) {
        $agent = $agentStorage->load($agentId);
        $nodes[] = [
          'id' => (int) $agentId,
          'label' => $agent ? ($agent->get('name')->value ?? (string) $agentId) : (string) new TranslatableMarkup('Agente @id', ['@id' => $agentId]),
          'type' => 'agent',
        ];
      }

      // Construir aristas (handoffs).
      $edges = [];
      if (!empty($handoffIds)) {
        $handoffEntities = $handoffStorage->loadMultiple($handoffIds);
        foreach ($handoffEntities as $handoff) {
          $edges[] = [
            'from' => (int) ($handoff->get('from_agent_id')->target_id ?? 0),
            'to' => (int) ($handoff->get('to_agent_id')->target_id ?? 0),
            'label' => $handoff->get('reason')->value ?? '',
            'confidence' => (float) ($handoff->get('confidence')->value ?? 0),
            'timestamp' => $handoff->get('handoff_at')->value ?? '',
          ];
        }
      }

      return [
        'nodes' => $nodes,
        'edges' => $edges,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar visualizacion de cadena para conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return ['nodes' => [], 'edges' => []];
    }
  }

}
