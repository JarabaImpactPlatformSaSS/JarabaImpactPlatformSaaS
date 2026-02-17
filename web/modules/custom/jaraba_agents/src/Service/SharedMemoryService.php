<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de memoria compartida entre agentes en una conversacion.
 *
 * ESTRUCTURA:
 *   Gestiona el almacenamiento y recuperacion de contexto compartido
 *   entre agentes dentro de una conversacion multi-agente, usando
 *   el campo shared_context (JSON) de AgentConversation.
 *
 * LOGICA:
 *   - store(): Almacena un par clave-valor en el contexto compartido.
 *   - retrieve(): Recupera un valor por clave del contexto compartido.
 *   - search(): Busca claves que contienen una subcadena.
 *   - getContext(): Devuelve el contexto completo decodificado.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class SharedMemoryService {

  /**
   * Construye el servicio de memoria compartida.
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
   * Almacena un valor en el contexto compartido de una conversacion.
   *
   * Carga la conversacion, decodifica shared_context, agrega o
   * actualiza la clave y guarda de nuevo.
   *
   * @param int $conversationId
   *   ID de la conversacion.
   * @param string $key
   *   Clave bajo la que almacenar el valor.
   * @param mixed $value
   *   Valor a almacenar (serializable a JSON).
   */
  public function store(int $conversationId, string $key, mixed $value): void {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->error('Conversacion no encontrada para store: @id', ['@id' => $conversationId]);
        return;
      }

      $contextJson = $conversation->get('shared_context')->value ?? '{}';
      $context = json_decode($contextJson, TRUE) ?: [];
      $context[$key] = $value;

      $conversation->set('shared_context', json_encode($context, JSON_THROW_ON_ERROR));
      $conversation->save();

      $this->logger->debug('Contexto almacenado en conversacion @id: clave @key.', [
        '@id' => $conversationId,
        '@key' => $key,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al almacenar en memoria compartida de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Recupera un valor del contexto compartido de una conversacion.
   *
   * @param int $conversationId
   *   ID de la conversacion.
   * @param string $key
   *   Clave del valor a recuperar.
   *
   * @return mixed
   *   Valor almacenado o NULL si no existe.
   */
  public function retrieve(int $conversationId, string $key): mixed {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->warning('Conversacion no encontrada para retrieve: @id', ['@id' => $conversationId]);
        return NULL;
      }

      $contextJson = $conversation->get('shared_context')->value ?? '{}';
      $context = json_decode($contextJson, TRUE) ?: [];

      return $context[$key] ?? NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al recuperar de memoria compartida de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Busca claves en el contexto compartido que contienen una subcadena.
   *
   * @param int $conversationId
   *   ID de la conversacion.
   * @param string $query
   *   Subcadena a buscar en las claves del contexto.
   *
   * @return array
   *   Array asociativo con las claves y valores que coinciden.
   */
  public function search(int $conversationId, string $query): array {
    try {
      $context = $this->getContext($conversationId);
      $results = [];
      $normalizedQuery = mb_strtolower(trim($query));

      foreach ($context as $key => $value) {
        if (str_contains(mb_strtolower($key), $normalizedQuery)) {
          $results[$key] = $value;
        }
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al buscar en memoria compartida de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Devuelve el contexto completo de una conversacion.
   *
   * @param int $conversationId
   *   ID de la conversacion.
   *
   * @return array
   *   Array asociativo con todo el contexto compartido.
   */
  public function getContext(int $conversationId): array {
    try {
      $conversationStorage = $this->entityTypeManager->getStorage('agent_conversation');
      $conversation = $conversationStorage->load($conversationId);

      if (!$conversation) {
        $this->logger->warning('Conversacion no encontrada para getContext: @id', ['@id' => $conversationId]);
        return [];
      }

      $contextJson = $conversation->get('shared_context')->value ?? '{}';
      return json_decode($contextJson, TRUE) ?: [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener contexto de conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
