<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Drupal\jaraba_pwa\Service\PlatformPushService;
use Psr\Log\LoggerInterface;

/**
 * Monitor de Reputación de Marca.
 *
 * Analiza el sentimiento del contenido y dispara alertas si detecta
 * riesgos para la imagen de la marca (polaridad negativa extrema).
 *
 * F195 — Reputation Guard.
 */
class ReputationMonitorService {

  // Umbral de sentimiento negativo para disparar alerta (-1.0 a 1.0).
  protected const NEGATIVE_THRESHOLD = -0.4;

  public function __construct(
    protected readonly PlatformPushService $pushService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evalúa el impacto reputacional de un artículo.
   */
  public function evaluateContentRisk(ContentArticleInterface $article): void {
    $score = (float) $article->get('sentiment_score')->value;
    $label = $article->get('sentiment_label')->value;

    // Si el sentimiento es neutral o positivo, no hay riesgo.
    if ($score > self::NEGATIVE_THRESHOLD) {
      return;
    }

    // Riesgo detectado.
    $this->triggerReputationAlert($article, $score);
  }

  /**
   * Dispara la alerta de reputación.
   */
  protected function triggerReputationAlert(ContentArticleInterface $article, float $score): void {
    $tenantId = $article->get('tenant_id')->target_id ?? 0;
    
    $msg = "⚠️ ALERTA DE REPUTACIÓN: El artículo '{$article->label()}' tiene un tono negativo crítico (Score: {$score}).";
    
    $this->logger->warning($msg, ['tenant_id' => $tenantId]);

    // Notificar al Editor Jefe / Admin del Tenant vía PWA.
    // Asumimos UID 1 para demo, en producción se buscaría el rol 'editor_chief'.
    $this->pushService->sendToUser(
      1,
      '🛡️ Riesgo de Marca Detectado',
      $msg,
      ['url' => $article->toUrl('edit-form')->toString()]
    );
  }

}
