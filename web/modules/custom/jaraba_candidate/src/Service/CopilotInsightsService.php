<?php

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio de análisis de conversaciones de copilot para autoaprendizaje.
 *
 * PROPÓSITO:
 * Proporciona insights a partir de las conversaciones con copilotos:
 * - Detecta intents más frecuentes
 * - Identifica preguntas populares
 * - Encuentra queries sin resolver (gaps de KB)
 * - Genera métricas de efectividad
 *
 * BENEFICIOS:
 * - Permite a admins ver qué preguntan los usuarios
 * - Identifica áreas a mejorar en el KB
 * - Mide la efectividad de los copilotos
 * - Proporciona datos para reentrenamiento
 */
class CopilotInsightsService
{

    use StringTranslationTrait;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Current user.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        AccountProxyInterface $currentUser,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->currentUser = $currentUser;
        $this->logger = $loggerFactory->get('jaraba_candidate');
    }

    /**
     * Trackea un nuevo mensaje de conversación.
     *
     * @param string $conversationId
     *   UUID de la conversación.
     * @param string $role
     *   'user' o 'assistant'.
     * @param string $content
     *   Contenido del mensaje.
     * @param array $metadata
     *   Metadatos adicionales (intent, entities, tokens, etc).
     *
     * @return \Drupal\jaraba_candidate\Entity\CopilotMessage|null
     *   El mensaje creado o null si falla.
     */
    public function trackMessage(string $conversationId, string $role, string $content, array $metadata = []): ?object
    {
        try {
            $messageStorage = $this->entityTypeManager->getStorage('copilot_message');

            // Buscar conversación por UUID
            $conversations = $this->entityTypeManager
                ->getStorage('copilot_conversation')
                ->loadByProperties(['uuid' => $conversationId]);

            $conversation = reset($conversations);
            if (!$conversation) {
                $this->logger->warning('Conversation not found: @id', ['@id' => $conversationId]);
                return NULL;
            }

            // Crear mensaje
            $message = $messageStorage->create([
                'conversation_id' => $conversation->id(),
                'role' => $role,
                'content' => $content,
                'intent_detected' => $metadata['intent'] ?? 'unknown',
                'intent_confidence' => $metadata['confidence'] ?? 0,
                'tokens_input' => $metadata['tokens_input'] ?? 0,
                'tokens_output' => $metadata['tokens_output'] ?? 0,
                'latency_ms' => $metadata['latency_ms'] ?? 0,
                'model_used' => $metadata['model'] ?? 'claude-3.5-sonnet',
            ]);

            if (!empty($metadata['entities'])) {
                $message->setEntities($metadata['entities']);
            }
            if (!empty($metadata['knowledge_used'])) {
                $message->setKnowledgeUsed($metadata['knowledge_used']);
            }
            if (!empty($metadata['actions'])) {
                $message->setActions($metadata['actions']);
            }

            $message->save();

            // Actualizar conversación
            $conversation->incrementMessageCount();
            if (!empty($metadata['intent']) && $metadata['intent'] !== 'unknown') {
                $conversation->addTopic($metadata['intent']);
            }
            if (!empty($metadata['tokens_input']) || !empty($metadata['tokens_output'])) {
                $conversation->addTokens(
                    $metadata['tokens_input'] ?? 0,
                    $metadata['tokens_output'] ?? 0
                );
            }
            $conversation->save();

            return $message;
        } catch (\Exception $e) {
            $this->logger->error('Error tracking message: @error', ['@error' => $e->getMessage()]);
            return NULL;
        }
    }

    /**
     * Detecta el intent de un mensaje de usuario.
     *
     * Usa heurísticas basadas en keywords para clasificar el intent.
     * En producción podría usar un modelo ML.
     *
     * @param string $message
     *   El mensaje del usuario.
     *
     * @return array
     *   Array con 'intent' y 'confidence'.
     */
    public function detectIntent(string $message): array
    {
        $message = mb_strtolower($message);

        // Patrones de intent con keywords
        $patterns = [
            'job_search' => ['empleo', 'trabajo', 'oferta', 'vacante', 'busco trabajo', 'ofertas para mí'],
            'cv_help' => ['cv', 'currículum', 'curriculum', 'resumen', 'mejorar cv'],
            'interview_prep' => ['entrevista', 'preparar entrevista', 'preguntas entrevista'],
            'profile_improve' => ['perfil', 'mejorar perfil', 'completar perfil', 'headline'],
            'learning_path' => ['curso', 'formación', 'aprender', 'learning', 'certificación'],
            'application_status' => ['candidatura', 'aplicación', 'estado', 'mis aplicaciones'],
            'cover_letter' => ['carta', 'presentación', 'carta de motivación'],
            'platform_help' => ['cómo funciona', 'ayuda', 'dónde encuentro', 'no entiendo'],
            'emotional_support' => ['rechazado', 'frustrado', 'desmotivado', 'difícil'],
            'recruiter_screening' => ['candidatos', 'filtrar', 'screening', 'talento'],
            'job_posting' => ['publicar oferta', 'crear empleo', 'descripción puesto'],
            'employer_branding' => ['marca empleadora', 'empresa atractiva', 'employer branding'],
            'greeting' => ['hola', 'buenos días', 'buenas tardes', 'qué tal'],
        ];

        $bestIntent = 'unknown';
        $bestScore = 0;

        foreach ($patterns as $intent => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $matches++;
                }
            }
            if ($matches > $bestScore) {
                $bestScore = $matches;
                $bestIntent = $intent;
            }
        }

        // Calcular confianza basada en matches
        $confidence = $bestScore > 0 ? min(0.9, 0.3 + ($bestScore * 0.2)) : 0.1;

        return [
            'intent' => $bestIntent,
            'confidence' => $confidence,
        ];
    }

    /**
     * Extrae entidades de un mensaje.
     *
     * @param string $message
     *   El mensaje a analizar.
     *
     * @return array
     *   Array de entidades: skills, locations, companies, etc.
     */
    public function extractEntities(string $message): array
    {
        $entities = [
            'skills' => [],
            'locations' => [],
            'job_types' => [],
        ];

        // Patrones simples para skills comunes
        $skillPatterns = [
            'python',
            'javascript',
            'react',
            'angular',
            'vue',
            'php',
            'drupal',
            'marketing',
            'seo',
            'ventas',
            'excel',
            'powerbi',
            'sql',
            'gestión',
            'liderazgo',
            'comunicación',
            'inglés',
        ];

        $locationPatterns = [
            'madrid',
            'barcelona',
            'valencia',
            'sevilla',
            'bilbao',
            'málaga',
            'remoto',
            'híbrido',
            'presencial',
        ];

        $jobTypePatterns = [
            'tiempo completo',
            'media jornada',
            'freelance',
            'prácticas',
            'indefinido',
            'temporal',
        ];

        $messageLower = mb_strtolower($message);

        foreach ($skillPatterns as $skill) {
            if (str_contains($messageLower, $skill)) {
                $entities['skills'][] = $skill;
            }
        }

        foreach ($locationPatterns as $location) {
            if (str_contains($messageLower, $location)) {
                $entities['locations'][] = $location;
            }
        }

        foreach ($jobTypePatterns as $type) {
            if (str_contains($messageLower, $type)) {
                $entities['job_types'][] = $type;
            }
        }

        return $entities;
    }

    /**
     * Obtiene los topics más discutidos agregados por período.
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar (null = todos).
     * @param string $period
     *   'day', 'week', 'month'.
     *
     * @return array
     *   Array de topics con conteo.
     */
    public function aggregateTopics(?int $tenantId = NULL, string $period = 'week'): array
    {
        $startTime = match ($period) {
            'day' => strtotime('-1 day'),
            'week' => strtotime('-1 week'),
            'month' => strtotime('-1 month'),
            default => strtotime('-1 week'),
        };

        $query = $this->database->select('copilot_message', 'm');
        $query->join('copilot_conversation', 'c', 'm.conversation_id = c.id');
        $query->fields('m', ['intent_detected']);
        $query->addExpression('COUNT(*)', 'count');
        $query->condition('m.created', $startTime, '>=');
        $query->condition('m.role', 'user');
        $query->groupBy('m.intent_detected');
        $query->orderBy('count', 'DESC');

        if ($tenantId) {
            $query->condition('c.tenant_id', $tenantId);
        }

        $results = $query->execute()->fetchAll();

        return array_map(function ($row) {
            return [
                'intent' => $row->intent_detected,
                'count' => (int) $row->count,
            ];
        }, $results);
    }

    /**
     * Obtiene las preguntas más populares.
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar.
     * @param int $limit
     *   Número máximo de resultados.
     *
     * @return array
     *   Array de preguntas con metadata.
     */
    public function getPopularQuestions(?int $tenantId = NULL, int $limit = 10): array
    {
        // Verificar si la tabla existe
        if (!$this->database->schema()->tableExists('copilot_message')) {
            return [];
        }

        try {
            // Usar Entity Query en lugar de query directa
            $storage = $this->entityTypeManager->getStorage('copilot_message');
            $query = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('role', 'user')
                ->condition('intent_detected', 'greeting', '<>')
                ->sort('created', 'DESC')
                ->range(0, $limit * 3);

            $ids = $query->execute();

            if (empty($ids)) {
                return [];
            }

            $messages = $storage->loadMultiple($ids);

            // Agrupar por similitud básica (primeras 50 chars)
            $grouped = [];
            foreach ($messages as $message) {
                $content = $message->get('content')->value ?? '';
                $intent = $message->get('intent_detected')->value ?? 'unknown';

                $key = substr(mb_strtolower(trim($content)), 0, 50);
                if (empty($key)) {
                    continue;
                }

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'sample' => $content,
                        'intent' => $intent,
                        'count' => 0,
                    ];
                }
                $grouped[$key]['count']++;
            }

            // Ordenar por frecuencia
            usort($grouped, fn($a, $b) => $b['count'] <=> $a['count']);

            return array_slice($grouped, 0, $limit);
        } catch (\Exception $e) {
            $this->logger->warning('Error in getPopularQuestions: @error', ['@error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Identifica queries que no fueron resueltos (gaps de KB).
     *
     * @param int|null $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Array de conversaciones no resueltas.
     */
    public function getUnresolvedQueries(?int $tenantId = NULL): array
    {
        // Verificar si las tablas existen
        if (!$this->database->schema()->tableExists('copilot_conversation')) {
            return [];
        }

        try {
            $storage = $this->entityTypeManager->getStorage('copilot_conversation');
            $query = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('was_resolved', 0)
                ->condition('is_active', 0)
                ->sort('started_at', 'DESC')
                ->range(0, 20);

            if ($tenantId) {
                $query->condition('tenant_id', $tenantId);
            }

            $ids = $query->execute();

            if (empty($ids)) {
                return [];
            }

            $conversations = $storage->loadMultiple($ids);
            $unresolved = [];

            foreach ($conversations as $conversation) {
                $unresolved[] = [
                    'conversation_id' => $conversation->id(),
                    'copilot_type' => $conversation->get('copilot_type')->value ?? 'unknown',
                    'question' => 'Pregunta no disponible',
                    'intent' => 'unknown',
                    'messages' => $conversation->get('message_count')->value ?? 0,
                    'date' => date('Y-m-d H:i', $conversation->get('started_at')->value ?? time()),
                ];
            }

            return $unresolved;
        } catch (\Exception $e) {
            $this->logger->warning('Error in getUnresolvedQueries: @error', ['@error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtiene métricas de efectividad del copilot.
     *
     * @param string $period
     *   'day', 'week', 'month'.
     * @param int|null $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Métricas de efectividad.
     */
    public function getEffectivenessMetrics(string $period = 'week', ?int $tenantId = NULL): array
    {
        $startTime = match ($period) {
            'day' => strtotime('-1 day'),
            'week' => strtotime('-1 week'),
            'month' => strtotime('-1 month'),
            default => strtotime('-1 week'),
        };

        // Total conversaciones
        $totalQuery = $this->database->select('copilot_conversation', 'c');
        $totalQuery->condition('c.started_at', $startTime, '>=');
        if ($tenantId) {
            $totalQuery->condition('c.tenant_id', $tenantId);
        }
        $total = $totalQuery->countQuery()->execute()->fetchField();

        // Resueltas
        $resolvedQuery = clone $totalQuery;
        $resolvedQuery->condition('c.was_resolved', 1);
        $resolved = $resolvedQuery->countQuery()->execute()->fetchField();

        // Con rating
        $ratedQuery = $this->database->select('copilot_conversation', 'c');
        $ratedQuery->condition('c.started_at', $startTime, '>=');
        $ratedQuery->isNotNull('c.satisfaction_rating');
        if ($tenantId) {
            $ratedQuery->condition('c.tenant_id', $tenantId);
        }
        $ratedQuery->addExpression('AVG(c.satisfaction_rating)', 'avg_rating');
        $avgRating = $ratedQuery->execute()->fetchField();

        // Tokens y costos
        $tokenQuery = $this->database->select('copilot_conversation', 'c');
        $tokenQuery->condition('c.started_at', $startTime, '>=');
        if ($tenantId) {
            $tokenQuery->condition('c.tenant_id', $tenantId);
        }
        $tokenQuery->addExpression('SUM(c.total_tokens_input)', 'total_input');
        $tokenQuery->addExpression('SUM(c.total_tokens_output)', 'total_output');
        $tokenQuery->addExpression('SUM(c.estimated_cost)', 'total_cost');
        $tokens = $tokenQuery->execute()->fetchObject();

        return [
            'total_conversations' => (int) $total,
            'resolved_conversations' => (int) $resolved,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            'average_rating' => $avgRating ? round((float) $avgRating, 1) : NULL,
            'total_tokens_input' => (int) ($tokens->total_input ?? 0),
            'total_tokens_output' => (int) ($tokens->total_output ?? 0),
            'total_cost_usd' => round((float) ($tokens->total_cost ?? 0), 4),
            'period' => $period,
        ];
    }

    /**
     * Crea o recupera una conversación existente.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $copilotType
     *   Tipo de copilot.
     * @param int|null $tenantId
     *   ID del tenant.
     *
     * @return object
     *   La conversación.
     */
    public function getOrCreateConversation(int $userId, string $copilotType, ?int $tenantId = NULL): object
    {
        $storage = $this->entityTypeManager->getStorage('copilot_conversation');

        // Buscar conversación activa reciente (últimas 30 min)
        $recentTime = \Drupal::time()->getRequestTime() - 1800;
        $conversations = $storage->loadByProperties([
            'user_id' => $userId,
            'copilot_type' => $copilotType,
            'is_active' => TRUE,
        ]);

        foreach ($conversations as $conv) {
            if ($conv->get('started_at')->value >= $recentTime) {
                return $conv;
            }
        }

        // Crear nueva conversación
        $conversation = $storage->create([
            'user_id' => $userId,
            'copilot_type' => $copilotType,
            'tenant_id' => $tenantId,
        ]);
        $conversation->save();

        return $conversation;
    }

}
