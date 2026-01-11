<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_rag\Client\QdrantDirectClient;

/**
 * Servicio principal de orquestación RAG.
 *
 * Este servicio coordina el flujo completo de RAG:
 * 1. Recibe query del usuario
 * 2. Obtiene contexto del tenant
 * 3. Busca en la Knowledge Base (Qdrant)
 * 4. Enriquece con datos de Drupal
 * 5. Genera respuesta con LLM
 * 6. Valida grounding
 * 7. Registra analytics
 *
 * @see docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md
 */
class JarabaRagService
{

    /**
     * Constructs a JarabaRagService object.
     */
    public function __construct(
        protected TenantContextService $tenantContext,
        protected GroundingValidator $groundingValidator,
        protected QueryAnalyticsService $queryAnalytics,
        protected AiProviderPluginManager $aiProvider,
        protected QdrantDirectClient $qdrantClient,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerChannelFactoryInterface $loggerFactory,
        protected ConfigFactoryInterface $configFactory,
    ) {
    }

    /**
     * Procesa una query RAG y devuelve una respuesta grounded.
     *
     * @param string $query
     *   La pregunta del usuario.
     * @param array $options
     *   Opciones adicionales:
     *   - 'tenant_id': Override del tenant (para admin).
     *   - 'top_k': Número de resultados a recuperar.
     *   - 'include_sources': Incluir fuentes en respuesta.
     *
     * @return array
     *   Array con:
     *   - 'response': Texto de respuesta.
     *   - 'sources': Array de fuentes citadas.
     *   - 'confidence': Score de confianza (0-1).
     *   - 'classification': Tipo de respuesta (ANSWERED_FULL, etc).
     */
    public function query(string $query, array $options = []): array
    {
        $startTime = microtime(TRUE);
        $config = $this->configFactory->get('jaraba_rag.settings');

        try {
            // 1. Obtener contexto del tenant
            $tenantFilters = $this->tenantContext->getSearchFilters();
            $this->log('Query recibida', [
                'query' => $query,
                'tenant_id' => $tenantFilters['tenant_id'] ?? 'unknown',
            ]);

            // 2. Generar embedding de la query
            $queryEmbedding = $this->generateEmbedding($query);

            // 3. Buscar en Qdrant con filtros de tenant
            $topK = $options['top_k'] ?? $config->get('search.top_k') ?? 5;
            $minScore = $config->get('search.min_score') ?? 0.7;

            $searchResults = $this->searchVectorDb($queryEmbedding, $tenantFilters, $topK, $minScore);

            // 4. Enriquecer resultados con datos actuales de Drupal
            $enrichedContext = $this->enrichWithDrupalData($searchResults);

            // 5. Construir prompt y generar respuesta
            $response = $this->generateResponse($query, $enrichedContext, $tenantFilters);

            // 6. Validar grounding
            $validationResult = $this->groundingValidator->validate(
                $response['text'],
                $enrichedContext
            );

            // 7. Si hay alucinaciones, regenerar o usar fallback
            if (!$validationResult['is_valid']) {
                $response = $this->handleHallucinations($query, $enrichedContext, $validationResult);
            }

            // 8. Clasificar la respuesta
            $classification = $this->classifyResponse($query, $searchResults, $response);

            // 9. Registrar analytics
            $this->queryAnalytics->log([
                'query' => $query,
                'tenant_id' => $tenantFilters['tenant_id'] ?? NULL,
                'classification' => $classification,
                'confidence' => $validationResult['confidence'] ?? 0.5,
                'response_time_ms' => (microtime(TRUE) - $startTime) * 1000,
                'sources_count' => count($searchResults),
            ]);

            return [
                'response' => $response['text'],
                'sources' => $this->extractSources($enrichedContext),
                'confidence' => $validationResult['confidence'] ?? 0.5,
                'classification' => $classification,
            ];

        } catch (\Exception $e) {
            $this->log('Error en query RAG', [
                'error' => $e->getMessage(),
                'query' => $query,
            ], 'error');

            return [
                'response' => $config->get('grounding.fallback_message') ?? 'Lo siento, ha ocurrido un error.',
                'sources' => [],
                'confidence' => 0,
                'classification' => 'ERROR',
            ];
        }
    }

