<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Trait;

/**
 * Trait with common methods for Copilot message entities.
 *
 * Provides default implementations for CopilotMessageInterface.
 */
trait CopilotMessageTrait {

  /**
   * {@inheritdoc}
   */
  public function getRole(): string {
    return $this->get('role')->value ?? 'user';
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(): string {
    return $this->get('content')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getModelUsed(): ?string {
    if ($this->hasField('model_used')) {
      return $this->get('model_used')->value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokensInput(): int {
    if ($this->hasField('tokens_input')) {
      return (int) ($this->get('tokens_input')->value ?? 0);
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokensOutput(): int {
    if ($this->hasField('tokens_output')) {
      return (int) ($this->get('tokens_output')->value ?? 0);
    }
    return 0;
  }

}
