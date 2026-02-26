<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sugerencias SEO generadas con IA (Sprint C4.1).
 *
 * Analiza el contenido HTML de una página y genera sugerencias SEO
 * accionables usando el modelo de lenguaje configurado en @ai.provider.
 *
 * PATRÓN:
 * - Usa `\Drupal::service('ai.provider')` para obtener el LLM.
 * - Failover: Si el proveedor por defecto falla, genera reglas simples.
 * - Log: Todas las queries se registran via CopilotQueryLoggerService.
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §11
 * @see Drupal\jaraba_ai_agents\Agent\BaseAgent (patrón de referencia)
 */
class SeoSuggestionService
{

    /**
     * Logger del servicio.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
     *   Factoría de loggers.
     */
    public function __construct(
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('jaraba_page_builder.seo_ai');
    }

    /**
     * Genera sugerencias SEO para el contenido HTML de una página.
     *
     * @param string $html
     *   Contenido HTML de la página.
     * @param string $keyword
     *   Keyword objetivo para optimización.
     * @param string $pageTitle
     *   Título de la página.
     * @param string $metaDescription
     *   Meta descripción actual.
     *
     * @return array
     *   Array con sugerencias priorizadas:
     *   - suggestions: array de {type, priority, message, fix}
     *   - score: int (0-100) score SEO estimado
     *   - provider: string (nombre del proveedor usado)
     */
    public function suggestImprovements(
        string $html,
        string $keyword = '',
        string $pageTitle = '',
        string $metaDescription = '',
    ): array {
        try {
            return $this->generateWithAI($html, $keyword, $pageTitle, $metaDescription);
        } catch (\Exception $e) {
            $this->logger->warning(
                'Sugerencias SEO con IA fallaron, usando fallback: @error',
                ['@error' => $e->getMessage()]
            );
            return $this->generateFallbackSuggestions($html, $keyword, $pageTitle, $metaDescription);
        }
    }

    /**
     * Genera sugerencias SEO usando el LLM configurado.
     *
     * @param string $html
     *   Contenido HTML.
     * @param string $keyword
     *   Keyword objetivo.
     * @param string $pageTitle
     *   Título de la página.
     * @param string $metaDescription
     *   Meta descripción actual.
     *
     * @return array
     *   Sugerencias generadas por IA.
     */
    protected function generateWithAI(
        string $html,
        string $keyword,
        string $pageTitle,
        string $metaDescription,
    ): array {
        /** @var \Drupal\ai\AiProviderPluginManager $aiProvider */
        $aiProvider = \Drupal::service('ai.provider');

        $defaults = $aiProvider->getDefaultProviderForOperationType('chat');
        if (empty($defaults)) {
            throw new \RuntimeException('Sin proveedor IA configurado para chat.');
        }

        $provider = $aiProvider->createInstance($defaults['provider_id']);
        $modelId = $defaults['model_id'];

        $prompt = $this->buildSeoPrompt($html, $keyword, $pageTitle, $metaDescription);

        // FIX-014: AI-IDENTITY-001 universal.
        $systemPrompt = AIIdentityRule::apply(
            'Eres un experto SEO senior. Analiza la página web y genera sugerencias accionables. '
            . 'Responde SIEMPRE en formato JSON válido con la estructura indicada. Responde en español.'
        );

        $chatInput = new \Drupal\ai\OperationType\Chat\ChatInput([
            new \Drupal\ai\OperationType\Chat\ChatMessage('system', $systemPrompt),
            new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
        ]);

        $configuration = ['temperature' => 0.3]; // Baja temperatura para precisión SEO.
        $response = $provider->chat($chatInput, $modelId, $configuration);
        $responseText = $response->getNormalized()->getText();

        // Log de la query IA.
        $this->logAIQuery('seo_suggestion', $prompt, $responseText);

        // Parsear respuesta JSON.
        return $this->parseSuggestions($responseText);
    }

