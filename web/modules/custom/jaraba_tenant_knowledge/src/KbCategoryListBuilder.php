<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * LIST BUILDER PARA CATEGORÍAS KB
 *
 * PROPÓSITO:
 * Muestra la lista de categorías de la base de conocimiento.
 *
 * MULTI-TENANCY:
 * Filtra automáticamente por tenant actual.
 */
class KbCategoryListBuilder extends EntityListBuilder
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
        $header['name'] = $this->t('Nombre');
        $header['slug'] = $this->t('Slug');
        $header['icon'] = $this->t('Icono');
        $header['order'] = $this->t('Orden');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbCategory $entity */
        $row = [];

        $row['name'] = $entity->getName();
        $row['slug'] = $entity->getSlug();
        $row['icon'] = $entity->getIcon();
        $row['order'] = $entity->get('sort_order')->value ?? 0;

        $statusLabels = [
            'active' => $this->t('Activa'),
            'inactive' => $this->t('Inactiva'),
        ];
        $row['status'] = $statusLabels[$entity->getCategoryStatus()] ?? $entity->getCategoryStatus();

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        $tenantId = $this->getCurrentTenantId();

        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('sort_order', 'ASC')
            ->sort('name', 'ASC');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

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
