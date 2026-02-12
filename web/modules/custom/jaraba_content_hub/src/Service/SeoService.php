<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio para funcionalidad SEO del Content Hub.
 *
 * PROPÓSITO:
 * Proporciona utilidades para optimización SEO de artículos:
 * generación de Schema.org markup y análisis de calidad SEO.
 *
 * CARACTERÍSTICAS:
 * - Generación de JSON-LD Schema.org para artículos
 * - Análisis de calidad SEO con puntuación
 * - Sugerencias de mejora automáticas
 * - Validación de Answer Capsule para GEO
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class SeoService
{

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un SeoService.
     *
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Genera el markup Schema.org de tipo Article.
     *
     * Crea un array JSON-LD que cumple con las especificaciones
     * de Schema.org para artículos, incluyendo autor, publicador
     * e imagen destacada.
     *
     * @param array $data
     *   Datos del artículo:
     *   - 'title': Título del artículo.
     *   - 'excerpt': Descripción/extracto.
     *   - 'publish_date': Fecha de publicación ISO 8601.
     *   - 'changed': Fecha de última modificación.
     *   - 'author_name': Nombre del autor.
     *   - 'publisher_name': Nombre del publicador.
     *   - 'publisher_logo': URL del logo.
     *   - 'featured_image': URL de la imagen destacada.
     *   - 'url': URL canónica del artículo.
     *
     * @return array
     *   Array Schema.org JSON-LD listo para serializar.
     */
    public function generateArticleSchema(array $data): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $data['title'] ?? '',
            'description' => $data['excerpt'] ?? '',
            'datePublished' => $data['publish_date'] ?? '',
            'dateModified' => $data['changed'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $data['author_name'] ?? '',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $data['publisher_name'] ?? 'Jaraba',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $data['publisher_logo'] ?? '',
                ],
            ],
            'image' => $data['featured_image'] ?? '',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $data['url'] ?? '',
            ],
        ];
    }

    /**
     * Analiza la calidad SEO de un artículo.
     *
     * Evalúa múltiples factores SEO y genera una puntuación
     * de 0-100 junto con sugerencias de mejora específicas.
     *
     * Factores evaluados:
     * - Longitud del título SEO (30-60 caracteres)
     * - Longitud de la meta description (120-160 caracteres)
     * - Presencia de Answer Capsule (para GEO)
     * - Imagen destacada (para redes sociales)
     * - Longitud del contenido (mínimo 300, ideal 1000+)
     *
     * @param array $data
     *   Datos del artículo:
     *   - 'title': Título del artículo.
     *   - 'seo_title': Título SEO (si difiere).
     *   - 'seo_description': Meta description.
     *   - 'answer_capsule': Answer Capsule para GEO.
     *   - 'featured_image': URL de la imagen.
     *   - 'body': Contenido HTML del artículo.
     *
     * @return array
     *   Resultado del análisis:
     *   - 'score': Puntuación 0-100.
     *   - 'suggestions': Array de sugerencias de mejora.
     *   - 'word_count': Conteo de palabras del cuerpo.
     */
    public function analyzeArticleSeo(array $data): array
    {
        $score = 0;
        $suggestions = [];

        // Verificación del título.
        $title = $data['seo_title'] ?? $data['title'] ?? '';
        $titleLength = strlen($title);
        if ($titleLength >= 30 && $titleLength <= 60) {
            $score += 20;
        } else {
            $suggestions[] = 'El título SEO debería tener entre 30-60 caracteres.';
        }

        // Verificación de la descripción.
        $description = $data['seo_description'] ?? '';
        $descLength = strlen($description);
        if ($descLength >= 120 && $descLength <= 160) {
            $score += 20;
        } else {
            $suggestions[] = 'La meta description debería tener entre 120-160 caracteres.';
        }

        // Verificación del Answer Capsule.
        $answerCapsule = $data['answer_capsule'] ?? '';
        if (!empty($answerCapsule)) {
            $score += 15;
        } else {
            $suggestions[] = 'Añade un Answer Capsule para optimización GEO.';
        }

        // Verificación de imagen destacada.
        if (!empty($data['featured_image'])) {
            $score += 15;
        } else {
            $suggestions[] = 'Añade una imagen destacada para compartir en redes.';
        }

        // Verificación de longitud del contenido.
        $body = $data['body'] ?? '';
        $wordCount = str_word_count(strip_tags($body));
        if ($wordCount >= 300) {
            $score += 15;
        } else {
            $suggestions[] = 'El artículo debería tener al menos 300 palabras.';
        }

        if ($wordCount >= 1000) {
            $score += 15;
        } else {
            $suggestions[] = 'El contenido largo (1000+ palabras) tiene mejor rendimiento.';
        }

        return [
            'score' => min(100, $score),
            'suggestions' => $suggestions,
            'word_count' => $wordCount,
        ];
    }

}
