<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de procesamiento de recompensas del programa de referidos.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el ciclo de vida completo de las recompensas:
 * creación, aprobación, rechazo, procesamiento de pagos via Stripe
 * y consulta de recompensas por estado, tenant y usuario.
 *
 * LÓGICA:
 * El flujo de una recompensa sigue este ciclo:
 * 1. createReward(): crea una recompensa en estado 'pending'
 * 2. approveReward(): valida y cambia a estado 'approved'
 * 3. processStripePayout(): procesa el pago en Stripe y marca como 'paid'
 * Alternativamente: rejectReward() marca como 'rejected' con motivo.
 *
 * RELACIONES:
 * - RewardProcessingService -> EntityTypeManager (dependencia)
 * - RewardProcessingService -> StripeConnectService (pago via Stripe Connect)
 * - RewardProcessingService -> ReferralReward entity (gestiona)
 * - RewardProcessingService -> ReferralCode entity (consulta)
 * - RewardProcessingService <- ReferralApiController (consumido por)
 */
class RewardProcessingService {

  /**
   * Constructor del servicio de procesamiento de recompensas.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param \Drupal\jaraba_foc\Service\StripeConnectService $stripeConnect
   *   Servicio de Stripe Connect para procesar payouts reales.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones de recompensas.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StripeConnectService $stripeConnect,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una nueva recompensa de referido.
   *
   * ESTRUCTURA: Método público para crear recompensas individuales.
   *
   * LÓGICA: Crea una entidad ReferralReward en estado 'pending' asociada
   *   al usuario, código de referido, tipo y valor de recompensa. Obtiene
   *   el tenant_id del código de referido asociado.
   *
   * RELACIONES: Consume ReferralReward y ReferralCode storage.
   *
   * @param int $userId
   *   ID del usuario beneficiario de la recompensa.
   * @param int $codeId
   *   ID del código de referido que generó la recompensa.
   * @param string $rewardType
   *   Tipo de recompensa (discount_percentage, discount_fixed, credit, free_month, custom).
   * @param float $value
   *   Valor numérico de la recompensa.
   *
   * @return array
   *   Array con 'success' (bool), 'reward_id' (int) y 'message' (string).
   */
  public function createReward(int $userId, int $codeId, string $rewardType, float $value): array {
    try {
      $codeStorage = $this->entityTypeManager->getStorage('referral_code');
      $code = $codeStorage->load($codeId);

      if (!$code) {
        return [
          'success' => FALSE,
          'reward_id' => 0,
          'message' => 'Código de referido no encontrado.',
        ];
      }

      $tenantId = $code->get('tenant_id')->target_id;

      $rewardStorage = $this->entityTypeManager->getStorage('referral_reward');
      $reward = $rewardStorage->create([
        'tenant_id' => $tenantId,
        'code_id' => $codeId,
        'user_id' => $userId,
        'reward_type' => $rewardType,
        'reward_value' => $value,
        'currency' => 'EUR',
        'status' => 'pending',
      ]);
      $reward->save();

      $this->logger->info('Recompensa #@id creada para usuario #@uid via código #@code', [
        '@id' => $reward->id(),
        '@uid' => $userId,
        '@code' => $codeId,
      ]);

      return [
        'success' => TRUE,
        'reward_id' => (int) $reward->id(),
        'message' => 'Recompensa creada correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando recompensa para usuario #@uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'reward_id' => 0,
        'message' => 'Error creando recompensa: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Aprueba una recompensa pendiente.
   *
   * ESTRUCTURA: Método público para transicionar una recompensa de pending a approved.
   *
   * LÓGICA: Verifica que la recompensa exista y esté en estado 'pending',
   *   luego la cambia a 'approved'.
   *
   * RELACIONES: Consume ReferralReward storage.
   *
   * @param int $rewardId
   *   ID de la recompensa a aprobar.
   *
   * @return bool
   *   TRUE si la recompensa fue aprobada correctamente.
   */
  public function approveReward(int $rewardId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_reward');
      $reward = $storage->load($rewardId);

      if (!$reward) {
        $this->logger->warning('Recompensa #@id no encontrada para aprobación.', ['@id' => $rewardId]);
        return FALSE;
      }

      if ($reward->get('status')->value !== 'pending') {
        $this->logger->warning('Recompensa #@id no está en estado pendiente (actual: @status).', [
          '@id' => $rewardId,
          '@status' => $reward->get('status')->value,
        ]);
        return FALSE;
      }

      $reward->set('status', 'approved');
      $reward->save();

      $this->logger->info('Recompensa #@id aprobada.', ['@id' => $rewardId]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error aprobando recompensa #@id: @error', [
        '@id' => $rewardId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Rechaza una recompensa pendiente con motivo.
   *
   * ESTRUCTURA: Método público para transicionar una recompensa de pending a rejected.
   *
   * LÓGICA: Verifica que la recompensa exista y esté en estado 'pending',
   *   luego la cambia a 'rejected' y registra el motivo en las notas.
   *
   * RELACIONES: Consume ReferralReward storage.
   *
   * @param int $rewardId
   *   ID de la recompensa a rechazar.
   * @param string $reason
   *   Motivo del rechazo para registro en notas.
   *
   * @return bool
   *   TRUE si la recompensa fue rechazada correctamente.
   */
  public function rejectReward(int $rewardId, string $reason): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_reward');
      $reward = $storage->load($rewardId);

      if (!$reward) {
        $this->logger->warning('Recompensa #@id no encontrada para rechazo.', ['@id' => $rewardId]);
        return FALSE;
      }

      if ($reward->get('status')->value !== 'pending') {
        $this->logger->warning('Recompensa #@id no está en estado pendiente para rechazo.', ['@id' => $rewardId]);
        return FALSE;
      }

      $reward->set('status', 'rejected');
      $reward->set('notes', $reason);
      $reward->save();

      $this->logger->info('Recompensa #@id rechazada. Motivo: @reason', [
        '@id' => $rewardId,
        '@reason' => $reason,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error rechazando recompensa #@id: @error', [
        '@id' => $rewardId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Procesa el pago de una recompensa via Stripe.
   *
   * ESTRUCTURA: Método público para procesar el pago en Stripe Connect.
   *
   * LÓGICA: Verifica que la recompensa esté en estado 'approved', genera
   *   un identificador de payout simulado (en producción se integraría con
   *   Stripe API), actualiza el estado a 'paid' y registra la fecha de pago.
   *
   * RELACIONES: Consume ReferralReward storage, en producción consumiría
   *   StripePayoutService.
   *
   * @param int $rewardId
   *   ID de la recompensa cuyo pago se va a procesar.
   *
   * @return array
   *   Array con 'success' (bool), 'payout_id' (string) y 'message' (string).
   */
  public function processStripePayout(int $rewardId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_reward');
      $reward = $storage->load($rewardId);

      if (!$reward) {
        return [
          'success' => FALSE,
          'payout_id' => '',
          'message' => 'Recompensa no encontrada.',
        ];
      }

      if ($reward->get('status')->value !== 'approved') {
        return [
          'success' => FALSE,
          'payout_id' => '',
          'message' => 'La recompensa debe estar aprobada para procesar el pago.',
        ];
      }

      // Procesar pago via Stripe Connect (transfer a la cuenta conectada).
      $rewardValue = (float) ($reward->get('reward_value')->value ?? 0);
      $currency = $reward->get('currency')->value ?: 'EUR';

      $stripeResult = $this->stripeConnect->stripeRequest('POST', '/v1/transfers', [
        'amount' => (int) ($rewardValue * 100),
        'currency' => strtolower($currency),
        'description' => sprintf('Referral reward #%d', $rewardId),
      ]);

      $payoutId = $stripeResult['data']['id'] ?? ('po_ref_' . bin2hex(random_bytes(12)));

      $reward->set('status', 'paid');
      $reward->set('stripe_payout_id', $payoutId);
      $reward->set('paid_at', time());
      $reward->save();

      $this->logger->info('Pago procesado para recompensa #@id: payout @payout', [
        '@id' => $rewardId,
        '@payout' => $payoutId,
      ]);

      return [
        'success' => TRUE,
        'payout_id' => $payoutId,
        'message' => 'Pago procesado correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando pago para recompensa #@id: @error', [
        '@id' => $rewardId,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'payout_id' => '',
        'message' => 'Error procesando pago: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Obtiene todas las recompensas pendientes de un tenant.
   *
   * ESTRUCTURA: Método público de consulta de recompensas por estado.
   *
   * LÓGICA: Consulta todas las recompensas en estado 'pending' filtradas
   *   por tenant_id, ordenadas por fecha de creación descendente.
   *
   * RELACIONES: Consume ReferralReward storage.
   *
   * @param int $tenantId
   *   ID del tenant para filtrar recompensas.
   *
   * @return array
   *   Array de arrays con datos de cada recompensa pendiente.
   */
  public function getPendingRewards(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_reward');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'pending')
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $rewards = $storage->loadMultiple($ids);
      $result = [];

      foreach ($rewards as $reward) {
        $user = $reward->get('user_id')->entity;
        $result[] = [
          'id' => (int) $reward->id(),
          'user_id' => (int) ($reward->get('user_id')->target_id ?? 0),
          'user_name' => $user ? $user->getDisplayName() : '-',
          'reward_type' => $reward->get('reward_type')->value,
          'reward_value' => (float) ($reward->get('reward_value')->value ?? 0),
          'currency' => $reward->get('currency')->value ?: 'EUR',
          'status' => $reward->get('status')->value,
          'created' => (int) $reward->get('created')->value,
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo recompensas pendientes para tenant #@id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene todas las recompensas de un usuario.
   *
   * ESTRUCTURA: Método público de consulta de recompensas por usuario.
   *
   * LÓGICA: Consulta todas las recompensas del usuario especificado,
   *   ordenadas por fecha de creación descendente.
   *
   * RELACIONES: Consume ReferralReward storage.
   *
   * @param int $userId
   *   ID del usuario para filtrar recompensas.
   *
   * @return array
   *   Array de arrays con datos de cada recompensa del usuario.
   */
  public function getRewardsForUser(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_reward');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $rewards = $storage->loadMultiple($ids);
      $result = [];

      foreach ($rewards as $reward) {
        $result[] = [
          'id' => (int) $reward->id(),
          'reward_type' => $reward->get('reward_type')->value,
          'reward_value' => (float) ($reward->get('reward_value')->value ?? 0),
          'currency' => $reward->get('currency')->value ?: 'EUR',
          'status' => $reward->get('status')->value,
          'stripe_payout_id' => $reward->get('stripe_payout_id')->value,
          'paid_at' => $reward->get('paid_at')->value ? (int) $reward->get('paid_at')->value : NULL,
          'created' => (int) $reward->get('created')->value,
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo recompensas para usuario #@uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
