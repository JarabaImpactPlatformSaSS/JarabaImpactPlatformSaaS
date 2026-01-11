<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Client;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Cliente directo para Qdrant Vector Database.
 *
 * Este cliente proporciona una API simplificada para interactuar con Qdrant,
 * diseñada específicamente para el sistema RAG de Jaraba.
 *
 * Características:
 * - API simple y clara
 * - Manejo robusto de errores
 * - Timeout configurado
 * - Validación de inputs
 * - Multi-tenancy via filtros
 *
 * @see https://qdrant.tech/documentation/
 */
class QdrantDirectClient
{

    /**
     * Timeout por defecto en segundos.
     * Reducido de 30s a 3s tras Game Day #1 (2026-01-11).
     */
    protected const DEFAULT_TIMEOUT = 3;

    /**
     * Timeout de conexión en segundos (fail-fast).
     */
    protected const CONNECT_TIMEOUT = 2;

    /**
     * Dimension de vectores para OpenAI text-embedding-3-small.
     */
    protected const VECTOR_DIMENSION = 1536;

    /**
     * Constructs a QdrantDirectClient.
     */
    public function __construct(
        protected ClientInterface $httpClient,
        protected ConfigFactoryInterface $configFactory,
        protected KeyRepositoryInterface $keyRepository,
        protected LoggerChannelFactoryInterface $loggerFactory,
    ) {
    }

    /**
     * Obtiene la configuración de conexión de Qdrant.
     *
     * @return array
     *   Array con host, port, api_key, collection.
     */
    protected function getConnectionConfig(): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');

        // Obtener configuración con fallbacks robustos
        // Usamos ?: porque ?? no funciona si el valor es '' (string vacío)
        $host = $config->get('vector_db.host') ?: 'http://qdrant:6333';
        $collection = $config->get('vector_db.collection') ?: 'jaraba_kb';

        // Obtener API key del módulo Key si está configurada
        $apiKey = '';
        $keyId = $config->get('vector_db.api_key');
        if ($keyId) {
            $key = $this->keyRepository->getKey($keyId);
            if ($key) {
                $apiKey = $key->getKeyValue();
            }
        }