    /**
     * Construye el prompt para análisis SEO.
     *
     * @param string $html
     *   Contenido HTML.
     * @param string $keyword
     *   Keyword objetivo.
     * @param string $pageTitle
     *   Título de la página.
     * @param string $metaDescription
     *   Meta descripción.
     *
     * @return string
     *   Prompt completo para el LLM.
     */
    protected function buildSeoPrompt(
        string $html,
        string $keyword,
        string $pageTitle,
        string $metaDescription,
    ): string {
        // Truncar HTML a un tamaño razonable para el prompt.
        $htmlContent = mb_substr(strip_tags($html), 0, 3000);
        $htmlStructure = $this->extractHtmlStructure($html);

        $prompt = "Analiza esta página web para SEO y genera sugerencias.\n\n";

        if ($keyword) {
            $prompt .= "KEYWORD OBJETIVO: \"{$keyword}\"\n\n";
        }

        if ($pageTitle) {
            $prompt .= "TÍTULO ACTUAL: \"{$pageTitle}\"\n";
        }

        if ($metaDescription) {
            $prompt .= "META DESCRIPCIÓN ACTUAL: \"{$metaDescription}\"\n";
        }

        $prompt .= "\nESTRUCTURA HTML:\n{$htmlStructure}\n\n";
        $prompt .= "CONTENIDO TEXTO (primeros 3000 chars):\n{$htmlContent}\n\n";

        $prompt .= "Genera un JSON con esta estructura EXACTA:\n";
        $prompt .= <<<'JSON'
{
  "score": 75,
  "suggestions": [
    {
      "type": "title",
      "priority": "high",
      "message": "Descripción del problema",
      "fix": "Solución concreta"
    }
  ]
}
JSON;

        $prompt .= "\n\nTypes válidos: title, meta_description, headings, keyword_density, alt_text, internal_links, content_length, semantic_html, readability, mobile.\n";
        $prompt .= "Priorities válidas: high, medium, low.\n";
        $prompt .= "Genera entre 3 y 8 sugerencias ordenadas por prioridad.\n";

        return $prompt;
    }

