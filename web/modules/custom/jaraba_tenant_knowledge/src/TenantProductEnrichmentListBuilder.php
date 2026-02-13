<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * LIST BUILDER PARA ENRIQUECIMIENTO DE PRODUCTOS.
 */
class TenantProductEnrichmentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [];
        $header['name'] = $this->t('Producto');
        $header['category'] = $this->t('Categoría');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantProductEnrichment $entity */
        $row = [];

        $row['name'] = $entity->getProductName();
        $row['category'] = $entity->getCategory() ?: '-';
        $row['status'] = $entity->isPublished()
            ? '✅ ' . $this->t('Publicado')
            : '⏸️ ' . $this->t('Borrador');

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
            ->sort('product_name', 'ASC');

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
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
