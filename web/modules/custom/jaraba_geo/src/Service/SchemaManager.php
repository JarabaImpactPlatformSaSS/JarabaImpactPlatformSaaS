<?php

declare(strict_types=1);

namespace Drupal\jaraba_geo\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para gestionar Schema.org estructurado.
 *
 * PROPÓSITO:
 * Genera Schema.org estructurado para que los motores de IA generativa
 * puedan entender y citar correctamente la información de la plataforma.
 *
 * SCHEMAS SOPORTADOS:
 * - Organization: Información de la organización
 * - Product: Productos con precio, disponibilidad, marca
 * - FAQPage: Preguntas frecuentes
 * - Article: Artículos y blog posts
 * - HowTo: Guías paso a paso
 * - Review: Reseñas y testimonios
 * - LocalBusiness: Negocios locales (productores)
 */
class SchemaManager
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected FileUrlGeneratorInterface $fileUrlGenerator,
        protected RequestStack $requestStack,
    ) {
    }

    /**
     * Obtiene la URL base del sitio.
     */
    protected function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? $request->getSchemeAndHttpHost() : 'https://jaraba-saas.lndo.site';
    }

    /**
     * Genera Schema.org WebSite para la homepage.
     */
    public function buildWebSiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Jaraba Impact Platform',
            'description' => 'La primera plataforma de comercio diseñada para que la Inteligencia Artificial venda tus productos.',
            'url' => $this->getBaseUrl(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->getBaseUrl() . '/search?keys={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Genera Schema.org LocalBusiness para un productor.
     */
    public function buildLocalBusinessSchema(EntityInterface $producer): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $producer->label(),
            'url' => $producer->toUrl()->setAbsolute()->toString(),
        ];

        // Descripción.
        $description = $this->getFieldValue($producer, ['body', 'field_description', 'field_biografia']);
        if ($description) {
            $schema['description'] = $description;
        }

        // Imagen.
        $image = $this->getImageUrl($producer);
        if ($image) {
            $schema['image'] = $image;
        }

        // Ubicación.
        if ($producer->hasField('field_location') && !$producer->get('field_location')->isEmpty()) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $producer->get('field_location')->value,
                'addressCountry' => 'ES',
            ];
        }

        return $schema;
    }

    /**
     * Genera Schema.org HowTo para guías.
     */
    public function buildHowToSchema(EntityInterface $entity, array $steps = []): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $entity->label(),
            'description' => $this->getFieldValue($entity, ['body', 'field_description']),
        ];

        // Si no hay pasos proporcionados, intentar extraerlos del contenido.
        if (empty($steps) && $entity->hasField('body')) {
            $body = $entity->get('body')->value;
            $steps = $this->extractStepsFromContent($body);
        }

        if (!empty($steps)) {
            $schema['step'] = [];
            foreach ($steps as $index => $step) {
                $schema['step'][] = [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'text' => $step,
                ];
            }
        }

        return $schema;
    }

    /**
     * Genera Schema.org Review para testimonios.
     */
    public function buildReviewSchema(EntityInterface $entity): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'reviewBody' => $this->getFieldValue($entity, ['body', 'field_review', 'field_testimonio']),
            'datePublished' => date('c', $entity->getCreatedTime()),
        ];

        // Autor del review.
        $author = $entity->getOwner();
        if ($author) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $author->getDisplayName(),
            ];
        }

        // Rating si existe.
        if ($entity->hasField('field_rating') && !$entity->get('field_rating')->isEmpty()) {
            $rating = $entity->get('field_rating')->value;
            $schema['reviewRating'] = [
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $schema;
    }

    /**
     * Genera BreadcrumbList Schema.
     */
    public function buildBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Helper: Obtiene el valor de un campo.
     */
    protected function getFieldValue(EntityInterface $entity, array $fieldNames): string
    {
        foreach ($fieldNames as $fieldName) {
            if ($entity->hasField($fieldName) && !$entity->get($fieldName)->isEmpty()) {
                return mb_substr(strip_tags($entity->get($fieldName)->value), 0, 300);
            }
        }
        return '';
    }

    /**
     * Helper: Obtiene la URL de una imagen.
     */
    protected function getImageUrl(EntityInterface $entity): ?string
    {
        $imageFields = ['field_image', 'field_imagen', 'field_photo', 'field_foto'];

        foreach ($imageFields as $fieldName) {
            if ($entity->hasField($fieldName) && !$entity->get($fieldName)->isEmpty()) {
                $image = $entity->get($fieldName)->entity;
                if ($image) {
                    return $this->fileUrlGenerator->generateAbsoluteString($image->getFileUri());
                }
            }
        }

        return NULL;
    }

    /**
     * Helper: Extrae pasos de contenido HTML.
     */
    protected function extractStepsFromContent(string $html): array
    {
        $steps = [];

        // Buscar listas ordenadas.
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $html, $matches)) {
            foreach ($matches[1] as $step) {
                $steps[] = strip_tags($step);
            }
        }

        return $steps;
    }

}
