<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\ai\AiProviderPluginManager;
use Psr\Log\LoggerInterface;

/**
 * Servicio de embeddings para artículos del Content Hub.
 *
 * Genera embeddings usando el módulo AI de Drupal (text-embedding-3-small,
 * 1536 dimensiones). Usa la abstracción de proveedores para failover
 * automático, cost tracking y gestión centralizada de claves.
 *
 * ARQUITECTURA:
 * - Usa Drupal AI module (AiProviderPluginManager) en vez de HTTP directo
 * - Mismo modelo de embedding que jaraba_rag para consistencia
 * - Optimizado para contenido de blog (título, resumen, body)
 * - Incluye categoría y tags para mejor semántica
 */
class ContentEmbeddingService
{

    /**
     * Modelo de embedding (fallback si no hay default configurado).
     */
    const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * Dimensiones del vector.
     */
    const VECTOR_DIMENSIONS = 1536;

    /**
     * Cache de embeddings en memoria.
     */
    protected array $cache = [];

    /**
     * Constructor.
     *
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   Gestor de proveedores AI.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger del módulo.
     */
    public function __construct(
        protected AiProviderPluginManager $aiProvider,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera embedding para un texto.
     *
     * Usa el módulo AI de Drupal para abstracción de proveedores.
     * Esto permite failover automático y gestión centralizada de claves.
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
            if (!$defaults) {
                $this->logger->error('No hay proveedor de embeddings configurado en AI module.');
                return [];
            }

            /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);
            $result = $provider->embeddings($text, $defaults['model_id'] ?? self::EMBEDDING_MODEL);
            $vector = $result->getNormalized();

            if (!empty($vector) && is_array($vector)) {
                $this->cache[$cacheKey] = $vector;
                return $vector;
            }

            $this->logger->warning('Invalid embedding response structure from AI provider.');
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

}
