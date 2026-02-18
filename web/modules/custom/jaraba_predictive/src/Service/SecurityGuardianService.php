<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\jaraba_pwa\Service\PlatformPushService;
use Psr\Log\LoggerInterface;

/**
 * Guardián de Seguridad Predictiva.
 *
 * Orquesta la respuesta inmediata ante anomalías críticas detectadas
 * por el AnomalyDetectorService.
 *
 * F191 — Predictive Security & SOC2.
 */
class SecurityGuardianService {

  public function __construct(
    protected readonly AnomalyDetectorService $anomalyDetector,
    protected readonly PlatformPushService $pushService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Ejecuta un chequeo de seguridad completo para un tenant.
   */
  public function auditTenantSafety(int $tenantId): void {
    // 1. Chequeo de Anomalías en IA.
    $aiAnomaly = $this->anomalyDetector->detectAiUsageAnomaly($tenantId);
    
    if ($aiAnomaly['is_anomaly']) {
      $this->triggerSecurityAlert($tenantId, $aiAnomaly);
    }
  }

  /**
   * Dispara una alerta de seguridad multi-canal.
   */
  protected function triggerSecurityAlert(int $tenantId, array $anomaly): void {
    $msg = "⚠️ ALERTA CRÍTICA: Detectada anomalía '{$anomaly['type']}' en Tenant #{$tenantId}. Valor actual: {$anomaly['current_value']}.";
    
    $this->logger->critical($msg);

    // Notificar al Super Admin (UID 1) vía PWA.
    $this->pushService->sendToUser(
      1,
      '🚨 Brecha de Seguridad detectada',
      $msg,
      ['url' => '/admin/predictive/anomalies']
    );
  }

}
