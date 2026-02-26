<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService;
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
     * FIX-018 + FIX-027: Persona mapping per vertical for the RAG system prompt.
     *
     * Each vertical gets a contextually appropriate assistant persona
     * instead of the hardcoded "asistente de compras" for all tenants.
     * Uses canonical vertical names from BaseAgent::VERTICALS.
     */
    protected const VERTICAL_PERSONAS = [
        // Canonical verticals (FIX-027).
        'empleabilidad' => 'asistente de carrera profesional',
        'emprendimiento' => 'asistente de emprendimiento',
        'comercioconecta' => 'asistente de compras para comercio de proximidad',
        'agroconecta' => 'asistente de productos agroalimentarios',
        'jarabalex' => 'asistente jurídico especializado',
        'serviciosconecta' => 'asistente de servicios profesionales',
        'andalucia_ei' => 'asistente de emprendimiento e innovación',
        'jaraba_content_hub' => 'asistente de contenido editorial',
        'formacion' => 'asistente de formación y aprendizaje',
        'demo' => 'asistente de demostración',
        // Legacy aliases (backward compatibility).
        'empleo' => 'asistente de carrera profesional',
        'comercio' => 'asistente de compras para comercio de proximidad',
        'instituciones' => 'asistente institucional',
        // Fallback.
        'general' => 'asistente de la plataforma',
    ];

    /**
     * Optional LLM re-ranker for improved relevance (FIX-037).
     *
     * @var \Drupal\jaraba_rag\Service\LlmReRankerService|null
     */
    protected ?LlmReRankerService $llmReRanker = NULL;

    /**
     * Constructs a JarabaRagService object.
     */
    public function __construct(
        protected RagTenantFilterService $tenantContext,
        protected GroundingValidator $groundingValidator,
        protected QueryAnalyticsService $queryAnalytics,
        protected AiProviderPluginManager $aiProvider,
        protected QdrantDirectClient $qdrantClient,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerChannelFactoryInterface $loggerFactory,
        protected ConfigFactoryInterface $configFactory,
        protected ?CacheBackendInterface $responseCache = NULL,
        protected ?AIGuardrailsService $guardrails = NULL,
    ) {
        // FIX-037: Resolve LlmReRankerService optionally.
        if (\Drupal::hasService('jaraba_rag.llm_reranker')) {
            try {
                $this->llmReRanker = \Drupal::service('jaraba_rag.llm_reranker');
            } catch (\Exception $e) {
                // Optional — not critical.
            }
        }
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

        // AI-15: Generate correlation ID for request tracing.
        $correlationId = bin2hex(random_bytes(8));

        try {
            // 1. Obtener contexto del tenant.
            $tenantFilters = $this->tenantContext->getSearchFilters();
            $this->log('Query recibida', [
                'query' => $query,
                'tenant_id' => $tenantFilters['tenant_id'] ?? 'unknown',
                'correlation_id' => $correlationId,
            ]);

            // AI-11 + AI-16: Verificar caché con query normalizada para mejor hit rate.
            $normalizedQuery = $this->normalizeQueryForCache($query);
            $cacheKey = 'rag_response:' . hash('sha256', json_encode([
                'query' => $normalizedQuery,
                'top_k' => $options['top_k'] ?? NULL,
                'tenant_id' => $tenantFilters['tenant_id'] ?? 'unknown',
            ]));

            if ($this->responseCache) {
                $cached = $this->responseCache->get($cacheKey);
                if ($cached && !empty($cached->data)) {
                    $this->log('Respuesta RAG obtenida de caché', [
                        'query' => $query,
                    ]);

                    // Registrar analytics como cache hit.
                    $this->queryAnalytics->log([
                        'query' => $query,
                        'tenant_id' => $tenantFilters['tenant_id'] ?? NULL,
                        'classification' => $cached->data['classification'] ?? 'CACHE_HIT',
                        'confidence' => $cached->data['confidence'] ?? 0.5,
                        'response_time_ms' => (microtime(TRUE) - $startTime) * 1000,
                        'sources_count' => count($cached->data['sources'] ?? []),
                        'cache_hit' => TRUE,
                    ]);

                    return $cached->data;
                }
            }

            // FIX-015: Validar query con AIGuardrailsService antes de procesarla.
            if ($this->guardrails) {
                $guardrailResult = $this->guardrails->validate($query, [
                    'tenant_id' => $tenantFilters['tenant_id'] ?? 'unknown',
                    'user_id' => \Drupal::currentUser()->id(),
                    'pipeline' => 'rag',
                ]);

                if ($guardrailResult['action'] === AIGuardrailsService::ACTION_BLOCK) {
                    $this->log('Query bloqueada por AIGuardrailsService', [
                        'query' => $query,
                        'violations' => $guardrailResult['violations'],
                        'correlation_id' => $correlationId,
                    ], 'warning');

                    return [
                        'response' => 'No puedo procesar esa consulta. Por favor, reformula tu pregunta.',
                        'sources' => [],
                        'confidence' => 0,
                        'classification' => 'BLOCKED_GUARDRAIL',
                    ];
                }

                // Si hubo PII sanitizado, usar el prompt limpio.
                if ($guardrailResult['action'] === AIGuardrailsService::ACTION_MODIFY) {
                    $query = $guardrailResult['processed_prompt'];
                }
            }

            // 2. Generar embedding de la query.
            $queryEmbedding = $this->generateEmbedding($query);

            // 3. Buscar en Qdrant con filtros de tenant.
            $topK = $options['top_k'] ?? $config->get('search.top_k') ?? 5;
            // FIX-007: Alineado con JarabaRagConfigForm que usa 'search.score_threshold'.
            $minScore = $config->get('search.score_threshold') ?? $config->get('search.min_score') ?? 0.7;

            // AI-08: Fetch more candidates for re-ranking, then trim.
            $fetchK = min($topK * 3, 15);
            $searchResults = $this->searchVectorDb($queryEmbedding, $tenantFilters, $fetchK, $minScore);

            // AI-13: Apply temporal decay to boost fresh content.
            $searchResults = $this->applyTemporalDecay($searchResults);

            // AI-08: Re-rank results using cross-encoder scoring.
            $searchResults = $this->reRankResults($query, $searchResults, $topK);

            // 4. Enriquecer resultados con datos actuales de Drupal.
            $enrichedContext = $this->enrichWithDrupalData($searchResults);

            // 5. Construir prompt y generar respuesta.
            $response = $this->generateResponse($query, $enrichedContext, $tenantFilters);

            // 6. Validar grounding.
            $validationResult = $this->groundingValidator->validate(
                $response['text'],
                $enrichedContext
            );

            // 7. Si hay alucinaciones, regenerar o usar fallback.
            if (!$validationResult['is_valid']) {
                $response = $this->handleHallucinations($query, $enrichedContext, $validationResult);
            }

            // 8. Clasificar la respuesta.
            $classification = $this->classifyResponse($query, $searchResults, $response);

            // 9. Registrar analytics.
            $this->queryAnalytics->log([
                'query' => $query,
                'tenant_id' => $tenantFilters['tenant_id'] ?? NULL,
                'classification' => $classification,
                'confidence' => $validationResult['confidence'] ?? 0.5,
                'response_time_ms' => (microtime(TRUE) - $startTime) * 1000,
                'sources_count' => count($searchResults),
                'correlation_id' => $correlationId,
            ]);

            // 10. Registrar uso en FinOps para tracking de costes.
            try {
                $finopsTracker = \Drupal::service('ecosistema_jaraba_core.finops_tracking');
                // Extract usage.total_tokens from the LLM response.
                $tokensUsed = 0;
                if (!empty($response['raw_response'])) {
                    $rawOutput = $response['raw_response'];
                    if (is_array($rawOutput) && isset($rawOutput['usage']['total_tokens'])) {
                        $tokensUsed = (int) $rawOutput['usage']['total_tokens'];
                    }
                    elseif (is_object($rawOutput) && method_exists($rawOutput, 'getUsage')) {
                        $usage = $rawOutput->getUsage();
                        $tokensUsed = (int) ($usage['total_tokens'] ?? 0);
                    }
                }
                $finopsTracker->trackRagQuery(
                    $tenantFilters['tenant_id'] ?? 'unknown',
                    $tokensUsed
                );
            } catch (\Exception $e) {
                // Silent fail - no bloquear por tracking.
            }

            $result = [
                'response' => $response['text'],
                'sources' => $this->extractSources($enrichedContext),
                'confidence' => $validationResult['confidence'] ?? 0.5,
                'classification' => $classification,
            ];

            // AI-11: Guardar respuesta en caché (TTL 1 hora por defecto).
            // Solo cachear respuestas exitosas con confianza aceptable.
            if ($this->responseCache && ($result['confidence'] ?? 0) >= 0.5) {
                $cacheTtl = (int) ($config->get('cache.response_ttl') ?: 3600);
                $this->responseCache->set($cacheKey, $result, time() + $cacheTtl);
            }

            return $result;

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

            // AI-09: Formatear resultados aplicando priority multiplier.
            // Los answer capsules se indexan con priority 1.5x para boosting.
            $formatted = [];
            foreach ($results as $result) {
                $priority = (float) ($result['payload']['priority'] ?? 1.0);
                $formatted[] = [
                    'score' => $result['score'] * $priority,
                    'payload' => $result['payload'],
                ];
            }

            // Re-ordenar por score ajustado (mayor primero).
            usort($formatted, fn($a, $b) => $b['score'] <=> $a['score']);

            return $formatted;
        } catch (\Exception $e) {
            $this->log('Error en busqueda VDB', [
                'error' => $e->getMessage(),
            ], 'error');
            return [];
        }
    }

    /**
     * AI-08: Re-ranks search results using lightweight cross-encoder scoring.
     *
     * Uses reciprocal rank fusion between vector score and keyword overlap
     * to improve relevance without requiring an external re-ranking API.
     *
     * @param string $query
     *   The user query.
     * @param array $results
     *   Vector search results with 'score' and 'payload.text'.
     * @param int $topK
     *   Number of results to return after re-ranking.
     *
     * @return array
     *   Re-ranked and trimmed results.
     */
    protected function reRankResults(string $query, array $results, int $topK): array
    {
        if (count($results) <= $topK) {
            return $results;
        }

        // FIX-037: Use LLM re-ranker if available and configured.
        $config = $this->configFactory->get('jaraba_rag.settings');
        $strategy = $config->get('reranking.strategy') ?? 'keyword';

        if (($strategy === 'llm' || $strategy === 'hybrid') && $this->llmReRanker) {
            try {
                $candidates = [];
                foreach ($results as $r) {
                    $candidates[] = [
                        'text' => $r['payload']['text'] ?? '',
                        'score' => $r['score'],
                        'payload' => $r['payload'] ?? [],
                    ];
                }

                $reranked = $this->llmReRanker->reRank($query, $candidates, $topK);
                if (!empty($reranked)) {
                    // Map back to original format.
                    $mapped = [];
                    foreach ($reranked as $item) {
                        $mapped[] = [
                            'score' => $item['hybrid_score'] ?? $item['score'],
                            'payload' => $item['payload'] ?? $item,
                        ];
                    }
                    return $mapped;
                }
            } catch (\Exception $e) {
                $this->log('LLM re-ranker failed, falling back to keyword', [
                    'error' => $e->getMessage(),
                ], 'warning');
            }

            // If strategy is 'llm' only (no hybrid fallback), return trimmed.
            if ($strategy === 'llm') {
                return array_slice($results, 0, $topK);
            }
        }

        // Normalize query for keyword matching.
        $queryWords = array_unique(array_filter(
            preg_split('/\s+/', mb_strtolower(trim($query))),
            fn($w) => mb_strlen($w) > 2
        ));

        if (empty($queryWords)) {
            return array_slice($results, 0, $topK);
        }

        foreach ($results as &$result) {
            $text = mb_strtolower($result['payload']['text'] ?? '');
            $vectorScore = $result['score'];

            // Calculate keyword overlap score (0 to 1).
            $matchCount = 0;
            foreach ($queryWords as $word) {
                if (mb_strpos($text, $word) !== FALSE) {
                    $matchCount++;
                }
            }
            $keywordScore = count($queryWords) > 0 ? $matchCount / count($queryWords) : 0;

            // Boost for exact phrase match.
            $phraseBoost = mb_strpos($text, mb_strtolower($query)) !== FALSE ? 0.15 : 0;

            // Reciprocal rank fusion: combine vector and keyword scores.
            $result['rerank_score'] = ($vectorScore * 0.7) + ($keywordScore * 0.2) + $phraseBoost;
        }
        unset($result);

        usort($results, fn($a, $b) => $b['rerank_score'] <=> $a['rerank_score']);

        // Update scores to reflect re-ranking and trim.
        $reranked = array_slice($results, 0, $topK);
        foreach ($reranked as &$item) {
            $item['score'] = $item['rerank_score'];
            unset($item['rerank_score']);
        }

        return $reranked;
    }

    /**
     * AI-13: Applies temporal decay to penalize stale content.
     *
     * Uses exponential decay: score *= e^(-lambda * age_days).
     * Content older than 180 days loses ~50% of its score boost.
     *
     * @param array $results
     *   Search results with payload containing 'indexed_at'.
     *
     * @return array
     *   Results with adjusted scores.
     */
    protected function applyTemporalDecay(array $results): array
    {
        $now = time();
        // Half-life of ~180 days: lambda = ln(2) / 180 ≈ 0.00385.
        $lambda = 0.00385;

        foreach ($results as &$result) {
            $indexedAt = $result['payload']['indexed_at'] ?? NULL;
            if ($indexedAt) {
                $indexedTimestamp = strtotime($indexedAt);
                if ($indexedTimestamp) {
                    $ageDays = ($now - $indexedTimestamp) / 86400;
                    $decayFactor = exp(-$lambda * max(0, $ageDays));
                    // Blend: 80% original score + 20% decay-adjusted.
                    $result['score'] = $result['score'] * (0.8 + 0.2 * $decayFactor);
                }
            }
        }
        unset($result);

        return $results;
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
        // SEC-01: Sanitizar inputs antes de interpolar en el system prompt.
        // Previene inyección de instrucciones vía configuración de tenant.
        $tenantName = $this->sanitizePromptInput($tenantFilters['tenant_name'] ?? 'la tienda');

        // FIX-018 + FIX-027: Parametrizar persona según vertical del tenant.
        // Lookup uses raw value (before sanitization) to match VERTICAL_PERSONAS keys.
        $rawVertical = $tenantFilters['vertical'] ?? 'general';
        $persona = self::VERTICAL_PERSONAS[$rawVertical] ?? self::VERTICAL_PERSONAS['general'];

        $systemPrompt = <<<PROMPT
Eres el {$persona} de "{$tenantName}" en Jaraba Impact Platform.

## REGLAS INQUEBRANTABLES

1. SOLO CONTEXTO: Responde ÚNICAMENTE usando la información del CATÁLOGO proporcionado abajo. NUNCA inventes.

2. HONESTIDAD: Si no tienes información, responde:
   "No tengo esa información. ¿Puedo ayudarte con algo más?"

3. CITAS: Cada producto mencionado DEBE incluir enlace.
   Formato: [Nombre Producto](/producto/slug)

4. LÍMITE: Solo hablas del ámbito de "{$tenantName}".
   NO mencionas competidores ni plataformas externas.

5. TONO: Tu tono es cálido y profesional.
   Transmites calidad y cuidado en tus respuestas.

## CATÁLOGO Y CONOCIMIENTO
═══════════════════════════════════════════════════════════════
{$context}
═══════════════════════════════════════════════════════════════
PROMPT;

        // FIX-014 + FIX-018: Aplicar regla de identidad universal.
        return AIIdentityRule::apply($systemPrompt);
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
     * Sanitiza un input antes de interpolarlo en un prompt del sistema.
     *
     * SEC-01: Previene inyección de instrucciones (prompt injection) eliminando
     * patrones que podrían alterar el comportamiento del LLM.
     */
    protected function sanitizePromptInput(string $input): string
    {
        // Limitar longitud para evitar inyecciones extensas.
        $input = mb_substr($input, 0, 100);

        // Eliminar caracteres de control y delimitadores de prompt.
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);

        // Eliminar patrones comunes de inyección de prompts.
        $dangerousPatterns = [
            '/ignore\s+(all\s+)?(previous|above|prior)\s+(instructions?|rules?|prompts?)/i',
            '/you\s+are\s+now/i',
            '/new\s+instructions?:/i',
            '/system\s*:/i',
            '/\bignora\b.*\b(instrucciones|reglas|anteriores)\b/i',
            '/\bahora\s+eres\b/i',
            '/\bnuevas?\s+instrucciones?\b/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '[FILTERED]', $input);
        }

        return trim($input);
    }

    /**
     * Maneja casos donde se detectan alucinaciones.
     *
     * AI-04: Si hay contexto disponible, reintenta con prompt más restrictivo
     * antes de devolver el fallback genérico.
     */
    /**
     * Maneja casos donde se detectan alucinaciones.
     *
     * FIX-006: Corregidos dos errores fatales:
     * 1. $this->callLlm() no existe → usa generateResponse() (método real).
     * 2. $this->logger no existe → usa $this->loggerFactory->get().
     *
     * AI-04: Si hay contexto disponible, reintenta con prompt más restrictivo
     * antes de devolver el fallback genérico.
     */
    protected function handleHallucinations(string $query, array $context, array $validationResult): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $fallback = $config->get('grounding.fallback_message');
        $logger = $this->loggerFactory->get('jaraba_rag');

        // AI-04: Si hay contexto, reintentar con prompt estricto.
        if (!empty($context)) {
            try {
                // Construir contexto estricto con reglas anti-alucinación reforzadas.
                $strictContext = $context;

                // Usar generateResponse() con filtros de tenant vacíos (ya resueltos)
                // pero con el contexto estricto que fuerza grounding.
                $tenantFilters = $this->tenantContext->getSearchFilters();

                // Sobreescribir temporalmente el system prompt con reglas estrictas.
                $strictSystemPrompt = "IMPORTANTE: Responde SOLO con información que aparece TEXTUALMENTE en el catálogo. "
                    . "Si la información no está en el catálogo, responde: 'No tengo esa información.' "
                    . "NO hagas inferencias ni suposiciones.\n\n"
                    . $this->formatContextForPrompt($strictContext);

                // Llamar al LLM directamente usando el patrón de generateResponse().
                $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
                if ($defaults) {
                    $provider = $this->aiProvider->createInstance($defaults['provider_id']);

                    $chatInput = new ChatInput([
                        new ChatMessage('system', $strictSystemPrompt),
                        new ChatMessage('user', $query),
                    ]);

                    $modelId = $defaults['model_id'] ?? $config->get('llm.model') ?? 'gpt-4o-mini';
                    $result = $provider->chat($chatInput, $modelId, [
                        'temperature' => 0.1,
                        'max_tokens' => $config->get('llm.max_tokens') ?? 500,
                    ]);

                    $retryText = $result->getNormalized()->getText();

                    if (!empty($retryText)) {
                        $logger->info('AI-04: Regeneración con prompt estricto exitosa para query: @query', [
                            '@query' => mb_substr($query, 0, 100),
                        ]);
                        return [
                            'text' => $retryText,
                            'raw_response' => $result->getRawOutput(),
                        ];
                    }
                }
            }
            catch (\Exception $e) {
                $logger->warning('AI-04: Fallo en regeneración estricta: @error', [
                    '@error' => $e->getMessage(),
                ]);
            }
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
     * AI-16: Normalizes query for cache key to improve hit rate.
     *
     * Strips punctuation, lowercases, removes stopwords, sorts words.
     * "¿Qué aceite de oliva tenéis?" → "aceite oliva"
     */
    protected function normalizeQueryForCache(string $query): string
    {
        $normalized = mb_strtolower(trim($query));
        // Remove accents.
        $normalized = strtr($normalized, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        // Remove punctuation.
        $normalized = preg_replace('/[^\w\s]/u', '', $normalized);
        // Remove Spanish/English stopwords.
        $stopwords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'en',
            'con', 'para', 'por', 'que', 'como', 'es', 'son', 'se', 'al',
            'the', 'a', 'an', 'is', 'are', 'of', 'and', 'or', 'to', 'in',
            'me', 'te', 'lo', 'nos', 'les', 'hay', 'tiene', 'tienen',
            'teneis', 'cual', 'cuales', 'donde', 'cuando',
        ];
        $words = array_filter(
            preg_split('/\s+/', $normalized),
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopwords)
        );
        sort($words);
        return implode(' ', $words);
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
