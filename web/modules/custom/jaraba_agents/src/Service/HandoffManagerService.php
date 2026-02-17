<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de handoffs entre agentes autonomos.
 *
 * ESTRUCTURA:
 *   Gestiona las transferencias (handoffs) entre agentes dentro
 *   de una conversacion multi-agente. Crea registros de handoff
 *   y actualiza la conversacion padre.
 *
 * LOGICA:
 *   - handoff(): Crea entidad AgentHandoff, actualiza AgentConversation
 *     (current_agent_id, handoff_count++, agent_chain append).
 *   - getChain(): Devuelve la cadena de agentes de una conversacion.
 *   - resumeConversation(): Restablece el estado de la conversacion a 'active'.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class HandoffManagerService {

  /**
   * Construye el servicio de gestion de handoffs.
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
   * Realiza un handoff de una conversacion entre dos agentes.
   *
   * Crea una entidad AgentHandoff y actualiza la conversacion padre:
   * cambia current_agent_id, incrementa handoff_count, y agrega el
   * nuevo agente a la cadena agent_chain.
   *
   * @param int $conversationId
   *   ID de la conversacion en la que se produce el handoff.
   * @param int $fromAgentId
   *   ID del agente que transfiere la conversacion.
   * @param int $toAgentId
   *   ID del agente que recibe la conversacion.
   * @param string $reason
   *   Justificacion del handoff.
   * @param array $context
   *   Datos de contexto adicionales a transferir.
   *
   * @return array
   *   Array con ['success' => bool, 'handoff_id' => int].
   */
  public function handoff(int $conversationId, int $fromAgentId, int $toAgentId, string $reason, array $context = []): array {
    try {
      // Cargar la conversacion para actualizar.
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->error('Conversacion no encontrada para handoff: @id', ['@id' => $conversationId]);
        return [
          'success' => FALSE,
          'handoff_id' => 0,
          'error' => (string) new TranslatableMarkup('Conversacion con ID @id no encontrada.', ['@id' => $conversationId]),
        ];
      }

      // Crear la entidad AgentHandoff.
      $handoffStorage = $this->entityTypeManager->getStorage('agent_handoff');
      $handoff = $handoffStorage->create([
        'conversation_id' => $conversationId,
        'from_agent_id' => $fromAgentId,
        'to_agent_id' => $toAgentId,
        'reason' => $reason,
        'context_transferred' => !empty($context) ? json_encode($context, JSON_THROW_ON_ERROR) : NULL,
        'confidence' => $context['confidence'] ?? 0.0,
        'handoff_at' => date('Y-m-d\TH:i:s'),
      ]);
      $handoff->save();

      $handoffId = (int) $handoff->id();

      // Actualizar la conversacion: current_agent_id.
      $conversation->set('current_agent_id', $toAgentId);

      // Incrementar handoff_count.
      $currentCount = (int) ($conversation->get('handoff_count')->value ?? 0);
      $conversation->set('handoff_count', $currentCount + 1);

      // Append al agent_chain.
      $chainJson = $conversation->get('agent_chain')->value ?? '[]';
      $chain = json_decode($chainJson, TRUE) ?: [];
      $chain[] = $toAgentId;
      $conversation->set('agent_chain', json_encode($chain, JSON_THROW_ON_ERROR));

      $conversation->save();

      $this->logger->info('Handoff @handoff: conversacion @conv transferida de agente @from a @to.', [
        '@handoff' => $handoffId,
        '@conv' => $conversationId,
        '@from' => $fromAgentId,
        '@to' => $toAgentId,
      ]);

      return [
        'success' => TRUE,
        'handoff_id' => $handoffId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al realizar handoff en conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'handoff_id' => 0,
        'error' => (string) new TranslatableMarkup('Error interno al realizar el handoff.'),
      ];
    }
  }

  /**
   * Obtiene la cadena de agentes de una conversacion.
   *
   * Carga la conversacion y decodifica el campo agent_chain (JSON).
   *
   * @param int $conversationId
   *   ID de la conversacion a consultar.
   *
   * @return array
   *   Array de IDs de agentes en orden de participacion.
   */
  public function getChain(int $conversationId): array {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->warning('Conversacion no encontrada para getChain: @id', ['@id' => $conversationId]);
        return [];
      }

      $chainJson = $conversation->get('agent_chain')->value ?? '[]';
      return json_decode($chainJson, TRUE) ?: [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener cadena de agentes de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Reanuda una conversacion estableciendo su estado a 'active'.
   *
   * @param int $conversationId
   *   ID de la conversacion a reanudar.
   *
   * @return array
   *   Array con ['success' => bool] y datos de la operacion.
   */
  public function resumeConversation(int $conversationId): array {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Conversacion con ID @id no encontrada.', ['@id' => $conversationId]),
        ];
      }

      $previousStatus = $conversation->get('status')->value;
      $conversation->set('status', 'active');
      $conversation->save();

      $this->logger->info('Conversacion @id reanudada (estado anterior: @prev).', [
        '@id' => $conversationId,
        '@prev' => $previousStatus,
      ]);

      return [
        'success' => TRUE,
        'conversation_id' => $conversationId,
        'previous_status' => $previousStatus,
        'status' => 'active',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al reanudar conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error interno al reanudar la conversacion.'),
      ];
    }
  }

}
