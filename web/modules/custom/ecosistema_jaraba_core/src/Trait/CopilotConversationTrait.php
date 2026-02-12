<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Trait;

/**
 * Trait with common methods for Copilot conversation entities.
 *
 * Provides default implementations for CopilotConversationInterface.
 * Each entity may override these if their field names differ.
 */
trait CopilotConversationTrait {

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    if ($this->hasField('tenant_id')) {
      $value = $this->get('tenant_id')->target_id ?? $this->get('tenant_id')->value ?? NULL;
      return $value !== NULL ? (int) $value : NULL;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversationState(): string {
    // Support different field names across entities.
    if ($this->hasField('state')) {
      return $this->get('state')->value ?? 'active';
    }
    if ($this->hasField('is_active')) {
      return ((bool) $this->get('is_active')->value) ? 'active' : 'closed';
    }
    return 'active';
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageCount(): int {
    if ($this->hasField('messages_count')) {
      return (int) ($this->get('messages_count')->value ?? 0);
    }
    if ($this->hasField('message_count')) {
      return (int) ($this->get('message_count')->value ?? 0);
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getSatisfactionRating(): ?int {
    if ($this->hasField('satisfaction_rating')) {
      $val = $this->get('satisfaction_rating')->value;
      return $val !== NULL ? (int) $val : NULL;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isConversationActive(): bool {
    return $this->getConversationState() === 'active';
  }

}
