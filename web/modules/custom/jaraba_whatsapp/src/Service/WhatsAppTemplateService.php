<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whatsapp\Entity\WaTemplateInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages WhatsApp templates (WaTemplate config entities).
 */
class WhatsAppTemplateService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WhatsAppApiService $apiService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets all approved templates.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaTemplateInterface[]
   */
  public function getApprovedTemplates(): array {
    $storage = $this->entityTypeManager->getStorage('wa_template');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status_meta', 'approved')
      ->condition('status', TRUE)
      ->execute();

    if ($ids === []) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Gets a template by machine name.
   *
   * @param string $templateName
   *   Template machine name.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaTemplateInterface|null
   */
  public function getTemplate(string $templateName): ?WaTemplateInterface {
    $template = $this->entityTypeManager->getStorage('wa_template')->load($templateName);
    if ($template instanceof WaTemplateInterface) {
      return $template;
    }
    return NULL;
  }

  /**
   * Sends a template message via WhatsApp.
   *
   * @param string $templateName
   *   Template machine name.
   * @param string $phone
   *   Recipient phone.
   * @param array $vars
   *   Template variables.
   *
   * @return array{success: bool, message_id?: string, error?: string}
   */
  public function sendTemplate(string $templateName, string $phone, array $vars = []): array {
    $template = $this->getTemplate($templateName);
    if ($template === NULL) {
      $this->logger->error('Template @name not found.', ['@name' => $templateName]);
      return ['success' => false, 'error' => 'Template not found: ' . $templateName];
    }

    if ($template->getStatusMeta() !== 'approved') {
      $this->logger->warning('Template @name is not approved (status: @s).', [
        '@name' => $templateName,
        '@s' => $template->getStatusMeta(),
      ]);
      return ['success' => false, 'error' => 'Template not approved'];
    }

    return $this->apiService->sendTemplateMessage(
      $phone,
      $templateName,
      $vars,
      $template->get('langcode') ?? 'es',
    );
  }

}
