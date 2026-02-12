<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_referral\Service\LeaderboardService;
use Drupal\jaraba_referral\Service\ReferralTrackingService;
use Drupal\jaraba_referral\Service\RewardProcessingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API REST del programa de referidos (v2).
 *
 * ESTRUCTURA:
 * Controlador que expone endpoints REST para el programa de referidos:
 * programas, códigos, recompensas, leaderboard, tracking y estadísticas.
 * Todos los endpoints devuelven JsonResponse con estructura data/meta/errors.
 *
 * LÓGICA:
 * GET  /api/v1/referral/programs     — lista programas activos del tenant
 * GET  /api/v1/referral/codes        — códigos del usuario autenticado
 * POST /api/v1/referral/codes        — genera un nuevo código de referido
 * GET  /api/v1/referral/leaderboard  — ranking de referidores del tenant
 * GET  /api/v1/referral/rewards      — recompensas del usuario autenticado
 * POST /api/v1/referral/track/click  — registra un click en enlace de referido
 * POST /api/v1/referral/track/signup — registra un signup via código
 * POST /api/v1/referral/track/conversion — registra una conversión
 * GET  /api/v1/referral/stats        — estadísticas del programa por tenant
 *
 * RELACIONES:
 * - ReferralApiController -> RewardProcessingService (consume)
 * - ReferralApiController -> LeaderboardService (consume)
 * - ReferralApiController -> ReferralTrackingService (consume)
 * - ReferralApiController -> jaraba_referral.routing.yml (definido en)
 */
class ReferralApiController extends ControllerBase {

  /**
   * Servicio de procesamiento de recompensas.
   */
  protected RewardProcessingService $rewardProcessing;

  /**
   * Servicio de leaderboard y gamificación.
   */
  protected LeaderboardService $leaderboard;

  /**
   * Servicio de tracking de referidos.
   */
  protected ReferralTrackingService $tracking;

  /**
   * Canal de log dedicado.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del controlador API de referidos.
   */
  public function __construct(
    RewardProcessingService $reward_processing,
    LeaderboardService $leaderboard,
    ReferralTrackingService $tracking,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->rewardProcessing = $reward_processing;
    $this->leaderboard = $leaderboard;
    $this->tracking = $tracking;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_referral.reward_processing'),
      $container->get('jaraba_referral.leaderboard'),
      $container->get('jaraba_referral.tracking'),
      $container->get('logger.channel.jaraba_referral'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Obtiene el ID del tenant actual desde el servicio de contexto.
   *
   * ESTRUCTURA: Método protegido auxiliar para aislamiento multi-tenant.
   *
   * LÓGICA: Intenta obtener el tenant_id del servicio TenantContextService.
   *   Si el servicio no está disponible, devuelve 0.
   *
   * @return int
   *   ID del tenant actual o 0 si no se puede determinar.
   */
  protected function getCurrentTenantId(): int {
    try {
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      if (method_exists($tenantContext, 'getCurrentTenantId')) {
        return (int) $tenantContext->getCurrentTenantId();
      }
    }
    catch (\Exception $e) {
      // Servicio opcional no disponible.
    }
    return 0;
  }

  /**
   * GET /api/v1/referral/programs — Lista programas activos del tenant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con los programas de referidos activos.
   */
  public function listPrograms(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $storage = $this->entityTypeManager->getStorage('referral_program');

      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('is_active', TRUE)
        ->sort('created', 'DESC');

      if ($tenantId > 0) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      $programs = $storage->loadMultiple($ids);

      $data = [];
      foreach ($programs as $program) {
        $data[] = [
          'id' => (int) $program->id(),
          'name' => $program->get('name')->value,
          'description' => $program->get('description')->value,
          'reward_type' => $program->get('reward_type')->value,
          'reward_value' => (float) ($program->get('reward_value')->value ?? 0),
          'reward_currency' => $program->get('reward_currency')->value ?: 'EUR',
          'referee_reward_type' => $program->get('referee_reward_type')->value,
          'referee_reward_value' => (float) ($program->get('referee_reward_value')->value ?? 0),
          'is_active' => (bool) $program->get('is_active')->value,
          'starts_at' => $program->get('starts_at')->value,
          'ends_at' => $program->get('ends_at')->value,
          'created' => (int) $program->get('created')->value,
        ];
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => ['count' => count($data)],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando programas de referidos: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error obteniendo programas de referidos.']],
      ], 500);
    }
  }

