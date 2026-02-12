<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Asistente IA para sugerencias SEO.
 *
 * Genera sugerencias de meta tags, keywords y mejoras SEO usando
 * el proveedor de IA configurado en @ai.provider (DIR-17).
 *
 * PATRÓN:
 * - Usa AiProviderPluginManager para obtener el LLM configurado.
 * - Failover: Si el proveedor falla, genera sugerencias basadas en reglas.
 * - Circuit breaker implícito: catch + fallback.
 * - Prompt sanitizado: solo texto plano truncado.
 *
 * Fase 4 Doc 179: SEO AI Assistant.
 */
class SeoAiAssistantService
{

    use StringTranslationTrait;

    /**
     * Máximo de caracteres de contenido a enviar al LLM.
     */
    protected const MAX_CONTENT_LENGTH = 2000;

    public function __construct(
        protected AiProviderPluginManager $aiProvider,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera sugerencias de meta tags a partir del contenido de una página.
     *
     * @param int $pageId
     *   ID de la página (page_content).
     *
     * @return array
     *   Array con:
     *   - suggestions: array de sugerencias
     *   - provider: nombre del proveedor usado
     *   - fallback: bool si se usó fallback
     */
    public function suggestMetaTags(int $pageId): array
    {
        $page = $this->entityTypeManager->getStorage('page_content')->load($pageId);
        if (!$page) {
            return $this->emptyResult('Página no encontrada.');
        }

        $pageTitle = $page->label() ?? '';
        $pageContent = $this->extractPageContent($page);
        $currentMeta = $this->getCurrentMetaData($pageId);

        try {
            return $this->generateWithAI($pageTitle, $pageContent, $currentMeta);
        }
        catch (\Exception $e) {
            $this->logger->warning(
                'Sugerencias SEO IA fallaron para página @page: @error',
                ['@page' => $pageId, '@error' => $e->getMessage()]
            );
            return $this->generateFallbackSuggestions($pageTitle, $pageContent, $currentMeta);
        }
    }

    /**
     * Genera sugerencias usando el LLM.
     */
    protected function generateWithAI(string $pageTitle, string $pageContent, array $currentMeta): array
    {
        $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
        if (empty($defaults)) {
            throw new \RuntimeException('Sin proveedor IA configurado para chat.');
        }

        $provider = $this->aiProvider->createInstance($defaults['provider_id']);
        $modelId = $defaults['model_id'];

        $systemPrompt = 'Eres un experto SEO senior especializado en SEO on-page. '
            . 'Analiza el contenido proporcionado y genera sugerencias SEO accionables. '
            . 'Responde SIEMPRE en formato JSON válido con la estructura indicada. '
            . 'Responde en español.';

        $userPrompt = $this->buildPrompt($pageTitle, $pageContent, $currentMeta);

        $chatInput = new ChatInput([
            new ChatMessage('system', $systemPrompt),
            new ChatMessage('user', $userPrompt),
        ]);

        $configuration = ['temperature' => 0.3];
        $response = $provider->chat($chatInput, $modelId, $configuration);
        $responseText = $response->getNormalized()->getText();

        $this->logger->info('Sugerencias SEO IA generadas con proveedor @provider.', [
            '@provider' => $defaults['provider_id'],
        ]);

        $parsed = $this->parseAiResponse($responseText);
        $parsed['provider'] = $defaults['provider_id'] . '/' . $modelId;
        $parsed['fallback'] = FALSE;

        return $parsed;
    }

    /**
     * Construye el prompt para análisis SEO.
     */
    protected function buildPrompt(string $pageTitle, string $pageContent, array $currentMeta): string
    {
        $prompt = "Analiza esta página web y genera sugerencias SEO.\n\n";
        $prompt .= "TÍTULO ACTUAL: " . mb_substr($pageTitle, 0, 100) . "\n";

        if (!empty($currentMeta['meta_title'])) {
            $prompt .= "META TITLE ACTUAL: " . $currentMeta['meta_title'] . "\n";
        }
        if (!empty($currentMeta['meta_description'])) {
            $prompt .= "META DESCRIPTION ACTUAL: " . $currentMeta['meta_description'] . "\n";
        }

        // Sanitizar y truncar contenido.
        $cleanContent = strip_tags($pageContent);
        $cleanContent = preg_replace('/\s+/', ' ', trim($cleanContent));
        $cleanContent = mb_substr($cleanContent, 0, self::MAX_CONTENT_LENGTH);
        $prompt .= "\nCONTENIDO:\n" . $cleanContent . "\n\n";

        $prompt .= "Responde con un JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= '  "meta_title": "Título SEO sugerido (max 60 chars)",' . "\n";
        $prompt .= '  "meta_description": "Descripción SEO sugerida (max 155 chars)",' . "\n";
        $prompt .= '  "keywords": ["keyword1", "keyword2", "keyword3", "keyword4", "keyword5"],' . "\n";
        $prompt .= '  "improvements": [' . "\n";
        $prompt .= '    {"priority": "high|medium|low", "category": "title|description|content|structure|keywords", "suggestion": "Texto de la sugerencia"}' . "\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Parsea la respuesta del LLM.
     */
    protected function parseAiResponse(string $responseText): array
    {
        // Extraer JSON del texto (puede estar envuelto en markdown).
        $jsonStr = $responseText;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $responseText, $matches)) {
            $jsonStr = $matches[1];
        }

        $decoded = json_decode(trim($jsonStr), TRUE);
        if (!is_array($decoded)) {
            $this->logger->warning('Respuesta IA no válida como JSON, usando fallback.');
            return $this->emptyResult('Respuesta IA no parseable.');
        }

        return [
            'suggestions' => [
                'meta_title' => $decoded['meta_title'] ?? '',
                'meta_description' => $decoded['meta_description'] ?? '',
                'keywords' => $decoded['keywords'] ?? [],
                'improvements' => $decoded['improvements'] ?? [],
            ],
        ];
    }

