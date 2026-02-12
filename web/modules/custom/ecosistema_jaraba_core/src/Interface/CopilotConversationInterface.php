<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Interface;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface unificada para entidades de conversación de Copilots.
 *
 * Permite operar sobre cualquier tipo de conversación
 * (candidate, producer, sales) de forma agnóstica.
 */
interface CopilotConversationInterface extends ContentEntityInterface {

  /**
   * Gets the tenant ID for multi-tenant filtering.
   */
  public function getTenantId(): ?int;

  /**
   * Gets the current state of the conversation.
   */
  public function getConversationState(): string;

  /**
   * Gets the total message count.
   */
  public function getMessageCount(): int;

  /**
   * Gets the satisfaction rating (1-5).
   */
  public function getSatisfactionRating(): ?int;

  /**
   * Checks if the conversation is still active.
   */
  public function isConversationActive(): bool;

}