  /**
   * GET /api/v1/referral/codes — Lista códigos del usuario autenticado.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con los códigos del usuario actual.
   */
  public function listCodes(): JsonResponse {
    try {
      $uid = (int) $this->currentUser()->id();
      $storage = $this->entityTypeManager->getStorage('referral_code');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->sort('created', 'DESC')
        ->execute();

      $codes = !empty($ids) ? $storage->loadMultiple($ids) : [];

      $data = [];
      foreach ($codes as $code) {
        $data[] = [
          'id' => (int) $code->id(),
          'code' => $code->get('code')->value,
          'custom_url' => $code->get('custom_url')->value,
          'total_clicks' => (int) ($code->get('total_clicks')->value ?? 0),
          'total_signups' => (int) ($code->get('total_signups')->value ?? 0),
          'total_conversions' => (int) ($code->get('total_conversions')->value ?? 0),
          'total_revenue' => (float) ($code->get('total_revenue')->value ?? 0),
          'is_active' => (bool) $code->get('is_active')->value,
          'expires_at' => $code->get('expires_at')->value,
          'created' => (int) $code->get('created')->value,
        ];
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => ['count' => count($data)],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando códigos de referido: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error obteniendo códigos de referido.']],
      ], 500);
    }
  }

  /**
   * POST /api/v1/referral/codes — Genera un nuevo código de referido.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Petición HTTP con JSON body: { "program_id": int }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el código generado.
   */
  public function generateCode(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      $programId = $content['program_id'] ?? 0;
      $uid = (int) $this->currentUser()->id();
      $tenantId = $this->getCurrentTenantId();

      if (empty($programId)) {
        return new JsonResponse([
          'errors' => [['message' => 'El campo program_id es obligatorio.']],
        ], 400);
      }

      // Verificar que el programa existe y está activo.
      $programStorage = $this->entityTypeManager->getStorage('referral_program');
      $program = $programStorage->load($programId);

      if (!$program || !$program->get('is_active')->value) {
        return new JsonResponse([
          'errors' => [['message' => 'Programa de referidos no encontrado o inactivo.']],
        ], 404);
      }

      // Generar código alfanumérico único.
      $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      $codeString = '';
      $bytes = random_bytes(8);
      for ($i = 0; $i < 8; $i++) {
        $codeString .= $characters[ord($bytes[$i]) % strlen($characters)];
      }

      $codeStorage = $this->entityTypeManager->getStorage('referral_code');
      $codeEntity = $codeStorage->create([
        'tenant_id' => $tenantId > 0 ? $tenantId : ($program->get('tenant_id')->target_id ?? NULL),
        'program_id' => $programId,
        'user_id' => $uid,
        'code' => $codeString,
        'is_active' => TRUE,
      ]);
      $codeEntity->save();

      $baseUrl = $request->getSchemeAndHttpHost();

      return new JsonResponse([
        'data' => [
          'id' => (int) $codeEntity->id(),
          'code' => $codeString,
          'share_url' => $baseUrl . '/ref/' . $codeString,
          'program_id' => (int) $programId,
        ],
      ], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando código de referido: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error generando código de referido.']],
      ], 500);
    }
  }

  /**
   * GET /api/v1/referral/leaderboard — Ranking de referidores del tenant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el leaderboard.
   */
  public function getLeaderboard(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      $leaderboard = $this->leaderboard->getLeaderboard($tenantId);

      return new JsonResponse([
        'data' => $leaderboard,
        'meta' => ['count' => count($leaderboard), 'tenant_id' => $tenantId],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo leaderboard: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error obteniendo leaderboard.']],
      ], 500);
    }
  }

  /**
   * GET /api/v1/referral/rewards — Recompensas del usuario autenticado.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con las recompensas del usuario.
   */
  public function listRewards(): JsonResponse {
    try {
      $uid = (int) $this->currentUser()->id();
      $rewards = $this->rewardProcessing->getRewardsForUser($uid);

      return new JsonResponse([
        'data' => $rewards,
        'meta' => ['count' => count($rewards)],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando recompensas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error obteniendo recompensas.']],
      ], 500);
    }
  }

  /**
   * POST /api/v1/referral/track/click — Registra un click en enlace de referido.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Petición HTTP con JSON body: { "code": string }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado del tracking.
   */
  public function trackClick(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      $code = $content['code'] ?? '';

      if (empty($code)) {
        return new JsonResponse([
          'errors' => [['message' => 'El campo code es obligatorio.']],
        ], 400);
      }

      $context = [
        'ip' => $request->getClientIp(),
        'user_agent' => $request->headers->get('User-Agent', ''),
        'referer' => $request->headers->get('Referer', ''),
      ];

      $result = $this->tracking->trackClick($code, $context);

      return new JsonResponse([
        'data' => ['tracked' => $result],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando click: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error registrando click.']],
      ], 500);
    }
  }

  /**
   * POST /api/v1/referral/track/signup — Registra un signup via código.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Petición HTTP con JSON body: { "code": string, "user_id": int }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado del tracking.
   */
  public function trackSignup(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      $code = $content['code'] ?? '';
      $newUserId = (int) ($content['user_id'] ?? 0);

      if (empty($code) || $newUserId <= 0) {
        return new JsonResponse([
          'errors' => [['message' => 'Los campos code y user_id son obligatorios.']],
        ], 400);
      }

      $result = $this->tracking->trackSignup($code, $newUserId);

      return new JsonResponse([
        'data' => ['tracked' => $result],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando signup: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error registrando signup.']],
      ], 500);
    }
  }

  /**
   * POST /api/v1/referral/track/conversion — Registra una conversión.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Petición HTTP con JSON body: { "code": string, "user_id": int, "value": float }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado del tracking.
   */
  public function trackConversion(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      $code = $content['code'] ?? '';
      $userId = (int) ($content['user_id'] ?? 0);
      $value = (float) ($content['value'] ?? 0);

      if (empty($code) || $userId <= 0) {
        return new JsonResponse([
          'errors' => [['message' => 'Los campos code y user_id son obligatorios.']],
        ], 400);
      }

      $result = $this->tracking->trackConversion($code, $userId, $value);

      return new JsonResponse([
        'data' => ['tracked' => $result, 'value' => $value],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversión: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error registrando conversión.']],
      ], 500);
    }
  }

  /**
   * GET /api/v1/referrals — Lista referidos del usuario autenticado (v1 legacy).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con los referidos del usuario actual.
   */
  public function listReferrals(): JsonResponse {
    try {
      $uid = (int) $this->currentUser()->id();
      $tenantId = $this->getCurrentTenantId();
      $storage = $this->entityTypeManager->getStorage('referral');

      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('referrer_uid', $uid)
        ->sort('created', 'DESC');

      if ($tenantId > 0) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      $referrals = !empty($ids) ? $storage->loadMultiple($ids) : [];

      $data = [];
      foreach ($referrals as $referral) {
        $data[] = [
          'id' => (int) $referral->id(),
          'referral_code' => $referral->get('referral_code')->value,
          'status' => $referral->get('status')->value,
          'reward_type' => $referral->get('reward_type')->value ?? NULL,
          'reward_value' => (float) ($referral->get('reward_value')->value ?? 0),
          'created' => (int) $referral->get('created')->value,
        ];
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => ['count' => count($data)],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando referidos: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error obteniendo referidos.']],
      ], 500);
    }
  }

  /**
   * POST /api/v1/referrals/process — Procesa un referido pendiente.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Petición HTTP con JSON body: { "code": string, "action": string }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado del procesamiento.
   */
  public function processReferral(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      $code = $content['code'] ?? '';
      $action = $content['action'] ?? 'confirm';

      if (empty($code)) {
        return new JsonResponse([
          'errors' => [['message' => 'El campo code es obligatorio.']],
        ], 400);
      }

      $uid = (int) $this->currentUser()->id();
      $result = $this->rewardProcessing->processReferral($code, $uid, $action);

      return new JsonResponse([
        'data' => ['processed' => $result, 'action' => $action],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando referido: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error procesando referido.']],
      ], 500);
    }
  }

  /**
   * GET /api/v1/referrals/stats — Alias para getStats (ruta v1 legacy).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con estadísticas completas del programa.
   */
  public function stats(): JsonResponse {
    return $this->getStats();
  }

  /**
   * GET /api/v1/referral/stats — Estadísticas del programa por tenant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con estadísticas completas del programa.
   */
  public function getStats(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();

      $trackingStats = $this->tracking->getTrackingStats($tenantId);
      $leaderboardStats = $this->leaderboard->getLeaderboardStats($tenantId);
      $pendingRewards = $this->rewardProcessing->getPendingRewards($tenantId);

      return new JsonResponse([
        'data' => [
          'tracking' => $trackingStats,
          'leaderboard' => $leaderboardStats,
          'pending_rewards' => count($pendingRewards),
        ],
        'meta' => ['tenant_id' => $tenantId],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadísticas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'errors' => [['message' => 'Error obteniendo estadísticas.']],
      ], 500);
    }
  }

}
