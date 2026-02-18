<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Servicio de Analytics para queries de la Knowledge Base.
 *
 * Este servicio:
 * - Registra todas las interacciones con la KB
 * - Clasifica intención de las queries
 * - Detecta gaps de contenido (preguntas sin respuesta frecuentes)
 * - Genera métricas para el dashboard del productor
 *
 * @see docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md (Sección 3, Paso 8)
 */
class QueryAnalyticsService
{

    /**
     * Clasificaciones de queries.
     */
    public const ANSWERED_FULL = 'ANSWERED_FULL';
    public const ANSWERED_PARTIAL = 'ANSWERED_PARTIAL';
    public const UNANSWERED = 'UNANSWERED';
    public const OUT_OF_SCOPE = 'OUT_OF_SCOPE';
    public const PURCHASE_INTENT = 'PURCHASE_INTENT';
    public const UPSELL_OPPORTUNITY = 'UPSELL_OPPORTUNITY';
    public const ERROR = 'ERROR';

    /**
     * Nombre de la tabla de analytics.
     */
    protected const TABLE_NAME = 'jaraba_rag_query_log';

    /**
     * Constructs a QueryAnalyticsService object.
     */
    public function __construct(
        protected Connection $database,
        protected LoggerChannelFactoryInterface $loggerFactory,
        protected ConfigFactoryInterface $configFactory,
    ) {
    }

    /**
     * Registra una interacción con la Knowledge Base.
     *
     * @param array $data
     *   Datos de la interacción:
     *   - 'query': string - La pregunta del usuario.
     *   - 'tenant_id': int|null - ID del tenant.
     *   - 'classification': string - Tipo de respuesta.
     *   - 'confidence': float - Score de confianza (0-1).
     *   - 'response_time_ms': float - Tiempo de respuesta.
     *   - 'sources_count': int - Número de fuentes usadas.
     *   - 'user_id': int|null - ID del usuario (opcional).
     *   - 'hallucination_count': int - Claims detectados como alucinación (0+).
     *   - 'token_usage': int - Tokens totales consumidos (prompt + completion).
     *   - 'provider_id': string|null - ID del proveedor AI (e.g., 'openai', 'anthropic').
     */
    public function log(array $data): void
    {
        $config = $this->configFactory->get('jaraba_rag.settings');

        if (!$config->get('analytics.enabled')) {
            return;
        }

        try {
            // Generar hash de la query para agrupar similares
            $queryHash = $this->generateQueryHash($data['query'] ?? '');

            // Insertar registro
            $this->database->insert(self::TABLE_NAME)
                ->fields([
                    'query_text' => mb_substr($data['query'] ?? '', 0, 500),
                    'query_hash' => $queryHash,
                    'tenant_id' => $data['tenant_id'] ?? NULL,
                    'user_id' => $data['user_id'] ?? NULL,
                    'classification' => $data['classification'] ?? self::ANSWERED_FULL,
                    'confidence_score' => $data['confidence'] ?? 0,
                    'response_time_ms' => (int) ($data['response_time_ms'] ?? 0),
                    'sources_count' => $data['sources_count'] ?? 0,
                    'hallucination_count' => (int) ($data['hallucination_count'] ?? 0),
                    'token_usage' => (int) ($data['token_usage'] ?? 0),
                    'provider_id' => $data['provider_id'] ?? NULL,
                    'created' => \Drupal::time()->getRequestTime(),
                ])
                ->execute();

            // Verificar si hay un gap (query sin respuesta frecuente)
            if (in_array($data['classification'], [self::UNANSWERED, self::ANSWERED_PARTIAL])) {
                $this->checkForGap($queryHash, $data['tenant_id'] ?? NULL);
            }

        } catch (\Exception $e) {
            $this->loggerFactory->get('jaraba_rag')->error('Error registrando analytics: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Genera hash normalizado de una query para agrupar similares.
     *
     * Normalización:
     * - Minúsculas
     * - Eliminar acentos
     * - Eliminar signos de puntuación
     * - Eliminar stopwords
     * - Ordenar palabras alfabéticamente
     */
    protected function generateQueryHash(string $query): string
    {
        // Normalizar
        $normalized = mb_strtolower($query);
        $normalized = $this->removeAccents($normalized);
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);

        // Eliminar stopwords
        $stopwords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'en', 'con', 'para', 'por', 'que', 'teneis', 'tienen', 'hay'];
        $words = explode(' ', $normalized);
        $words = array_diff($words, $stopwords);
        $words = array_filter($words);

        // Ordenar y unir
        sort($words);
        $normalized = implode(' ', $words);

        // Hash
        return hash('sha256', $normalized);
    }

    /**
     * Elimina acentos de un texto.
     */
    protected function removeAccents(string $text): string
    {
        $accents = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'];
        $noAccents = ['a', 'e', 'i', 'o', 'u', 'n', 'u'];
        return str_replace($accents, $noAccents, $text);
    }

    /**
     * Verifica si hay un gap de contenido que deba alertarse.
     */
    protected function checkForGap(string $queryHash, ?int $tenantId): void
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $threshold = $config->get('analytics.gap_alert_threshold') ?? 5;

        // Contar queries similares sin respuesta en últimas 24h
        $oneDayAgo = \Drupal::time()->getRequestTime() - 86400;

        $count = $this->database->select(self::TABLE_NAME, 'q')
            ->condition('query_hash', $queryHash)
            ->condition('tenant_id', $tenantId)
            ->condition('classification', [self::UNANSWERED, self::ANSWERED_PARTIAL], 'IN')
            ->condition('created', $oneDayAgo, '>=')
            ->countQuery()
            ->execute()
            ->fetchField();

        if ($count >= $threshold) {
            // Disparar alerta de gap
            $this->alertGap($queryHash, $tenantId, $count);
        }
    }

