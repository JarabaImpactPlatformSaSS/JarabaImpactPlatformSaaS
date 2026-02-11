<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Video Content GEO Service.
 *
 * Optimización de contenido de video para LLMs:
 * - Video Schema.org
 * - Transcripciones indexables
 * - YouTube descriptions optimizadas
 */
class VideoGeoService
{

    use StringTranslationTrait;

    /**
     * Genera Schema.org VideoObject para un video.
     *
     * @param array $videoData
     *   Datos del video (title, description, url, thumbnail, duration).
     *
     * @return array
     *   Schema.org VideoObject.
     */
    public function generateVideoSchema(array $videoData): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $videoData['title'] ?? 'Video',
            'description' => $videoData['description'] ?? '',
            'thumbnailUrl' => $videoData['thumbnail'] ?? '',
            'contentUrl' => $videoData['url'] ?? '',
            'uploadDate' => $videoData['upload_date'] ?? date('c'),
            'duration' => $this->formatIsoDuration($videoData['duration'] ?? 0),
        ];

        // Añadir transcripción si existe.
        if (!empty($videoData['transcript'])) {
            $schema['transcript'] = $videoData['transcript'];
        }

        // Añadir embedUrl para YouTube.
        if (!empty($videoData['youtube_id'])) {
            $schema['embedUrl'] = "https://www.youtube.com/embed/{$videoData['youtube_id']}";
            $schema['contentUrl'] = "https://www.youtube.com/watch?v={$videoData['youtube_id']}";
        }

        // Publisher.
        $schema['publisher'] = [
            '@type' => 'Organization',
            'name' => 'Jaraba Impact Platform',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => 'https://jaraba-impact.com/logo.png',
            ],
        ];

        return $schema;
    }

    /**
     * Genera descripción optimizada para YouTube.
     *
     * @param array $productData
     *   Datos del producto.
     *
     * @return string
     *   Descripción optimizada.
     */
    public function generateYouTubeDescription(array $productData): string
    {
        $title = $productData['title'] ?? 'Producto';
        $category = $productData['category'] ?? 'general';
        $storeUrl = $productData['store_url'] ?? '';
        $keywords = $productData['keywords'] ?? [];

        $description = "✅ {$title} - Producto Premium Artesanal\n\n";
        $description .= "Descubre {$title}, un producto excepcional de la categoría {$category}.\n\n";

        $description .= "🛒 COMPRAR AHORA: {$storeUrl}\n\n";

        $description .= "📋 EN ESTE VIDEO:\n";
        $description .= "• Características del producto\n";
        $description .= "• Proceso de elaboración\n";
        $description .= "• Maridajes recomendados\n";
        $description .= "• Información nutricional\n\n";

        $description .= "🏷️ TAGS: " . implode(', ', $keywords) . "\n\n";

        $description .= "📌 TIMESTAMPS:\n";
        $description .= "0:00 Introducción\n";
        $description .= "0:30 Características\n";
        $description .= "1:00 Proceso de elaboración\n";
        $description .= "2:00 Maridajes\n";
        $description .= "3:00 Dónde comprar\n\n";

        $description .= "🌐 Visítanos: https://jaraba-impact.com\n";
        $description .= "📱 Síguenos en redes: @jarabaimpact\n\n";

        $description .= "#artesanal #gourmet #productolocal #" . strtolower(str_replace(' ', '', $category));

        return $description;
    }

    /**
     * Extrae transcripción de un video (placeholder).
     *
     * @param string $videoUrl
     *   URL del video.
     *
     * @return string|null
     *   Transcripción o NULL.
     */
    public function extractTranscript(string $videoUrl): ?string
    {
        // TODO: Integrar con servicio de transcripción (Whisper API).
        // Por ahora, retornar placeholder.
        return "Transcripción del video pendiente de generar.";
    }

    /**
     * Convierte segundos a formato ISO 8601 duration.
     *
     * @param int $seconds
     *   Duración en segundos.
     *
     * @return string
     *   Formato PT#H#M#S.
     */
    protected function formatIsoDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $duration = 'PT';
        if ($hours > 0) {
            $duration .= "{$hours}H";
        }
        if ($minutes > 0) {
            $duration .= "{$minutes}M";
        }
        if ($secs > 0 || ($hours == 0 && $minutes == 0)) {
            $duration .= "{$secs}S";
        }

        return $duration;
    }

}
