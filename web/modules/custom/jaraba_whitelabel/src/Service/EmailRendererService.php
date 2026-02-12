<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Entity\WhitelabelEmailTemplate;
use Psr\Log\LoggerInterface;

/**
 * Renders branded email templates with variable replacement.
 *
 * Loads tenant-specific email templates and replaces placeholder
 * variables with provided values.
 */
class EmailRendererService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Renders an email using the tenant's branded template.
   *
   * @param string $templateKey
   *   The template key (e.g. welcome, invoice, password_reset).
   * @param int $tenantId
   *   The tenant (group) ID.
   * @param array $variables
   *   Key-value pairs for token replacement (e.g. ['user_name' => 'John']).
   *
   * @return array
   *   Associative array with keys:
   *   - subject: The rendered subject line.
   *   - body_html: The rendered HTML body.
   *   - body_text: The rendered plain text body.
   */
  public function renderEmail(string $templateKey, int $tenantId, array $variables): array {
    $result = [
      'subject' => '',
      'body_html' => '',
      'body_text' => '',
    ];

    try {
      $template = $this->getTemplate($templateKey, $tenantId);
      if ($template === NULL) {
        $this->logger->warning('No email template found for key @key, tenant @tenant.', [
          '@key' => $templateKey,
          '@tenant' => $tenantId,
        ]);
        return $result;
      }

      $result['subject'] = $this->replaceTokens($template['subject'], $variables);
      $result['body_html'] = $this->replaceTokens($template['body_html'], $variables);
      $result['body_text'] = $this->replaceTokens($template['body_text'], $variables);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error rendering email template @key for tenant @tenant: @message', [
        '@key' => $templateKey,
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Loads a specific email template for a tenant.
   *
   * @param string $templateKey
   *   The template key.
   * @param int $tenantId
   *   The tenant (group) ID.
   *
   * @return array|null
   *   Template data array or NULL if not found.
   */
  public function getTemplate(string $templateKey, int $tenantId): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('whitelabel_email_template');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('template_key', $templateKey)
        ->condition('tenant_id', $tenantId)
        ->condition('template_status', WhitelabelEmailTemplate::STATUS_ACTIVE)
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $entity = $storage->load(reset($ids));
      if (!$entity instanceof WhitelabelEmailTemplate) {
        return NULL;
      }

      return [
        'id' => (int) $entity->id(),
        'template_key' => $entity->get('template_key')->value,
        'subject' => $entity->get('subject')->value ?? '',
        'body_html' => $entity->get('body_html')->value ?? '',
        'body_text' => $entity->get('body_text')->value ?? '',
        'tenant_id' => $tenantId,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading email template @key for tenant @tenant: @message', [
        '@key' => $templateKey,
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Replaces {{ token }} placeholders in a string.
   *
   * @param string $text
   *   The template text.
   * @param array $variables
   *   Key-value pairs for replacement.
   *
   * @return string
   *   The text with tokens replaced.
   */
  protected function replaceTokens(string $text, array $variables): string {
    foreach ($variables as $key => $value) {
      $text = str_replace('{{ ' . $key . ' }}', (string) $value, $text);
    }
    return $text;
  }

}
