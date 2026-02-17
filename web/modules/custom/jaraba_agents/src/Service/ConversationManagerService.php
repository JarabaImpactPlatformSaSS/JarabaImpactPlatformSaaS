<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion del ciclo de vida de conversaciones multi-agente.
 *
 * ESTRUCTURA:
 *   Gestiona la creacion, mensajeria, finalizacion y valoracion
 *   de conversaciones entre usuarios y agentes autonomos.
 *
 * LOGICA:
 *   - start(): Crea una nueva conversacion con estado 'active'.
 *   - addMessage(): Agrega un mensaje al array 'messages' del shared_context.
 *   - end(): Finaliza la conversacion (status + completed_at).
 *   - rate(): Establece la puntuacion de satisfaccion (1-5).
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class ConversationManagerService {

  /**
   * Construye el servicio de gestion de conversaciones.
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
   * Inicia una nueva conversacion multi-agente.
   *
   * Crea una entidad AgentConversation con estado 'active', asigna
   * el usuario y opcionalmente un agente inicial.
   *
   * @param int $userId
   *   ID del usuario que inicia la conversacion.
   * @param int|null $agentId
   *   ID del agente inicial (opcional).
   *
   * @return array
   *   Array con ['success' => bool, 'conversation_id' => int].
   */
  public function start(int $userId, ?int $agentId = NULL): array {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');

      // Obtener tenant_id del contexto. AUDIT-CONS-005.
      $tenantId = NULL;
      if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
        $tenantId = $this->tenantContext->getCurrentTenantId();
      }

      $initialChain = $agentId ? [$agentId] : [];

      $conversation = $conversationStorage->create([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'current_agent_id' => $agentId,
        'agent_chain' => json_encode($initialChain, JSON_THROW_ON_ERROR),
        'shared_context' => json_encode(['messages' => []], JSON_THROW_ON_ERROR),
        'handoff_count' => 0,
        'status' => 'active',
        'total_tokens' => 0,
        'started_at' => date('Y-m-d\TH:i:s'),
      ]);
      $conversation->save();

      $conversationId = (int) $conversation->id();

      $this->logger->info('Conversacion @id iniciada por usuario @user (agente: @agent).', [
        '@id' => $conversationId,
        '@user' => $userId,
        '@agent' => $agentId ?? 'ninguno',
      ]);

      return [
        'success' => TRUE,
        'conversation_id' => $conversationId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al iniciar conversacion para usuario @user: @message', [
        '@user' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'conversation_id' => 0,
        'error' => (string) new TranslatableMarkup('Error interno al iniciar la conversacion.'),
      ];
    }
  }

  /**
   * Agrega un mensaje a la conversacion.
   *
   * Agrega el mensaje al array 'messages' dentro del shared_context
   * de la conversacion.
   *
   * @param int $conversationId
   *   ID de la conversacion.
   * @param string $role
   *   Rol del emisor: 'user', 'agent', 'system'.
   * @param string $content
   *   Contenido del mensaje.
   */
  public function addMessage(int $conversationId, string $role, string $content): void {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->error('Conversacion no encontrada para addMessage: @id', ['@id' => $conversationId]);
        return;
      }

      $contextJson = $conversation->get('shared_context')->value ?? '{}';
      $context = json_decode($contextJson, TRUE) ?: [];

      if (!isset($context['messages'])) {
        $context['messages'] = [];
      }

      $context['messages'][] = [
        'role' => $role,
        'content' => $content,
        'timestamp' => date('Y-m-d\TH:i:s'),
      ];

      $conversation->set('shared_context', json_encode($context, JSON_THROW_ON_ERROR));
      $conversation->save();

      $this->logger->debug('Mensaje agregado a conversacion @id (role: @role).', [
        '@id' => $conversationId,
        '@role' => $role,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al agregar mensaje a conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Finaliza una conversacion estableciendo estado y fecha de finalizacion.
   *
   * @param int $conversationId
   *   ID de la conversacion a finalizar.
   * @param string $status
   *   Estado final: 'completed', 'escalated', 'timeout'.
   *
   * @return array
   *   Array con ['success' => bool] y datos de la operacion.
   */
  public function end(int $conversationId, string $status = 'completed'): array {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Conversacion con ID @id no encontrada.', ['@id' => $conversationId]),
        ];
      }

      $conversation->set('status', $status);
      $conversation->set('completed_at', date('Y-m-d\TH:i:s'));
      $conversation->save();

      $this->logger->info('Conversacion @id finalizada con estado @status.', [
        '@id' => $conversationId,
        '@status' => $status,
      ]);

      return [
        'success' => TRUE,
        'conversation_id' => $conversationId,
        'status' => $status,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al finalizar conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error interno al finalizar la conversacion.'),
      ];
    }
  }

  /**
   * Establece la puntuacion de satisfaccion de una conversacion.
   *
   * Valida que el score este entre 1 y 5 antes de asignar.
   *
   * @param int $conversationId
   *   ID de la conversacion a valorar.
   * @param int $score
   *   Puntuacion de satisfaccion (1-5).
   */
  public function rate(int $conversationId, int $score): void {
    try {
      // Validar rango de puntuacion.
      if ($score < 1 || $score > 5) {
        $this->logger->warning('Puntuacion invalida @score para conversacion @id. Debe ser 1-5.', [
          '@score' => $score,
          '@id' => $conversationId,
        ]);
        return;
      }

      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->error('Conversacion no encontrada para rate: @id', ['@id' => $conversationId]);
        return;
      }

      $conversation->set('satisfaction_score', $score);
      $conversation->save();

      $this->logger->info('Conversacion @id valorada con puntuacion @score.', [
        '@id' => $conversationId,
        '@score' => $score,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al valorar conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
