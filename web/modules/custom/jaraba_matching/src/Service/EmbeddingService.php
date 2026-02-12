<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de embeddings para el Matching Engine.
 *
 * Genera embeddings usando OpenAI text-embedding-3-small (1536 dimensiones).
 * Reutiliza la configuración de jaraba_rag para API keys.
 *
 * ARQUITECTURA:
 * - Mismo modelo de embedding que jaraba_rag para consistencia
 * - Cachea embeddings para evitar llamadas duplicadas
 */
class EmbeddingService
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
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Cache de embeddings en memoria.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        $logger_factory
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_matching');
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

        // Check cache
        $cacheKey = md5($text);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $apiKey = $this->getOpenAiApiKey();
        if (empty($apiKey)) {
            $this->logger->error('OpenAI API key not configured');
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
                'timeout' => 10,
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
            $this->logger->error('Embedding generation failed: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Genera texto para embedding de un job posting.
     *
     * @param object $job
     *   Entidad job_posting.
     *
     * @return string
     *   Texto concatenado optimizado para embedding.
     */
    public function getJobEmbeddingText($job): string
    {
        $parts = [];

        // Título y descripción son lo más importante
        $parts[] = 'Job Title: ' . ($job->label() ?? '');

        if ($job->hasField('description') && $job->get('description')->value) {
            $parts[] = 'Description: ' . strip_tags($job->get('description')->value);
        }

        if ($job->hasField('requirements') && $job->get('requirements')->value) {
            $parts[] = 'Requirements: ' . strip_tags($job->get('requirements')->value);
        }

        // Ubicación y tipo
        if ($job->hasField('location_city') && $job->get('location_city')->value) {
            $parts[] = 'Location: ' . $job->get('location_city')->value;
        }

        if ($job->hasField('remote_type') && $job->get('remote_type')->value) {
            $parts[] = 'Work Type: ' . $job->get('remote_type')->value;
        }

        if ($job->hasField('experience_level') && $job->get('experience_level')->value) {
            $parts[] = 'Experience Level: ' . $job->get('experience_level')->value;
        }

        // Skills como lista
        if ($job->hasField('skills_required')) {
            $skills = [];
            foreach ($job->get('skills_required') as $item) {
                if ($item->entity) {
                    $skills[] = $item->entity->label();
                }
            }
            if ($skills) {
                $parts[] = 'Required Skills: ' . implode(', ', $skills);
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Genera texto para embedding de un candidate profile.
     *
     * @param object $candidate
     *   Entidad candidate_profile.
     *
     * @return string
     *   Texto concatenado optimizado para embedding.
     */
    public function getCandidateEmbeddingText($candidate): string
    {
        $parts = [];

        // Headline y resumen
        if ($candidate->hasField('headline') && $candidate->get('headline')->value) {
            $parts[] = 'Headline: ' . $candidate->get('headline')->value;
        }

        if ($candidate->hasField('summary') && $candidate->get('summary')->value) {
            $parts[] = 'Summary: ' . strip_tags($candidate->get('summary')->value);
        }

        // Ubicación
        if ($candidate->hasField('location_city') && $candidate->get('location_city')->value) {
            $parts[] = 'Location: ' . $candidate->get('location_city')->value;
        }

        // Experiencia
        if ($candidate->hasField('experience_years') && $candidate->get('experience_years')->value) {
            $parts[] = 'Experience: ' . $candidate->get('experience_years')->value . ' years';
        }

        // Skills
        if ($candidate->hasField('skills')) {
            $skills = [];
            foreach ($candidate->get('skills') as $item) {
                if ($item->entity) {
                    $skills[] = $item->entity->label();
                }
            }
            if ($skills) {
                $parts[] = 'Skills: ' . implode(', ', $skills);
            }
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

        // Buscar keys comunes de OpenAI directamente
        $keyNames = ['openai_api', 'openai', 'openai_api_key'];
        foreach ($keyNames as $keyName) {
            $key = $keyRepository->getKey($keyName);
            if ($key) {
                return $key->getKeyValue();
            }
        }

        // Intentar desde config de jaraba_rag
        $ragConfig = $this->configFactory->get('jaraba_rag.settings');
        $keyId = $ragConfig->get('openai_api_key');
        if ($keyId) {
            $key = $keyRepository->getKey($keyId);
            if ($key) {
                return $key->getKeyValue();
            }
        }

        // Fallback a variable de entorno
        return getenv('OPENAI_API_KEY') ?: '';
    }

}