    /**
     * Genera embedding de un texto usando OpenAI.
     */
    protected function generateEmbedding(string $text): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $model = $config->get('embeddings.model') ?? 'text-embedding-3-small';

        try {
            // Obtener el proveedor por defecto para embeddings
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');

            if (!$defaults) {
                throw new \Exception('No hay proveedor de embeddings configurado');
            }

            /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            // Generar embedding
            $result = $provider->embeddings($text, $defaults['model_id'] ?? $model);

            return $result->getNormalized();
        } catch (\Exception $e) {
            $this->log('Error generando embedding', [
                'error' => $e->getMessage(),
            ], 'error');
            return [];
        }
    }

    /**
     * Busca en la base de datos vectorial (Qdrant) usando cliente directo.
     */
    protected function searchVectorDb(array $queryEmbedding, array $filters, int $topK, float $minScore): array
    {
        if (empty($queryEmbedding)) {
            return [];
        }

        try {
            // Construir filtros de Qdrant
            $qdrantFilters = $this->tenantContext->buildQdrantFilter($filters);

            // Ejecutar busqueda vectorial con cliente directo
            $results = $this->qdrantClient->vectorSearch(
                vector: $queryEmbedding,
                filter: $qdrantFilters,
                limit: $topK,
                scoreThreshold: $minScore
            );

            // Formatear resultados
            $formatted = [];
            foreach ($results as $result) {
                $formatted[] = [
                    'score' => $result['score'],
                    'payload' => $result['payload'],
                ];
            }

            return $formatted;
        } catch (\Exception $e) {
            $this->log('Error en busqueda VDB', [
                'error' => $e->getMessage(),
            ], 'error');
            return [];
        }
    }

    /**
     * Enriquece resultados de búsqueda con datos actuales de Drupal.
     */
    protected function enrichWithDrupalData(array $searchResults): array
    {
        $enriched = [];

        foreach ($searchResults as $result) {
            $entityType = $result['payload']['drupal_entity_type'] ?? NULL;
            $entityId = $result['payload']['drupal_entity_id'] ?? NULL;

            if (!$entityType || !$entityId) {
                continue;
            }

            try {
                $storage = $this->entityTypeManager->getStorage($entityType);
                $entity = $storage->load($entityId);

                if ($entity) {
                    $enriched[] = [
                        'score' => $result['score'],
                        'chunk_text' => $result['payload']['text'] ?? '',
                        'entity' => $entity,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'url' => $result['payload']['source_url'] ?? '',
                        'title' => $result['payload']['source_title'] ?? '',
                    ];
                }
            } catch (\Exception $e) {
                // Entidad no existe, omitir
                continue;
            }
        }

        return $enriched;
    }

    /**
     * Genera respuesta usando LLM con el contexto proporcionado.
     */
    protected function generateResponse(string $query, array $context, array $tenantFilters): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');

        // Construir contexto formateado para el prompt
        $formattedContext = $this->formatContextForPrompt($context);

        // System prompt con reglas de grounding
        $systemPrompt = $this->buildSystemPrompt($tenantFilters, $formattedContext);

        try {
            // Obtener proveedor LLM
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');

            if (!$defaults) {
                throw new \Exception('No hay proveedor de chat configurado');
            }

            /** @var \Drupal\ai\OperationType\Chat\ChatInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            // Configurar temperatura baja para respuestas mas deterministas
            $provider->setConfiguration([
                'temperature' => $config->get('llm.temperature') ?? 0.3,
                'max_tokens' => $config->get('llm.max_tokens') ?? 500,
            ]);

            // Crear input de chat
            $chatInput = new ChatInput([
                new ChatMessage('system', $systemPrompt),
                new ChatMessage('user', $query),
            ]);

            // Generar respuesta
            $modelId = $defaults['model_id'] ?? $config->get('llm.model') ?? 'gpt-4o-mini';
            $result = $provider->chat($chatInput, $modelId);

            $message = $result->getNormalized();

            return [
                'text' => $message->getText(),
                'raw_response' => $result->getRawOutput(),
            ];
        } catch (\Exception $e) {
            $this->log('Error generando respuesta LLM', [
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'text' => $config->get('grounding.fallback_message') ?? 'Lo siento, ha ocurrido un error.',
                'raw_response' => NULL,
            ];
        }
    }

    /**
     * Construye el system prompt con reglas de grounding.
     */
    protected function buildSystemPrompt(array $tenantFilters, string $context): string
    {
        $tenantName = $tenantFilters['tenant_name'] ?? 'la tienda';
        $vertical = $tenantFilters['vertical'] ?? 'comercio';

        return <<<PROMPT
Eres el asistente de compras de "{$tenantName}", una tienda de {$vertical} en Jaraba Impact Platform.

## REGLAS INQUEBRANTABLES

1. SOLO CONTEXTO: Responde ÚNICAMENTE usando la información del CATÁLOGO proporcionado abajo. NUNCA inventes.

2. HONESTIDAD: Si no tienes información, responde:
   "No tengo esa información. ¿Puedo ayudarte con algo más?"

3. CITAS: Cada producto mencionado DEBE incluir enlace.
   Formato: [Nombre Producto](/producto/slug)

4. LÍMITE: Solo hablas de productos de "{$tenantName}".
   NO mencionas competidores ni productos externos.

5. FILOSOFÍA 'GOURMET DIGITAL': Tu tono es cálido, artesanal.
   Transmites calidad y cuidado, no vendes agresivamente.

## CATÁLOGO Y CONOCIMIENTO
═══════════════════════════════════════════════════════════════
{$context}
═══════════════════════════════════════════════════════════════
PROMPT;
    }

    /**
     * Formatea el contexto para incluir en el prompt.
     */
    protected function formatContextForPrompt(array $context): string
    {
        $lines = [];

        foreach ($context as $i => $item) {
            $num = $i + 1;
            $title = $item['title'] ?? 'Sin título';
            $url = $item['url'] ?? '#';
            $text = $item['chunk_text'] ?? '';

            // Añadir precio si es producto
            $price = '';
            if ($item['entity_type'] === 'commerce_product' && $item['entity']) {
                $priceField = $item['entity']->get('price');
                if (!$priceField->isEmpty()) {
                    $price = ' | Precio: €' . $priceField->first()->toPrice()->getNumber();
                }
            }

            $lines[] = "PRODUCTO {$num}: {$title}{$price}";
            $lines[] = "URL: {$url}";
            $lines[] = "Descripción: {$text}";
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    /**
     * Maneja casos donde se detectan alucinaciones.
     */
    protected function handleHallucinations(string $query, array $context, array $validationResult): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $fallback = $config->get('grounding.fallback_message');

        // Si hay contexto pero las respuestas no son válidas,
        // intentar regenerar con prompt más restrictivo
        if (!empty($context)) {
            // @todo Implementar regeneración con prompt más estricto
        }

        return [
            'text' => $fallback ?? 'No tengo esa información. ¿Puedo ayudarte con algo más?',
            'raw_response' => NULL,
        ];
    }

    /**
     * Clasifica el tipo de respuesta.
     */
    protected function classifyResponse(string $query, array $searchResults, array $response): string
    {
        // Sin resultados de búsqueda = UNANSWERED
        if (empty($searchResults)) {
            return 'UNANSWERED';
        }

        // Score promedio bajo = ANSWERED_PARTIAL
        $avgScore = array_sum(array_column($searchResults, 'score')) / count($searchResults);
        if ($avgScore < 0.75) {
            return 'ANSWERED_PARTIAL';
        }

        // Detectar intención de compra
        $purchaseKeywords = ['comprar', 'precio', 'envío', 'carrito', 'pedido'];
        foreach ($purchaseKeywords as $keyword) {
            if (stripos($query, $keyword) !== FALSE) {
                return 'PURCHASE_INTENT';
            }
        }

        return 'ANSWERED_FULL';
    }

    /**
     * Extrae fuentes citables del contexto.
     */
    protected function extractSources(array $context): array
    {
        $sources = [];

        foreach ($context as $item) {
            $sources[] = [
                'title' => $item['title'] ?? '',
                'url' => $item['url'] ?? '',
                'entity_type' => $item['entity_type'] ?? '',
                'entity_id' => $item['entity_id'] ?? '',
            ];
        }

        return $sources;
    }

    /**
     * Helper para logging.
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        $this->loggerFactory->get('jaraba_rag')->{$level}($message . ' | @context', [
            '@context' => json_encode($context),
        ]);
    }

}
