<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para logging de queries del Copiloto.
 *
 * Registra las consultas y respuestas del Copiloto para:
 * - Análisis de preguntas frecuentes
 * - Identificación de gaps en contenido
 * - Sistema de feedback para mejora continua
 *
 * Adaptado de AgroConecta CopilotQueryLogger.
 */
class CopilotQueryLoggerService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected Connection $database,
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Verifica si la tabla de logging existe.
     */
    public function isTableReady(): bool
    {
        try {
            return $this->database->schema()->tableExists('copilot_query_log');
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Registra una consulta del Copiloto.
     *
     * @param string $source
     *   Fuente del copiloto (public, emprendimiento, empleabilidad).
     * @param string $query
     *   La pregunta del usuario.
     * @param string $response
     *   La respuesta generada.
     * @param array $context
     *   Contexto adicional (página, avatar, modo, etc.).
     * @param string|null $sessionId
     *   ID de sesión opcional.
     *
     * @return int|null
     *   El ID del log creado, o NULL si el logging está desactivado.
     */
    public function logQuery(
        string $source,
        string $query,
        string $response,
        array $context = [],
        ?string $sessionId = NULL,
    ): ?int {
        // Verificar si tabla existe primero
        if (!$this->isTableReady()) {
            return NULL;
        }

        // Verificar si logging está habilitado (por defecto: TRUE)
        $config = $this->configFactory->get('jaraba_copilot_v2.settings');
        $loggingEnabled = $config->get('log_queries') ?? TRUE;

        if (!$loggingEnabled) {
            return NULL;
        }

        try {
            $id = $this->database->insert('copilot_query_log')
                ->fields([
                    'source' => $source,
                    'query' => substr($query, 0, 65535),
                    'response' => substr($response, 0, 16777215),
                    'context_data' => json_encode($context),
                    'session_id' => $sessionId,
                    'page_url' => isset($context['page']) ? substr($context['page'], 0, 512) : NULL,
                    'mode' => $context['mode'] ?? NULL,
                    'created' => time(),
                ])
                ->execute();

            return (int) $id;
        } catch (\Exception $e) {
            $this->logger->error('Error al registrar query: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Registra feedback del usuario sobre una respuesta.
     *
     * @param int $logId
     *   ID del log a actualizar.
     * @param bool $wasHelpful
     *   TRUE si fue útil, FALSE si no.
     *
     * @return bool
     *   TRUE si se actualizó correctamente.
     */
    public function logFeedback(int $logId, bool $wasHelpful): bool
    {
        if (!$this->isTableReady()) {
            return FALSE;
        }

        try {
            $updated = $this->database->update('copilot_query_log')
                ->fields(['was_helpful' => $wasHelpful ? 1 : 0])
                ->condition('id', $logId)
                ->execute();

            return $updated > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error al registrar feedback: @error', [
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Obtiene estadísticas de queries.
     *
     * AUDIT-SEC-N05: Añadido filtro obligatorio por tenant_id.
     *
     * @param string $source
     *   Fuente del copiloto o 'all'.
     * @param int $days
     *   Número de días a consultar.
     * @param int|null $tenantId
     *   ID del tenant para filtrar. NULL solo permitido para super-admins.
     *
     * @return array
     *   Estadísticas con totales, feedback, etc.
     */
    public function getStats(string $source = 'all', int $days = 30, ?int $tenantId = NULL): array
    {
        if (!$this->isTableReady()) {
            return [
                'total' => 0,
                'helpful' => 0,
                'not_helpful' => 0,
                'no_feedback' => 0,
                'satisfaction_rate' => NULL,
                'days' => $days,
                'source' => $source,
                'table_missing' => TRUE,
            ];
        }

        $since = time() - ($days * 86400);

        try {
            $query = $this->database->select('copilot_query_log', 'cql');
            $query->condition('cql.created', $since, '>=');

            if ($source !== 'all') {
                $query->condition('cql.source', $source);
            }

            if ($tenantId !== NULL) {
                $query->condition('cql.tenant_id', $tenantId);
            }

            // Total de queries
            $totalQuery = clone $query;
            $total = (int) $totalQuery->countQuery()->execute()->fetchField();

            // Queries con feedback positivo
            $helpfulQuery = clone $query;
            $helpful = (int) $helpfulQuery
                ->condition('cql.was_helpful', 1)
                ->countQuery()
                ->execute()
                ->fetchField();

            // Queries con feedback negativo
            $notHelpfulQuery = clone $query;
            $notHelpful = (int) $notHelpfulQuery
                ->condition('cql.was_helpful', 0)
                ->countQuery()
                ->execute()
                ->fetchField();

            // Queries sin feedback
            $noFeedback = $total - $helpful - $notHelpful;

            // Tasa de satisfacción
            $feedbackTotal = $helpful + $notHelpful;
            $satisfactionRate = $feedbackTotal > 0
                ? round(($helpful / $feedbackTotal) * 100, 1)
                : NULL;

            return [
                'total' => $total,
                'helpful' => $helpful,
                'not_helpful' => $notHelpful,
                'no_feedback' => $noFeedback,
                'satisfaction_rate' => $satisfactionRate,
                'days' => $days,
                'source' => $source,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'total' => 0,
            ];
        }
    }

    /**
     * Obtiene las preguntas más frecuentes sin respuesta satisfactoria.
     *
     * AUDIT-SEC-N05: Añadido filtro obligatorio por tenant_id.
     *
     * @param int $limit
     *   Máximo de resultados.
     * @param int|null $tenantId
     *   ID del tenant para filtrar. NULL solo permitido para super-admins.
     *
     * @return array
     *   Lista de queries problemáticas.
     */
    public function getProblematicQueries(int $limit = 20, ?int $tenantId = NULL): array
    {
        if (!$this->isTableReady()) {
            return [];
        }

        try {
            $query = $this->database->select('copilot_query_log', 'cql')
                ->fields('cql', ['id', 'query', 'response', 'created', 'source', 'mode'])
                ->condition('cql.was_helpful', 0)
                ->orderBy('cql.created', 'DESC')
                ->range(0, $limit);

            if ($tenantId !== NULL) {
                $query->condition('cql.tenant_id', $tenantId);
            }

            return $query->execute()->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene las queries más recientes.
     *
     * AUDIT-SEC-N05: Añadido filtro obligatorio por tenant_id.
     *
     * @param string $source
     *   Fuente del copiloto o 'all'.
     * @param int $limit
     *   Máximo de resultados.
     * @param int|null $tenantId
     *   ID del tenant para filtrar. NULL solo permitido para super-admins.
     *
     * @return array
     *   Lista de queries recientes.
     */
    public function getRecentQueries(string $source = 'all', int $limit = 50, ?int $tenantId = NULL): array
    {
        if (!$this->isTableReady()) {
            return [];
        }

        try {
            $query = $this->database->select('copilot_query_log', 'cql')
                ->fields('cql', ['id', 'query', 'response', 'was_helpful', 'created', 'source', 'mode', 'page_url'])
                ->orderBy('cql.created', 'DESC')
                ->range(0, $limit);

            if ($source !== 'all') {
                $query->condition('cql.source', $source);
            }

            if ($tenantId !== NULL) {
                $query->condition('cql.tenant_id', $tenantId);
            }

            return $query->execute()->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene preguntas frecuentes agrupadas.
     *
     * AUDIT-SEC-N05: Añadido filtro obligatorio por tenant_id.
     *
     * @param int $days
     *   Días a considerar.
     * @param int $limit
     *   Máximo de grupos.
     * @param string $source
     *   Fuente del copiloto (all, public, empleabilidad, emprendimiento, comercio).
     * @param int|null $tenantId
     *   ID del tenant para filtrar. NULL solo permitido para super-admins.
     *
     * @return array
     *   Preguntas agrupadas por similitud.
     */
    public function getFrequentQuestions(int $days = 30, int $limit = 20, string $source = 'all', ?int $tenantId = NULL): array
    {
        if (!$this->isTableReady()) {
            return [];
        }

        $since = time() - ($days * 86400);

        try {
            // Construir condiciones dinámicas
            $sourceCondition = '';
            $tenantCondition = '';
            $params = [
                ':since' => $since,
                ':limit' => $limit,
            ];

            if ($source !== 'all') {
                $sourceCondition = 'AND source = :source';
                $params[':source'] = $source;
            }

            if ($tenantId !== NULL) {
                $tenantCondition = 'AND tenant_id = :tenant_id';
                $params[':tenant_id'] = $tenantId;
            }

            return $this->database->query("
                SELECT
                    LEFT(query, 100) as query_prefix,
                    source,
                    COUNT(*) as count,
                    SUM(CASE WHEN was_helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
                    SUM(CASE WHEN was_helpful = 0 THEN 1 ELSE 0 END) as not_helpful_count
                FROM {copilot_query_log}
                WHERE created >= :since $sourceCondition $tenantCondition
                GROUP BY LEFT(query, 100), source
                HAVING COUNT(*) > 1
                ORDER BY count DESC
                LIMIT :limit
            ", $params)->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene el historial de una sesion con user_id y role.
     *
     * AUDIT-SEC-N05: Añadido filtro obligatorio por tenant_id para evitar
     * acceso cross-tenant a historiales de sesión.
     *
     * @param string $sessionId
     *   ID de la sesion.
     * @param int|null $tenantId
     *   ID del tenant para filtrar. NULL solo permitido para super-admins.
     *
     * @return array
     *   Mensajes de la sesion ordenados cronologicamente.
     */
    public function getSessionHistory(string $sessionId, ?int $tenantId = NULL): array {
        if (!$this->isTableReady()) {
            return [];
        }

        try {
            $fields = ['id', 'source', 'query', 'response', 'mode', 'session_id', 'created'];

            // Check if new columns exist
            if ($this->database->schema()->fieldExists('copilot_query_log', 'user_id')) {
                $fields[] = 'user_id';
            }
            if ($this->database->schema()->fieldExists('copilot_query_log', 'role')) {
                $fields[] = 'role';
            }
            if ($this->database->schema()->fieldExists('copilot_query_log', 'tokens_used')) {
                $fields[] = 'tokens_used';
            }

            $selectQuery = $this->database->select('copilot_query_log', 'q')
                ->fields('q', $fields)
                ->condition('session_id', $sessionId);

            if ($tenantId !== NULL) {
                $selectQuery->condition('q.tenant_id', $tenantId);
            }

            $result = $selectQuery
                ->orderBy('created', 'ASC')
                ->orderBy('id', 'ASC')
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            $messages = [];
            foreach ($result as $row) {
                // User message
                $messages[] = [
                    'role' => $row['role'] ?? 'user',
                    'content' => $row['query'],
                    'timestamp' => (int) $row['created'],
                    'mode' => $row['mode'] ?? NULL,
                ];

                // Assistant response
                if (!empty($row['response'])) {
                    $messages[] = [
                        'role' => 'assistant',
                        'content' => $row['response'],
                        'timestamp' => (int) $row['created'],
                        'mode' => $row['mode'] ?? NULL,
                        'tokens_used' => (int) ($row['tokens_used'] ?? 0),
                    ];
                }
            }

            return $messages;
        }
        catch (\Exception $e) {
            $this->logger->warning('Error getting session history: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return [];
        }
    }

}
