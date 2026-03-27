<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event dispatched when a WhatsApp template should be sent.
 *
 * Triggered by form submissions (participant/business forms) to
 * initiate the first automated WhatsApp contact.
 */
class WhatsAppSendTemplateEvent extends Event {

  /**
   * Event name constant.
   */
  public const EVENT_NAME = 'jaraba_whatsapp.send_template';

  public function __construct(
    protected string $phone,
    protected string $templateName,
    protected array $templateVars = [],
    protected int $tenantId = 0,
    protected array $utmParams = [],
  ) {}

  /**
   * Gets the phone number.
   */
  public function getPhone(): string {
    return $this->phone;
  }

  /**
   * Gets the template name.
   */
  public function getTemplateName(): string {
    return $this->templateName;
  }

  /**
   * Gets template variables.
   */
  public function getTemplateVars(): array {
    return $this->templateVars;
  }

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): int {
    return $this->tenantId;
  }

  /**
   * Gets UTM parameters.
   */
  public function getUtmParams(): array {
    return $this->utmParams;
  }

}
