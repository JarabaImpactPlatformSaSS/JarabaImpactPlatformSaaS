<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sugerencias de imagen con IA para el Page Builder.
 *
 * P2-05: Genera sugerencias de imagenes relevantes para bloques del Page
 * Builder usando dos fases:
 * 1. IA genera keywords de busqueda basados en contexto del contenido.
 * 2. Unsplash API devuelve imagenes curadas con esas keywords.
 *
 * PATRON:
 * - Usa `\Drupal::service('ai.provider')` para generar keywords.
 * - Unsplash API para buscar imagenes (key en config).
 * - Failover: keywords predefinidos por tipo de bloque si IA falla.
 * - Log: Queries registradas via CopilotQueryLoggerService.
 *
 * @see docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260126_v1.md P2-05
 */
class AiImageSuggestionService {

  /**
   * Logger del servicio.
   */
  protected LoggerInterface $logger;

  /**
   * Cliente HTTP para llamadas a Unsplash.
   */
  protected ClientInterface $httpClient;

  /**
   * Keywords predefinidos por tipo de bloque (fallback sin IA).
   *
   * @var array<string, string[]>
   */
  protected const BLOCK_TYPE_KEYWORDS = [
    'hero' => ['business landscape', 'team collaboration', 'modern workspace'],
    'features' => ['technology icons', 'digital innovation', 'abstract shapes'],
    'testimonials' => ['people portrait', 'office meeting', 'handshake business'],
    'team' => ['professional headshot', 'team portrait', 'office people'],
    'gallery' => ['portfolio showcase', 'creative design', 'product display'],
    'blog' => ['writing workspace', 'coffee laptop', 'reading desk'],
    'cta' => ['success celebration', 'rocket launch', 'growth chart'],
    'pricing' => ['value comparison', 'chart business', 'modern office'],
    'faq' => ['question answer', 'help support', 'lightbulb idea'],
    'contact' => ['communication devices', 'map location', 'envelope mail'],
    'portfolio' => ['creative project', 'design showcase', 'artistic work'],
    'stats' => ['data analytics', 'growth metrics', 'dashboard modern'],
    'about' => ['company building', 'team culture', 'mission statement'],
    'services' => ['professional service', 'consulting meeting', 'solution delivery'],
    'blockquote' => ['inspiration quote', 'wisdom book', 'leader speaking'],
    'image_gallery' => ['curated photography', 'beautiful landscape', 'artistic composition'],
  ];

  /**
   * Keywords por vertical de la plataforma.
   *
   * @var array<string, string>
   */
  protected const VERTICAL_CONTEXT = [
    'empleabilidad' => 'employment job career professional',
    'emprendimiento' => 'startup entrepreneurship innovation business',
    'agroconecta' => 'agriculture farming organic rural',
    'formacion' => 'education learning training classroom',
    'comercio' => 'ecommerce shopping products marketplace',
    'servicios' => 'professional services consulting expertise',
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Factoria de loggers.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Cliente HTTP para Unsplash API.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client,
  ) {
    $this->logger = $logger_factory->get('jaraba_page_builder.ai_image');
    $this->httpClient = $http_client;
  }

