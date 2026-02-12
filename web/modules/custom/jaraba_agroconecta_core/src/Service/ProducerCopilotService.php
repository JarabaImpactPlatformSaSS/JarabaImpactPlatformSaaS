<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Agent\ProducerCopilotAgent;

/**
 * Servicio del Copiloto IA para Productores.
 *
 * RESPONSABILIDADES:
 * - Orquesta la interacción entre el controller y el ProducerCopilotAgent.
 * - Gestiona el ciclo de vida de conversaciones y mensajes.
 * - Enriquece el contexto con datos reales de entidades Drupal.
 * - Detecta intents para enrutar acciones al agente.
 *
 * INTEGRACIÓN:
 * Delega toda la generación de contenido IA al ProducerCopilotAgent
 * (jaraba_ai_agents), que usa SmartBaseAgent con Model Routing,
 * Brand Voice, Observability y Unified Prompt Builder.
 */
class ProducerCopilotService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected ProducerCopilotAgent $copilotAgent,
    ) {
    }

    // ===================================================
    // Generación de Contenido (delegado al Agente)
    // ===================================================

    /**
     * Genera una descripción SEO-optimizada para un producto.
     */
    public function generateDescription(int $productId, ?string $tenantId = NULL): array
    {
        $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
        if (!$product) {
            return ['error' => 'Producto no encontrado'];
        }

        // Configurar contexto multi-tenant.
        if ($tenantId) {
            $this->copilotAgent->setTenantContext($tenantId, 'agroalimentario');
        }

        // Construir contexto real desde la entidad.
        $context = $this->buildProductContext($product);
        $context['product_name'] = $product->label() ?? 'Producto';

        // Ejecutar acción del agente.
        $result = $this->copilotAgent->execute('generate_description', $context);

        // Enriquecer resultado con metadatos de la entidad.
        $result['product_id'] = $productId;
        if (isset($result['routing'])) {
            $result['model'] = $result['routing']['model'] ?? 'unknown';
        }

        return $result;
    }

    /**
     * Sugiere un precio competitivo basado en datos de mercado.
     */
    public function suggestPrice(int $productId, ?string $tenantId = NULL): array
    {
        $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
        if (!$product) {
            return ['error' => 'Producto no encontrado'];
        }

        if ($tenantId) {
            $this->copilotAgent->setTenantContext($tenantId, 'agroalimentario');
        }

        $currentPrice = (float) ($product->get('price')->value ?? 0);
        $productName = $product->label() ?? 'Producto';

        // Buscar precios de competidores.
        $competitorPrices = $this->getCompetitorPrices($productId);

        $context = [
            'product_name' => $productName,
            'current_price' => $currentPrice,
            'competitor_prices' => $competitorPrices,
            'has_traceability' => $this->hasTraceability($product),
        ];

        $result = $this->copilotAgent->execute('suggest_price', $context);
        $result['product_id'] = $productId;

        // Agregar datos de mercado al resultado.
        if (!empty($competitorPrices)) {
            $result['market_data'] = [
                'avg_price' => round(array_sum($competitorPrices) / count($competitorPrices), 2),
                'min_price' => round(min($competitorPrices), 2),
                'max_price' => round(max($competitorPrices), 2),
                'competitors_counted' => count($competitorPrices),
            ];
        }

        return $result;
    }

    /**
     * Genera un borrador de respuesta a una reseña.
     */
    public function respondToReview(int $reviewId, ?string $tenantId = NULL): array
    {
        try {
            $review = $this->entityTypeManager->getStorage('review_agro')->load($reviewId);
        } catch (\Exception $e) {
            return ['error' => 'Reseña no encontrada'];
        }

        if (!$review) {
            return ['error' => 'Reseña no encontrada'];
        }

        if ($tenantId) {
            $this->copilotAgent->setTenantContext($tenantId, 'agroalimentario');
        }

        $context = [
            'review_rating' => (int) ($review->get('rating')->value ?? 3),
            'review_comment' => $review->get('comment')->value ?? '',
        ];

        $result = $this->copilotAgent->execute('respond_review', $context);
        $result['review_id'] = $reviewId;

        return $result;
    }

    // ===================================================
    // Conversación Chat
    // ===================================================

    /**
     * Procesa un mensaje en una conversación del copiloto.
     */
    public function chat(int $producerId, string $message, ?int $conversationId = NULL, ?string $tenantId = NULL): array
    {
        $convStorage = $this->entityTypeManager->getStorage('copilot_conversation_agro');
        $msgStorage = $this->entityTypeManager->getStorage('copilot_message_agro');

        if ($tenantId) {
            $this->copilotAgent->setTenantContext($tenantId, 'agroalimentario');
        }

        // Crear o recuperar conversación.
        if ($conversationId) {
            $conversation = $convStorage->load($conversationId);
            if (!$conversation) {
                return ['error' => 'Conversación no encontrada'];
            }
        } else {
            $conversation = $convStorage->create([
                'producer_id' => $producerId,
                'title' => mb_substr($message, 0, 100),
                'intent' => $this->detectIntent($message),
                'uid' => \Drupal::currentUser()->id(),
            ]);
            $conversation->save();
        }

        // Guardar mensaje del usuario.
        $userMsg = $msgStorage->create([
            'conversation_id' => $conversation->id(),
            'role' => 'user',
            'content' => $message,
            'uid' => \Drupal::currentUser()->id(),
        ]);
        $userMsg->save();

        // Construir historial para contexto.
        $conversationHistory = $this->buildConversationHistory($conversationId ? (int) $conversation->id() : 0);

        // Ejecutar chat vía agente IA.
        $startTime = microtime(TRUE);
        $agentResult = $this->copilotAgent->execute('chat', [
            'message' => $message,
            'conversation_history' => $conversationHistory,
        ]);
        $latency = (int) ((microtime(TRUE) - $startTime) * 1000);

        // Extraer respuesta del agente.
        $responseText = '';
        $intent = $this->detectIntent($message);
        $modelUsed = 'unknown';

        if ($agentResult['success'] && isset($agentResult['data'])) {
            $responseText = $agentResult['data']['response'] ?? ($agentResult['data']['text'] ?? '');
            $intent = $agentResult['data']['detected_intent'] ?? $intent;
            $modelUsed = $agentResult['routing']['model'] ?? ($agentResult['agent_id'] ?? 'producer_copilot');
        } else {
            $responseText = 'Lo siento, ha ocurrido un error al procesar tu mensaje. Por favor, inténtalo de nuevo.';
        }

        // Estimar tokens.
        $tokensIn = (int) ceil(mb_strlen($message) / 4);
        $tokensOut = (int) ceil(mb_strlen($responseText) / 4);

        // Guardar respuesta del copiloto.
        $assistantMsg = $msgStorage->create([
            'conversation_id' => $conversation->id(),
            'role' => 'assistant',
            'content' => $responseText,
            'intent_detected' => $intent,
            'model_used' => $modelUsed,
            'tokens_input' => $tokensIn,
            'tokens_output' => $tokensOut,
            'latency_ms' => $latency,
            'uid' => \Drupal::currentUser()->id(),
        ]);
        $assistantMsg->save();

        // Actualizar contadores de conversación.
        $msgCount = (int) ($conversation->get('message_count')->value ?? 0);
        $conversation->set('message_count', $msgCount + 2);
        $conversation->set('intent', $intent);
        $totalTokIn = (int) ($conversation->get('total_tokens_input')->value ?? 0);
        $totalTokOut = (int) ($conversation->get('total_tokens_output')->value ?? 0);
        $conversation->set('total_tokens_input', $totalTokIn + $tokensIn);
        $conversation->set('total_tokens_output', $totalTokOut + $tokensOut);
        $conversation->save();

        return [
            'conversation_id' => (int) $conversation->id(),
            'message' => [
                'role' => 'assistant',
                'content' => $responseText,
                'intent' => $intent,
                'model' => $modelUsed,
                'latency_ms' => $latency,
            ],
        ];
    }

    /**
     * Obtiene el historial de conversaciones de un productor.
     */
    public function getConversations(int $producerId, int $limit = 20): array
    {
        $storage = $this->entityTypeManager->getStorage('copilot_conversation_agro');
        $ids = $storage->getQuery()
            ->condition('producer_id', $producerId)
            ->condition('is_archived', FALSE)
            ->sort('changed', 'DESC')
            ->range(0, $limit)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $conversations = $storage->loadMultiple($ids);
        $result = [];
        foreach ($conversations as $conv) {
            $result[] = [
                'id' => (int) $conv->id(),
                'title' => $conv->get('title')->value ?? '',
                'intent' => $conv->get('intent')->value ?? 'general',
                'message_count' => (int) ($conv->get('message_count')->value ?? 0),
                'updated' => $conv->get('changed')->value,
            ];
        }

        return $result;
    }

    /**
     * Obtiene los mensajes de una conversación.
     */
    public function getMessages(int $conversationId): array
    {
        $storage = $this->entityTypeManager->getStorage('copilot_message_agro');
        $ids = $storage->getQuery()
            ->condition('conversation_id', $conversationId)
            ->sort('created', 'ASC')
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $messages = $storage->loadMultiple($ids);
        $result = [];
        foreach ($messages as $msg) {
            $result[] = [
                'id' => (int) $msg->id(),
                'role' => $msg->get('role')->value ?? 'user',
                'content' => $msg->get('content')->value ?? '',
                'model' => $msg->get('model_used')->value ?? NULL,
                'latency_ms' => (int) ($msg->get('latency_ms')->value ?? 0),
                'timestamp' => $msg->get('created')->value,
            ];
        }

        return $result;
    }

    // ===================================================
    // Métodos internos
    // ===================================================

    protected function detectIntent(string $message): string
    {
        $message = mb_strtolower($message);
        $intents = [
            'description' => ['descripción', 'describir', 'copy', 'texto', 'presentación'],
            'pricing' => ['precio', 'tarifa', 'coste', 'competencia', 'cara', 'barato'],
            'review_response' => ['reseña', 'valoración', 'comentario', 'responder'],
            'seo' => ['seo', 'posicionamiento', 'google', 'visibilidad', 'keywords'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== FALSE) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * Construye contexto real desde una entidad producto.
     */
    protected function buildProductContext(object $product): array
    {
        $context = [
            'product_name' => $product->label() ?? '',
        ];

        // Extraer campos disponibles de forma segura.
        try {
            if ($product->hasField('price')) {
                $context['product_price'] = (float) ($product->get('price')->value ?? 0);
            }
            if ($product->hasField('category')) {
                $context['product_category'] = $product->get('category')->entity?->label() ?? '';
            }
        } catch (\Exception $e) {
            // Campos no disponibles, usar contexto parcial.
        }

        return $context;
    }

    /**
     * Obtiene precios de competidores para comparación.
     */
    protected function getCompetitorPrices(int $excludeProductId): array
    {
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('id', $excludeProductId, '!=')
            ->range(0, 20)
            ->execute();

        $prices = [];
        if (!empty($ids)) {
            $competitors = $storage->loadMultiple($ids);
            foreach ($competitors as $comp) {
                try {
                    $p = (float) ($comp->get('price')->value ?? 0);
                    if ($p > 0) {
                        $prices[] = $p;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $prices;
    }

    /**
     * Comprueba si un producto tiene trazabilidad.
     */
    protected function hasTraceability(object $product): bool
    {
        try {
            if ($product->hasField('traceability_enabled')) {
                return (bool) ($product->get('traceability_enabled')->value ?? FALSE);
            }
        } catch (\Exception $e) {
            // Campo no disponible.
        }
        return FALSE;
    }

    /**
     * Construye el historial de conversación como texto para contexto.
     */
    protected function buildConversationHistory(int $conversationId): string
    {
        if (!$conversationId) {
            return '';
        }

        $storage = $this->entityTypeManager->getStorage('copilot_message_agro');
        $ids = $storage->getQuery()
            ->condition('conversation_id', $conversationId)
            ->sort('created', 'ASC')
            ->range(0, 10) // Últimos 10 mensajes para contexto.
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return '';
        }

        $messages = $storage->loadMultiple($ids);
        $lines = [];
        foreach ($messages as $msg) {
            $role = $msg->get('role')->value ?? 'user';
            $content = mb_substr($msg->get('content')->value ?? '', 0, 500);
            $label = $role === 'assistant' ? 'COPILOTO' : 'PRODUCTOR';
            $lines[] = "{$label}: {$content}";
        }

        return implode("\n", $lines);
    }
}
