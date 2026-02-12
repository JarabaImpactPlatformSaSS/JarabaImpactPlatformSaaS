<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Entity\CustomDomain;
use Psr\Log\LoggerInterface;

/**
 * Manages custom domains for whitelabel tenants.
 *
 * Handles adding, verifying DNS, listing and removing custom domains.
 */
class DomainManagerService {

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
   * Adds a custom domain for a tenant.
   *
   * @param int $tenantId
   *   The tenant (group) ID.
   * @param string $domain
   *   The fully qualified domain name.
   *
   * @return int|null
   *   The new entity ID, or NULL on failure.
   */
  public function addDomain(int $tenantId, string $domain): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('custom_domain');

      // Check for duplicates.
      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('domain', $domain)
        ->count()
        ->execute();

      if ($existing > 0) {
        $this->logger->warning('Attempted to add duplicate domain @domain for tenant @tenant.', [
          '@domain' => $domain,
          '@tenant' => $tenantId,
        ]);
        return NULL;
      }

      $token = bin2hex(random_bytes(32));

      /** @var \Drupal\jaraba_whitelabel\Entity\CustomDomain $entity */
      $entity = $storage->create([
        'domain' => $domain,
        'tenant_id' => $tenantId,
        'ssl_status' => CustomDomain::SSL_PENDING,
        'dns_verified' => FALSE,
        'dns_verification_token' => $token,
        'domain_status' => CustomDomain::DOMAIN_PENDING,
      ]);
      $entity->save();

      $this->logger->info('Custom domain @domain added for tenant @tenant (id: @id).', [
        '@domain' => $domain,
        '@tenant' => $tenantId,
        '@id' => $entity->id(),
      ]);

      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Error adding domain @domain for tenant @tenant: @message', [
        '@domain' => $domain,
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Verifies DNS records for a custom domain.
   *
   * Checks whether the expected TXT record is present.
   *
   * @param int $domainId
   *   The custom_domain entity ID.
   *
   * @return bool
   *   TRUE if DNS verification succeeded.
   */
  public function verifyDns(int $domainId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('custom_domain');
      $entity = $storage->load($domainId);

      if (!$entity instanceof CustomDomain) {
        return FALSE;
      }

      $domain = $entity->get('domain')->value;
      $expectedToken = $entity->get('dns_verification_token')->value;

      if (empty($domain) || empty($expectedToken)) {
        return FALSE;
      }

      // Check TXT records for the verification token.
      $records = @dns_get_record('_jaraba-verify.' . $domain, DNS_TXT);

      $verified = FALSE;
      if (is_array($records)) {
        foreach ($records as $record) {
          if (isset($record['txt']) && $record['txt'] === $expectedToken) {
            $verified = TRUE;
            break;
          }
        }
      }

      $entity->set('dns_verified', $verified);
      if ($verified) {
        $entity->set('domain_status', CustomDomain::DOMAIN_ACTIVE);
        $entity->set('ssl_status', CustomDomain::SSL_ACTIVE);
        $entity->set('provisioned_at', \Drupal::time()->getRequestTime());
      }
      $entity->save();

      $this->logger->info('DNS verification for @domain: @result', [
        '@domain' => $domain,
        '@result' => $verified ? 'success' : 'failed',
      ]);

      return $verified;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error verifying DNS for domain @id: @message', [
        '@id' => $domainId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets all domains for a tenant.
   *
   * @param int $tenantId
   *   The tenant (group) ID.
   *
   * @return array
   *   Array of domain data arrays.
   */
  public function getDomainsForTenant(int $tenantId): array {
    $domains = [];

    try {
      $storage = $this->entityTypeManager->getStorage('custom_domain');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return $domains;
      }

      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        /** @var \Drupal\jaraba_whitelabel\Entity\CustomDomain $entity */
        $domains[] = [
          'id' => (int) $entity->id(),
          'domain' => $entity->get('domain')->value,
          'ssl_status' => $entity->get('ssl_status')->value,
          'dns_verified' => (bool) $entity->get('dns_verified')->value,
          'domain_status' => $entity->get('domain_status')->value,
          'dns_verification_token' => $entity->get('dns_verification_token')->value,
          'created' => $entity->get('created')->value,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading domains for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $domains;
  }

  /**
   * Removes a custom domain.
   *
   * @param int $domainId
   *   The custom_domain entity ID.
   *
   * @return bool
   *   TRUE if deletion succeeded.
   */
  public function removeDomain(int $domainId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('custom_domain');
      $entity = $storage->load($domainId);

      if (!$entity instanceof CustomDomain) {
        return FALSE;
      }

      $domain = $entity->get('domain')->value;
      $entity->delete();

      $this->logger->info('Custom domain @domain (id: @id) removed.', [
        '@domain' => $domain,
        '@id' => $domainId,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error removing domain @id: @message', [
        '@id' => $domainId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