  /**
   * Genera sugerencias de imagen para un bloque del Page Builder.
   *
   * @param string $block_type
   *   Tipo de bloque (hero, features, testimonials, etc.).
   * @param string $content_context
   *   Texto del contenido circundante para contexto.
   * @param string $vertical
   *   Vertical de la plataforma (empleabilidad, emprendimiento, etc.).
   * @param string $page_title
   *   Titulo de la pagina donde se inserta el bloque.
   * @param int $count
   *   Numero de sugerencias a devolver (max 12).
   *
   * @return array
   *   Array con:
   *   - images: array de {url, thumb_url, alt_text, photographer, photographer_url, download_url}
   *   - keywords: string[] keywords usados para buscar
   *   - source: 'unsplash' | 'fallback'
   */
  public function suggestImages(
    string $block_type,
    string $content_context = '',
    string $vertical = '',
    string $page_title = '',
    int $count = 8,
  ): array {
    $count = min(12, max(1, $count));

    // Fase 1: Generar keywords de busqueda con IA.
    try {
      $keywords = $this->generateKeywordsWithAI($block_type, $content_context, $vertical, $page_title);
    }
    catch (\Exception $e) {
      $this->logger->warning('IA no disponible para keywords, usando fallback: @error', [
        '@error' => $e->getMessage(),
      ]);
      $keywords = $this->getFallbackKeywords($block_type, $vertical);
    }

    // Fase 2: Buscar imagenes en Unsplash.
    try {
      $images = $this->searchUnsplash($keywords, $count);
      if (!empty($images)) {
        return [
          'images' => $images,
          'keywords' => $keywords,
          'source' => 'unsplash',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Unsplash API fallo: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // Fallback: URLs placeholder de Unsplash sin API.
    return [
      'images' => $this->generatePlaceholderImages($keywords, $count),
      'keywords' => $keywords,
      'source' => 'fallback',
    ];
  }

  /**
   * Genera keywords de busqueda de imagenes usando el LLM.
   *
   * @param string $block_type
   *   Tipo de bloque.
   * @param string $content_context
   *   Contexto de contenido.
   * @param string $vertical
   *   Vertical de la plataforma.
   * @param string $page_title
   *   Titulo de la pagina.
   *
   * @return string[]
   *   Array de keywords para buscar imagenes.
   */
  protected function generateKeywordsWithAI(
    string $block_type,
    string $content_context,
    string $vertical,
    string $page_title,
  ): array {
    /** @var \Drupal\ai\AiProviderPluginManager $aiProvider */
    $aiProvider = \Drupal::service('ai.provider');

    $defaults = $aiProvider->getDefaultProviderForOperationType('chat');
    if (empty($defaults)) {
      throw new \RuntimeException('Sin proveedor IA configurado para chat.');
    }

    $provider = $aiProvider->createInstance($defaults['provider_id']);
    $modelId = $defaults['model_id'];

    $systemPrompt = 'Eres un experto en fotografia stock y diseno web. '
      . 'Genera keywords de busqueda para encontrar imagenes relevantes en Unsplash. '
      . 'Responde SOLO con un array JSON de strings, sin explicaciones.';

    $userPrompt = $this->buildKeywordPrompt($block_type, $content_context, $vertical, $page_title);

    $chatInput = new \Drupal\ai\OperationType\Chat\ChatInput([
      new \Drupal\ai\OperationType\Chat\ChatMessage('system', $systemPrompt),
      new \Drupal\ai\OperationType\Chat\ChatMessage('user', $userPrompt),
    ]);

    $configuration = ['temperature' => 0.5];
    $response = $provider->chat($chatInput, $modelId, $configuration);
    $responseText = $response->getNormalized()->getText();

    // Log de la query.
    $this->logAIQuery('image_suggestion_keywords', $userPrompt, $responseText);

    return $this->parseKeywordsResponse($responseText);
  }

  /**
   * Construye el prompt para generar keywords de busqueda.
   *
   * @param string $block_type
   *   Tipo de bloque.
   * @param string $content_context
   *   Contexto de contenido.
   * @param string $vertical
   *   Vertical.
   * @param string $page_title
   *   Titulo de la pagina.
   *
   * @return string
   *   Prompt completo.
   */
  protected function buildKeywordPrompt(
    string $block_type,
    string $content_context,
    string $vertical,
    string $page_title,
  ): string {
    $prompt = "Genera 3 keywords de busqueda en INGLES para encontrar imagenes en Unsplash.\n\n";
    $prompt .= "CONTEXTO:\n";
    $prompt .= "- Tipo de bloque web: {$block_type}\n";

    if ($page_title) {
      $prompt .= "- Titulo de la pagina: {$page_title}\n";
    }

    if ($vertical) {
      $prompt .= "- Sector/vertical: {$vertical}\n";
    }

    if ($content_context) {
      $truncated = mb_substr($content_context, 0, 500);
      $prompt .= "- Contenido del bloque: {$truncated}\n";
    }

    $prompt .= "\nREGLAS:\n";
    $prompt .= "- Keywords en ingles (Unsplash funciona mejor en ingles).\n";
    $prompt .= "- Cada keyword debe ser 2-4 palabras.\n";
    $prompt .= "- Orientados a fotografia profesional, NO clipart ni iconos.\n";
    $prompt .= "- Relevantes al contexto del bloque y la pagina.\n";
    $prompt .= "\nResponde con un array JSON de exactamente 3 strings. Ejemplo: [\"modern office space\", \"team collaboration meeting\", \"business growth chart\"]";

    return $prompt;
  }

  /**
   * Parsea la respuesta del LLM como array de keywords.
   *
   * @param string $responseText
   *   Respuesta del LLM.
   *
   * @return string[]
   *   Keywords parseados.
   */
  protected function parseKeywordsResponse(string $responseText): array {
    $json = $responseText;
    if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $responseText, $matches)) {
      $json = $matches[1];
    }

    // Intentar extraer array JSON.
    if (preg_match('/\[.*\]/s', $json, $matches)) {
      $json = $matches[0];
    }

    $parsed = json_decode(trim($json), TRUE);
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
      return array_filter($parsed, 'is_string');
    }

    $this->logger->warning('No se pudo parsear keywords de IA: @response', [
      '@response' => mb_substr($responseText, 0, 200),
    ]);

    return [];
  }

  /**
   * Keywords de fallback cuando la IA no esta disponible.
   *
   * @param string $block_type
   *   Tipo de bloque.
   * @param string $vertical
   *   Vertical.
   *
   * @return string[]
   *   Keywords predefinidos.
   */
  protected function getFallbackKeywords(string $block_type, string $vertical): array {
    $keywords = self::BLOCK_TYPE_KEYWORDS[$block_type] ?? ['modern business', 'professional workspace', 'digital technology'];

    // Agregar contexto de vertical si existe.
    if ($vertical && isset(self::VERTICAL_CONTEXT[$vertical])) {
      $verticalKeyword = explode(' ', self::VERTICAL_CONTEXT[$vertical]);
      $keywords[0] = $verticalKeyword[0] . ' ' . explode(' ', $keywords[0])[0];
    }

    return $keywords;
  }

