<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for WaTemplate config entities.
 */
interface WaTemplateInterface extends ConfigEntityInterface {

  /**
   * Gets the template category (marketing, utility, authentication).
   */
  public function getCategory(): string;

  /**
   * Gets the Meta approval status.
   */
  public function getStatusMeta(): string;

  /**
   * Gets the body text with placeholders.
   */
  public function getBodyText(): string;

  /**
   * Gets the Meta template ID.
   */
  public function getMetaTemplateId(): ?string;

  /**
   * Gets the variables schema.
   */
  public function getVariablesSchema(): array;

}
