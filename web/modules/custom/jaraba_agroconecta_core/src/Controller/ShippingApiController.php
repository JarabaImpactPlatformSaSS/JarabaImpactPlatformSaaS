<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_agroconecta_core\Service\AgroShippingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller para operaciones de envío.
 */
class ShippingApiController extends ControllerBase {

  public function __construct(
    protected AgroShippingService $shippingService,
    protected readonly TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_agroconecta_core.shipping_service'),
      $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-CONS-N10: Proper DI for tenant context.
    );
  }

  /**
   * GET /api/v1/agro/shipping/rates?items={JSON}&postal_code={PC}
   *
   * Devuelve las tarifas de envío para los items proporcionados.
   */
  /**
   * GET /api/v1/agro/shipping/zones
   */
  public function zones(): JsonResponse {
    try {
      $tenantId = (int) $this->tenantContext->getCurrentTenantId();
      $zones = \Drupal::entityTypeManager()
        ->getStorage('shipping_zone_agro')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->execute();
      $entities = \Drupal::entityTypeManager()
        ->getStorage('shipping_zone_agro')
        ->loadMultiple($zones);

      $data = [];
      foreach ($entities as $zone) {
        $data[] = [
          'id' => $zone->id(),
          'label' => $zone->label(),
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/agro/shipping/methods
   */
  public function methods(): JsonResponse {
    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        ['id' => 'standard', 'label' => 'Envío estándar'],
        ['id' => 'express', 'label' => 'Envío exprés'],
        ['id' => 'refrigerated', 'label' => 'Envío refrigerado'],
      ],
    ]);
  }

  /**
   * POST /api/v1/agro/shipping/calculate
   */
  public function calculate(Request $request): JsonResponse {
    return $this->getRates($request);
  }

  /**
   * GET /api/v1/agro/shipping/detect-zone
   */
  public function detectZone(Request $request): JsonResponse {
    $postalCode = $request->query->get('postal_code', '');
    if (empty($postalCode)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Código postal requerido.'], 400);
    }
    return new JsonResponse([
      'success' => TRUE,
      'data' => ['zone' => 'peninsula', 'postal_code' => $postalCode],
    ]);
  }

  public function getRates(Request $request): JsonResponse {
    $items_json = $request->query->get('items', '[]');
    $postal_code = $request->query->get('postal_code', '');
    
    if (empty($postal_code)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Código postal requerido.'], 400);
    }

    try {
      $items = json_decode($items_json, TRUE);
      $tenant_id = $this->tenantContext->getCurrentTenantId(); // AUDIT-CONS-N10: Proper DI for tenant context.
      
      $rates = $this->shippingService->calculateRates($items, $postal_code, (int) $tenant_id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $rates,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

}
