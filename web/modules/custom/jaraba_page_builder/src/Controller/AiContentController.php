<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para generación de contenido con IA en Page Builder.
 *
 * PROPÓSITO:
 * Proporciona endpoints API para generar contenido desde el Form Builder
 * usando ContentWriterAgent o servicios de IA del ecosistema.
 *
 * ENDPOINTS:
 * - POST /api/v1/page-builder/generate-content: Genera texto para campos
 * - POST /api/v1/page-builder/seo-ai-suggest: Sugerencias SEO con IA (C4.1)
 * - POST /api/v1/page-builder/ai/generate-template: Genera templates con IA (C4.2)
 * - POST /api/v1/page-builder/ai/generate-page: Prompt-to-Page (C4.4)
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class AiContentController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * The AI content writer agent.
     *
     * @var object|null
     */
    protected $contentWriterAgent;

    /**
     * The tenant resolver service.
     *
     * @var \Drupal\jaraba_page_builder\Service\TenantResolverService|null
     */
    protected $tenantResolver;

    /**
     * Servicio de sugerencias SEO con IA (Sprint C4.1).
     *
     * @var \Drupal\jaraba_page_builder\Service\SeoSuggestionService|null
     */
    protected $seoSuggestionService;

    /**
     * Servicio de generación de templates con IA (Sprint C4.2).
     *
     * @var \Drupal\jaraba_page_builder\Service\AiTemplateGeneratorService|null
     */
    protected $aiTemplateGenerator;

    /**
     * Servicio de sugerencias de imagen con IA (P2-05).
     *
     * @var \Drupal\jaraba_page_builder\Service\AiImageSuggestionService|null
     */
    protected $aiImageSuggestion;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = new static();

        // ContentWriterAgent es opcional - puede no estar instalado.
        if ($container->has('jaraba_content_hub.agent.content_writer')) {
            $instance->contentWriterAgent = $container->get('jaraba_content_hub.agent.content_writer');
        }

        if ($container->has('jaraba_page_builder.tenant_resolver')) {
            $instance->tenantResolver = $container->get('jaraba_page_builder.tenant_resolver');
        }

        // Servicios C4: SEO Suggestion + AI Template Generator.
        if ($container->has('jaraba_page_builder.seo_suggestion')) {
            $instance->seoSuggestionService = $container->get('jaraba_page_builder.seo_suggestion');
        }
        if ($container->has('jaraba_page_builder.ai_template_generator')) {
            $instance->aiTemplateGenerator = $container->get('jaraba_page_builder.ai_template_generator');
        }
        if ($container->has('jaraba_page_builder.ai_image_suggestion')) {
            $instance->aiImageSuggestion = $container->get('jaraba_page_builder.ai_image_suggestion');
        }

        return $instance;
    }

    /**
     * Genera contenido con IA para un campo del Form Builder.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The HTTP request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response con el contenido generado.
     */
    public function generateContent(Request $request): JsonResponse
    {
        // Verificar método POST.
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Method not allowed'),
            ], 405);
        }

        // Obtener datos del request.
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['field_type']) || empty($data['context'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Missing required parameters: field_type and context'),
            ], 400);
        }

        $fieldType = $data['field_type'];
        $context = $data['context'];
        $currentValue = $data['current_value'] ?? '';

        try {
            $result = $this->generateForFieldType($fieldType, $context, $currentValue);

            return new JsonResponse([
                'success' => TRUE,
                'content' => $result['content'],
                'variants' => $result['variants'] ?? [],
                'tokens_used' => $result['tokens_used'] ?? 0,
            ]);
        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'AI content generation failed: @message',
                ['@message' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Content generation failed. Please try again.'),
            ], 500);
        }
    }

    /**
     * Genera contenido según el tipo de campo.
     *
     * @param string $fieldType
     *   Tipo de campo: headline, description, text, cta.
     * @param array $context
     *   Contexto para la generación (template, página, vertical, etc.).
     * @param string $currentValue
     *   Valor actual del campo (para mejora).
     *
     * @return array
     *   Array con 'content' y opcionalmente 'variants'.
     */
    protected function generateForFieldType(string $fieldType, array $context, string $currentValue = ''): array
    {
        // Si ContentWriterAgent está disponible, usarlo.
        if ($this->contentWriterAgent) {
            return $this->generateWithAgent($fieldType, $context, $currentValue);
        }

        // Fallback: usar generación básica con prompts predefinidos.
        return $this->generateWithFallback($fieldType, $context, $currentValue);
    }

    /**
     * Genera contenido usando ContentWriterAgent.
     */
    protected function generateWithAgent(string $fieldType, array $context, string $currentValue): array
    {
        $action = match ($fieldType) {
            'headline', 'title' => 'optimize_headline',
            'description', 'subtitle' => 'improve_seo',
            'cta', 'button' => 'optimize_headline',
            default => 'expand_section',
        };

        // Preparar contexto para el agente.
        $agentContext = [
            'topic' => $context['page_title'] ?? $context['template_name'] ?? 'contenido web',
            'vertical' => $context['vertical'] ?? 'general',
            'tone' => $context['tone'] ?? 'professional',
            'target_audience' => $context['audience'] ?? 'usuarios web',
            'current_text' => $currentValue,
        ];

        // Para headlines, pedir variantes.
        if ($action === 'optimize_headline') {
            $agentContext['headline'] = $currentValue ?: ($context['page_title'] ?? '');
            $agentContext['variants_count'] = 3;
        }

        // Para SEO, proporcionar contexto adicional.
        if ($action === 'improve_seo') {
            $agentContext['title'] = $context['page_title'] ?? '';
            $agentContext['keywords'] = $context['keywords'] ?? [];
        }

        $result = $this->contentWriterAgent->execute($action, $agentContext);

        // Procesar resultado según el action.
        if ($action === 'optimize_headline' && !empty($result['variants'])) {
            return [
                'content' => $result['variants'][0] ?? '',
                'variants' => $result['variants'],
                'tokens_used' => $result['tokens_used'] ?? 0,
            ];
        }

        return [
            'content' => $result['content'] ?? $result['meta_description'] ?? '',
            'tokens_used' => $result['tokens_used'] ?? 0,
        ];
    }

    /**
     * Generación de fallback cuando no hay agente disponible.
     *
     * Usa el módulo AI de Drupal directamente con ChatInput/ChatMessage
     * siguiendo el patrón establecido en BaseAgent.
     */
    protected function generateWithFallback(string $fieldType, array $context, string $currentValue): array
    {
        // Intentar usar el servicio genérico de IA si está disponible.
        try {
            /** @var \Drupal\ai\AiProviderPluginManager $aiProvider */
            $aiProvider = \Drupal::service('ai.provider');

            // Obtener proveedor por defecto para operación chat.
            $defaults = $aiProvider->getDefaultProviderForOperationType('chat');
            if (empty($defaults)) {
                // Sin proveedor IA configurado, usar placeholder.
                return [
                    'content' => $this->generatePlaceholder($fieldType, $context),
                    'tokens_used' => 0,
                ];
            }

            $provider = $aiProvider->createInstance($defaults['provider_id']);
            $modelId = $defaults['model_id'];

            $prompt = $this->buildFallbackPrompt($fieldType, $context, $currentValue);
            $systemPrompt = 'Eres el copywriter de Jaraba Impact Platform. Genera contenido conciso y profesional en español. NUNCA menciones plataformas competidoras ni modelos de IA externos.';

            // Construir input de chat con la API correcta.
            $chatInput = new \Drupal\ai\OperationType\Chat\ChatInput([
                new \Drupal\ai\OperationType\Chat\ChatMessage('system', $systemPrompt),
                new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
            ]);

            $configuration = ['temperature' => 0.7];
            $response = $provider->chat($chatInput, $modelId, $configuration);
            $content = $response->getNormalized()->getText();

            // Estimar tokens usados.
            $tokensUsed = (int) ceil(strlen($content) / 4);

            return [
                'content' => trim($content),
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_page_builder')->warning(
                'IA fallback usó placeholder debido a: @error',
                ['@error' => $e->getMessage()]
            );
            // Si todo falla, generar placeholder inteligente.
            return [
                'content' => $this->generatePlaceholder($fieldType, $context),
                'tokens_used' => 0,
            ];
        }
    }


    /**
     * Construye prompt para generación de fallback.
     */
    protected function buildFallbackPrompt(string $fieldType, array $context, string $currentValue): string
    {
        $topic = $context['page_title'] ?? $context['template_name'] ?? 'página web';
        $vertical = $context['vertical'] ?? 'general';

        return match ($fieldType) {
            'headline', 'title' => sprintf(
                'Genera un título atractivo y profesional para una página sobre "%s" en el sector de %s. Máximo 60 caracteres.',
                $topic,
                $vertical
            ),
            'description', 'subtitle' => sprintf(
                'Genera una descripción persuasiva de 2 líneas para una página sobre "%s" en el sector %s.',
                $topic,
                $vertical
            ),
            'cta', 'button' => sprintf(
                'Genera un texto de llamada a la acción para una página sobre "%s". Máximo 4 palabras, verbos de acción.',
                $topic
            ),
            default => sprintf(
                'Genera un párrafo informativo sobre "%s" para el sector %s. 3-4 oraciones.',
                $topic,
                $vertical
            ),
        };
    }

    /**
     * Genera placeholder cuando no hay IA disponible.
     */
    protected function generatePlaceholder(string $fieldType, array $context): string
    {
        $topic = $context['page_title'] ?? 'Tu página';

        return match ($fieldType) {
            'headline', 'title' => sprintf('Descubre %s', $topic),
            'description', 'subtitle' => sprintf('Explora todo lo que %s puede ofrecerte. Encuentra la solución perfecta para tus necesidades.', $topic),
            'cta', 'button' => 'Empezar ahora',
            default => 'Contenido personalizado para tu página. Edita este texto para añadir tu propia información.',
        };
    }

    /**
     * Genera sugerencias SEO con IA para una página (Sprint C4.1).
     *
     * ENDPOINT: POST /api/v1/page-builder/seo-ai-suggest
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con JSON: {html, keyword?, page_title?, meta_description?}.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Sugerencias SEO priorizadas.
     */
    public function seoSuggest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['html'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se requiere el contenido HTML de la página.'),
            ], 400);
        }

        try {
            if (!$this->seoSuggestionService) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Servicio de sugerencias SEO no disponible.'),
                ], 503);
            }

            $result = $this->seoSuggestionService->suggestImprovements(
                $data['html'],
                $data['keyword'] ?? '',
                $data['page_title'] ?? '',
                $data['meta_description'] ?? '',
            );

            return new JsonResponse([
                'success' => TRUE,
                'score' => $result['score'],
                'suggestions' => $result['suggestions'],
                'provider' => $result['provider'],
            ]);
        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'SEO AI suggestion failed: @message',
                ['@message' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al generar sugerencias SEO.'),
            ], 500);
        }
    }

    /**
     * Genera un template completo con IA (Sprint C4.2).
     *
     * ENDPOINT: POST /api/v1/page-builder/ai/generate-template
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con JSON: {prompt, vertical?, tone?, sections?}.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Template generado (HTML + CSS + secciones).
     */
    public function generateAITemplate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['prompt'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se requiere un prompt con instrucciones.'),
            ], 400);
        }

        try {
            if (!$this->aiTemplateGenerator) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Servicio de generación de templates no disponible.'),
                ], 503);
            }

            $result = $this->aiTemplateGenerator->generateTemplate(
                $data['prompt'],
                $data['vertical'] ?? 'generica',
                $data['tone'] ?? 'profesional',
                $data['sections'] ?? ['hero', 'features', 'cta'],
            );

            return new JsonResponse([
                'success' => TRUE,
                'html' => $result['html'],
                'css' => $result['css'],
                'sections' => $result['sections'],
                'provider' => $result['provider'],
            ]);
        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'AI template generation failed: @message',
                ['@message' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al generar template con IA.'),
            ], 500);
        }
    }

    /**
     * Genera una página completa con múltiples secciones — Prompt-to-Page (Sprint C4.4).
     *
     * ENDPOINT: POST /api/v1/page-builder/ai/generate-page
     *
     * A diferencia de generateAITemplate, este endpoint genera la página
     * como secciones individuales con contenido coherente entre sí.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con JSON: {prompt, vertical?, sections?, tone?}.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Secciones generadas individualmente.
     */
    public function generateFullPage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['prompt'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se requiere un prompt con instrucciones.'),
            ], 400);
        }

        $prompt = $data['prompt'];
        $vertical = $data['vertical'] ?? 'generica';
        $tone = $data['tone'] ?? 'profesional';
        $sections = $data['sections'] ?? ['hero', 'features', 'pricing', 'cta'];

        try {
            if (!$this->aiTemplateGenerator) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Servicio de generación de templates no disponible.'),
                ], 503);
            }

            // Usar AiTemplateGeneratorService con todas las secciones solicitadas.
            $result = $this->aiTemplateGenerator->generateTemplate(
                $prompt,
                $vertical,
                $tone,
                $sections,
            );

            return new JsonResponse([
                'success' => TRUE,
                'html' => $result['html'],
                'css' => $result['css'],
                'sections' => $result['sections'],
                'total_sections' => count($result['sections']),
                'provider' => $result['provider'],
            ]);
        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'Prompt-to-Page generation failed: @message',
                ['@message' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al generar página con IA.'),
            ], 500);
        }
    }

    /**
     * Sugiere imagenes relevantes con IA para un bloque (P2-05).
     *
     * ENDPOINT: POST /api/v1/page-builder/ai/suggest-images
     *
     * Genera keywords de busqueda con IA segun el contexto del bloque
     * y busca imagenes curadas en Unsplash.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con JSON: {block_type, content_context?, vertical?, page_title?, count?}.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Imagenes sugeridas con metadatos.
     */
    public function suggestImages(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['block_type'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se requiere el tipo de bloque (block_type).'),
            ], 400);
        }

        try {
            if (!$this->aiImageSuggestion) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Servicio de sugerencias de imagen no disponible.'),
                ], 503);
            }

            $result = $this->aiImageSuggestion->suggestImages(
                $data['block_type'],
                $data['content_context'] ?? '',
                $data['vertical'] ?? '',
                $data['page_title'] ?? '',
                (int) ($data['count'] ?? 8),
            );

            return new JsonResponse([
                'success' => TRUE,
                'images' => $result['images'],
                'keywords' => $result['keywords'],
                'source' => $result['source'],
                'total' => count($result['images']),
            ]);
        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'AI image suggestion failed: @message',
                ['@message' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Error al sugerir imagenes.'),
            ], 500);
        }
    }

    /**
     * Notifica a Unsplash que se descargo una imagen (P2-05).
     *
     * ENDPOINT: POST /api/v1/page-builder/ai/track-image-download
     *
     * Requerido por los TOS de Unsplash para tracking de descargas.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con JSON: {download_url}.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Confirmacion.
     */
    public function trackImageDownload(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['download_url'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se requiere la URL de descarga.'),
            ], 400);
        }

        if (!$this->aiImageSuggestion) {
            return new JsonResponse(['success' => FALSE], 503);
        }

        $tracked = $this->aiImageSuggestion->trackDownload($data['download_url']);

        return new JsonResponse([
            'success' => $tracked,
        ]);
    }

}
