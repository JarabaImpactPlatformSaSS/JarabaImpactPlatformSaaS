<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Interface;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface unificada para entidades de mensaje de Copilots.
 */
interface CopilotMessageInterface extends ContentEntityInterface {

  /**
   * Gets the message role (user/assistant/system).
   */
  public function getRole(): string;

  /**
   * Gets the message content text.
   */
  public function getContent(): string;

  /**
   * Gets the AI model used for this message.
   */
  public function getModelUsed(): ?string;

  /**
   * Gets input tokens count.
   */
  public function getTokensInput(): int;

  /**
   * Gets output tokens count.
   */
  public function getTokensOutput(): int;

}
