<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * BE-03: Service for tenant domain management.
 *
 * Extracted from TenantManager to follow Single Responsibility Principle.
 * Handles: domain validation, tenant lookup by domain.
 */
class TenantDomainService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Checks if a domain is already in use by another tenant.
     */
    public function domainExists(string $domain): bool
    {
        $tenants = $this->entityTypeManager
            ->getStorage('tenant')
            ->loadByProperties(['domain' => $domain]);

        return !empty($tenants);
    }

    /**
     * Retrieves a tenant by its domain.
     */
    public function getTenantByDomain(string $domain): ?TenantInterface
    {
        $tenants = $this->entityTypeManager
            ->getStorage('tenant')
            ->loadByProperties(['domain' => $domain]);

        if (!empty($tenants)) {
            $tenant = reset($tenants);
            return $tenant instanceof TenantInterface ? $tenant : NULL;
        }

        return NULL;
    }

}
