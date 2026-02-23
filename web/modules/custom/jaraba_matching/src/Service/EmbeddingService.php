<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInput;
use Psr\Log\LoggerInterface;

/**
 * Servicio de embeddings para el Matching Engine.
 *
 * Genera embeddings usando el módulo Drupal AI (provider abstraction).
 * Utiliza el provider y modelo configurados como default para 'embeddings',
 * con fallback a 'text-embedding-3-small'.
 *
 * ARQUITECTURA:
 * - Usa AiProviderPluginManager para failover, cost tracking y key management
 * - Cachea embeddings en memoria para evitar llamadas duplicadas
 */
class EmbeddingService
{

    /**
     * Modelo de embedding por defecto (fallback).
     */
    const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * Dimensiones del vector.
     */
    const VECTOR_DIMENSIONS = 1536;

    /**
     * AI provider plugin manager.
     *
     * @var \Drupal\ai\AiProviderPluginManager
     */
    protected $aiProvider;

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
        AiProviderPluginManager $ai_provider,
        $logger_factory
    ) {
        $this->aiProvider = $ai_provider;
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

        // Check cache.
        $cacheKey = md5($text);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');

            if (empty($defaults['provider_id'])) {
                $this->logger->error('No AI provider configured for embeddings. Configure at /admin/config/ai/settings');
                return [];
            }

            $provider = $this->aiProvider->createInstance($defaults['provider_id']);
            $modelId = $defaults['model_id'] ?? self::EMBEDDING_MODEL;

            $input = new EmbeddingsInput($text);
            $result = $provider->embeddings($input, $modelId, ['jaraba_matching']);
            $embedding = $result->getNormalized();

            $this->cache[$cacheKey] = $embedding;
            return $embedding;
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

}
