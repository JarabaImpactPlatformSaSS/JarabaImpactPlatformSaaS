<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de embeddings para artículos del Content Hub.
 *
 * Genera embeddings usando OpenAI text-embedding-3-small (1536 dimensiones).
 * Reutiliza la configuración de jaraba_rag para API keys.
 *
 * ARQUITECTURA:
 * - Mismo modelo de embedding que jaraba_rag para consistencia
 * - Optimizado para contenido de blog (título, resumen, body)
 * - Incluye categoría y tags para mejor semántica
 */
class ContentEmbeddingService
{

    /**
     * Modelo de embedding de OpenAI.
     */
    const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * Dimensiones del vector.
     */
    const VECTOR_DIMENSIONS = 1536;

    /**
     * HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Cache de embeddings en memoria.
     */
    protected array $cache = [];

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        $logger_factory,
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_content_hub');
    }

    /**
     * Genera embedding para un texto.
     *
     * @param string $text
     *   Texto a embedear.
     *
     * @return array
     *   Vector de 1536 dimensiones o array vacío si falla.
     */
    public function generate(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        // Check cache.
        $cacheKey = md5($text);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $apiKey = $this->getOpenAiApiKey();
        if (empty($apiKey)) {
            $this->logger->error('OpenAI API key not configured for embeddings');
            return [];
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::EMBEDDING_MODEL,
                    'input' => $text,
                ],
                'timeout' => 15,
            ]);

            $data = json_decode($response->getBody()->getContents(), TRUE);

            if (isset($data['data'][0]['embedding'])) {
                $embedding = $data['data'][0]['embedding'];
                $this->cache[$cacheKey] = $embedding;
                return $embedding;
            }

            $this->logger->warning('Invalid embedding response structure');
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Article embedding generation failed: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Genera texto para embedding de un artículo.
     *
     * @param \Drupal\jaraba_content_hub\Entity\ContentArticle $article
     *   Entidad ContentArticle.
     *
     * @return string
     *   Texto concatenado optimizado para embedding.
     */
    public function getArticleEmbeddingText($article): string
    {
        $parts = [];

        // Título es lo más importante.
        $parts[] = 'Title: ' . ($article->label() ?? '');

        // Answer capsule (resumen optimizado).
        if ($article->hasField('answer_capsule') && $article->get('answer_capsule')->value) {
            $parts[] = 'Summary: ' . strip_tags($article->get('answer_capsule')->value);
        }

        // Excerpt.
        if ($article->hasField('excerpt') && $article->get('excerpt')->value) {
            $parts[] = 'Excerpt: ' . strip_tags($article->get('excerpt')->value);
        }

        // Body (primeros 1500 caracteres para no exceder límites).
        if ($article->hasField('body') && $article->get('body')->value) {
            $bodyText = strip_tags($article->get('body')->value);
            $parts[] = 'Content: ' . mb_substr($bodyText, 0, 1500);
        }

        // Categoría.
        if ($article->hasField('category') && $article->get('category')->entity) {
            $parts[] = 'Category: ' . $article->get('category')->entity->label();
        }

        // Tags/Keywords si existen.
        if ($article->hasField('seo_keywords') && $article->get('seo_keywords')->value) {
            $parts[] = 'Keywords: ' . $article->get('seo_keywords')->value;
        }

        // Vertical.
        if ($article->hasField('vertical') && $article->get('vertical')->value) {
            $parts[] = 'Topic: ' . $article->get('vertical')->value;
        }

        return implode("\n", $parts);
    }

    /**
     * Obtiene la API key de OpenAI desde configuración.
     *
     * @return string
     *   API key o string vacío.
     */
    protected function getOpenAiApiKey(): string
    {
        if (!\Drupal::hasService('key.repository')) {
            return getenv('OPENAI_API_KEY') ?: '';
        }

        $keyRepository = \Drupal::service('key.repository');

        // Buscar keys comunes de OpenAI directamente.
        $keyNames = ['openai_api', 'openai', 'openai_api_key'];
        foreach ($keyNames as $keyName) {
            $key = $keyRepository->getKey($keyName);
            if ($key) {
                return $key->getKeyValue();
            }
        }

        // Intentar desde config de jaraba_rag.
        $ragConfig = $this->configFactory->get('jaraba_rag.settings');
        $keyId = $ragConfig->get('openai_api_key');
        if ($keyId) {
            $key = $keyRepository->getKey($keyId);
            if ($key) {
                return $key->getKeyValue();
            }
        }

        // Fallback a variable de entorno.
        return getenv('OPENAI_API_KEY') ?: '';
    }

}
