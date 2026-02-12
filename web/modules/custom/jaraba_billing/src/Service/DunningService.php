<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestiona el proceso de dunning (cobro) para pagos fallidos.
 *
 * Implementa la secuencia de 6 pasos definida en spec 134 §6:
 * 0. Email suave (día 0)
 * 1. Recordatorio (día 3)
 * 2. Email urgente + deshabilitar premium (día 7)
 * 3. Advertencia final + modo solo lectura (día 10)
 * 4. Suspensión (día 14)
 * 5. Cancelación (día 21)
 */
class DunningService {

  /**
   * Secuencia de pasos de dunning con días y acciones.
   */
  protected array $dunningSequence = [
    ['days' => 0, 'action' => 'email_soft', 'restrict' => NULL],
    ['days' => 3, 'action' => 'email_reminder', 'restrict' => 'banner'],
    ['days' => 7, 'action' => 'email_urgent', 'restrict' => 'premium_disabled'],
    ['days' => 10, 'action' => 'email_final_warning', 'restrict' => 'readonly'],
    ['days' => 14, 'action' => 'email_suspension', 'restrict' => 'suspended'],
    ['days' => 21, 'action' => 'cancel', 'restrict' => 'canceled'],
  ];

  public function __construct(
    protected TenantSubscriptionService $tenantSubscription,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Inicia el proceso de dunning para un tenant.
   */
  public function startDunning(int $tenantId): void {
    if ($this->isInDunning($tenantId)) {
      return;
    }

    $now = time();
    $this->database->merge('billing_dunning_state')
      ->key('tenant_id', $tenantId)
      ->fields([
        'started_at' => $now,
        'current_step' => 0,
        'last_action_at' => $now,
      ])
      ->execute();

    $this->executeStep($tenantId, 0);

    $this->logger->info('Dunning iniciado para tenant @id', ['@id' => $tenantId]);
  }

  /**
   * Procesa todos los registros de dunning activos (cron job).
   *
   * @return int
   *   Número de tenants procesados.
   */
  public function processDunning(): int {
    $records = $this->database->select('billing_dunning_state', 'ds')
      ->fields('ds')
      ->execute()
      ->fetchAll();

    $processed = 0;
    $now = time();

    foreach ($records as $record) {
      $currentStep = (int) $record->current_step;
      $startedAt = (int) $record->started_at;

      // Check if it's time for the next step.
      $nextStep = $currentStep + 1;
      if ($nextStep >= count($this->dunningSequence)) {
        continue;
      }

      $nextStepConfig = $this->dunningSequence[$nextStep];
      $daysSinceStart = ($now - $startedAt) / 86400;

      if ($daysSinceStart >= $nextStepConfig['days']) {
        $this->executeStep((int) $record->tenant_id, $nextStep);

        $this->database->update('billing_dunning_state')
          ->fields([
            'current_step' => $nextStep,
            'last_action_at' => $now,
          ])
          ->condition('tenant_id', $record->tenant_id)
          ->execute();

        $processed++;
      }
    }

    return $processed;
  }

  /**
   * Detiene el proceso de dunning (pago recuperado).
   */
  public function stopDunning(int $tenantId): void {
    $this->database->delete('billing_dunning_state')
      ->condition('tenant_id', $tenantId)
      ->execute();

    // Restore tenant to active status.
    try {
      $tenant = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if ($tenant) {
        $this->tenantSubscription->activateSubscription($tenant);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error restaurando tenant @id después de dunning: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    $this->logger->info('Dunning detenido para tenant @id (pago recuperado)', ['@id' => $tenantId]);
  }

  /**
   * Comprueba si un tenant está en proceso de dunning.
   */
  public function isInDunning(int $tenantId): bool {
    $count = $this->database->select('billing_dunning_state', 'ds')
      ->condition('tenant_id', $tenantId)
      ->countQuery()
      ->execute()
      ->fetchField();

    return (int) $count > 0;
  }

  /**
   * Obtiene el estado actual de dunning de un tenant.
   *
   * @return array|null
   *   Array con started_at, current_step, last_action_at, step_config, o NULL.
   */
  public function getDunningStatus(int $tenantId): ?array {
    $record = $this->database->select('billing_dunning_state', 'ds')
      ->fields('ds')
      ->condition('tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      return NULL;
    }

    $step = (int) $record['current_step'];
    return [
      'tenant_id' => (int) $record['tenant_id'],
      'started_at' => (int) $record['started_at'],
      'current_step' => $step,
      'last_action_at' => (int) $record['last_action_at'],
      'step_config' => $this->dunningSequence[$step] ?? NULL,
      'total_steps' => count($this->dunningSequence),
    ];
  }

  /**
   * Ejecuta la restricción y notificación de un paso de dunning.
   */
  public function executeStep(int $tenantId, int $step): void {
    if ($step < 0 || $step >= count($this->dunningSequence)) {
      return;
    }

    $config = $this->dunningSequence[$step];

    try {
      $tenant = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$tenant) {
        $this->logger->error('Tenant @id no encontrado para dunning step @step', [
          '@id' => $tenantId,
          '@step' => $step,
        ]);
        return;
      }

      // Apply restriction based on step.
      if ($config['restrict'] === 'suspended') {
        $this->tenantSubscription->suspendTenant($tenant, 'Dunning step ' . $step);
      }
      elseif ($config['restrict'] === 'canceled') {
        $this->tenantSubscription->cancelSubscription($tenant, TRUE);
      }

      // Send notification email.
      $this->sendDunningEmail($tenantId, $config['action'], $step);

      $this->logger->info('Dunning step @step ejecutado para tenant @id: @action', [
        '@step' => $step,
        '@id' => $tenantId,
        '@action' => $config['action'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error ejecutando dunning step @step para tenant @id: @error', [
        '@step' => $step,
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Envía email de notificación de dunning.
   */
  protected function sendDunningEmail(int $tenantId, string $action, int $step): void {
    try {
      $tenant = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$tenant) {
        return;
      }

      $customerStorage = $this->entityTypeManager->getStorage('billing_customer');
      $customers = $customerStorage->loadByProperties(['tenant_id' => $tenantId]);
      $customer = !empty($customers) ? reset($customers) : NULL;

      $to = $customer ? $customer->get('billing_email')->value : NULL;
      if (!$to) {
        $this->logger->warning('No billing email for tenant @id, skipping dunning email', ['@id' => $tenantId]);
        return;
      }

      $params = [
        'action' => $action,
        'step' => $step,
        'tenant_id' => $tenantId,
        'tenant_label' => $tenant->label(),
      ];

      $this->mailManager->mail('jaraba_billing', 'dunning_' . $action, $to, 'es', $params);
    }
    catch (\Exception $e) {
      $this->logger->error('Error sending dunning email for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
