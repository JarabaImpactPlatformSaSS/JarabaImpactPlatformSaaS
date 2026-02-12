<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Service\ConfigResolverService;
use Drupal\jaraba_whitelabel\Service\DomainManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for whitelabel operations.
 *
 * Provides JSON endpoints for retrieving whitelabel configuration,
 * listing custom domains and adding new domains via AJAX.
 */
class WhitelabelApiController extends ControllerBase {

  /**
   * The whitelabel config resolver service.
   */
  protected ConfigResolverService $configResolver;

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
   * @param \Drupal\jaraba_whitelabel\Service\ConfigResolverService $config_resolver
   *   The config resolver service.
   * @param \Drupal\jaraba_whitelabel\Service\DomainManagerService $domain_manager
   *   The domain manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigResolverService $config_resolver,
    DomainManagerService $domain_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configResolver = $config_resolver;
    $this->domainManager = $domain_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_whitelabel.config_resolver'),
      $container->get('jaraba_whitelabel.domain_manager'),
      $container->get('logger.channel.jaraba_whitelabel'),
    );
  }

  /**
   * Returns the resolved whitelabel configuration as JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with config data or an error message.
   */
  public function getConfig(Request $request): JsonResponse {
    try {
      $tenantId = $request->query->get('tenant_id');
      $tenantIdInt = ($tenantId !== NULL && is_numeric($tenantId))
        ? (int) $tenantId
        : NULL;

      $config = $this->configResolver->resolveConfig($tenantIdInt);

      if ($config === NULL) {
        return new JsonResponse([
          'status' => 'not_found',
          'message' => 'No whitelabel configuration found.',
        ], 404);
      }

      return new JsonResponse([
        'status' => 'ok',
        'data' => $config,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('API error getting whitelabel config: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Internal server error.',
      ], 500);
    }
  }

  /**
   * Returns the list of custom domains for a tenant as JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with domains array.
   */
  public function getDomains(Request $request): JsonResponse {
    try {
      $tenantId = $request->query->get('tenant_id');
      if ($tenantId === NULL || !is_numeric($tenantId)) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Missing or invalid tenant_id parameter.',
        ], 400);
      }

      $domains = $this->domainManager->getDomainsForTenant((int) $tenantId);

      return new JsonResponse([
        'status' => 'ok',
        'data' => $domains,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('API error getting domains: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Internal server error.',
      ], 500);
    }
  }

  /**
   * Adds a new custom domain for a tenant.
   *
   * Expects JSON body with 'tenant_id' and 'domain' keys.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with the new domain ID or an error.
   */
  public function addDomain(Request $request): JsonResponse {
    try {
      $content = $request->getContent();
      $data = json_decode($content, TRUE);

      if (!is_array($data)) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Invalid JSON body.',
        ], 400);
      }

      $tenantId = $data['tenant_id'] ?? NULL;
      $domain = $data['domain'] ?? NULL;

      if (!is_numeric($tenantId) || empty($domain) || !is_string($domain)) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Missing or invalid tenant_id or domain.',
        ], 400);
      }

      // Basic domain format validation.
      if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?)+$/', $domain)) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Invalid domain format.',
        ], 400);
      }

      $domainId = $this->domainManager->addDomain((int) $tenantId, $domain);

      if ($domainId === NULL) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Domain already exists or could not be added.',
        ], 409);
      }

      return new JsonResponse([
        'status' => 'ok',
        'data' => [
          'domain_id' => $domainId,
          'domain' => $domain,
          'tenant_id' => (int) $tenantId,
        ],
      ], 201);
    }
    catch (\Throwable $e) {
      $this->logger->error('API error adding domain: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Internal server error.',
      ], 500);
    }
  }

}
