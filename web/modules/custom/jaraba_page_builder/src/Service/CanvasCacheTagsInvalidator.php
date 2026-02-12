<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Invalida cache tags al actualizar paginas del Canvas Editor.
 *
 * Estructura: Servicio registrado con tag 'cache_tags_invalidator' que
 * responde a invalidaciones de cache tags de entidades page_content.
 * Extiende la invalidacion para cubrir tags de coleccion y sitemap.
 *
 * Logica: Cuando se invalida un tag de page_content individual,
 * tambien invalida los tags de coleccion (listados), sitemap y
 * preview del Canvas Editor para evitar contenido obsoleto.
 *
 * Sintaxis: Implementa CacheTagsInvalidatorInterface de Drupal Core.
 */
class CanvasCacheTagsInvalidator implements CacheTagsInvalidatorInterface
{

    /**
     * Prefijo de los tags de entidades page_content.
     */
    protected const PAGE_CONTENT_TAG_PREFIX = 'page_content:';

    /**
     * Tag de coleccion de todas las paginas.
     */
    protected const COLLECTION_TAG = 'page_content_list';

    /**
     * Tag del sitemap XML.
     */
    protected const SITEMAP_TAG = 'jaraba_sitemap';

    /**
     * Tag de preview del Canvas Editor.
     */
    protected const CANVAS_PREVIEW_TAG = 'canvas_preview';

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $innerInvalidator
     *   Invalidador de cache tags interno (cache_tags.invalidator).
     * @param \Psr\Log\LoggerInterface $logger
     *   Canal de log del modulo.
     */
    public function __construct(
        protected CacheTagsInvalidatorInterface $innerInvalidator,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Estructura: Recibe array de tags a invalidar y anade tags derivados.
     * Logica: Detecta tags page_content:N, anade coleccion + sitemap + preview.
     */
    public function invalidateTags(array $tags): void
    {
        $additionalTags = [];

        foreach ($tags as $tag) {
            // Detectar tags de entidades page_content individuales.
            if (str_starts_with($tag, self::PAGE_CONTENT_TAG_PREFIX)) {
                $pageId = substr($tag, strlen(self::PAGE_CONTENT_TAG_PREFIX));

                // Invalidar tag de coleccion (listados de paginas).
                $additionalTags[] = self::COLLECTION_TAG;

                // Invalidar tag de sitemap (URL puede haber cambiado).
                $additionalTags[] = self::SITEMAP_TAG;

                // Invalidar tag de preview del canvas (evitar cache obsoleta).
                $additionalTags[] = self::CANVAS_PREVIEW_TAG;

                // Tag especifico de preview para esta pagina.
                $additionalTags[] = self::CANVAS_PREVIEW_TAG . ':' . $pageId;

                $this->logger->debug('Cache tags invalidados para page_content @id: coleccion + sitemap + preview.', [
                    '@id' => $pageId,
                ]);
            }
        }

        // Delegar la invalidacion de tags adicionales al invalidador interno.
        if (!empty($additionalTags)) {
            $this->innerInvalidator->invalidateTags(array_unique($additionalTags));
        }
    }

}