        return [
            'host' => rtrim($host, '/'),
            'collection' => $collection,
            'api_key' => $apiKey,
        ];
    }

    /**
     * Verifica si una colección existe.
     *
     * @param string|null $collection
     *   Nombre de la colección. Si es null, usa la configurada.
     *
     * @return bool
     *   TRUE si la colección existe.
     */
    public function collectionExists(?string $collection = NULL): bool
    {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        try {
            $response = $this->request('GET', "/collections/{$collection}");
            return isset($response['result']);
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Crea una colección si no existe.
     *
     * @param string|null $collection
     *   Nombre de la colección.
     * @param int $dimensions
     *   Dimensiones del vector (default: 1536 para OpenAI).
     *
     * @return bool
     *   TRUE si se creó correctamente o ya existía.
     */
    public function ensureCollection(?string $collection = NULL, int $dimensions = self::VECTOR_DIMENSION): bool
    {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        if ($this->collectionExists($collection)) {
            return TRUE;
        }

        try {
            $payload = [
                'vectors' => [
                    'size' => $dimensions,
                    'distance' => 'Cosine',
                ],
            ];

            $this->request('PUT', "/collections/{$collection}", $payload);
            $this->log("Colección '{$collection}' creada exitosamente");
            return TRUE;
        } catch (\Exception $e) {
            $this->log("Error creando colección: " . $e->getMessage(), 'error');
            return FALSE;
        }
    }

    /**
     * Inserta o actualiza puntos en la colección.
     *
     * @param array $points
     *   Array de puntos. Cada punto debe tener:
     *   - id: string (identificador único)
     *   - vector: array (embedding)
     *   - payload: array (metadatos)
     * @param string|null $collection
     *   Nombre de la colección.
     *
     * @return bool
     *   TRUE si la operación fue exitosa.
     */
    public function upsertPoints(array $points, ?string $collection = NULL): bool
    {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        if (empty($points)) {
            return TRUE;
        }

        // Asegurar que la colección existe
        $this->ensureCollection($collection);

        // Formatear puntos para Qdrant
        $formattedPoints = [];
        foreach ($points as $point) {
            // Generar ID hash para Qdrant (requiere UUID o número)
            $pointId = $this->generatePointId($point['id']);

            $formattedPoints[] = [
                'id' => $pointId,
                'vector' => $point['vector'],
                'payload' => array_merge($point['payload'] ?? [], [
                    'drupal_id' => $point['id'], // Guardar ID original en payload
                ]),
            ];
        }

        try {
            $payload = ['points' => $formattedPoints];
            $response = $this->request('PUT', "/collections/{$collection}/points", $payload);

            if (isset($response['result']['status']) && $response['result']['status'] === 'acknowledged') {
                $this->log("Insertados " . count($points) . " puntos en '{$collection}'");
                return TRUE;
            }

            return FALSE;
        } catch (\Exception $e) {
            $this->log("Error insertando puntos: " . $e->getMessage(), 'error');
            return FALSE;
        }
    }

    /**
     * Elimina puntos por sus IDs de Drupal.
     *
     * @param array $drupalIds
     *   Array de IDs de Drupal (e.g., "commerce_product_1_chunk_0").
     * @param string|null $collection
     *   Nombre de la colección.
     *
     * @return bool
     *   TRUE si la operación fue exitosa.
     */
    public function deletePoints(array $drupalIds, ?string $collection = NULL): bool
    {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        if (empty($drupalIds)) {
            return TRUE;
        }

        // Convertir IDs de Drupal a IDs de Qdrant
        $pointIds = array_map([$this, 'generatePointId'], $drupalIds);

        try {
            $payload = ['points' => $pointIds];
            $this->request('POST', "/collections/{$collection}/points/delete", $payload);

            $this->log("Eliminados " . count($drupalIds) . " puntos de '{$collection}'");
            return TRUE;
        } catch (\Exception $e) {
            $this->log("Error eliminando puntos: " . $e->getMessage(), 'error');
            return FALSE;
        }
    }

    /**
     * Elimina puntos por filtro (e.g., por entity_type y entity_id).
     *
     * @param array $filter
     *   Filtro en formato Qdrant.
     * @param string|null $collection
     *   Nombre de la colección.
     *
     * @return bool
     *   TRUE si la operación fue exitosa.
     */
    public function deleteByFilter(array $filter, ?string $collection = NULL): bool
    {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        if (empty($filter)) {
            return FALSE;
        }

        try {
            $payload = ['filter' => $filter];
            $this->request('POST', "/collections/{$collection}/points/delete", $payload);

            $this->log("Puntos eliminados por filtro de '{$collection}'");
            return TRUE;
        } catch (\Exception $e) {
            $this->log("Error eliminando por filtro: " . $e->getMessage(), 'error');
            return FALSE;
        }
    }

    /**
     * Realiza búsqueda vectorial.
     *
     * @param array $vector
     *   Vector de consulta (embedding).
     * @param array $filter
     *   Filtro opcional en formato Qdrant.
     * @param int $limit
     *   Número máximo de resultados.
     * @param float $scoreThreshold
     *   Score mínimo para incluir resultados (0-1).
     * @param string|null $collection
     *   Nombre de la colección.
     *
     * @return array
     *   Array de resultados con 'id', 'score', 'payload'.
     */
    public function vectorSearch(
        array $vector,
        array $filter = [],
        int $limit = 5,
        float $scoreThreshold = 0.7,
        ?string $collection = NULL
    ): array {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        if (empty($vector)) {
            return [];
        }

        try {
            $payload = [
                'vector' => $vector,
                'limit' => $limit,
                'with_payload' => TRUE,
                'with_vector' => FALSE,
                'score_threshold' => $scoreThreshold,
            ];

            if (!empty($filter)) {
                $payload['filter'] = $filter;
            }

            $response = $this->request('POST', "/collections/{$collection}/points/search", $payload);

            $results = [];
            if (isset($response['result']) && is_array($response['result'])) {
                foreach ($response['result'] as $hit) {
                    $results[] = [
                        'id' => $hit['payload']['drupal_id'] ?? $hit['id'],
                        'score' => $hit['score'],
                        'payload' => $hit['payload'] ?? [],
                    ];
                }
            }

            $this->log("Búsqueda vectorial: " . count($results) . " resultados");
            return $results;
        } catch (\Exception $e) {
            $this->log("Error en búsqueda vectorial: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Obtiene puntos por filtro (scroll).
     *
     * @param array $filter
     *   Filtro en formato Qdrant.
     * @param int $limit
     *   Número máximo de resultados.
     * @param string|null $collection
     *   Nombre de la colección.
     *
     * @return array
     *   Array de puntos.
     */
    public function scroll(array $filter = [], int $limit = 100, ?string $collection = NULL): array
    {
        $config = $this->getConnectionConfig();
        $collection = $collection ?? $config['collection'];

        try {
            $payload = [
                'limit' => $limit,
                'with_payload' => TRUE,
                'with_vector' => FALSE,
            ];

            if (!empty($filter)) {
                $payload['filter'] = $filter;
            }

            $response = $this->request('POST', "/collections/{$collection}/points/scroll", $payload);

            $points = [];
            if (isset($response['result']['points']) && is_array($response['result']['points'])) {
                foreach ($response['result']['points'] as $point) {
                    $points[] = [
                        'id' => $point['payload']['drupal_id'] ?? $point['id'],
                        'payload' => $point['payload'] ?? [],
                    ];
                }
            }

            return $points;
        } catch (\Exception $e) {
            $this->log("Error en scroll: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Verifica la conexión con Qdrant.
     *
     * @return bool
     *   TRUE si la conexión es exitosa.
     */
    public function ping(): bool
    {
        try {
            $response = $this->request('GET', '/');
            return isset($response['title']) && $response['title'] === 'qdrant - vectorass engine';
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Genera un ID de punto válido para Qdrant.
     *
     * Qdrant acepta UUIDs o enteros. Usamos un hash MD5
     * convertido a un formato UUID-like para consistencia.
     *
     * @param string $drupalId
     *   ID original de Drupal.
     *
     * @return string
     *   UUID generado del ID.
     */
    protected function generatePointId(string $drupalId): string
    {
        $hash = md5($drupalId);
        // Formatear como UUID para Qdrant
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }

    /**
     * Realiza una petición HTTP a Qdrant.
     *
     * @param string $method
     *   Método HTTP.
     * @param string $endpoint
     *   Endpoint (e.g., "/collections").
     * @param array|null $data
     *   Datos a enviar como JSON.
     *
     * @return array
     *   Respuesta decodificada.
     *
     * @throws \Exception
     *   Si la petición falla.
     */
    protected function request(string $method, string $endpoint, ?array $data = NULL): array
    {
        $config = $this->getConnectionConfig();
        $url = $config['host'] . $endpoint;

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::CONNECT_TIMEOUT,
        ];

        // Añadir API key si está configurada
        if (!empty($config['api_key'])) {
            $options['headers']['Api-Key'] = $config['api_key'];
        }

        // Añadir body si hay datos
        if ($data !== NULL) {
            $options['json'] = $data;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();

            return json_decode($body, TRUE) ?? [];
        } catch (RequestException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($body, TRUE);
                if (isset($decoded['status']['error'])) {
                    $message = $decoded['status']['error'];
                }
            }
            throw new \Exception("Qdrant API error: {$message}");
        }
    }

    /**
     * Helper para logging.
     */
    protected function log(string $message, string $level = 'info'): void
    {
        $this->loggerFactory->get('jaraba_rag')->{$level}("QdrantClient: {$message}");
    }

}
