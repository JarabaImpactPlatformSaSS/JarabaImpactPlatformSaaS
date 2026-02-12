<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\PromotionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para promociones y cupones.
 *
 * ENDPOINTS:
 * GET  /api/v1/agro/promotions          → Promociones activas
 * GET  /api/v1/agro/promotions/{id}     → Detalle de promoción
 * POST /api/v1/agro/promotions/evaluate → Evaluar carrito
 * POST /api/v1/agro/coupons/validate    → Validar cupón
 * POST /api/v1/agro/coupons/redeem      → Canjear cupón
 */
class PromotionApiController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        protected PromotionService $promotionService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.promotion_service'),
        );
    }

    /**
     * Lista promociones activas.
     *
     * GET /api/v1/agro/promotions?tenant_id=1
     */
    public function list(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);

        $promotions = $this->promotionService->getActivePromotions($tenantId);

        return new JsonResponse([
            'promotions' => $promotions,
            'total' => count($promotions),
        ]);
    }

    /**
     * Detalle de una promoción.
     *
     * GET /api/v1/agro/promotions/{promotion_id}
     */
    public function detail(int $promotion_id): JsonResponse
    {
        $promotion = $this->promotionService->getPromotion($promotion_id);

        if (!$promotion) {
            return new JsonResponse(['error' => 'Promoción no encontrada.'], 404);
        }

        return new JsonResponse($promotion);
    }

    /**
     * Evalúa qué promociones aplican a un carrito.
     *
     * POST /api/v1/agro/promotions/evaluate
     * Body: {"tenant_id": 1, "items": [...], "subtotal": 50.00}
     */
    public function evaluate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON inválido.'], 400);
        }

        $tenantId = (int) ($data['tenant_id'] ?? 1);
        $items = $data['items'] ?? [];
        $subtotal = (float) ($data['subtotal'] ?? 0);

        if (empty($items) && $subtotal <= 0) {
            return new JsonResponse(['error' => 'El carrito está vacío.'], 400);
        }

        $applicable = $this->promotionService->evaluateCart($tenantId, $items, $subtotal);

        // Calcular descuento total.
        $totalDiscount = 0;
        $freeShipping = FALSE;
        foreach ($applicable as $item) {
            $totalDiscount += $item['discount']['amount'] ?? 0;
            if ($item['discount']['free_shipping'] ?? FALSE) {
                $freeShipping = TRUE;
            }
        }

        return new JsonResponse([
            'applicable_promotions' => $applicable,
            'total_discount' => round($totalDiscount, 2),
            'free_shipping' => $freeShipping,
            'final_subtotal' => round(max(0, $subtotal - $totalDiscount), 2),
        ]);
    }

    /**
     * Valida un código de cupón.
     *
     * POST /api/v1/agro/coupons/validate
     * Body: {"code": "VERANO2026", "tenant_id": 1, "subtotal": 50.00}
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (!$data || empty($data['code'])) {
            return new JsonResponse(['error' => 'Código de cupón requerido.'], 400);
        }

        $code = (string) $data['code'];
        $tenantId = (int) ($data['tenant_id'] ?? 1);
        $subtotal = (float) ($data['subtotal'] ?? 0);

        $result = $this->promotionService->validateCoupon($code, $tenantId, $subtotal);

        $statusCode = $result['valid'] ? 200 : 422;

        return new JsonResponse($result, $statusCode);
    }

    /**
     * Canjea un cupón (se llama al confirmar pedido).
     *
     * POST /api/v1/agro/coupons/redeem
     * Body: {"code": "VERANO2026", "tenant_id": 1}
     */
    public function redeemCoupon(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (!$data || empty($data['code'])) {
            return new JsonResponse(['error' => 'Código de cupón requerido.'], 400);
        }

        $code = (string) $data['code'];
        $tenantId = (int) ($data['tenant_id'] ?? 1);

        $success = $this->promotionService->redeemCoupon($code, $tenantId);

        if (!$success) {
            return new JsonResponse(['success' => FALSE, 'message' => 'No se pudo canjear el cupón.'], 422);
        }

        return new JsonResponse(['success' => TRUE, 'message' => 'Cupón canjeado correctamente.']);
    }

}
