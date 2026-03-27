<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for WaMessage entities.
 */
interface WaMessageInterface extends ContentEntityInterface {

  /**
   * Gets the conversation entity reference ID.
   */
  public function getConversationId(): ?int;

  /**
   * Gets the message direction.
   */
  public function getDirection(): string;

  /**
   * Gets the sender type.
   */
  public function getSenderType(): string;

  /**
   * Gets the message body.
   */
  public function getBody(): string;

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): ?int;

}
