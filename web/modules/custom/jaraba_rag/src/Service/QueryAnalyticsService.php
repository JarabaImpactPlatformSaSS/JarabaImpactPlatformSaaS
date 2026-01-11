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

        // @todo Notificar al admin del tenant via ECA/Brevo
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

        return [
            'total_queries' => (int) $totalQueries,
            'answered_rate' => $totalQueries > 0 ? $answered / $totalQueries : 0,
            'avg_confidence' => (float) $avgConfidence,
            'avg_response_time_ms' => (float) $avgResponseTime,
            'top_unanswered' => $topUnanswered,
            'purchase_intents' => (int) $purchaseIntents,
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
