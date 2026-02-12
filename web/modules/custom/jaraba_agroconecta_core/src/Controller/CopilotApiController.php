<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\ProducerCopilotService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para el Copiloto IA de Productores.
 *
 * ENDPOINTS:
 * POST /api/v1/producer/copilot/chat                   → Conversación libre
 * POST /api/v1/producer/copilot/generate/description    → Generar descripción SEO
 * POST /api/v1/producer/copilot/generate/price          → Sugerencia de precio
 * POST /api/v1/producer/copilot/generate/review-response → Respuesta a reseña
 * GET  /api/v1/producer/copilot/conversations           → Historial conversaciones
 * GET  /api/v1/producer/copilot/conversations/{id}/messages → Mensajes
 */
class CopilotApiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected ProducerCopilotService $copilotService,
    ) {
    }

    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.copilot_service'),
        );
    }

    /**
     * Conversación libre con el copiloto.
     */
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $producerId = (int) ($data['producer_id'] ?? 0);
        $message = $data['message'] ?? '';
        $conversationId = $data['conversation_id'] ?? NULL;

        if (!$producerId || !$message) {
            return new JsonResponse(['error' => 'producer_id y message son requeridos'], 400);
        }

        $result = $this->copilotService->chat($producerId, $message, $conversationId ? (int) $conversationId : NULL);

        if (isset($result['error'])) {
            return new JsonResponse($result, 400);
        }

        return new JsonResponse($result);
    }

    /**
     * Generar descripción SEO para un producto.
     */
    public function generateDescription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $productId = (int) ($data['product_id'] ?? 0);

        if (!$productId) {
            return new JsonResponse(['error' => 'product_id es requerido'], 400);
        }

        $result = $this->copilotService->generateDescription($productId);

        if (isset($result['error'])) {
            return new JsonResponse($result, 400);
        }

        return new JsonResponse($result);
    }

    /**
     * Sugerencia de precio.
     */
    public function suggestPrice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $productId = (int) ($data['product_id'] ?? 0);

        if (!$productId) {
            return new JsonResponse(['error' => 'product_id es requerido'], 400);
        }

        $result = $this->copilotService->suggestPrice($productId);

        if (isset($result['error'])) {
            return new JsonResponse($result, 400);
        }

        return new JsonResponse($result);
    }

    /**
     * Borrador de respuesta a reseña.
     */
    public function reviewResponse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $reviewId = (int) ($data['review_id'] ?? 0);

        if (!$reviewId) {
            return new JsonResponse(['error' => 'review_id es requerido'], 400);
        }

        $result = $this->copilotService->respondToReview($reviewId);

        if (isset($result['error'])) {
            return new JsonResponse($result, 400);
        }

        return new JsonResponse($result);
    }

    /**
     * Historial de conversaciones de un productor.
     */
    public function conversations(Request $request): JsonResponse
    {
        $producerId = (int) $request->query->get('producer_id', 0);
        $limit = min((int) $request->query->get('limit', 20), 50);

        if (!$producerId) {
            return new JsonResponse(['error' => 'producer_id es requerido'], 400);
        }

        $conversations = $this->copilotService->getConversations($producerId, $limit);

        return new JsonResponse([
            'conversations' => $conversations,
            'total' => count($conversations),
        ]);
    }

    /**
     * Mensajes de una conversación.
     */
    public function messages(int $conversation_id): JsonResponse
    {
        $messages = $this->copilotService->getMessages($conversation_id);

        return new JsonResponse([
            'conversation_id' => $conversation_id,
            'messages' => $messages,
            'total' => count($messages),
        ]);
    }
}