  /**
   * Busca imagenes en la API de Unsplash.
   *
   * @param string[] $keywords
   *   Keywords de busqueda.
   * @param int $count
   *   Numero de resultados.
   *
   * @return array
   *   Array de imagenes con metadatos.
   */
  protected function searchUnsplash(array $keywords, int $count): array {
    $config = \Drupal::config('jaraba_page_builder.settings');
    $apiKey = $config->get('unsplash_api_key');

    if (empty($apiKey)) {
      throw new \RuntimeException('Unsplash API key no configurada.');
    }

    $images = [];
    $perKeyword = (int) ceil($count / max(1, count($keywords)));

    foreach ($keywords as $keyword) {
      if (count($images) >= $count) {
        break;
      }

      try {
        $response = $this->httpClient->request('GET', 'https://api.unsplash.com/search/photos', [
          'query' => [
            'query' => $keyword,
            'per_page' => $perKeyword,
            'orientation' => 'landscape',
          ],
          'headers' => [
            'Authorization' => 'Client-ID ' . $apiKey,
            'Accept-Version' => 'v1',
          ],
          'timeout' => 10,
        ]);

        $data = json_decode((string) $response->getBody(), TRUE);

        foreach (($data['results'] ?? []) as $photo) {
          if (count($images) >= $count) {
            break;
          }

          $images[] = [
            'id' => $photo['id'],
            'url' => $photo['urls']['regular'] ?? '',
            'thumb_url' => $photo['urls']['small'] ?? '',
            'alt_text' => $photo['alt_description'] ?? $photo['description'] ?? $keyword,
            'width' => $photo['width'] ?? 0,
            'height' => $photo['height'] ?? 0,
            'color' => $photo['color'] ?? '#cccccc',
            'photographer' => $photo['user']['name'] ?? '',
            'photographer_url' => $photo['user']['links']['html'] ?? '',
            'download_url' => $photo['links']['download_location'] ?? '',
          ];
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Unsplash busqueda fallo para "@keyword": @error', [
          '@keyword' => $keyword,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $images;
  }

  /**
   * Genera imagenes placeholder cuando Unsplash no esta disponible.
   *
   * Usa URLs directas de Unsplash Source (no requiere API key).
   *
   * @param string[] $keywords
   *   Keywords de busqueda.
   * @param int $count
   *   Numero de resultados.
   *
   * @return array
   *   Array de imagenes placeholder.
   */
  protected function generatePlaceholderImages(array $keywords, int $count): array {
    $images = [];
    $query = implode(',', array_map(function ($k) {
      return str_replace(' ', ',', $k);
    }, $keywords));

    for ($i = 0; $i < $count; $i++) {
      $sig = md5($query . $i);
      $images[] = [
        'id' => 'placeholder_' . $sig,
        'url' => "https://images.unsplash.com/photo-placeholder?w=800&h=600&q=80&sig={$sig}",
        'thumb_url' => "https://images.unsplash.com/photo-placeholder?w=400&h=300&q=60&sig={$sig}",
        'alt_text' => implode(' ', $keywords),
        'width' => 800,
        'height' => 600,
        'color' => '#e0e0e0',
        'photographer' => '',
        'photographer_url' => '',
        'download_url' => '',
      ];
    }

    return $images;
  }

  /**
   * Notifica a Unsplash que se descargo una imagen (requerido por API TOS).
   *
   * @param string $download_url
   *   La URL de download_location de Unsplash.
   *
   * @return bool
   *   TRUE si la notificacion fue exitosa.
   */
  public function trackDownload(string $download_url): bool {
    if (empty($download_url)) {
      return FALSE;
    }

    $config = \Drupal::config('jaraba_page_builder.settings');
    $apiKey = $config->get('unsplash_api_key');

    if (empty($apiKey)) {
      return FALSE;
    }

    try {
      $this->httpClient->request('GET', $download_url, [
        'headers' => [
          'Authorization' => 'Client-ID ' . $apiKey,
        ],
        'timeout' => 5,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->warning('Unsplash download tracking fallo: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Registra una query IA en el log del copilot.
   *
   * @param string $type
   *   Tipo de query.
   * @param string $prompt
   *   Prompt enviado.
   * @param string $response
   *   Respuesta recibida.
   */
  protected function logAIQuery(string $type, string $prompt, string $response): void {
    try {
      if (\Drupal::hasService('jaraba_copilot_v2.query_logger')) {
        /** @var \Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService $logger */
        $logger = \Drupal::service('jaraba_copilot_v2.query_logger');
        $logger->logQuery($type, $prompt, $response, [
          'source' => 'page_builder_image_suggestion',
        ]);
      }
    }
    catch (\Exception $e) {
      // Si el logger no esta disponible, no es critico.
    }
  }

}
