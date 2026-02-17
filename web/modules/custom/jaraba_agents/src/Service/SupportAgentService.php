<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Agente autonomo de soporte con base de conocimiento RAG.
 *
 * ESTRUCTURA:
 *   Chatbot autonomo que atiende consultas de usuarios buscando
 *   primero en la base de conocimiento (Knowledge Base) mediante
 *   RAG (Retrieval-Augmented Generation) y generando respuestas
 *   contextualizadas. Escala a soporte humano cuando la confianza
 *   es insuficiente.
 *
 * LOGICA:
 *   El flujo principal es: query -> searchKb() -> generar respuesta.
 *   Si la confianza de la respuesta es inferior al umbral configurado,
 *   se ofrece escalado a soporte humano via escalate().
 *   Las fuentes de la KB se incluyen en la respuesta para trazabilidad.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class SupportAgentService {

  /**
   * Umbral minimo de confianza para considerar una respuesta valida.
   */
  protected const MIN_CONFIDENCE_THRESHOLD = 0.6;

  /**
   * Numero maximo de articulos de KB a consultar por query.
   */
  protected const MAX_KB_RESULTS = 5;

  /**
   * Construye el servicio del agente de soporte.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   * @param object $tenantContext
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly object $tenantContext,
  ) {}

  /**
   * Procesa una consulta del usuario y genera una respuesta.
   *
   * Busca primero en la base de conocimiento del tenant y luego
   * genera una respuesta contextualizada basada en los resultados.
   * Si la confianza es baja, sugiere escalado a soporte humano.
   *
   * @param string $query
   *   Texto de la consulta del usuario.
   * @param int $tenantId
   *   ID del grupo/tenant (AUDIT-CONS-005: entity_reference a group).
   *
   * @return array
   *   Array con claves:
   *   - 'response': string con la respuesta generada.
   *   - 'sources': array de fuentes de la KB utilizadas.
   *   - 'confidence': float entre 0.0 y 1.0.
   *   - 'suggest_escalation': bool si se recomienda escalado.
   */
  public function handleQuery(string $query, int $tenantId): array {
    try {
      if (empty(trim($query))) {
        return [
          'response' => (string) new TranslatableMarkup('Por favor, escriba su consulta para que pueda ayudarle.'),
          'sources' => [],
          'confidence' => 0.0,
          'suggest_escalation' => FALSE,
        ];
      }

      $this->logger->info('Procesando consulta de soporte para tenant @tenant: @query', [
        '@tenant' => $tenantId,
        '@query' => mb_substr($query, 0, 100),
      ]);

      // Buscar en la base de conocimiento del tenant.
      $kbResults = $this->searchKb($query);

      // Calcular confianza basada en los resultados de KB.
      $confidence = $this->calculateConfidence($kbResults, $query);

      // Construir fuentes para trazabilidad.
      $sources = [];
      foreach ($kbResults as $result) {
        $sources[] = [
          'id' => (int) $result->id(),
          'title' => $result->label(),
          'type' => $result->bundle(),
          'relevance' => $this->calculateRelevance($result, $query),
        ];
      }

      // Generar respuesta basada en los resultados de KB.
      $response = $this->generateResponse($query, $kbResults, $confidence);

      // Determinar si se sugiere escalado.
      $suggestEscalation = $confidence < self::MIN_CONFIDENCE_THRESHOLD;

      if ($suggestEscalation) {
        $response .= ' ' . (string) new TranslatableMarkup(
          'Si necesita asistencia adicional, puedo transferirle a un agente humano.'
        );
      }

      $this->logger->info('Respuesta generada para tenant @tenant con confianza @conf (@sources fuentes).', [
        '@tenant' => $tenantId,
        '@conf' => round($confidence, 2),
        '@sources' => count($sources),
      ]);

      return [
        'response' => $response,
        'sources' => $sources,
        'confidence' => round($confidence, 2),
        'suggest_escalation' => $suggestEscalation,
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $tenantId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al procesar consulta de soporte: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'response' => (string) new TranslatableMarkup('Lo siento, ha ocurrido un error al procesar su consulta. Por favor, intente de nuevo.'),
        'sources' => [],
        'confidence' => 0.0,
        'suggest_escalation' => TRUE,
      ];
    }
  }

  /**
   * Busca en la base de conocimiento articulos relevantes.
   *
   * Realiza una busqueda por texto en los contenidos de tipo KB
   * (knowledge_base_article, faq, documentation) filtrados por
   * el tenant actual.
   *
   * @param string $query
   *   Texto de busqueda.
   *
   * @return array
   *   Array de entidades de contenido relevantes.
   */
  public function searchKb(string $query): array {
    try {
      if (empty(trim($query))) {
        return [];
      }

      $nodeStorage = $this->entityTypeManager->getStorage('node');

      // Buscar en tipos de contenido de base de conocimiento.
      $kbTypes = ['knowledge_base_article', 'faq', 'documentation'];

      $allResults = [];

      foreach ($kbTypes as $contentType) {
        try {
          $queryBuilder = $nodeStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('type', $contentType)
            ->condition('status', 1)
            ->condition('title', '%' . $query . '%', 'LIKE')
            ->sort('created', 'DESC')
            ->range(0, self::MAX_KB_RESULTS);

          // AUDIT-CONS-005: Filtrar por tenant_id si esta disponible.
          $tenantId = $this->getCurrentTenantId();
          if ($tenantId !== NULL) {
            $queryBuilder->condition('tenant_id', $tenantId);
          }

          $ids = $queryBuilder->execute();
          if (!empty($ids)) {
            $entities = $nodeStorage->loadMultiple($ids);
            $allResults = array_merge($allResults, $entities);
          }
        }
        catch (\Exception $e) {
          // Tipo de contenido puede no existir, continuar con el siguiente.
          $this->logger->notice('Tipo de contenido KB "@type" no disponible: @message', [
            '@type' => $contentType,
            '@message' => $e->getMessage(),
          ]);
        }
      }

      // Limitar al maximo de resultados.
      return array_slice($allResults, 0, self::MAX_KB_RESULTS);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al buscar en base de conocimiento: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Escala una conversacion a soporte humano.
   *
   * Crea un registro de escalado con el motivo y la conversacion
   * asociada para que un agente humano pueda continuar.
   *
   * @param int $conversationId
   *   ID de la conversacion a escalar.
   * @param string $reason
   *   Motivo del escalado.
   *
   * @return array
   *   Array con ['success' => true] o error.
   */
  public function escalate(int $conversationId, string $reason): array {
    try {
      if (empty(trim($reason))) {
        $reason = (string) new TranslatableMarkup('Confianza insuficiente en la respuesta automatica.');
      }

      $nodeStorage = $this->entityTypeManager->getStorage('node');

      // Crear registro de escalado.
      $escalation = $nodeStorage->create([
        'type' => 'support_escalation',
        'title' => (string) new TranslatableMarkup('Escalado de conversacion #@id', ['@id' => $conversationId]),
        'field_conversation_id' => $conversationId,
        'field_reason' => $reason,
        'field_status' => 'pending',
        'field_escalated_at' => date('Y-m-d\TH:i:s'),
        'status' => 1,
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $this->getCurrentTenantId(),
      ]);
      $escalation->save();

      $this->logger->info('Conversacion @id escalada a soporte humano. Motivo: @reason', [
        '@id' => $conversationId,
        '@reason' => mb_substr($reason, 0, 200),
      ]);

      return [
        'success' => TRUE,
        'escalation_id' => (int) $escalation->id(),
        'message' => (string) new TranslatableMarkup('Su consulta ha sido transferida a un agente humano. Le atenderemos lo antes posible.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al escalar conversacion @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al transferir la consulta a soporte humano.'),
      ];
    }
  }

  /**
   * Devuelve la lista de capacidades del agente de soporte.
   *
   * @return array
   *   Lista de identificadores de acciones que este agente puede realizar.
   */
  public function getCapabilities(): array {
    return [
      'handle_user_query',
      'search_knowledge_base',
      'generate_contextual_response',
      'escalate_to_human_support',
      'view_conversation_history',
      'rate_response_quality',
    ];
  }

  /**
   * Genera una respuesta contextualizada basada en resultados de KB.
   *
   * @param string $query
   *   Consulta original del usuario.
   * @param array $kbResults
   *   Resultados de la busqueda en KB.
   * @param float $confidence
   *   Nivel de confianza calculado.
   *
   * @return string
   *   Respuesta generada para el usuario.
   */
  protected function generateResponse(string $query, array $kbResults, float $confidence): string {
    if (empty($kbResults)) {
      return (string) new TranslatableMarkup(
        'No he encontrado informacion especifica sobre su consulta en nuestra base de conocimiento. ¿Podria reformular su pregunta o proporcionar mas detalles?'
      );
    }

    if ($confidence >= 0.8) {
      $firstResult = reset($kbResults);
      $title = $firstResult->label();
      return (string) new TranslatableMarkup(
        'Segun nuestra base de conocimiento, he encontrado informacion relevante en "@title". Consulte este recurso para obtener detalles completos sobre su consulta.',
        ['@title' => $title],
      );
    }

    $resultCount = count($kbResults);
    return (string) new TranslatableMarkup(
      'He encontrado @count recursos que podrian estar relacionados con su consulta. Le recomiendo revisar los resultados adjuntos para encontrar la informacion que necesita.',
      ['@count' => $resultCount],
    );
  }

  /**
   * Calcula la confianza de la respuesta basandose en resultados de KB.
   *
   * @param array $kbResults
   *   Resultados de la busqueda en KB.
   * @param string $query
   *   Consulta original del usuario.
   *
   * @return float
   *   Nivel de confianza entre 0.0 y 1.0.
   */
  protected function calculateConfidence(array $kbResults, string $query): float {
    if (empty($kbResults)) {
      return 0.0;
    }

    // Confianza base segun cantidad de resultados.
    $baseConfidence = min(0.5, count($kbResults) * 0.1);

    // Bonus por coincidencia exacta en titulos.
    $queryLower = mb_strtolower($query);
    foreach ($kbResults as $result) {
      $titleLower = mb_strtolower($result->label());
      if (str_contains($titleLower, $queryLower)) {
        $baseConfidence += 0.3;
        break;
      }
    }

    // Bonus por tener multiples resultados relevantes.
    if (count($kbResults) >= 3) {
      $baseConfidence += 0.2;
    }

    return min(1.0, $baseConfidence);
  }

  /**
   * Calcula la relevancia de un resultado de KB respecto a la query.
   *
   * @param object $result
   *   Entidad de contenido de la KB.
   * @param string $query
   *   Consulta del usuario.
   *
   * @return float
   *   Puntuacion de relevancia entre 0.0 y 1.0.
   */
  protected function calculateRelevance(object $result, string $query): float {
    $relevance = 0.5;

    $queryLower = mb_strtolower($query);
    $titleLower = mb_strtolower($result->label());

    // Coincidencia exacta en titulo.
    if (str_contains($titleLower, $queryLower)) {
      $relevance += 0.4;
    }
    // Coincidencia parcial (alguna palabra del query).
    else {
      $queryWords = explode(' ', $queryLower);
      $matchedWords = 0;
      foreach ($queryWords as $word) {
        if (mb_strlen($word) > 2 && str_contains($titleLower, $word)) {
          $matchedWords++;
        }
      }
      if (!empty($queryWords)) {
        $relevance += ($matchedWords / count($queryWords)) * 0.3;
      }
    }

    return min(1.0, round($relevance, 2));
  }

  /**
   * Obtiene el ID del tenant actual desde el contexto.
   *
   * AUDIT-CONS-005: tenant_id como entity_reference a group.
   *
   * @return int|null
   *   ID del tenant actual o NULL si no esta disponible.
   */
  protected function getCurrentTenantId(): ?int {
    try {
      if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
        return $this->tenantContext->getCurrentTenantId();
      }
    }
    catch (\Exception $e) {
      $this->logger->notice('Contexto de tenant no disponible: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

}
