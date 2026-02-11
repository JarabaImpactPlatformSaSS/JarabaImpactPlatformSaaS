<?php

namespace Drupal\jaraba_referral\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_referral\Service\ReferralManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API REST del programa de referidos.
 *
 * ESTRUCTURA:
 * Controlador que expone endpoints REST para el programa de referidos.
 * Todos los endpoints devuelven JsonResponse con estructura data/meta/errors.
 *
 * LÓGICA:
 * GET /api/v1/referrals — listado de referidos del usuario autenticado
 * POST /api/v1/referrals/generate — generar código de referido
 * POST /api/v1/referrals/process — procesar un referido entrante
 * GET /api/v1/referrals/stats — estadísticas admin por tenant
 *
 * RELACIONES:
 * - ReferralApiController -> ReferralManagerService (consume)
 * - ReferralApiController -> jaraba_referral.routing.yml (definido en)
 */
class ReferralApiController extends ControllerBase {

  protected ReferralManagerService $referralManager;

  public function __construct(
    ReferralManagerService $referral_manager,
    AccountProxyInterface $current_user,
  ) {
    $this->referralManager = $referral_manager;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_referral.manager'),
      $container->get('current_user'),
    );
  }

  public function listReferrals(): JsonResponse {
    try {
      $uid = (int) $this->currentUser->id();
      $referrals = $this->referralManager->getMyReferrals($uid);

      $items = [];
      foreach ($referrals as $referral) {
        $referred = $referral->get('referred_uid')->entity;
        $items[] = [
          'id' => (int) $referral->id(),
          'code' => $referral->get('referral_code')->value,
          'status' => $referral->get('status')->value,
          'referred_name' => $referred ? $referred->getDisplayName() : NULL,
          'reward_type' => $referral->get('reward_type')->value,
          'reward_value' => (float) ($referral->get('reward_value')->value ?? 0),
          'created' => date('c', $referral->get('created')->value),
        ];
      }

      return new JsonResponse([
        'data' => $items,
        'meta' => ['count' => count($items)],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'errors' => [['message' => $e->getMessage()]],
      ], 500);
    }
  }

  public function generateCode(): JsonResponse {
    try {
      $uid = (int) $this->currentUser->id();
      $referral = $this->referralManager->generateCode($uid);
      $code = $referral->get('referral_code')->value;
      $base_url = \Drupal::request()->getSchemeAndHttpHost();

      return new JsonResponse([
        'data' => [
          'code' => $code,
          'share_url' => $base_url . '/ref/' . $code,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'errors' => [['message' => $e->getMessage()]],
      ], 500);
    }
  }

  public function processReferral(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      $code = $content['code'] ?? '';
      $uid = (int) $this->currentUser->id();

      if (empty($code)) {
        return new JsonResponse([
          'errors' => [['message' => $this->t('El código de referido es obligatorio.')->__toString()]],
        ], 400);
      }

      $referral = $this->referralManager->processReferral($code, $uid);

      return new JsonResponse([
        'data' => [
          'status' => 'confirmed',
          'referral_id' => (int) $referral->id(),
        ],
      ]);
    }
    catch (\RuntimeException $e) {
      return new JsonResponse([
        'errors' => [['message' => $e->getMessage()]],
      ], 422);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'errors' => [['message' => $this->t('Error procesando referido.')->__toString()]],
      ], 500);
    }
  }

  public function stats(): JsonResponse {
    try {
      $tenant_id = 0;
      try {
        $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        if (method_exists($tenant_context, 'getCurrentTenantId')) {
          $tenant_id = (int) $tenant_context->getCurrentTenantId();
        }
      }
      catch (\Exception $e) {
        // Optional service not available.
      }

      $stats = $this->referralManager->getReferralStats($tenant_id);

      return new JsonResponse([
        'data' => $stats,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'errors' => [['message' => $e->getMessage()]],
      ], 500);
    }
  }

}
