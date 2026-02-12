<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Entity\CollaborationSession;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Colaboración entre Agentes IA (G108-2).
 *
 * PROPÓSITO:
 * Gestiona sesiones de colaboración multi-agente, permitiendo que varios
 * agentes IA trabajen juntos para resolver tareas complejas mediante
 * intercambio de mensajes, handoffs y consolidación de resultados.
 *
 * FLUJO TÍPICO:
 * 1. createSession() - Un agente iniciador crea la sesión
 * 2. addMessage() - Los agentes intercambian mensajes
 * 3. handoff() - Un agente transfiere el contexto a otro
 * 4. completeSession() / failSession() - Se cierra la sesión
 *
 * ROLES DE MENSAJE:
 * - request: Solicitud de un agente a otro
 * - response: Respuesta a una solicitud
 * - handoff: Transferencia de contexto entre agentes
 * - feedback: Retroalimentación sobre el resultado parcial
 */
class AgentCollaborationService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una nueva sesión de colaboración entre agentes.
   *
   * @param string $initiatorAgent
   *   ID del agente que inicia la colaboración.
   * @param array $participantAgents
   *   Array de IDs de agentes participantes.
   * @param string $taskDescription
   *   Descripción de la tarea a resolver.
   * @param int|null $tenantId
   *   ID del tenant asociado (opcional).
   *
   * @return \Drupal\jaraba_ai_agents\Entity\CollaborationSession
   *   La sesión de colaboración creada.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Si ocurre un error al guardar la entidad.
   */
  public function createSession(
    string $initiatorAgent,
    array $participantAgents,
    string $taskDescription,
    ?int $tenantId = NULL,
  ): CollaborationSession {
    $storage = $this->entityTypeManager->getStorage('collaboration_session');

    $allAgents = array_unique(array_merge([$initiatorAgent], $participantAgents));
    $sessionName = sprintf(
      'Colaboración: %s + %d agentes - %s',
      $initiatorAgent,
      count($participantAgents),
      mb_substr($taskDescription, 0, 80)
    );

    $values = [
      'name' => $sessionName,
      'initiator_agent' => $initiatorAgent,
      'participant_agents' => json_encode(array_values($allAgents)),
      'task_description' => $taskDescription,
      'status' => CollaborationSession::STATUS_ACTIVE,
      'messages' => json_encode([]),
      'token_usage' => 0,
    ];

    if ($tenantId !== NULL) {
      $values['tenant_id'] = $tenantId;
    }

    /** @var \Drupal\jaraba_ai_agents\Entity\CollaborationSession $session */
    $session = $storage->create($values);
    $session->save();

    $this->logger->info('Sesión de colaboración @id creada: @initiator con @participants para tarea: @task', [
      '@id' => $session->id(),
      '@initiator' => $initiatorAgent,
      '@participants' => implode(', ', $participantAgents),
      '@task' => mb_substr($taskDescription, 0, 100),
    ]);

    return $session;
  }

  /**
   * Añade un mensaje a la sesión de colaboración.
   *
   * @param int $sessionId
   *   ID de la sesión.
   * @param string $agentId
   *   ID del agente que envía el mensaje.
   * @param string $role
   *   Rol del mensaje: 'request', 'response', 'handoff', 'feedback'.
   * @param string $content
   *   Contenido del mensaje.
   *
   * @throws \InvalidArgumentException
   *   Si el rol no es válido.
   * @throws \RuntimeException
   *   Si la sesión no existe o no está activa.
   */
  public function addMessage(int $sessionId, string $agentId, string $role, string $content): void {
    $validRoles = ['request', 'response', 'handoff', 'feedback'];
    if (!in_array($role, $validRoles, TRUE)) {
      throw new \InvalidArgumentException(
        sprintf('Rol de mensaje inválido: "%s". Roles válidos: %s', $role, implode(', ', $validRoles))
      );
    }

    $session = $this->loadSession($sessionId);

    if (!$session->isActive()) {
      throw new \RuntimeException(
        sprintf('No se puede añadir mensajes a una sesión con estado "%s".', $session->getStatus())
      );
    }

    $messages = $session->getMessages();
    $messages[] = [
      'agent_id' => $agentId,
      'role' => $role,
      'content' => $content,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];

    $session->setMessages($messages);
    $session->save();

    $this->logger->debug('Mensaje añadido a sesión @id por agente @agent (rol: @role).', [
      '@id' => $sessionId,
      '@agent' => $agentId,
      '@role' => $role,
    ]);
  }

  /**
   * Marca una sesión como completada con su resultado.
   *
   * @param int $sessionId
   *   ID de la sesión.
   * @param array $result
   *   Array con el resultado de la colaboración.
   *
   * @throws \RuntimeException
   *   Si la sesión no existe.
   */
  public function completeSession(int $sessionId, array $result): void {
    $session = $this->loadSession($sessionId);

    $session->setStatus(CollaborationSession::STATUS_COMPLETED);
    $session->setResult($result);
    $session->save();

    $this->logger->info('Sesión de colaboración @id completada. Mensajes: @count.', [
      '@id' => $sessionId,
      '@count' => count($session->getMessages()),
    ]);
  }

  /**
   * Marca una sesión como fallida.
   *
   * @param int $sessionId
   *   ID de la sesión.
   * @param string $reason
   *   Motivo del fallo.
   *
   * @throws \RuntimeException
   *   Si la sesión no existe.
   */
  public function failSession(int $sessionId, string $reason): void {
    $session = $this->loadSession($sessionId);

    $session->setStatus(CollaborationSession::STATUS_FAILED);
    $session->setResult([
      'error' => TRUE,
      'reason' => $reason,
      'failed_at' => \Drupal::time()->getRequestTime(),
    ]);
    $session->save();

    $this->logger->warning('Sesión de colaboración @id fallida: @reason', [
      '@id' => $sessionId,
      '@reason' => $reason,
    ]);
  }

  /**
   * Obtiene los mensajes de una sesión.
   *
   * @param int $sessionId
   *   ID de la sesión.
   *
   * @return array
   *   Array de mensajes decodificados.
   *
   * @throws \RuntimeException
   *   Si la sesión no existe.
   */
  public function getSessionMessages(int $sessionId): array {
    $session = $this->loadSession($sessionId);
    return $session->getMessages();
  }

  /**
   * Obtiene las sesiones activas, opcionalmente filtradas por tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar (opcional).
   *
   * @return array
   *   Array de entidades CollaborationSession activas.
   */
  public function getActiveSessions(?int $tenantId = NULL): array {
    $storage = $this->entityTypeManager->getStorage('collaboration_session');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', CollaborationSession::STATUS_ACTIVE);

    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    $query->sort('created', 'DESC');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Registra un handoff (transferencia) entre agentes dentro de una sesión.
   *
   * Un handoff ocurre cuando un agente transfiere el contexto y la
   * responsabilidad de la tarea a otro agente participante.
   *
   * @param int $sessionId
   *   ID de la sesión.
   * @param string $fromAgent
   *   ID del agente que transfiere.
   * @param string $toAgent
   *   ID del agente que recibe.
   * @param string $context
   *   Contexto de la transferencia (motivo, estado actual, instrucciones).
   *
   * @throws \RuntimeException
   *   Si la sesión no existe o no está activa.
   */
  public function handoff(int $sessionId, string $fromAgent, string $toAgent, string $context): void {
    $handoffContent = json_encode([
      'from' => $fromAgent,
      'to' => $toAgent,
      'context' => $context,
    ]);

    $this->addMessage($sessionId, $fromAgent, 'handoff', $handoffContent);

    $this->logger->info('Handoff en sesión @id: @from -> @to.', [
      '@id' => $sessionId,
      '@from' => $fromAgent,
      '@to' => $toAgent,
    ]);
  }

  /**
   * Carga una sesión de colaboración por ID.
   *
   * @param int $sessionId
   *   ID de la sesión.
   *
   * @return \Drupal\jaraba_ai_agents\Entity\CollaborationSession
   *   La sesión cargada.
   *
   * @throws \RuntimeException
   *   Si la sesión no existe.
   */
  protected function loadSession(int $sessionId): CollaborationSession {
    $storage = $this->entityTypeManager->getStorage('collaboration_session');
    $session = $storage->load($sessionId);

    if (!$session instanceof CollaborationSession) {
      throw new \RuntimeException(
        sprintf('Sesión de colaboración con ID %d no encontrada.', $sessionId)
      );
    }

    return $session;
  }

}
