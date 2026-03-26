<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Email Copilot Bridge — contextualiza el copilot con datos de email marketing.
 */
class EmailCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'email',
      'has_email_data' => FALSE,
      'campaigns_count' => 0,
      'subscribers_count' => 0,
      'sequences_active' => 0,
    ];

    try {
      // Count campaigns.
      if ($this->entityTypeManager->hasDefinition('email_campaign')) {
        $count = $this->entityTypeManager
          ->getStorage('email_campaign')
          ->getQuery()
          ->accessCheck(TRUE)
          ->count()
          ->execute();
        $context['campaigns_count'] = $count;
      }

      // Count subscribers.
      if ($this->entityTypeManager->hasDefinition('email_subscriber')) {
        $count = $this->entityTypeManager
          ->getStorage('email_subscriber')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'active')
          ->count()
          ->execute();
        $context['subscribers_count'] = $count;
      }

      // Count active sequences.
      if ($this->entityTypeManager->hasDefinition('email_sequence')) {
        $count = $this->entityTypeManager
          ->getStorage('email_sequence')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'active')
          ->count()
          ->execute();
        $context['sequences_active'] = $count;
      }

      $context['has_email_data'] = $context['campaigns_count'] > 0 || $context['subscribers_count'] > 0;

      $context['_system_prompt_addition'] = sprintf(
        "Datos de email marketing: %d campanas, %d suscriptores activos, %d secuencias activas. "
        . "Ayuda a optimizar subject lines, segmentacion y timing de envios. "
        . "NUNCA inventes direcciones de email ni datos de suscriptores.",
        $context['campaigns_count'],
        $context['subscribers_count'],
        $context['sequences_active'],
      );

    }
    catch (\Exception $e) {
      $this->logger->warning('Email CopilotBridge error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|null
   */
  public function getSoftSuggestion(int $userId): ?array {
    return NULL;
  }

}
