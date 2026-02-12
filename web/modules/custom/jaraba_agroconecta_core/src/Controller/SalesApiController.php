<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_agroconecta_core\Service\CartRecoveryService;
use Drupal\jaraba_agroconecta_core\Service\CrossSellEngine;
use Drupal\jaraba_agroconecta_core\Service\SalesAgentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST para el Sales Agent de consumidores.
 *
 * 12 endpoints: chat, conversations, search, recommendations,
 * cart add, coupon apply, order status, preferences, rate,
 * cross-sell, upsell, recovery stats.
 * Referencia: Doc 68 — Sales Agent v1 + Fase 10 Cross-Sell & Cart Recovery.
 */
class SalesApiController extends ControllerBase
{

    public function __construct(
        protected SalesAgentService $salesAgent,
        protected CrossSellEngine $crossSellEngine,
        protected CartRecoveryService $cartRecovery,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.sales_agent'),
            $container->get('jaraba_agroconecta_core.cross_sell_engine'),
            $container->get('jaraba_agroconecta_core.cart_recovery'),
        );
    }

    /**
     * POST /api/v1/sales/chat
     *
     * Enviar mensaje al Sales Agent y recibir respuesta.
     */
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['message'])) {
            return new JsonResponse(['error' => 'message_required'], 400);
        }

        $sessionId = $data['session_id'] ?? $request->getSession()->getId();
        $context = [
            'tenant_id' => $data['tenant_id'] ?? NULL,
            'page' => $data['page'] ?? NULL,
            'product_id' => $data['product_id'] ?? NULL,
            'cart_id' => $data['cart_id'] ?? NULL,
            'channel' => $data['channel'] ?? 'web',
        ];

        $result = $this->salesAgent->chat($sessionId, $data['message'], $context);

        return new JsonResponse($result);
    }

    /**
     * GET /api/v1/sales/conversations
     *
     * Listar conversaciones del usuario actual.
     */
    public function listConversations(Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('sales_conversation_agro');
        $userId = $this->currentUser()->id();

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->sort('changed', 'DESC')
            ->range(0, 20);

        $ids = $query->execute();
        $conversations = [];

        foreach ($storage->loadMultiple($ids) as $conv) {
            $conversations[] = [
                'id' => (int) $conv->id(),
                'session_id' => $conv->get('session_id')->value,
                'state' => $conv->get('state')->value,
                'channel' => $conv->get('channel')->value,
                'messages_count' => (int) $conv->get('messages_count')->value,
                'last_intent' => $conv->get('last_intent')->value,
                'created' => $conv->get('created')->value,
                'changed' => $conv->get('changed')->value,
            ];
        }

        return new JsonResponse(['conversations' => $conversations]);
    }

    /**
     * POST /api/v1/sales/search
     *
     * Búsqueda semántica de productos via agente.
     */
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $query = $data['query'] ?? '';

        if (empty($query)) {
            return new JsonResponse(['error' => 'query_required'], 400);
        }

        // Delegar al servicio de ventas para búsqueda contextual.
        $result = $this->salesAgent->chat(
            $request->getSession()->getId(),
            $query,
            ['intent_override' => 'search']
        );

        return new JsonResponse([
            'products' => $result['products'] ?? [],
            'suggestions' => $result['suggestions'] ?? [],
        ]);
    }

    /**
     * GET /api/v1/sales/recommendations
     *
     * Recomendaciones personalizadas para el usuario.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $userId = (int) $this->currentUser()->id();
        $context = [
            'category' => $request->query->get('category'),
            'page' => $request->query->get('page'),
        ];

        $recommendations = $this->salesAgent->getRecommendations($userId, $context);

        return new JsonResponse(['recommendations' => $recommendations]);
    }

    /**
     * POST /api/v1/sales/cart/add
     *
     * Añadir producto al carrito desde el chat.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['product_id'])) {
            return new JsonResponse(['error' => 'product_id_required'], 400);
        }

        // TODO: Integrar con CartService real.
        return new JsonResponse([
            'success' => TRUE,
            'message' => $this->t('Producto añadido al carrito'),
            'product_id' => (int) $data['product_id'],
            'quantity' => (int) ($data['quantity'] ?? 1),
        ]);
    }

    /**
     * POST /api/v1/sales/coupon/apply
     *
     * Aplicar cupón desde el chat del agente.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['code'])) {
            return new JsonResponse(['error' => 'code_required'], 400);
        }

        // TODO: Integrar con CouponService real.
        return new JsonResponse([
            'success' => TRUE,
            'message' => $this->t('Cupón aplicado correctamente'),
            'code' => $data['code'],
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);
    }

    /**
     * GET /api/v1/sales/order/{order_id}/status
     *
     * Estado del pedido consultado via agente.
     */
    public function orderStatus(int $order_id): JsonResponse
    {
        $result = $this->salesAgent->getOrderStatus($order_id);
        return new JsonResponse($result);
    }

    /**
     * GET /api/v1/sales/preferences
     *
     * Preferencias del consumidor actual.
     */
    public function getPreferences(): JsonResponse
    {
        $userId = (int) $this->currentUser()->id();

        if ($userId <= 0) {
            return new JsonResponse(['preferences' => []]);
        }

        $storage = $this->entityTypeManager()->getStorage('customer_preference_agro');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->condition('is_active', TRUE)
            ->execute();

        $preferences = [];
        foreach ($storage->loadMultiple($ids) as $pref) {
            $preferences[] = [
                'type' => $pref->get('preference_type')->value,
                'key' => $pref->get('preference_key')->value,
                'value' => $pref->get('preference_value')->value,
                'source' => $pref->get('source')->value,
                'confidence' => (float) $pref->get('confidence')->value,
            ];
        }

        return new JsonResponse(['preferences' => $preferences]);
    }

    /**
     * POST /api/v1/sales/conversations/{conversation_id}/rate
     *
     * Valorar una conversación (1-5 estrellas).
     */
    public function rateConversation(int $conversation_id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $rating = (int) ($data['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            return new JsonResponse(['error' => 'rating_1_5_required'], 400);
        }

        $storage = $this->entityTypeManager()->getStorage('sales_conversation_agro');
        $conversation = $storage->load($conversation_id);

        if (!$conversation) {
            return new JsonResponse(['error' => 'conversation_not_found'], 404);
        }

        $conversation->set('satisfaction_rating', $rating);
        $conversation->save();

        return new JsonResponse([
            'success' => TRUE,
            'conversation_id' => $conversation_id,
            'rating' => $rating,
        ]);
    }

    /**
     * Sugerencias de venta cruzada.
     */
    public function crossSellSuggestions(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $productId = (int) ($data['product_id'] ?? 0);
        $cartItems = $data['cart_items'] ?? [];
        $cartTotal = (float) ($data['cart_total'] ?? 0);
        $trigger = $data['trigger'] ?? 'post-add';

        if (!$productId) {
            return new JsonResponse(['error' => 'product_id es requerido'], 400);
        }

        try {
            $suggestions = $this->crossSellEngine->generateCrossSellSuggestions(
                $productId, $cartItems, $cartTotal, $trigger
            );
            return new JsonResponse(['success' => TRUE, 'data' => $suggestions, 'total' => count($suggestions)]);
        }
        catch (\Exception $e) {
            return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sugerencias de upsell basadas en carrito.
     */
    public function upsellSuggestions(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $cartItems = $data['cart_items'] ?? [];
        $cartTotal = (float) ($data['cart_total'] ?? 0);

        try {
            $suggestions = $this->crossSellEngine->getUpsellSuggestions($cartItems, $cartTotal);
            return new JsonResponse(['success' => TRUE, 'data' => $suggestions, 'total' => count($suggestions)]);
        }
        catch (\Exception $e) {
            return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Estadísticas de recuperación de carritos.
     */
    public function recoveryStats(Request $request): JsonResponse
    {
        $days = min((int) $request->query->get('days', 30), 90);

        try {
            $stats = $this->cartRecovery->getRecoveryStats($days);
            return new JsonResponse(['success' => TRUE, 'data' => $stats]);
        }
        catch (\Exception $e) {
            return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
        }
    }
}