    /**
     * Genera sugerencias basadas en reglas cuando el LLM no está disponible.
     */
    protected function generateFallbackSuggestions(string $pageTitle, string $pageContent, array $currentMeta): array
    {
        $suggestions = [];
        $improvements = [];

        // Analizar meta title.
        $currentTitle = $currentMeta['meta_title'] ?? '';
        if (empty($currentTitle)) {
            $suggestions['meta_title'] = mb_substr($pageTitle, 0, 60);
            $improvements[] = [
                'priority' => 'high',
                'category' => 'title',
                'suggestion' => $this->t('Añade un meta título. Se ha generado uno a partir del título de la página.')->__toString(),
            ];
        }
        elseif (mb_strlen($currentTitle) > 60) {
            $improvements[] = [
                'priority' => 'medium',
                'category' => 'title',
                'suggestion' => $this->t('El meta título tiene @len caracteres. Recortar a máximo 60.', ['@len' => mb_strlen($currentTitle)])->__toString(),
            ];
        }

        // Analizar meta description.
        $currentDesc = $currentMeta['meta_description'] ?? '';
        if (empty($currentDesc)) {
            $plainContent = strip_tags($pageContent);
            $plainContent = preg_replace('/\s+/', ' ', trim($plainContent));
            $suggestions['meta_description'] = mb_substr($plainContent, 0, 155);
            $improvements[] = [
                'priority' => 'high',
                'category' => 'description',
                'suggestion' => $this->t('Añade una meta descripción. Se ha generado una desde el contenido.')->__toString(),
            ];
        }
        elseif (mb_strlen($currentDesc) > 160) {
            $improvements[] = [
                'priority' => 'medium',
                'category' => 'description',
                'suggestion' => $this->t('La meta descripción tiene @len caracteres. Recortar a máximo 160.', ['@len' => mb_strlen($currentDesc)])->__toString(),
            ];
        }

        // Analizar contenido.
        $wordCount = str_word_count(strip_tags($pageContent));
        if ($wordCount < 300) {
            $improvements[] = [
                'priority' => 'medium',
                'category' => 'content',
                'suggestion' => $this->t('El contenido tiene @count palabras. Se recomiendan al menos 300 para buen SEO.', ['@count' => $wordCount])->__toString(),
            ];
        }

        // Keywords básicas (extraer las palabras más frecuentes).
        $keywords = $this->extractBasicKeywords($pageContent, 5);
        $suggestions['keywords'] = $keywords;
        $suggestions['improvements'] = $improvements;

        return [
            'suggestions' => $suggestions,
            'provider' => 'rule-based-fallback',
            'fallback' => TRUE,
        ];
    }

    /**
     * Extrae keywords básicas por frecuencia de palabras.
     */
    protected function extractBasicKeywords(string $content, int $limit = 5): array
    {
        $text = mb_strtolower(strip_tags($content));
        $text = preg_replace('/[^\p{L}\s]/u', '', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrar stop words en español.
        $stopWords = ['de', 'la', 'el', 'en', 'y', 'los', 'las', 'del', 'un', 'una',
            'que', 'por', 'con', 'para', 'es', 'se', 'al', 'lo', 'su', 'más',
            'como', 'no', 'este', 'esta', 'son', 'o', 'a', 'e'];

        $filtered = array_filter($words, function ($word) use ($stopWords) {
            return mb_strlen($word) > 3 && !in_array($word, $stopWords, TRUE);
        });

        $frequency = array_count_values($filtered);
        arsort($frequency);

        return array_slice(array_keys($frequency), 0, $limit);
    }

    /**
     * Extrae el contenido de texto de una página.
     */
    protected function extractPageContent($page): string
    {
        // Priorizar rendered_html.
        if ($page->hasField('rendered_html')) {
            $html = $page->get('rendered_html')->value;
            if (!empty($html)) {
                return $html;
            }
        }

        // Fallback: canvas_data.
        if ($page->hasField('canvas_data')) {
            $canvasRaw = $page->get('canvas_data')->value;
            if (!empty($canvasRaw)) {
                $canvasData = json_decode($canvasRaw, TRUE);
                if (isset($canvasData['html'])) {
                    return $canvasData['html'];
                }
            }
        }

        // Fallback: content_data.
        if ($page->hasField('content_data')) {
            $raw = $page->get('content_data')->value;
            if (!empty($raw)) {
                return $raw;
            }
        }

        return '';
    }

    /**
     * Obtiene los meta datos actuales de la configuración SEO de una página.
     */
    protected function getCurrentMetaData(int $pageId): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return [];
        }

        $storage = $this->entityTypeManager->getStorage('seo_page_config');
        $results = $storage->loadByProperties([
            'page_id' => $pageId,
            'tenant_id' => $tenant->id(),
        ]);

        if (empty($results)) {
            return [];
        }

        $config = reset($results);
        return [
            'meta_title' => $config->get('meta_title')->value ?? '',
            'meta_description' => $config->get('meta_description')->value ?? '',
            'keywords' => $config->get('keywords')->value ?? '',
        ];
    }

    /**
     * Devuelve resultado vacío con mensaje.
     */
    protected function emptyResult(string $message): array
    {
        return [
            'suggestions' => [
                'meta_title' => '',
                'meta_description' => '',
                'keywords' => [],
                'improvements' => [
                    [
                        'priority' => 'high',
                        'category' => 'general',
                        'suggestion' => $message,
                    ],
                ],
            ],
            'provider' => 'none',
            'fallback' => TRUE,
        ];
    }

}
