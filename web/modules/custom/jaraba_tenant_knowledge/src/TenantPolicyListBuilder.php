<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * LIST BUILDER PARA POLÍTICAS DEL TENANT
 *
 * PROPÓSITO:
 * Muestra la lista de políticas del tenant actual.
 * Filtrado automático multi-tenant.
 */
class TenantPolicyListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [];
        $header['title'] = $this->t('Título');
        $header['type'] = $this->t('Tipo');
        $header['version'] = $this->t('Versión');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantPolicy $entity */
        $row = [];

        $row['title'] = $entity->getTitle();
        $row['type'] = $entity->getPolicyTypeLabel();
        $row['version'] = 'v' . $entity->getVersionNumber();
        $row['status'] = $entity->isPublished()
            ? $this->t('Publicada')
            : $this->t('Borrador');

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        $tenantId = $this->getCurrentTenantId();

        if (!$tenantId) {
            return [];
        }

        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('tenant_id', $tenantId)
            ->sort('policy_type', 'ASC')
            ->sort('title', 'ASC');

        if ($this->limit) {
            $query->pager($this->limit);
        }

        return $query->execute();
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
