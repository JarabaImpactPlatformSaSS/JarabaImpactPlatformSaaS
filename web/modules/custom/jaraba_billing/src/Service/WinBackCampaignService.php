<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Win-back email campaign for churned customers.
 *
 * Sends 4 automated email touches after a tenant cancels their subscription:
 *   - Day 7:  "Te echamos de menos" + 1 month free offer
 *   - Day 30: Feature announcement (what they're missing)
 *   - Day 60: 50% discount for 3 months
 *   - Day 90: Final survey "¿Has encontrado lo que buscabas?"
 *
 * Benchmark: Well-executed win-back recovers 8-15% of churned users within 90d.
 *
 * DIRECTRICES:
 * - TENANT-001: All queries filtered by tenant
 * - QUIZ-FOLLOWUP-DRIP-001: Same dedup pattern as QuizFollowUpCron
 * - UPDATE-HOOK-CATCH-001: catch(\Throwable) everywhere
 *
 * @see docs/implementacion/2026-03-20_Plan_Implementacion_Pricing_4Tiers_Clase_Mundial_v1.md §6.5
 */
class WinBackCampaignService {

  /**
   * Win-back phases with timing (days after cancellation).
   */
  protected const PHASES = [
    'welcome_back_7d'  => ['days' => 7, 'subject' => 'Te echamos de menos — vuelve con 1 mes gratis'],
    'feature_update_30d' => ['days' => 30, 'subject' => 'Novedades que no te puedes perder'],
    'discount_60d'     => ['days' => 60, 'subject' => '50% de descuento durante 3 meses'],
    'final_survey_90d' => ['days' => 90, 'subject' => '¿Nos ayudas con tu opinion?'],
  ];

  /**
   * State key prefix for dedup tracking.
   */
  protected const STATE_PREFIX = 'winback:';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Processes win-back emails for all cancelled tenants.
   *
   * Called from hook_cron. Checks each cancelled tenant against the phase
   * timeline and sends the appropriate email if not already sent.
   *
   * @param int $batchSize
   *   Maximum number of emails to send per cron run.
   *
   * @return int
   *   Number of emails sent.
   */
  public function processWinBackEmails(int $batchSize = 25): int {
    $sent = 0;

    try {
      $tenantStorage = $this->entityTypeManager->getStorage('tenant');
      $ids = $tenantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('subscription_status', 'cancelled')
        ->range(0, $batchSize * 2)
        ->execute();

      if ($ids === []) {
        return 0;
      }

      $tenants = $tenantStorage->loadMultiple($ids);

      foreach ($tenants as $tenant) {
        if ($sent >= $batchSize) {
          break;
        }

        $tenantId = (int) $tenant->id();
        $cancelledAt = $this->getCancelledTimestamp($tenant);
        if ($cancelledAt === 0) {
          continue;
        }

        $daysSinceCancellation = (int) floor((time() - $cancelledAt) / 86400);
        $sentPhases = $this->getSentPhases($tenantId);

        foreach (self::PHASES as $phaseKey => $phaseConfig) {
          if ($daysSinceCancellation >= $phaseConfig['days'] && !in_array($phaseKey, $sentPhases, TRUE)) {
            if ($this->sendWinBackEmail($tenant, $phaseKey, $phaseConfig)) {
              $this->markPhaseSent($tenantId, $phaseKey);
              $sent++;
            }
            // One email per tenant per cron run.
            break;
          }
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Win-back campaign error: @error', ['@error' => $e->getMessage()]);
    }

    if ($sent > 0) {
      $this->logger->info('Win-back campaign: sent @count emails.', ['@count' => $sent]);
    }

    return $sent;
  }

  /**
   * Sends a win-back email for a specific phase.
   *
   * @param mixed $tenant
   *   The tenant entity.
   * @param string $phaseKey
   *   The phase key (e.g. 'welcome_back_7d').
   * @param array{days: int, subject: string} $phaseConfig
   *   Phase configuration with 'days' and 'subject'.
   *
   * @return bool
   *   TRUE if email sent successfully.
   */
  protected function sendWinBackEmail(mixed $tenant, string $phaseKey, array $phaseConfig): bool {
    try {
      $adminUser = $tenant->get('admin_user')->entity ?? NULL;
      if ($adminUser === NULL) {
        return FALSE;
      }

      $email = $adminUser->getEmail();
      if (!is_string($email) || $email === '') {
        return FALSE;
      }

      $tenantName = $tenant->getName() ?? 'tu empresa';
      $vertical = $tenant->get('vertical')->entity?->get('machine_name') ?? 'demo';

      $params = [
        'phase' => $phaseKey,
        'subject' => $phaseConfig['subject'],
        'tenant_name' => $tenantName,
        'vertical' => $vertical,
        'tenant_id' => (int) $tenant->id(),
      ];

      $result = $this->mailManager->mail(
        'jaraba_billing',
        'winback_' . $phaseKey,
        $email,
        'es',
        $params,
        NULL,
        TRUE
      );

      return isset($result['result']) && $result['result'] === TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Win-back email failed for tenant @id phase @phase: @error', [
        '@id' => $tenant->id(),
        '@phase' => $phaseKey,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets the timestamp when the tenant was cancelled.
   *
   * @param mixed $tenant
   *   The tenant entity.
   *
   * @return int
   *   Unix timestamp, or 0 if unknown.
   */
  protected function getCancelledTimestamp(mixed $tenant): int {
    // Try cancel_at field first, then changed timestamp.
    $cancelAt = $tenant->get('cancel_at')->value ?? NULL;
    if ($cancelAt !== NULL) {
      try {
        return (new \DateTimeImmutable($cancelAt))->getTimestamp();
      }
      catch (\Throwable) {
        // Fall through.
      }
    }

    return (int) ($tenant->get('changed')->value ?? 0);
  }

  /**
   * Gets the list of phases already sent for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return list<string>
   *   Array of phase keys that have been sent.
   */
  protected function getSentPhases(int $tenantId): array {
    $phases = $this->state->get(self::STATE_PREFIX . $tenantId, []);
    return is_array($phases) ? $phases : [];
  }

  /**
   * Marks a phase as sent for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $phaseKey
   *   The phase key.
   */
  protected function markPhaseSent(int $tenantId, string $phaseKey): void {
    $sent = $this->getSentPhases($tenantId);
    $sent[] = $phaseKey;
    $this->state->set(self::STATE_PREFIX . $tenantId, $sent);
  }

}
