<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Servicio de embeddings para textos legales con almacenamiento en Qdrant.
 *
 * Genera embeddings vectoriales via AI provider y los almacena/consulta
 * en Qdrant para busqueda semantica de fragmentos normativos.
 *
 * ARQUITECTURA:
 * - Embeddings generados via Drupal AI module (text-embedding-3-small).
 * - Almacenamiento en Qdrant via HTTP (sin depender del cliente jaraba_rag).
 * - Coleccion configurable via jaraba_legal_knowledge.settings.
 * - Dimension de vectores: 1536 (OpenAI text-embedding-3-small).
 */
class LegalEmbeddingService {

  /**
   * Dimension de vectores para text-embedding-3-small.
   */
  protected const VECTOR_DIMENSION = 1536;

  /**
   * Timeout HTTP para Qdrant en segundos.
   */
  protected const QDRANT_TIMEOUT = 5;

  /**
   * Constructor.
   *
   * @param \Drupal\ai\AiProviderPluginManager $aiProvider
   *   Gestor de proveedores de IA.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected AiProviderPluginManager $aiProvider,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera un vector embedding para un texto dado.
   *
   * @param string $text
   *   Texto para el cual generar el embedding.
   *
   * @return array|null
   *   Vector de floats (1536 dimensiones) o NULL si falla.
   */
  public function generateEmbedding(string $text): ?array {
    if (empty(trim($text))) {
      return NULL;
    }

    $config = $this->configFactory->get('jaraba_legal_knowledge.settings');
    $model = $config->get('embedding_model') ?: 'text-embedding-3-small';

    try {
      $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');
      if (!$defaults) {
        $this->logger->error('No hay proveedor de embeddings configurado.');
        return NULL;
      }

      /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
      $provider = $this->aiProvider->createInstance($defaults['provider_id']);
      $result = $provider->embeddings($text, $defaults['model_id'] ?? $model);

      return $result->getNormalized();
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando embedding: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Almacena un embedding con su payload en Qdrant.
   *
   * @param string $pointId
   *   Identificador unico del punto (e.g., "legal_norm_123_chunk_0").
   * @param array $vector
   *   Vector de embedding (array de floats).
   * @param array $payload
   *   Metadatos asociados al punto (norm_id, chunk_index, text, etc.).
   *
   * @return bool
   *   TRUE si el almacenamiento fue exitoso.
   */
  public function storeEmbedding(string $pointId, array $vector, array $payload): bool {
    $collection = $this->getCollectionName();

    // Generar UUID compatible con Qdrant a partir del pointId.
    $uuid = $this->generateUuid($pointId);

    $requestData = [
      'points' => [
        [
          'id' => $uuid,
          'vector' => $vector,
          'payload' => array_merge($payload, [
            'drupal_id' => $pointId,
          ]),
        ],
      ],
    ];

    try {
      $qdrantConfig = $this->getQdrantConfig();
      $url = $qdrantConfig['host'] . '/collections/' . $collection . '/points';

      /** @var \GuzzleHttp\ClientInterface $httpClient */
      $httpClient = \Drupal::httpClient();
      $options = [
        'json' => $requestData,
        'timeout' => self::QDRANT_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json'],
      ];

      if (!empty($qdrantConfig['api_key'])) {
        $options['headers']['Api-Key'] = $qdrantConfig['api_key'];
      }

      $response = $httpClient->request('PUT', $url, $options);
      $body = json_decode((string) $response->getBody(), TRUE);

      $isSuccess = isset($body['result']['status']) && $body['result']['status'] === 'acknowledged';
      if (!$isSuccess) {
        $this->logger->warning('Qdrant upsert no confirmado para punto @id.', [
          '@id' => $pointId,
        ]);
      }

      return $isSuccess;
    }
    catch (\Exception $e) {
      $this->logger->error('Error almacenando embedding en Qdrant para @id: @error', [
        '@id' => $pointId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Busca chunks similares en Qdrant por embedding de consulta.
   *
   * @param string $query
   *   Texto de consulta para el cual buscar chunks similares.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param float $threshold
   *   Umbral minimo de similaridad (0-1).
   *
   * @return array
   *   Array de resultados con 'id', 'score' y 'payload'.
   */
  public function searchSimilar(string $query, int $limit = 5, float $threshold = 0.7): array {
    $queryEmbedding = $this->generateEmbedding($query);
    if (empty($queryEmbedding)) {
      return [];
    }

    $collection = $this->getCollectionName();

    try {
      $qdrantConfig = $this->getQdrantConfig();
      $url = $qdrantConfig['host'] . '/collections/' . $collection . '/points/search';

      $requestData = [
        'vector' => $queryEmbedding,
        'limit' => $limit,
        'score_threshold' => $threshold,
        'with_payload' => TRUE,
        'with_vector' => FALSE,
      ];

      /** @var \GuzzleHttp\ClientInterface $httpClient */
      $httpClient = \Drupal::httpClient();
      $options = [
        'json' => $requestData,
        'timeout' => self::QDRANT_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json'],
      ];

      if (!empty($qdrantConfig['api_key'])) {
        $options['headers']['Api-Key'] = $qdrantConfig['api_key'];
      }

      $response = $httpClient->request('POST', $url, $options);
      $body = json_decode((string) $response->getBody(), TRUE);

      $results = [];
      foreach ($body['result'] ?? [] as $hit) {
        $results[] = [
          'id' => $hit['payload']['drupal_id'] ?? $hit['id'],
          'score' => (float) $hit['score'],
          'payload' => $hit['payload'] ?? [],
        ];
      }

      $this->logger->info('Busqueda semantica en Qdrant: @count resultados (umbral @threshold).', [
        '@count' => count($results),
        '@threshold' => $threshold,
      ]);

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando en Qdrant: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Elimina un embedding de Qdrant por su identificador.
   *
   * @param string $pointId
   *   Identificador del punto a eliminar.
   *
   * @return bool
   *   TRUE si la eliminacion fue exitosa.
   */
  public function deleteEmbedding(string $pointId): bool {
    $collection = $this->getCollectionName();
    $uuid = $this->generateUuid($pointId);

    try {
      $qdrantConfig = $this->getQdrantConfig();
      $url = $qdrantConfig['host'] . '/collections/' . $collection . '/points/delete';

      /** @var \GuzzleHttp\ClientInterface $httpClient */
      $httpClient = \Drupal::httpClient();
      $options = [
        'json' => ['points' => [$uuid]],
        'timeout' => self::QDRANT_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json'],
      ];

      if (!empty($qdrantConfig['api_key'])) {
        $options['headers']['Api-Key'] = $qdrantConfig['api_key'];
      }

      $httpClient->request('POST', $url, $options);

      $this->logger->info('Embedding eliminado de Qdrant: @id.', [
        '@id' => $pointId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error eliminando embedding @id de Qdrant: @error', [
        '@id' => $pointId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene el nombre de la coleccion de Qdrant desde configuracion.
   *
   * @return string
   *   Nombre de la coleccion.
   */
  protected function getCollectionName(): string {
    $config = $this->configFactory->get('jaraba_legal_knowledge.settings');

    return $config->get('qdrant_collection') ?: 'legal_norms';
  }

  /**
   * Obtiene configuracion de conexion a Qdrant.
   *
   * Reutiliza la configuracion del modulo jaraba_rag si esta disponible,
   * con fallbacks a valores por defecto.
   *
   * @return array
   *   Array con 'host' y 'api_key'.
   */
  protected function getQdrantConfig(): array {
    $config = $this->configFactory->get('jaraba_rag.settings');

    return [
      'host' => rtrim($config->get('vector_db.host') ?: 'http://qdrant:6333', '/'),
      'api_key' => $config->get('vector_db.api_key') ?: '',
    ];
  }

  /**
   * Genera un UUID compatible con Qdrant a partir de un ID string.
   *
   * @param string $id
   *   Identificador original.
   *
   * @return string
   *   UUID en formato estandar.
   */
  protected function generateUuid(string $id): string {
    $hash = md5($id);

    return sprintf(
      '%s-%s-%s-%s-%s',
      substr($hash, 0, 8),
      substr($hash, 8, 4),
      substr($hash, 12, 4),
      substr($hash, 16, 4),
      substr($hash, 20, 12)
    );
  }

}
