<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Entity\WhitelabelConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves whitelabel configuration by request domain or tenant.
 *
 * Looks up the active WhitelabelConfig entity matching the current
 * request's host header, or by explicit tenant ID.
 */
class ConfigResolverService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->logger = $logger;
  }

  /**
   * Resolves whitelabel config for a tenant.
   *
   * If no tenant ID is provided, attempts to resolve from the current
   * request domain via the custom_domain entity.
   *
   * @param int|null $tenantId
   *   Explicit tenant (group) ID, or NULL to auto-detect.
   *
   * @return array|null
   *   Associative array of config values, or NULL if no config found.
   */
  public function resolveConfig(?int $tenantId = NULL): ?array {
    try {
      if ($tenantId === NULL) {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
          $domain = $request->getHost();
          return $this->getConfigByDomain($domain);
        }
        return NULL;
      }

      $storage = $this->entityTypeManager->getStorage('whitelabel_config');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('config_status', WhitelabelConfig::STATUS_ACTIVE)
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $config = $storage->load(reset($ids));
      if (!$config instanceof WhitelabelConfig) {
        return NULL;
      }

      return $this->entityToArray($config);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error resolving whitelabel config for tenant @id: @message', [
        '@id' => $tenantId ?? 'auto',
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets whitelabel config by matching a custom domain.
   *
   * @param string $domain
   *   The domain name to look up.
   *
   * @return array|null
   *   Config array or NULL.
   */
  public function getConfigByDomain(string $domain): ?array {
    try {
      $domainStorage = $this->entityTypeManager->getStorage('custom_domain');
      $domainIds = $domainStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('domain', $domain)
        ->condition('domain_status', 'active')
        ->range(0, 1)
        ->execute();

      if (empty($domainIds)) {
        return NULL;
      }

      $domainEntity = $domainStorage->load(reset($domainIds));
      if (!$domainEntity) {
        return NULL;
      }

      $tenantId = (int) $domainEntity->get('tenant_id')->target_id;
      if (empty($tenantId)) {
        return NULL;
      }

      return $this->resolveConfig($tenantId);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error resolving config for domain @domain: @message', [
        '@domain' => $domain,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Checks whether the current request is whitelabeled.
   *
   * @return bool
   *   TRUE if the current request has whitelabel config.
   */
  public function isWhitelabeled(): bool {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return FALSE;
    }

    return $request->attributes->has('whitelabel_config');
  }

  /**
   * Converts a WhitelabelConfig entity to an associative array.
   *
   * @param \Drupal\jaraba_whitelabel\Entity\WhitelabelConfig $config
   *   The config entity.
   *
   * @return array
   *   The config values.
   */
  protected function entityToArray(WhitelabelConfig $config): array {
    return [
      'id' => (int) $config->id(),
      'config_key' => $config->get('config_key')->value,
      'tenant_id' => (int) $config->get('tenant_id')->target_id,
      'logo_url' => $config->get('logo_url')->value,
      'favicon_url' => $config->get('favicon_url')->value,
      'company_name' => $config->get('company_name')->value,
      'primary_color' => $config->get('primary_color')->value,
      'secondary_color' => $config->get('secondary_color')->value,
      'custom_css' => $config->get('custom_css')->value,
      'custom_footer_html' => $config->get('custom_footer_html')->value,
      'hide_powered_by' => (bool) $config->get('hide_powered_by')->value,
      'config_status' => $config->get('config_status')->value,
    ];
  }

}
