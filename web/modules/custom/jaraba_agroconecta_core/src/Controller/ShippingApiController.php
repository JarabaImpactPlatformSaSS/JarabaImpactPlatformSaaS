<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\ShippingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para envío.
 *
 * ENDPOINTS:
 * GET  /api/v1/agro/shipping/zones             → Zonas activas
 * GET  /api/v1/agro/shipping/methods            → Métodos activos
 * POST /api/v1/agro/shipping/calculate          → Calcular envío para pedido
 * POST /api/v1/agro/shipping/detect-zone        → Detectar zona por CP
 */
class ShippingApiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected ShippingService $shippingService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.shipping_service'),
        );
    }

    /**
     * Lista zonas de envío activas.
     *
     * GET /api/v1/agro/shipping/zones?tenant_id=1
     */
    public function zones(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $zones = $this->shippingService->getZones($tenantId);

        return new JsonResponse([
            'zones' => $zones,
            'total' => count($zones),
        ]);
    }

    /**
     * Lista métodos de envío activos.
     *
     * GET /api/v1/agro/shipping/methods?tenant_id=1
     */
    public function methods(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $methods = $this->shippingService->getMethods($tenantId);

        return new JsonResponse([
            'methods' => $methods,
            'total' => count($methods),
        ]);
    }

    /**
     * Calcula opciones de envío para un pedido.
     *
     * POST /api/v1/agro/shipping/calculate
     * Body: {
     *   "tenant_id": 1,
     *   "postal_code": "41001",
     *   "country": "ES",
     *   "subtotal": 50.00,
     *   "total_weight": 3.5,
     *   "needs_cold_chain": false
     * }
     */
    public function calculate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (!$data || empty($data['postal_code'])) {
            return new JsonResponse(['error' => 'Código postal requerido.'], 400);
        }

        $tenantId = (int) ($data['tenant_id'] ?? 1);
        $postalCode = (string) $data['postal_code'];
        $country = (string) ($data['country'] ?? 'ES');
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $totalWeight = (float) ($data['total_weight'] ?? 0);
        $needsColdChain = (bool) ($data['needs_cold_chain'] ?? FALSE);

        $result = $this->shippingService->calculateShipping(
            $tenantId,
            $postalCode,
            $country,
            $subtotal,
            $totalWeight,
            $needsColdChain
        );

        return new JsonResponse($result);
    }

    /**
     * Detecta la zona de envío por código postal.
     *
     * POST /api/v1/agro/shipping/detect-zone
     * Body: {"tenant_id": 1, "postal_code": "41001", "country": "ES"}
     */
    public function detectZone(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (!$data || empty($data['postal_code'])) {
            return new JsonResponse(['error' => 'Código postal requerido.'], 400);
        }

        $tenantId = (int) ($data['tenant_id'] ?? 1);
        $postalCode = (string) $data['postal_code'];
        $country = (string) ($data['country'] ?? 'ES');

        $zone = $this->shippingService->detectZone($tenantId, $postalCode, $country);

        if (!$zone) {
            return new JsonResponse([
                'found' => FALSE,
                'message' => 'No se encontró una zona de envío para este código postal.',
            ], 404);
        }

        return new JsonResponse([
            'found' => TRUE,
            'zone' => $zone,
        ]);
    }

}
