<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_whitelabel\Entity\WhitelabelEmailTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the email template editor page.
 *
 * Provides a split-pane editor UI with a template selector sidebar,
 * subject/body editing area and a variable reference panel for building
 * branded email templates per tenant.
 */
class EmailTemplateEditorController extends ControllerBase {

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * The tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
   *   The tenant context service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    TenantContextService $tenant_context,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_whitelabel'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Renders the email template editor page.
   *
   * @return array
   *   A render array with the email template editor template.
   */
  public function editor(): array {
    try {
      $tenantId = $this->getTenantIdFromRequest();
      $templates = [];
      $currentTemplate = NULL;

      if ($tenantId) {
        $templates = $this->loadTemplatesForTenant($tenantId);
        if (!empty($templates)) {
          $currentTemplate = reset($templates);
        }
      }

      $availableVariables = $this->getAvailableVariables();

      return [
        '#theme' => 'jaraba_email_template_editor',
        '#templates' => $templates,
        '#current_template' => $currentTemplate,
        '#available_variables' => $availableVariables,
        '#tenant_id' => $tenantId,
        '#attached' => [
          'library' => ['jaraba_whitelabel/email-editor'],
        ],
        '#cache' => [
          'contexts' => ['user', 'url.query_args'],
          'tags' => ['whitelabel_email_template_list'],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading email template editor: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        '#markup' => $this->t('An error occurred loading the email template editor. Please try again later.'),
      ];
    }
  }

  /**
   * Loads email templates for a given tenant.
   *
   * @param int $tenantId
   *   The tenant (group) ID.
   *
   * @return array
   *   Array of template data arrays.
   */
  protected function loadTemplatesForTenant(int $tenantId): array {
    $storage = $this->entityTypeManager()->getStorage('whitelabel_email_template');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->sort('template_key', 'ASC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $templates = [];
    $entities = $storage->loadMultiple($ids);

    foreach ($entities as $entity) {
      if (!$entity instanceof WhitelabelEmailTemplate) {
        continue;
      }

      $templates[] = [
        'id' => (int) $entity->id(),
        'template_key' => $entity->get('template_key')->value,
        'subject' => $entity->get('subject')->value ?? '',
        'body_html' => $entity->get('body_html')->value ?? '',
        'body_text' => $entity->get('body_text')->value ?? '',
        'template_status' => $entity->get('template_status')->value,
      ];
    }

    return $templates;
  }

  /**
   * Returns the list of available template variables.
   *
   * @return array
   *   Array of variable definitions, each with 'token' and 'description'.
   */
  protected function getAvailableVariables(): array {
    return [
      ['token' => '{{ user_name }}', 'description' => (string) $this->t('Full name of the recipient')],
      ['token' => '{{ user_email }}', 'description' => (string) $this->t('Email address of the recipient')],
      ['token' => '{{ company_name }}', 'description' => (string) $this->t('Tenant company name')],
      ['token' => '{{ logo_url }}', 'description' => (string) $this->t('URL of the tenant logo')],
      ['token' => '{{ site_url }}', 'description' => (string) $this->t('Base URL of the site')],
      ['token' => '{{ current_date }}', 'description' => (string) $this->t('Current date')],
      ['token' => '{{ invoice_number }}', 'description' => (string) $this->t('Invoice number (invoice templates)')],
      ['token' => '{{ invoice_total }}', 'description' => (string) $this->t('Invoice total (invoice templates)')],
      ['token' => '{{ reset_url }}', 'description' => (string) $this->t('Password reset URL (reset templates)')],
      ['token' => '{{ action_url }}', 'description' => (string) $this->t('Call-to-action URL')],
    ];
  }

  /**
   * Attempts to resolve the current tenant ID from the request.
   *
   * @return int|null
   *   The tenant ID or NULL if not determinable.
   */
  protected function getTenantIdFromRequest(): ?int {
    $contextTenantId = $this->tenantContext->getCurrentTenantId();
    if ($contextTenantId !== NULL) {
      return $contextTenantId;
    }

    $request = \Drupal::request();

    $tenantId = $request->query->get('tenant_id');
    if ($tenantId !== NULL && is_numeric($tenantId)) {
      return (int) $tenantId;
    }

    $whitelabelConfig = $request->attributes->get('whitelabel_config');
    if (!empty($whitelabelConfig['tenant_id'])) {
      return (int) $whitelabelConfig['tenant_id'];
    }

    return NULL;
  }

}
