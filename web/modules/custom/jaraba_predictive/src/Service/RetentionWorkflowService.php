<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_pwa\Service\PlatformPushService;
use Drupal\jaraba_content_hub\Service\NewsletterBridgeService;
use Psr\Log\LoggerInterface;

/**
 * Orquestador de flujos de retención proactiva.
 *
 * Ejecuta acciones automáticas basadas en el nivel de riesgo detectado.
 * 
 * F189 — Proactive Retention.
 */
class RetentionWorkflowService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected PlatformPushService $pushService,
    protected NewsletterBridgeService $newsletterBridge,
  ) {}

  /**
   * Ejecuta el flujo de retención para un riesgo detectado.
   */
  public function triggerResponse(int $tenantId, int $riskScore, string $riskLevel): void {
    if ($riskScore < 60) {
      return;
    }

    $this->logger->warning('Churn Retention Triggered for Tenant @id (Score: @score)', [
      '@id' => $tenantId,
      '@score' => $riskScore,
    ]);

    // 1. Notificar al Super Admin / Account Manager vía PWA Push.
    $this->notifyAccountManager($tenantId, $riskScore);

    // 2. Si el riesgo es crítico, preparar oferta de retención.
    if ($riskLevel === 'critical') {
      $this->prepareRetentionEmail($tenantId);
    }
  }

  /**
   * Notifica al responsable de la cuenta.
   */
  protected function notifyAccountManager(int $tenantId, int $riskScore): void {
    // En un sistema real, buscaríamos el UID del Account Manager.
    // Usamos el UID 1 como fallback para la demo.
    $this->pushService->sendToUser(
      1,
      '⚠️ Riesgo de Churn detectado',
      "El tenant ID {$tenantId} tiene un riesgo del {$riskScore}%. Se requiere intervención.",
      ['url' => '/admin/predictive/churn']
    );
  }

  /**
   * Encola un email de fidelización personalizado.
   */
  protected function prepareRetentionEmail(int $tenantId): void {
    $this->logger->info('Encolando campaña de fidelización para tenant @id', ['@id' => $tenantId]);
    // Aquí se conectaría con jaraba_email para lanzar una EmailSequence.
  }

}