    /**
     * Extrae la estructura de headings y elementos clave del HTML.
     *
     * @param string $html
     *   HTML a analizar.
     *
     * @return string
     *   Estructura resumida.
     */
    protected function extractHtmlStructure(string $html): string
    {
        $structure = [];

        // Contar headings.
        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/si", $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $heading) {
                    $structure[] = str_repeat('  ', $i - 1) . "H{$i}: " . strip_tags($heading);
                }
            }
        }

        // Contar imágenes con/sin alt.
        preg_match_all('/<img[^>]*>/i', $html, $imgMatches);
        $totalImages = count($imgMatches[0]);
        preg_match_all('/<img[^>]*alt="[^"]+"/i', $html, $altMatches);
        $imagesWithAlt = count($altMatches[0]);

        $structure[] = "Imágenes: {$totalImages} total, {$imagesWithAlt} con alt text";

        // Contar enlaces.
        preg_match_all('/<a[^>]*href/i', $html, $linkMatches);
        $structure[] = "Enlaces: " . count($linkMatches[0]);

        // Longitud de contenido.
        $textContent = strip_tags($html);
        $wordCount = str_word_count($textContent);
        $structure[] = "Palabras: {$wordCount}";

        return implode("\n", $structure);
    }

    /**
     * Parsea la respuesta del LLM en formato JSON.
     *
     * @param string $responseText
     *   Texto de respuesta del LLM.
     *
     * @return array
     *   Sugerencias parseadas.
     */
    protected function parseSuggestions(string $responseText): array
    {
        // Extraer JSON de la respuesta (puede venir envuelto en markdown).
        $json = $responseText;
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $responseText, $matches)) {
            $json = $matches[1];
        }

        $parsed = json_decode(trim($json), TRUE);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            $this->logger->warning(
                'No se pudo parsear respuesta SEO IA: @error',
                ['@error' => json_last_error_msg()]
            );
            return [
                'score' => 0,
                'suggestions' => [],
                'provider' => 'ai_error',
            ];
        }

        return [
            'score' => (int) ($parsed['score'] ?? 0),
            'suggestions' => $parsed['suggestions'] ?? [],
            'provider' => 'ai',
        ];
    }

    /**
     * Genera sugerencias SEO basadas en reglas simples (sin IA).
     *
     * @param string $html
     *   Contenido HTML.
     * @param string $keyword
     *   Keyword objetivo.
     * @param string $pageTitle
     *   Título de la página.
     * @param string $metaDescription
     *   Meta descripción.
     *
     * @return array
     *   Sugerencias basadas en reglas.
     */
    protected function generateFallbackSuggestions(
        string $html,
        string $keyword,
        string $pageTitle,
        string $metaDescription,
    ): array {
        $suggestions = [];
        $score = 100;

        // Verificar título.
        if (empty($pageTitle)) {
            $suggestions[] = [
                'type' => 'title',
                'priority' => 'high',
                'message' => 'La página no tiene título definido.',
                'fix' => 'Añade un título descriptivo de 50-60 caracteres.',
            ];
            $score -= 20;
        } elseif (mb_strlen($pageTitle) > 60) {
            $suggestions[] = [
                'type' => 'title',
                'priority' => 'medium',
                'message' => 'El título (' . mb_strlen($pageTitle) . ' chars) es demasiado largo.',
                'fix' => 'Acórtalo a máximo 60 caracteres para mejorar visibilidad en SERPs.',
            ];
            $score -= 10;
        }

        // Verificar meta descripción.
        if (empty($metaDescription)) {
            $suggestions[] = [
                'type' => 'meta_description',
                'priority' => 'high',
                'message' => 'No hay meta descripción.',
                'fix' => 'Añade una meta descripción de 150-160 caracteres con la keyword objetivo.',
            ];
            $score -= 15;
        }

        // Verificar H1.
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/si', $html, $h1Matches);
        if (empty($h1Matches[1])) {
            $suggestions[] = [
                'type' => 'headings',
                'priority' => 'high',
                'message' => 'No se encontró ningún H1.',
                'fix' => 'Añade exactamente un H1 con la keyword principal.',
            ];
            $score -= 15;
        } elseif (count($h1Matches[1]) > 1) {
            $suggestions[] = [
                'type' => 'headings',
                'priority' => 'medium',
                'message' => 'Se encontraron ' . count($h1Matches[1]) . ' H1 (debería haber solo 1).',
                'fix' => 'Reduce a un solo H1. Los demás conviértelos en H2.',
            ];
            $score -= 10;
        }

        // Verificar alt text.
        preg_match_all('/<img[^>]*>/i', $html, $imgs);
        preg_match_all('/<img[^>]*alt="[^"]+"/i', $html, $alts);
        $missing = count($imgs[0]) - count($alts[0]);
        if ($missing > 0) {
            $suggestions[] = [
                'type' => 'alt_text',
                'priority' => 'medium',
                'message' => "{$missing} imagen(es) sin alt text.",
                'fix' => 'Añade alt text descriptivo a todas las imágenes.',
            ];
            $score -= min(15, $missing * 5);
        }

        // Longitud de contenido.
        $wordCount = str_word_count(strip_tags($html));
        if ($wordCount < 300) {
            $suggestions[] = [
                'type' => 'content_length',
                'priority' => 'medium',
                'message' => 'Contenido muy corto (' . $wordCount . ' palabras).',
                'fix' => 'Amplía a mínimo 300 palabras para mejorar ranking.',
            ];
            $score -= 10;
        }

        // Keyword density.
        if ($keyword && !empty(strip_tags($html))) {
            $text = mb_strtolower(strip_tags($html));
            $keywordLower = mb_strtolower($keyword);
            $count = mb_substr_count($text, $keywordLower);
            if ($count === 0) {
                $suggestions[] = [
                    'type' => 'keyword_density',
                    'priority' => 'high',
                    'message' => "La keyword \"{$keyword}\" no aparece en el contenido.",
                    'fix' => 'Incluye la keyword al menos 2-3 veces de forma natural.',
                ];
                $score -= 15;
            }
        }

        return [
            'score' => max(0, $score),
            'suggestions' => $suggestions,
            'provider' => 'fallback_rules',
        ];
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
    protected function logAIQuery(string $type, string $prompt, string $response): void
    {
        try {
            if (\Drupal::hasService('jaraba_copilot_v2.query_logger')) {
                /** @var \Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService $logger */
                $logger = \Drupal::service('jaraba_copilot_v2.query_logger');
                $logger->logQuery($type, $prompt, $response, [
                    'source' => 'page_builder_seo',
                ]);
            }
        } catch (\Exception $e) {
            // Si el logger no está disponible, no es crítico.
        }
    }

}