    /**
     * Alerta sobre un gap de contenido detectado.
     */
    protected function alertGap(string $queryHash, ?int $tenantId, int $count): void
    {
        // Obtener ejemplo de query
        $exampleQuery = $this->database->select(self::TABLE_NAME, 'q')
            ->fields('q', ['query_text'])
            ->condition('query_hash', $queryHash)
            ->orderBy('created', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchField();

        $this->loggerFactory->get('jaraba_rag')->warning('Gap de contenido detectado: "@query" (x@count en 24h, tenant: @tenant)', [
            '@query' => $exampleQuery,
            '@count' => $count,
            '@tenant' => $tenantId ?? 'platform',
        ]);

        // AUDIT-TODO-RESOLVED: Notify tenant admin via Drupal mail when gap threshold is hit.
        try {
            $adminEmail = NULL;

            // Resolve tenant admin email.
            if ($tenantId !== NULL) {
                $tenantStorage = \Drupal::entityTypeManager()->getStorage('group');
                $tenant = $tenantStorage->load($tenantId);
                if ($tenant) {
                    // Get the tenant owner/admin.
                    $adminUser = $tenant->getOwner();
                    if ($adminUser && $adminUser->getEmail()) {
                        $adminEmail = $adminUser->getEmail();
                    }
                }
            }

            // Fallback to site mail if no tenant admin found.
            if (empty($adminEmail)) {
                $adminEmail = \Drupal::config('system.site')->get('mail');
            }

            if (!empty($adminEmail)) {
                $mailManager = \Drupal::service('plugin.manager.mail');
                $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

                $params = [
                    'subject' => sprintf(
                        'Gap de contenido detectado en Knowledge Base (x%d en 24h)',
                        $count
                    ),
                    'body' => sprintf(
                        "Se ha detectado un gap de contenido recurrente en la Knowledge Base.\n\n"
                        . "Pregunta ejemplo: %s\n"
                        . "Frecuencia: %d veces en las ultimas 24 horas\n"
                        . "Tenant: %s\n\n"
                        . "Se recomienda crear contenido que responda a esta consulta para mejorar "
                        . "la experiencia de los usuarios.\n\n"
                        . "-- Jaraba Impact Platform (notificacion automatica)",
                        $exampleQuery,
                        $count,
                        $tenantId !== NULL ? (string) $tenantId : 'plataforma global'
                    ),
                ];

                $mailManager->mail(
                    'jaraba_rag',
                    'content_gap_alert',
                    $adminEmail,
                    $langcode,
                    $params
                );

                $this->loggerFactory->get('jaraba_rag')->info(
                    'Gap alert email sent to @email for query "@query" (tenant: @tenant).',
                    [
                        '@email' => $adminEmail,
                        '@query' => mb_substr($exampleQuery, 0, 100),
                        '@tenant' => $tenantId ?? 'platform',
                    ]
                );
            }
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('jaraba_rag')->error(
                'Failed to send gap alert notification: @msg',
                ['@msg' => $e->getMessage()]
            );
        }
    }

    /**
     * Obtiene estadísticas para un tenant.
     *
     * @param int|null $tenantId
     *   ID del tenant (NULL para estadísticas globales).
     * @param string $period
     *   Período: 'day', 'week', 'month'.
     *
     * @return array
     *   Estadísticas:
     *   - 'total_queries': int
     *   - 'answered_rate': float (0-1)
     *   - 'avg_confidence': float (0-1)
     *   - 'avg_response_time_ms': float
     *   - 'top_unanswered': array
     *   - 'purchase_intents': int
     *   - 'total_hallucinations': int
     *   - 'total_tokens': int
     *   - 'avg_tokens_per_query': float
     */
    public function getStats(?int $tenantId, string $period = 'week'): array
    {
        $since = $this->getPeriodTimestamp($period);

        $query = $this->database->select(self::TABLE_NAME, 'q');

        if ($tenantId !== NULL) {
            $query->condition('tenant_id', $tenantId);
        }
        $query->condition('created', $since, '>=');

        // Total queries
        $totalQueries = (clone $query)->countQuery()->execute()->fetchField();

        if ($totalQueries == 0) {
            return [
                'total_queries' => 0,
                'answered_rate' => 0,
                'avg_confidence' => 0,
                'avg_response_time_ms' => 0,
                'top_unanswered' => [],
                'purchase_intents' => 0,
                'total_hallucinations' => 0,
                'total_tokens' => 0,
                'avg_tokens_per_query' => 0,
            ];
        }

        // Answered rate
        $answered = (clone $query)
            ->condition('classification', [self::ANSWERED_FULL, self::ANSWERED_PARTIAL], 'IN')
            ->countQuery()
            ->execute()
            ->fetchField();

        // Average confidence
        $avgConfidence = (clone $query)
            ->addExpression('AVG(confidence_score)', 'avg_conf')
            ->execute()
            ->fetchField();

        // Average response time
        $avgResponseTime = (clone $query)
            ->addExpression('AVG(response_time_ms)', 'avg_time')
            ->execute()
            ->fetchField();

        // Purchase intents
        $purchaseIntents = (clone $query)
            ->condition('classification', self::PURCHASE_INTENT)
            ->countQuery()
            ->execute()
            ->fetchField();

        // Top unanswered queries
        $topUnanswered = $this->getTopUnansweredQueries($tenantId, $since);

        // AI-12: Métricas AI agregadas.
        $totalHallucinations = (clone $query)
            ->addExpression('SUM(hallucination_count)', 'total_hal')
            ->execute()
            ->fetchField();

        $totalTokens = (clone $query)
            ->addExpression('SUM(token_usage)', 'total_tok')
            ->execute()
            ->fetchField();

        return [
            'total_queries' => (int) $totalQueries,
            'answered_rate' => $totalQueries > 0 ? $answered / $totalQueries : 0,
            'avg_confidence' => (float) $avgConfidence,
            'avg_response_time_ms' => (float) $avgResponseTime,
            'top_unanswered' => $topUnanswered,
            'purchase_intents' => (int) $purchaseIntents,
            'total_hallucinations' => (int) ($totalHallucinations ?: 0),
            'total_tokens' => (int) ($totalTokens ?: 0),
            'avg_tokens_per_query' => $totalQueries > 0 ? round((int) ($totalTokens ?: 0) / $totalQueries, 1) : 0,
        ];
    }

    /**
     * Obtiene las queries sin respuesta más frecuentes.
     */
    protected function getTopUnansweredQueries(?int $tenantId, int $since, int $limit = 10): array
    {
        $query = $this->database->select(self::TABLE_NAME, 'q')
            ->fields('q', ['query_hash'])
            ->addExpression('COUNT(*)', 'count')
            ->addExpression('MAX(query_text)', 'example_query')
            ->condition('classification', [self::UNANSWERED, self::ANSWERED_PARTIAL], 'IN')
            ->condition('created', $since, '>=')
            ->groupBy('query_hash')
            ->orderBy('count', 'DESC')
            ->range(0, $limit);

        if ($tenantId !== NULL) {
            $query->condition('tenant_id', $tenantId);
        }

        $results = $query->execute()->fetchAll();

        return array_map(function ($row) {
            return [
                'query' => $row->example_query,
                'count' => (int) $row->count,
            ];
        }, $results);
    }

    /**
     * BIZ-03: Records an AI-driven conversion (query → purchase).
     *
     * Called when a user who recently interacted with the AI copilot
     * completes a purchase. Links the PURCHASE_INTENT query to the order.
     *
     * @param int $tenantId
     *   The tenant where the conversion happened.
     * @param int $userId
     *   The user who converted.
     * @param string $orderId
     *   The commerce order ID.
     * @param float $orderTotal
     *   The order total in EUR.
     */
    public function recordAiConversion(int $tenantId, int $userId, string $orderId, float $orderTotal): void
    {
        try {
            // Find the most recent PURCHASE_INTENT query from this user (last 30 min).
            $windowStart = \Drupal::time()->getRequestTime() - 1800;

            $queryLog = $this->database->select(self::TABLE_NAME, 'q')
                ->fields('q', ['query_text', 'query_hash', 'confidence_score'])
                ->condition('tenant_id', $tenantId)
                ->condition('user_id', $userId)
                ->condition('classification', self::PURCHASE_INTENT)
                ->condition('created', $windowStart, '>=')
                ->orderBy('created', 'DESC')
                ->range(0, 1)
                ->execute()
                ->fetchAssoc();

            // Store conversion via State API (lightweight, no extra table).
            $month = date('Y-m');
            $stateKey = "ai_conversions_{$tenantId}_{$month}";
            $conversions = \Drupal::state()->get($stateKey, [
                'count' => 0,
                'revenue' => 0.0,
                'queries_matched' => 0,
            ]);

            $conversions['count']++;
            $conversions['revenue'] += $orderTotal;
            if ($queryLog) {
                $conversions['queries_matched']++;
            }

            \Drupal::state()->set($stateKey, $conversions);

            $this->loggerFactory->get('jaraba_rag')->info(
                'BIZ-03: AI conversion recorded - tenant @tenant, order @order, total @total EUR, query matched: @matched',
                [
                    '@tenant' => $tenantId,
                    '@order' => $orderId,
                    '@total' => number_format($orderTotal, 2),
                    '@matched' => $queryLog ? 'yes' : 'no',
                ]
            );
        } catch (\Exception $e) {
            $this->loggerFactory->get('jaraba_rag')->error('Error recording AI conversion: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * BIZ-03: Gets AI conversion metrics for a tenant.
     *
     * @param int $tenantId
     *   The tenant ID.
     * @param string $period
     *   Period: 'month' (current), 'quarter' (last 3 months).
     *
     * @return array
     *   Conversion metrics:
     *   - 'total_conversions': int
     *   - 'total_revenue': float
     *   - 'queries_matched': int (conversions with a linked AI query)
     *   - 'purchase_intent_queries': int (total PURCHASE_INTENT queries)
     *   - 'conversion_rate': float (conversions / purchase_intent queries)
     */
    public function getAiConversionMetrics(int $tenantId, string $period = 'month'): array
    {
        $months = $period === 'quarter' ? 3 : 1;
        $totals = ['count' => 0, 'revenue' => 0.0, 'queries_matched' => 0];

        for ($i = 0; $i < $months; $i++) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $stateKey = "ai_conversions_{$tenantId}_{$month}";
            $data = \Drupal::state()->get($stateKey, []);
            $totals['count'] += $data['count'] ?? 0;
            $totals['revenue'] += $data['revenue'] ?? 0.0;
            $totals['queries_matched'] += $data['queries_matched'] ?? 0;
        }

        // Get total PURCHASE_INTENT queries in the same period.
        $since = $this->getPeriodTimestamp($period === 'quarter' ? 'month' : 'month');
        if ($period === 'quarter') {
            $since = \Drupal::time()->getRequestTime() - (86400 * 90);
        }

        $purchaseIntents = $this->database->select(self::TABLE_NAME, 'q')
            ->condition('tenant_id', $tenantId)
            ->condition('classification', self::PURCHASE_INTENT)
            ->condition('created', $since, '>=')
            ->countQuery()
            ->execute()
            ->fetchField();

        return [
            'total_conversions' => $totals['count'],
            'total_revenue' => round($totals['revenue'], 2),
            'queries_matched' => $totals['queries_matched'],
            'purchase_intent_queries' => (int) $purchaseIntents,
            'conversion_rate' => $purchaseIntents > 0
                ? round($totals['count'] / $purchaseIntents, 4)
                : 0.0,
        ];
    }

    /**
     * Convierte período a timestamp.
     */
    protected function getPeriodTimestamp(string $period): int
    {
        $now = \Drupal::time()->getRequestTime();

        return match ($period) {
            'day' => $now - 86400,
            'week' => $now - 604800,
            'month' => $now - 2592000,
            default => $now - 604800,
        };
    }

}
