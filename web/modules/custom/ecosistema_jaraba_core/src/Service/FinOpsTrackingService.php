<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para tracking de métricas FinOps por tenant.
 *
 * PROPÓSITO:
 * Registra y consulta uso real de recursos por tenant:
 * - API requests
 * - Storage (archivos + BD)
 * - CPU time (estimado)
 *
 * Los datos se almacenan en la tabla `finops_usage_log`.
 */
class FinOpsTrackingService
{
    /**
     * Constantes de tipos de métrica soportadas.
     */
    public const METRIC_API_REQUEST = 'api_request';
    public const METRIC_STORAGE = 'storage_update';
    public const METRIC_WEBHOOK = 'webhook';
    public const METRIC_RAG_QUERY = 'rag_query';
    public const METRIC_AI_TOKENS = 'ai_tokens';
    public const METRIC_FEATURE_USE = 'feature_use';


    /**
     * Conexión a la base de datos.
     */
    protected Connection $database;

    /**
     * State API para cachés de agregación.
     */
    protected StateInterface $state;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(Connection $database, StateInterface $state, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * Registra un request de API para un tenant.
     *
     * @param string $tenant_id
     *   ID del tenant.
     * @param string $endpoint
     *   Endpoint accedido.
     * @param float $response_time
     *   Tiempo de respuesta en ms.
     */
    public function trackApiRequest(string $tenant_id, string $endpoint = '', float $response_time = 0): void
    {
        if (empty($tenant_id) || $tenant_id === 'unknown') {
            return;
        }

        try {
            if (!$this->tableExists()) {
                return;
            }

            $this->database->insert('finops_usage_log')
                ->fields([
                    'timestamp' => time(),
                    'tenant_id' => $tenant_id,
                    'metric_type' => 'api_request',
                    'value' => 1,
                    'metadata' => json_encode([
                        'endpoint' => $endpoint,
                        'response_time_ms' => $response_time,
                    ]),
                ])
                ->execute();

            // Incrementar contador en state para acceso rápido
            $key = "finops_api_count_{$tenant_id}";
            $current = $this->state->get($key, 0);
            $this->state->set($key, $current + 1);

        } catch (\Exception $e) {
            // Silent fail - no bloquear requests por tracking
        }
    }

    /**
     * Actualiza el storage usado por un tenant.
     *
     * @param string $tenant_id
     *   ID del tenant.
     * @param float $storage_mb
     *   Storage en MB.
     */
    public function updateStorageUsage(string $tenant_id, float $storage_mb): void
    {
        if (empty($tenant_id)) {
            return;
        }

        try {
            if (!$this->tableExists()) {
                return;
            }

            $this->database->insert('finops_usage_log')
                ->fields([
                    'timestamp' => time(),
                    'tenant_id' => $tenant_id,
                    'metric_type' => 'storage_update',
                    'value' => $storage_mb,
                    'metadata' => NULL,
                ])
                ->execute();

            // Actualizar state para acceso rápido
            $this->state->set("finops_storage_{$tenant_id}", $storage_mb);

        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Obtiene el conteo de API requests para un tenant.
     *
     * @param string $tenant_id
     *   ID del tenant.
     * @param int $since
     *   Timestamp desde cuando contar (0 = todo).
     *
     * @return int
     *   Número de requests.
     */
    public function getApiRequestCount(string $tenant_id, int $since = 0): int
    {
        if (!$this->tableExists()) {
            // Fallback a state
            return $this->state->get("finops_api_count_{$tenant_id}", 0);
        }

        try {
            $query = $this->database->select('finops_usage_log', 'f')
                ->condition('tenant_id', $tenant_id)
                ->condition('metric_type', 'api_request');

            if ($since > 0) {
                $query->condition('timestamp', $since, '>=');
            }

            return (int) $query->countQuery()->execute()->fetchField();

        } catch (\Exception $e) {
            return $this->state->get("finops_api_count_{$tenant_id}", 0);
        }
    }

    /**
     * Obtiene el storage actual de un tenant calculado desde archivos reales.
     *
     * @param string $tenant_id
     *   ID del tenant.
     *
     * @return float
     *   Storage en MB.
     */
    public function calculateRealStorage(string $tenant_id): float
    {
        $total_bytes = 0;

        try {
            // 1. Calcular tamaño de archivos gestionados por este tenant
            $files_query = $this->database->select('file_managed', 'fm');
            $files_query->addExpression('SUM(fm.filesize)', 'total_size');

            // Intentar filtrar por tenant si hay campo
            try {
                $files_query->join('file__field_tenant', 'ft', 'fm.fid = ft.entity_id');
                $files_query->condition('ft.field_tenant_target_id', $tenant_id);
            } catch (\Exception $e) {
                // No hay campo tenant en files, usar estimación
            }

            $result = $files_query->execute()->fetchField();
            $total_bytes += (int) ($result ?: 0);

            // 2. Estimar tamaño de contenido (nodos)
            $node_count = 0;
            try {
                $nodes_query = $this->database->select('node_field_data', 'n');
                $nodes_query->addExpression('COUNT(*)', 'cnt');

                // Intentar filtrar por tenant
                try {
                    $nodes_query->join('node__field_tenant', 'nt', 'n.nid = nt.entity_id');
                    $nodes_query->condition('nt.field_tenant_target_id', $tenant_id);
                } catch (\Exception $e) {
                    // Sin campo tenant, no filtrar
                }

                $node_count = (int) $nodes_query->execute()->fetchField();
            } catch (\Exception $e) {
                $node_count = 0;
            }

            // Estimar 50KB por nodo de contenido promedio
            $total_bytes += $node_count * 50 * 1024;

        } catch (\Exception $e) {
            $this->logger->warning('Error calculando storage para tenant @id: @error', [
                '@id' => $tenant_id,
                '@error' => $e->getMessage(),
            ]);
        }

        // Convertir a MB
        $storage_mb = $total_bytes / (1024 * 1024);

        // Guardar en state
        $this->state->set("finops_storage_{$tenant_id}", round($storage_mb, 2));

        return round($storage_mb, 2);
    }

    /**
     * Obtiene el storage almacenado para un tenant.
     *
     * @param string $tenant_id
     *   ID del tenant.
     *
     * @return float
     *   Storage en MB (0 si no hay datos).
     */
    public function getStorageUsage(string $tenant_id): float
    {
        $cached = $this->state->get("finops_storage_{$tenant_id}");

        if ($cached !== NULL) {
            return (float) $cached;
        }

        // Calcular y cachear
        return $this->calculateRealStorage($tenant_id);
    }

    /**
     * Limpia registros antiguos (mantiene últimos 30 días).
     */
    public function cleanupOldRecords(): void
    {
        if (!$this->tableExists()) {
            return;
        }

        try {
            $threshold = time() - (30 * 24 * 60 * 60);

            $deleted = $this->database->delete('finops_usage_log')
                ->condition('timestamp', $threshold, '<')
                ->execute();

            if ($deleted > 0) {
                $this->logger->notice('FinOps cleanup: @count registros antiguos eliminados.', [
                    '@count' => $deleted,
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->warning('Error en cleanup FinOps: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica si la tabla finops_usage_log existe.
     */
    protected function tableExists(): bool
    {
        try {
            return $this->database->schema()->tableExists('finops_usage_log');
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Registra un evento de uso de una Feature específica.
     *
     * PROPÓSITO:
     * Permite tracking granular por feature para calcular costes
     * basados en `unit_cost` de cada Feature.
     *
     * @param string $tenant_id
     *   ID del tenant que usa la feature.
     * @param string $feature_id
     *   ID de la feature usada (ej: 'api_access', 'webhooks').
     * @param float $units
     *   Unidades consumidas (por defecto 1.0).
     * @param array $metadata
     *   Datos adicionales del evento.
     */
    public function trackFeatureUsage(string $tenant_id, string $feature_id, float $units = 1.0, array $metadata = []): void
    {
        if (empty($tenant_id) || empty($feature_id)) {
            return;
        }

        try {
            if (!$this->tableExists()) {
                return;
            }

            $metadata['feature_id'] = $feature_id;

            $this->database->insert('finops_usage_log')
                ->fields([
                    'timestamp' => time(),
                    'tenant_id' => $tenant_id,
                    'metric_type' => self::METRIC_FEATURE_USE,
                    'value' => $units,
                    'metadata' => json_encode($metadata),
                ])
                ->execute();

        } catch (\Exception $e) {
            // Silent fail - no bloquear por tracking
        }
    }

    /**
     * Registra uso de webhook para un tenant.
     *
     * @param string $tenant_id
     *   ID del tenant.
     * @param string $webhook_url
     *   URL del webhook enviado.
     */
    public function trackWebhook(string $tenant_id, string $webhook_url = ''): void
    {
        if (empty($tenant_id)) {
            return;
        }

        try {
            if (!$this->tableExists()) {
                return;
            }

            $this->database->insert('finops_usage_log')
                ->fields([
                    'timestamp' => time(),
                    'tenant_id' => $tenant_id,
                    'metric_type' => self::METRIC_WEBHOOK,
                    'value' => 1,
                    'metadata' => json_encode(['url' => $webhook_url]),
                ])
                ->execute();

        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Registra uso de query RAG/AI para un tenant.
     *
     * @param string $tenant_id
     *   ID del tenant.
     * @param int $tokens_used
     *   Tokens consumidos (si aplica).
     */
    public function trackRagQuery(string $tenant_id, int $tokens_used = 0): void
    {
        if (empty($tenant_id)) {
            return;
        }

        try {
            if (!$this->tableExists()) {
                return;
            }

            $this->database->insert('finops_usage_log')
                ->fields([
                    'timestamp' => time(),
                    'tenant_id' => $tenant_id,
                    'metric_type' => self::METRIC_RAG_QUERY,
                    'value' => 1,
                    'metadata' => json_encode(['tokens' => $tokens_used]),
                ])
                ->execute();

        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Calcula el coste de uso para un tenant basado en Feature unit_cost.
     *
     * LÓGICA:
     * 1. Obtiene el uso acumulado por tipo de métrica
     * 2. Mapea cada métrica a su Feature correspondiente
     * 3. Multiplica uso × unit_cost de la Feature
     *
     * @param string $tenant_id
     *   ID del tenant.
     * @param int $since_timestamp
     *   Timestamp desde cuándo calcular (0 = mes actual).
     *
     * @return array
     *   Array con desglose de costes por feature.
     */
    public function calculateUsageCosts(string $tenant_id, int $since_timestamp = 0): array
    {
        if ($since_timestamp === 0) {
            // Por defecto: inicio del mes actual
            $since_timestamp = strtotime('first day of this month midnight');
        }

        $costs = [];
        $total = 0.0;

        // Mapeo de metric_type a feature_id
        $metric_to_feature = [
            self::METRIC_API_REQUEST => 'api_access',
            self::METRIC_WEBHOOK => 'webhooks',
            self::METRIC_RAG_QUERY => 'rag_ai',
        ];

        try {
            $feature_storage = \Drupal::entityTypeManager()->getStorage('feature');

            foreach ($metric_to_feature as $metric_type => $feature_id) {
                // Obtener uso acumulado
                $query = $this->database->select('finops_usage_log', 'f')
                    ->condition('tenant_id', $tenant_id)
                    ->condition('metric_type', $metric_type)
                    ->condition('timestamp', $since_timestamp, '>=');
                $query->addExpression('SUM(value)', 'total');
                $units = (float) ($query->execute()->fetchField() ?: 0);

                if ($units <= 0) {
                    continue;
                }

                /** @var \Drupal\ecosistema_jaraba_core\Entity\FeatureInterface $feature */
                $feature = $feature_storage->load($feature_id);
                if (!$feature) {
                    continue;
                }

                $unit_cost = $feature->getUnitCost();
                $cost = $units * $unit_cost;

                $costs[$metric_type] = [
                    'units' => $units,
                    'unit_cost' => $unit_cost,
                    'total_cost' => round($cost, 4),
                    'feature_id' => $feature_id,
                    'feature_label' => $feature->label(),
                ];

                $total += $cost;
            }

        } catch (\Exception $e) {
            $this->logger->warning('Error calculando costes de uso: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return [
            'breakdown' => $costs,
            'total_usage_cost' => round($total, 2),
            'period_start' => $since_timestamp,
        ];
    }

}
