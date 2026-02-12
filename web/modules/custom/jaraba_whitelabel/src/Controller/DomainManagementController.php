<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Service\DomainManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the domain management overview page.
 *
 * Displays a list of custom domains associated with the current tenant,
 * their DNS verification status, SSL provisioning state and provides
 * actions to add, verify or remove domains.
 */
class DomainManagementController extends ControllerBase {

  /**
   * The domain manager service.
   */
  protected DomainManagerService $domainManager;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jaraba_whitelabel\Service\DomainManagerService $domain_manager
   *   The domain manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DomainManagerService $domain_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->domainManager = $domain_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_whitelabel.domain_manager'),
      $container->get('logger.channel.jaraba_whitelabel'),
    );
  }

  /**
   * Renders the domain management overview page.
   *
   * @return array
   *   A render array with the domain management template.
   */
  public function overview(): array {
    try {
      $tenantId = $this->getTenantIdFromRequest();
      $domains = [];
      $canAdd = FALSE;

      if ($tenantId) {
        $domains = $this->domainManager->getDomainsForTenant($tenantId);
        // Allow adding domains if the tenant has fewer than the maximum.
        $canAdd = count($domains) < 10;
      }

      return [
        '#theme' => 'jaraba_domain_management',
        '#domains' => $domains,
        '#tenant_id' => $tenantId,
        '#can_add' => $canAdd,
        '#attached' => [
          'library' => ['jaraba_whitelabel/domain-management'],
        ],
        '#cache' => [
          'contexts' => ['user', 'url.query_args'],
          'tags' => ['custom_domain_list'],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading domain management: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        '#markup' => $this->t('An error occurred loading domain management. Please try again later.'),
      ];
    }
  }

  /**
   * Attempts to resolve the current tenant ID from the request.
   *
   * @return int|null
   *   The tenant ID or NULL if not determinable.
   */
  protected function getTenantIdFromRequest(): ?int {
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
