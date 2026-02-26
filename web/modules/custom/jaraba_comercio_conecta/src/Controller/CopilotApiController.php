<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_comercio_conecta\Service\MerchantCopilotService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller REST API para el copiloto de ComercioConecta.
 *
 * FIX-017: REST API endpoints completa para MerchantCopilotAgent.
 *
 * ENDPOINTS:
 * GET|POST /api/v1/copilot/comercio/proactive         -> Acciones proactivas
 * POST /api/v1/merchant/copilot/generate/description   -> Generar descripcion
 * POST /api/v1/merchant/copilot/generate/price         -> Sugerir precio
 * POST /api/v1/merchant/copilot/generate/social-post   -> Post redes sociales
 * POST /api/v1/merchant/copilot/generate/flash-offer   -> Oferta flash
 * POST /api/v1/merchant/copilot/generate/review-response -> Responder resena
 * POST /api/v1/merchant/copilot/generate/email-promo   -> Email promocional
 *
 * @see \Drupal\jaraba_comercio_conecta\Service\MerchantCopilotService
 * @see \Drupal\jaraba_ai_agents\Agent\MerchantCopilotAgent
 * @see \Drupal\jaraba_agroconecta_core\Controller\CopilotApiController
 */
class CopilotApiController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected MerchantCopilotService $copilotService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.merchant_copilot'),
    );
  }

  // ==========================================================================
  // Proactive Actions (existing endpoint — preserved from original)
  // ==========================================================================

  /**
   * GET|POST /api/v1/copilot/comercio/proactive
   *
   * GET: Checks for pending proactive actions for the current user.
   * POST: Dismisses a proactive action.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   GET: { has_action: bool, action?: array }
   *   POST: { success: bool }
   */
  public function proactive(Request $request): JsonResponse {
    $userId = (int) $this->currentUser()->id();
    if (!$userId) {
      return new JsonResponse(['has_action' => FALSE]);
    }

    // POST: Dismiss a proactive action.
    if ($request->isMethod('POST')) {
      $data = json_decode($request->getContent(), TRUE);
      $ruleId = $data['rule_id'] ?? '';
      if ($ruleId && \Drupal::hasService('ecosistema_jaraba_core.comercioconecta_journey_progression')) {
        \Drupal::service('ecosistema_jaraba_core.comercioconecta_journey_progression')
          ->dismissAction($userId, $ruleId);
      }
      return new JsonResponse(['success' => TRUE]);
    }

    // GET: Check for pending proactive action.
    if (!\Drupal::hasService('ecosistema_jaraba_core.comercioconecta_journey_progression')) {
      return new JsonResponse(['has_action' => FALSE]);
    }

    try {
      $action = \Drupal::service('ecosistema_jaraba_core.comercioconecta_journey_progression')
        ->getPendingAction($userId);

      if ($action) {
        return new JsonResponse([
          'has_action' => TRUE,
          'action' => $action,
        ]);
      }
    }
    catch (\Exception $e) {
      // Non-critical — proactive actions are optional.
    }

    return new JsonResponse(['has_action' => FALSE]);
  }

  // ==========================================================================
  // FIX-017: MerchantCopilotAgent API Endpoints
  // ==========================================================================

  /**
   * POST /api/v1/merchant/copilot/generate/description
   *
   * Genera una descripcion atractiva para un producto.
   *
   * Request body JSON:
   *   - product_id (int, required): ID del producto.
   *   - tenant_id (string, optional): ID del tenant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del agente o error 400.
   */
  public function generateDescription(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $productId = (int) ($data['product_id'] ?? 0);

    if (!$productId) {
      return new JsonResponse([
        'error' => (string) $this->t('product_id is required.'),
      ], 400);
    }

    $tenantId = $data['tenant_id'] ?? NULL;
    $result = $this->copilotService->generateDescription($productId, $tenantId);

    if (isset($result['success']) && $result['success'] === FALSE) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

  /**
   * POST /api/v1/merchant/copilot/generate/price
   *
   * Sugiere un precio competitivo para un producto.
   *
   * Request body JSON:
   *   - product_id (int, required): ID del producto.
   *   - tenant_id (string, optional): ID del tenant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del agente o error 400.
   */
  public function suggestPrice(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $productId = (int) ($data['product_id'] ?? 0);

    if (!$productId) {
      return new JsonResponse([
        'error' => (string) $this->t('product_id is required.'),
      ], 400);
    }

    $tenantId = $data['tenant_id'] ?? NULL;
    $result = $this->copilotService->suggestPrice($productId, $tenantId);

    if (isset($result['success']) && $result['success'] === FALSE) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

  /**
   * POST /api/v1/merchant/copilot/generate/social-post
   *
   * Genera un post para redes sociales.
   *
   * Request body JSON:
   *   - product_name (string, required): Nombre del producto.
   *   - platform (string, required): Plataforma (instagram, facebook).
   *   - message (string, optional): Mensaje adicional.
   *   - tone (string, optional): Tono del post.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del agente o error 400.
   */
  public function generateSocialPost(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $productName = $data['product_name'] ?? '';
    $platform = $data['platform'] ?? '';

    if (!$productName || !$platform) {
      return new JsonResponse([
        'error' => (string) $this->t('product_name and platform are required.'),
      ], 400);
    }

    $result = $this->copilotService->generateSocialPost($data);

    if (isset($result['success']) && $result['success'] === FALSE) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

  /**
   * POST /api/v1/merchant/copilot/generate/flash-offer
   *
   * Genera una oferta flash para un producto.
   *
   * Request body JSON:
   *   - product_id (int, required): ID del producto.
   *   - discount (int, optional): Porcentaje de descuento (default 20).
   *   - duration (int, optional): Duracion en horas (default 24).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del agente o error 400.
   */
  public function generateFlashOffer(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $productId = (int) ($data['product_id'] ?? 0);

    if (!$productId) {
      return new JsonResponse([
        'error' => (string) $this->t('product_id is required.'),
      ], 400);
    }

    $offerParams = [
      'discount' => $data['discount'] ?? 20,
      'duration' => $data['duration'] ?? 24,
    ];

    $result = $this->copilotService->generateFlashOffer($productId, $offerParams);

    if (isset($result['success']) && $result['success'] === FALSE) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

  /**
   * POST /api/v1/merchant/copilot/generate/review-response
   *
   * Genera respuesta a una resena de cliente.
   *
   * Request body JSON:
   *   - review_text (string, required): Texto de la resena.
   *   - tenant_id (string, optional): ID del tenant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del agente o error 400.
   */
  public function respondReview(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $reviewText = $data['review_text'] ?? '';

    if (!$reviewText) {
      return new JsonResponse([
        'error' => (string) $this->t('review_text is required.'),
      ], 400);
    }

    $tenantId = $data['tenant_id'] ?? NULL;
    $result = $this->copilotService->respondReview($reviewText, $tenantId);

    if (isset($result['success']) && $result['success'] === FALSE) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

  /**
   * POST /api/v1/merchant/copilot/generate/email-promo
   *
   * Genera un email promocional.
   *
   * Request body JSON:
   *   - product_name (string, required): Nombre del producto.
   *   - offer_details (string, required): Detalles de la oferta.
   *   - audience (string, optional): Audiencia objetivo.
   *   - tone (string, optional): Tono del email.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del agente o error 400.
   */
  public function generateEmailPromo(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $productName = $data['product_name'] ?? '';
    $offerDetails = $data['offer_details'] ?? '';

    if (!$productName || !$offerDetails) {
      return new JsonResponse([
        'error' => (string) $this->t('product_name and offer_details are required.'),
      ], 400);
    }

    $result = $this->copilotService->generateEmailPromo($data);

    if (isset($result['success']) && $result['success'] === FALSE) {
      return new JsonResponse($result, 400);
    }

    return new JsonResponse($result);
  }

}
