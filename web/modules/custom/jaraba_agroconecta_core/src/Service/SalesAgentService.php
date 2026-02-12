<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_agroconecta_core\Entity\SalesConversationAgro;
use Drupal\jaraba_agroconecta_core\Entity\SalesMessageAgro;
use Drupal\jaraba_agroconecta_core\Entity\CustomerPreferenceAgro;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal del Sales Agent para consumidores.
 *
 * Orquesta conversaciones, búsqueda semántica, recomendaciones
 * personalizadas, cross-sell, y recuperación de carritos.
 * Referencia: Doc 68 — Sales Agent v1.
 */
class SalesAgentService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Procesa un mensaje del consumidor y genera respuesta IA.
     *
     * @param string $sessionId
     *   Identificador de sesión (cookie o generado).
     * @param string $message
     *   Mensaje del consumidor.
     * @param array $context
     *   Contexto adicional: page, product_id, cart_id, tenant_id.
     *
     * @return array
     *   Respuesta con keys: response, conversation_id, suggestions, products.
     */
    public function chat(string $sessionId, string $message, array $context = []): array
    {
        $startTime = microtime(true);

        // 1. Obtener o crear conversación.
        $conversation = $this->getOrCreateConversation($sessionId, $context);

        // 2. Guardar mensaje del usuario.
        $this->saveMessage($conversation, 'user', $message);

        // 3. Detectar intent.
        $intent = $this->detectIntent($message, $context);

        // 4. Generar respuesta según intent.
        $response = $this->generateResponse($conversation, $message, $intent, $context);

        // 5. Guardar respuesta del asistente con métricas.
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
        $this->saveMessage($conversation, 'assistant', $response['text'], [
            'intent' => $intent,
            'products_shown' => $response['products'] ?? [],
            'actions_taken' => $response['actions'] ?? [],
            'latency_ms' => $latencyMs,
        ]);

        // 6. Actualizar conversación.
        $conversation->set('messages_count', $conversation->getMessagesCount() + 2);
        $conversation->set('last_intent', $intent);
        if (!empty($response['products'])) {
            $shown = (int) $conversation->get('products_shown')->value;
            $conversation->set('products_shown', $shown + count($response['products']));
        }
        $conversation->save();

        return [
            'response' => $response['text'],
            'conversation_id' => (int) $conversation->id(),
            'suggestions' => $response['suggestions'] ?? [],
            'products' => $response['products'] ?? [],
            'intent' => $intent,
        ];
    }

    /**
     * Genera recomendaciones personalizadas para un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param array $context
     *   Contexto: page, category, viewed_products.
     *
     * @return array
     *   Array de productos recomendados con razón.
     */
    public function getRecommendations(int $userId, array $context = []): array
    {
        // Cargar preferencias del usuario.
        $preferences = $this->getUserPreferences($userId);

        // Consultar productos matching preferencias.
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->sort('created', 'DESC')
            ->range(0, 8);

        // Aplicar filtros de preferencias.
        if (!empty($preferences['origin'])) {
            $query->condition('origin', $preferences['origin']);
        }

        $ids = $query->execute();
        $products = $ids ? $storage->loadMultiple($ids) : [];

        $recommendations = [];
        foreach ($products as $product) {
            $recommendations[] = [
                'id' => (int) $product->id(),
                'name' => $product->label(),
                'reason' => $this->getRecommendationReason($product, $preferences),
            ];
        }

        return $recommendations;
    }

    /**
     * Genera un mensaje de recuperación de carrito.
     *
     * @param int $cartId
     *   ID del carrito abandonado.
     *
     * @return array
     *   Mensaje de recuperación con incentivo.
     */
    public function recoverCart(int $cartId): array
    {
        $this->logger->info('Recuperación de carrito iniciada: @cart', ['@cart' => $cartId]);

        return [
            'message' => '¡Hola! He visto que dejaste algunos productos en tu carrito. ¿Te gustaría completar tu pedido? Tenemos una oferta especial para ti. 🛒',
            'cart_id' => $cartId,
            'incentive' => [
                'type' => 'discount',
                'value' => 10,
                'code' => 'VUELVE10',
            ],
        ];
    }

    /**
     * Consulta estado de un pedido via agente.
     *
     * @param int $orderId
     *   ID del pedido.
     *
     * @return array
     *   Estado del pedido en formato conversacional.
     */
    public function getOrderStatus(int $orderId): array
    {
        $storage = $this->entityTypeManager->getStorage('order_agro');
        $order = $storage->load($orderId);

        if (!$order) {
            return [
                'found' => FALSE,
                'message' => 'No he encontrado ese pedido. ¿Puedes verificar el número?',
            ];
        }

        return [
            'found' => TRUE,
            'order_id' => $orderId,
            'status' => $order->get('status')->value ?? 'unknown',
            'message' => sprintf(
                'Tu pedido #%d está en estado: %s. Si necesitas más información, estoy aquí para ayudarte.',
                $orderId,
                $order->get('status')->value ?? 'desconocido'
            ),
        ];
    }

    /**
     * Obtiene o crea una conversación para la sesión.
     */
    protected function getOrCreateConversation(string $sessionId, array $context): SalesConversationAgro
    {
        $storage = $this->entityTypeManager->getStorage('sales_conversation_agro');

        // Buscar conversación activa existente.
        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('session_id', $sessionId)
            ->condition('state', 'active')
            ->sort('created', 'DESC')
            ->range(0, 1)
            ->execute();

        if (!empty($ids)) {
            return $storage->load(reset($ids));
        }

        // Crear nueva conversación.
        $conversation = $storage->create([
            'session_id' => $sessionId,
            'customer_id' => $this->currentUser->isAuthenticated() ? $this->currentUser->id() : NULL,
            'tenant_id' => $context['tenant_id'] ?? NULL,
            'channel' => $context['channel'] ?? 'web',
            'state' => 'active',
        ]);
        $conversation->save();

        $this->logger->info('Nueva conversación Sales Agent: @session', ['@session' => $sessionId]);

        return $conversation;
    }

    /**
     * Guarda un mensaje en la conversación.
     */
    protected function saveMessage(
        SalesConversationAgro $conversation,
        string $role,
        string $content,
        array $meta = []
    ): SalesMessageAgro {
        $storage = $this->entityTypeManager->getStorage('sales_message_agro');

        $data = [
            'conversation_id' => $conversation->id(),
            'role' => $role,
            'content' => $content,
        ];

        if (!empty($meta['intent'])) {
            $data['intent'] = $meta['intent'];
        }
        if (!empty($meta['products_shown'])) {
            $data['products_shown'] = json_encode($meta['products_shown']);
        }
        if (!empty($meta['actions_taken'])) {
            $data['actions_taken'] = json_encode($meta['actions_taken']);
        }
        if (isset($meta['latency_ms'])) {
            $data['latency_ms'] = $meta['latency_ms'];
        }

        $message = $storage->create($data);
        $message->save();

        return $message;
    }

    /**
     * Detecta el intent del mensaje del usuario.
     */
    protected function detectIntent(string $message, array $context): string
    {
        $lower = mb_strtolower($message);

        // Reglas heurísticas básicas (sustituir por NLU/LLM en producción).
        if (preg_match('/(busca|encuentra|quiero|necesito|tiene)/u', $lower)) {
            return 'search';
        }
        if (preg_match('/(recomienda|sugiere|qué me|cuál|mejor)/u', $lower)) {
            return 'recommend';
        }
        if (preg_match('/(carrito|añadir|comprar|pedir)/u', $lower)) {
            return 'add_to_cart';
        }
        if (preg_match('/(pedido|envío|seguimiento|tracking|estado)/u', $lower)) {
            return 'order_status';
        }
        if (preg_match('/(hola|buenos|buenas|hey)/u', $lower)) {
            return 'greeting';
        }
        if (preg_match('/(adiós|hasta luego|chao|gracias)/u', $lower)) {
            return 'farewell';
        }
        if (preg_match('/(queja|problema|mal|devol)/u', $lower)) {
            return 'complaint';
        }

        return 'browse';
    }

    /**
     * Genera respuesta del agente según intent.
     */
    protected function generateResponse(
        SalesConversationAgro $conversation,
        string $message,
        string $intent,
        array $context
    ): array {
        // En producción, esto llamaría al SmartBaseAgent con el prompt system.
        // Por ahora, respuestas template por intent.
        return match ($intent) {
            'greeting' => [
                'text' => '¡Hola! 👋 Soy tu asistente de AgroConecta. Puedo ayudarte a encontrar productos artesanales, recomendar según tus gustos, o resolver dudas sobre tus pedidos. ¿En qué puedo ayudarte?',
                'suggestions' => ['Ver productos destacados', '¿Qué me recomiendas?', 'Tengo una duda'],
            ],
            'search' => [
                'text' => 'Voy a buscar productos que coincidan con lo que necesitas. Un momento...',
                'suggestions' => ['Ver más opciones', 'Filtrar por precio', 'Solo ecológicos'],
                'products' => $this->searchProducts($message, $context),
            ],
            'recommend' => [
                'text' => 'Basándome en tus preferencias, te recomiendo estos productos artesanales. ¡Todos son de productores locales verificados!',
                'suggestions' => ['Más opciones', 'Solo aceite', 'Regalos'],
                'products' => $this->getRecommendations(
                    $this->currentUser->isAuthenticated() ? (int) $this->currentUser->id() : 0,
                    $context
                ),
            ],
            'order_status' => [
                'text' => '¿Cuál es el número de tu pedido? Lo busco enseguida.',
                'suggestions' => ['Mis pedidos recientes', 'Contactar soporte'],
            ],
            'farewell' => [
                'text' => '¡Hasta pronto! 🌿 Ha sido un placer ayudarte. Si necesitas algo más, aquí estaré.',
                'suggestions' => [],
            ],
            'complaint' => [
                'text' => 'Lamento que hayas tenido un inconveniente. Cuéntame qué ha ocurrido y haré todo lo posible por ayudarte a resolverlo.',
                'suggestions' => ['Problema con mi pedido', 'Producto dañado', 'Hablar con soporte'],
            ],
            default => [
                'text' => 'Entiendo. ¿Te gustaría que te muestre productos populares de nuestros productores locales?',
                'suggestions' => ['Ver catálogo', '¿Qué me recomiendas?', 'Estado de mi pedido'],
            ],
        };
    }

    /**
     * Búsqueda básica de productos por texto.
     */
    protected function searchProducts(string $query, array $context): array
    {
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->condition('title', '%' . $query . '%', 'LIKE')
            ->sort('created', 'DESC')
            ->range(0, 5)
            ->execute();

        $products = [];
        foreach ($storage->loadMultiple($ids) as $product) {
            $products[] = [
                'id' => (int) $product->id(),
                'name' => $product->label(),
            ];
        }

        return $products;
    }

    /**
     * Obtiene preferencias del usuario como array.
     */
    protected function getUserPreferences(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $storage = $this->entityTypeManager->getStorage('customer_preference_agro');
        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('customer_id', $userId)
            ->condition('is_active', TRUE)
            ->sort('confidence', 'DESC')
            ->execute();

        $prefs = [];
        foreach ($storage->loadMultiple($ids) as $pref) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\CustomerPreferenceAgro $pref */
            $prefs[$pref->getPreferenceKey()] = $pref->getPreferenceValue();
        }

        return $prefs;
    }

    /**
     * Genera razón de recomendación para un producto.
     */
    protected function getRecommendationReason(object $product, array $preferences): string
    {
        if (!empty($preferences['origin']) && str_contains($product->label(), $preferences['origin'])) {
            return 'De tu región favorita';
        }
        return 'Producto destacado';
    }
}
