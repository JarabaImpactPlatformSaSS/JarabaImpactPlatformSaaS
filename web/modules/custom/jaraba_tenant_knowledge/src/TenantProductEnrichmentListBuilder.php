<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * LIST BUILDER PARA ENRIQUECIMIENTO DE PRODUCTOS.
 */
class TenantProductEnrichmentListBuilder extends EntityListBuilder
{


    /**
     * Tenant context service. // AUDIT-CONS-N10: Proper DI for tenant context.
     */
    protected ?TenantContextService $tenantContext = NULL;

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
        $instance = parent::createInstance($container, $entity_type);
        if ($container->has('ecosistema_jaraba_core.tenant_context')) {
            $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context'); // AUDIT-CONS-N10: Proper DI for tenant context.
        }
        return $instance;
    }

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
        if ($this->tenantContext !== NULL) {
            $tenantContext = $this->tenantContext;
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
