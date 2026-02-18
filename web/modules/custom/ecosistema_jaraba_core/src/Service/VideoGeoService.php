<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

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
     * HTTP client for API calls.
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
     * Constructs a VideoGeoService object.
     */
    public function __construct(
        ClientInterface $httpClient,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
    ) {
        $this->httpClient = $httpClient;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
    }

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
        // AUDIT-TODO-RESOLVED: OpenAI Whisper API integration for audio transcription.
        try {
            $config = $this->configFactory->get('ecosistema_jaraba_core.settings');
            $openaiApiKey = $config->get('openai_api_key')
                ?: getenv('OPENAI_API_KEY');

            if (empty($openaiApiKey)) {
                $this->logger->warning('OpenAI API key not configured for Whisper transcription.');
                return NULL;
            }

            // Download the audio/video file to a temporary location.
            $tempFile = \Drupal::service('file_system')->tempnam('temporary://', 'whisper_');
            $downloadResponse = $this->httpClient->request('GET', $videoUrl, [
                'sink' => $tempFile,
                'timeout' => 120,
            ]);

            $realPath = \Drupal::service('file_system')->realpath($tempFile);
            if (!$realPath || !file_exists($realPath)) {
                $this->logger->error('Failed to download video for transcription: @url', [
                    '@url' => $videoUrl,
                ]);
                return NULL;
            }

            // Determine the file extension from the URL for the filename hint.
            $pathInfo = pathinfo(parse_url($videoUrl, PHP_URL_PATH) ?: 'audio.mp4');
            $extension = $pathInfo['extension'] ?? 'mp4';
            $filename = 'audio.' . $extension;

            // Call OpenAI Whisper API with multipart form data.
            $whisperResponse = $this->httpClient->request('POST', 'https://api.openai.com/v1/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openaiApiKey,
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($realPath, 'r'),
                        'filename' => $filename,
                    ],
                    [
                        'name' => 'model',
                        'contents' => 'whisper-1',
                    ],
                    [
                        'name' => 'language',
                        'contents' => 'es',
                    ],
                    [
                        'name' => 'response_format',
                        'contents' => 'text',
                    ],
                ],
                'timeout' => 300,
            ]);

            // Clean up temp file.
            @unlink($realPath);

            $transcript = trim((string) $whisperResponse->getBody());

            if (empty($transcript)) {
                $this->logger->info('Whisper returned empty transcript for @url', [
                    '@url' => $videoUrl,
                ]);
                return NULL;
            }

            $this->logger->info('Whisper transcription completed for @url (@len chars).', [
                '@url' => $videoUrl,
                '@len' => mb_strlen($transcript),
            ]);

            return $transcript;
        }
        catch (\Exception $e) {
            $this->logger->error('Whisper transcription error for @url: @msg', [
                '@url' => $videoUrl,
                '@msg' => $e->getMessage(),
            ]);
            return NULL;
        }
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
