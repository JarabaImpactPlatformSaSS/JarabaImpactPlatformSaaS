<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\CopilotBridge;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * WhatsApp Copilot Bridge — contextualize copilot with WhatsApp data.
 *
 * COPILOT-BRIDGE-COVERAGE-001: Every vertical/operational module needs a bridge.
 */
class WhatsAppCopilotBridgeService implements CopilotBridgeInterface {

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
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'whatsapp',
      'has_whatsapp_data' => FALSE,
      'active_conversations' => 0,
      'escalated_conversations' => 0,
      'total_conversations' => 0,
    ];

    try {
      if (!$this->entityTypeManager->hasDefinition('wa_conversation')) {
        return $context;
      }

      $storage = $this->entityTypeManager->getStorage('wa_conversation');

      $active = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->count()
        ->execute();
      $context['active_conversations'] = $active;

      $escalated = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'escalated')
        ->count()
        ->execute();
      $context['escalated_conversations'] = $escalated;

      $total = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();
      $context['total_conversations'] = $total;

      $context['has_whatsapp_data'] = $total > 0;

      $context['_system_prompt_addition'] = sprintf(
        "Datos de WhatsApp: %d conversaciones activas, %d escaladas, %d totales. "
        . "Puedes informar sobre el estado de las conversaciones WhatsApp del programa. "
        . "NUNCA reveles numeros de telefono completos.",
        $active,
        $escalated,
        $total,
      );
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp CopilotBridge error: @msg', ['@msg' => $e->getMessage()]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getSoftSuggestion(int $userId): array {
    return [];
  }

}
