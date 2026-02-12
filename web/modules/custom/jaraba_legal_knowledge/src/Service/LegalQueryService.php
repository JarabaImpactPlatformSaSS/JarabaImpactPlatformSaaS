<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de consultas legales con logging y feedback por tenant.
 *
 * Orquesta la consulta al RAG legal, registra cada consulta como
 * una entidad LegalQueryLog para trazabilidad por tenant, y permite
 * al usuario enviar feedback sobre la calidad de las respuestas.
 *
 * ARQUITECTURA:
 * - Delega la logica RAG a LegalRagService.
 * - Registra cada consulta como LegalQueryLog (multi-tenant).
 * - Soporta historial de consultas por tenant.
 * - Feedback: rating (1-5) y comentario opcional.
 */
class LegalQueryService {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalRagService $ragService
   *   Servicio RAG para consultas legales.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto del tenant actual.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Proxy del usuario actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LegalRagService $ragService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Procesa una consulta legal: RAG + logging.
   *
   * @param string $question
   *   Pregunta del usuario en lenguaje natural.
   * @param array $filters
   *   Filtros opcionales (scope, subject_areas).
   *
   * @return array
   *   Respuesta del RAG con campos adicionales:
   *   - query_log_id: (int) ID del registro de consulta para feedback.
   *   Mas todos los campos retornados por LegalRagService::query().
   */
  public function processQuery(string $question, array $filters = []): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenantId = $tenant ? (int) $tenant->id() : 0;
    $userId = (int) $this->currentUser->id();
    $startTime = microtime(TRUE);

    try {
      // Ejecutar consulta RAG.
      $response = $this->ragService->query($question, $filters);

      $responseTimeMs = (int) round((microtime(TRUE) - $startTime) * 1000);

      // Registrar consulta en LegalQueryLog.
      $queryLogId = $this->logQuery($tenantId, $userId, $question, $response, $responseTimeMs);

      $response['query_log_id'] = $queryLogId;

      $this->logger->info('Consulta legal procesada para tenant @tenant (usuario @user, @ms ms).', [
        '@tenant' => $tenantId,
        '@user' => $userId,
        '@ms' => $responseTimeMs,
      ]);

      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando consulta legal: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'answer' => 'Se produjo un error al procesar su consulta.',
        'citations' => [],
        'disclaimer' => '',
        'confidence' => 0.0,
        'chunks_used' => 0,
        'model_used' => '',
        'tokens_input' => 0,
        'tokens_output' => 0,
        'query_log_id' => NULL,
      ];
    }
  }

  /**
   * Obtiene el historial de consultas de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de registros a retornar.
   *
   * @return array
   *   Array de consultas recientes con: id, question, confidence,
   *   response_time_ms, created, rating.
   */
  public function getQueryHistory(int $tenantId, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_query_log');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];

      foreach ($entities as $entity) {
        $results[] = [
          'id' => (int) $entity->id(),
          'question' => $entity->get('question')->value,
          'confidence' => (float) $entity->get('confidence')->value,
          'response_time_ms' => (int) $entity->get('response_time_ms')->value,
          'chunks_used' => (int) $entity->get('chunks_used')->value,
          'created' => (int) $entity->get('created')->value,
          'rating' => $entity->get('rating')->value ? (int) $entity->get('rating')->value : NULL,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo historial de consultas para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Registra feedback del usuario sobre una respuesta.
   *
   * @param int $queryLogId
   *   ID de la entidad LegalQueryLog.
   * @param int $rating
   *   Valoracion del 1 al 5.
   * @param string|null $comment
   *   Comentario opcional del usuario.
   *
   * @return bool
   *   TRUE si el feedback se registro correctamente.
   */
  public function submitFeedback(int $queryLogId, int $rating, ?string $comment = NULL): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_query_log');
      $entity = $storage->load($queryLogId);

      if (!$entity) {
        $this->logger->warning('Feedback fallido: consulta @id no encontrada.', [
          '@id' => $queryLogId,
        ]);
        return FALSE;
      }

      $entity->set('rating', max(1, min(5, $rating)));
      if ($comment !== NULL) {
        $entity->set('feedback_comment', $comment);
      }
      $entity->save();

      $this->logger->info('Feedback registrado para consulta @id: rating @rating.', [
        '@id' => $queryLogId,
        '@rating' => $rating,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando feedback para consulta @id: @error', [
        '@id' => $queryLogId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Registra una consulta en la entidad LegalQueryLog.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $userId
   *   ID del usuario que realizo la consulta.
   * @param string $question
   *   Pregunta original.
   * @param array $response
   *   Respuesta del RAG.
   * @param int $responseTimeMs
   *   Tiempo de respuesta en milisegundos.
   *
   * @return int|null
   *   ID de la entidad creada o NULL si fallo.
   */
  protected function logQuery(int $tenantId, int $userId, string $question, array $response, int $responseTimeMs): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_query_log');

      $entity = $storage->create([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'question' => $question,
        'answer' => $response['answer'] ?? '',
        'confidence' => $response['confidence'] ?? 0.0,
        'chunks_used' => $response['chunks_used'] ?? 0,
        'model_used' => $response['model_used'] ?? '',
        'tokens_input' => $response['tokens_input'] ?? 0,
        'tokens_output' => $response['tokens_output'] ?? 0,
        'response_time_ms' => $responseTimeMs,
      ]);
      $entity->save();

      return (int) $entity->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando consulta en log: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
