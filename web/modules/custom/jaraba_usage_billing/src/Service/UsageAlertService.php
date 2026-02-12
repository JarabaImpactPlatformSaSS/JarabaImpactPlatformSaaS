<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sistema de alertas de uso.
 *
 * Verifica umbrales de consumo y envía notificaciones cuando
 * se superan los límites configurados (warning y critical).
 */
class UsageAlertService {

  /**
   * Tipos de alerta.
   */
  public const ALERT_WARNING = 'warning';
  public const ALERT_CRITICAL = 'critical';
  public const ALERT_OVERAGE = 'overage';

  /**
   * Umbrales por defecto (porcentaje del límite).
   */
  protected const DEFAULT_WARNING_THRESHOLD = 80;
  protected const DEFAULT_CRITICAL_THRESHOLD = 95;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Verifica los umbrales de uso para un tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant a verificar, o NULL para todos.
   *
   * @return array
   *   Array de alertas detectadas, cada una con:
   *   - tenant_id: int
   *   - metric: string
   *   - current_usage: float
   *   - limit: float
   *   - percentage: float
   *   - alert_type: string (warning|critical|overage)
   */
  public function checkThresholds(?int $tenantId = NULL): array {
    $alerts = [];

    try {
      // Obtener agregados mensuales del periodo actual.
      $storage = $this->entityTypeManager->getStorage('usage_aggregate');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('period_type', 'monthly')
        ->sort('period_start', 'DESC');

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return $alerts;
      }

      $aggregates = $storage->loadMultiple($ids);

      // Obtener reglas de pricing para determinar límites.
      $pricingStorage = $this->entityTypeManager->getStorage('pricing_rule');

      foreach ($aggregates as $aggregate) {
        $metric = $aggregate->get('metric_name')->value;
        $currentTenantId = (int) $aggregate->get('tenant_id')->target_id;
        $currentUsage = (float) $aggregate->get('total_quantity')->value;

        // Buscar regla de pricing para esta métrica y tenant.
        $ruleQuery = $pricingStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('metric_name', $metric)
          ->condition('status', 'active')
          ->range(0, 1);

        $ruleIds = $ruleQuery->execute();
        if (empty($ruleIds)) {
          continue;
        }

        $rule = $pricingStorage->load(reset($ruleIds));
        if (!$rule) {
          continue;
        }

        // Determinar el límite del tier más alto.
        $tiers = json_decode($rule->get('tiers_config')->value ?? '[]', TRUE);
        $limit = 0.0;
        if (is_array($tiers) && !empty($tiers)) {
          $lastTier = end($tiers);
          $limit = (float) ($lastTier['up_to'] ?? 0);
        }

        if ($limit <= 0) {
          continue;
        }

        $percentage = ($currentUsage / $limit) * 100;

        if ($percentage >= 100) {
          $alerts[] = [
            'tenant_id' => $currentTenantId,
            'metric' => $metric,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'percentage' => $percentage,
            'alert_type' => self::ALERT_OVERAGE,
          ];
        }
        elseif ($percentage >= self::DEFAULT_CRITICAL_THRESHOLD) {
          $alerts[] = [
            'tenant_id' => $currentTenantId,
            'metric' => $metric,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'percentage' => $percentage,
            'alert_type' => self::ALERT_CRITICAL,
          ];
        }
        elseif ($percentage >= self::DEFAULT_WARNING_THRESHOLD) {
          $alerts[] = [
            'tenant_id' => $currentTenantId,
            'metric' => $metric,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'percentage' => $percentage,
            'alert_type' => self::ALERT_WARNING,
          ];
        }
      }

      $this->logger->info('Verificación de umbrales completada: @count alertas detectadas.', [
        '@count' => count($alerts),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error verificando umbrales de uso: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $alerts;
  }

  /**
   * Envía una alerta por email a un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $alertType
   *   Tipo de alerta (warning, critical, overage).
   * @param array $data
   *   Datos adicionales de la alerta.
   */
  public function sendAlert(int $tenantId, string $alertType, array $data): void {
    try {
      // Obtener datos del tenant para el email.
      $tenant = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$tenant) {
        $this->logger->warning('Tenant @id no encontrado para enviar alerta.', [
          '@id' => $tenantId,
        ]);
        return;
      }

      // Buscar email del billing customer.
      $customerStorage = $this->entityTypeManager->getStorage('billing_customer');
      $customers = $customerStorage->loadByProperties(['tenant_id' => $tenantId]);
      $customer = !empty($customers) ? reset($customers) : NULL;

      $to = $customer ? $customer->get('billing_email')->value : NULL;
      if (!$to) {
        $this->logger->warning('No billing email for tenant @id, skipping usage alert.', [
          '@id' => $tenantId,
        ]);
        return;
      }

      $params = [
        'alert_type' => $alertType,
        'tenant_id' => $tenantId,
        'tenant_label' => $tenant->label(),
        'data' => $data,
      ];

      $this->mailManager->mail(
        'jaraba_usage_billing',
        'usage_alert_' . $alertType,
        $to,
        'es',
        $params
      );

      $this->logger->info('Alerta de uso @type enviada a tenant @id (@email).', [
        '@type' => $alertType,
        '@id' => $tenantId,
        '@email' => $to,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando alerta de uso para tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
