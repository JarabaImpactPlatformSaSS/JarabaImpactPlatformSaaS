<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * LIST BUILDER PARA DOCUMENTOS DEL TENANT
 *
 * PROPÓSITO:
 * Muestra la lista de documentos con estado de procesamiento.
 * Filtrado automático multi-tenant.
 */
class TenantDocumentListBuilder extends EntityListBuilder
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
        $header['title'] = $this->t('Título');
        $header['category'] = $this->t('Categoría');
        $header['status'] = $this->t('Estado');
        $header['chunks'] = $this->t('Chunks');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantDocument $entity */
        $row = [];

        $row['title'] = $entity->getTitle();
        $row['category'] = $entity->getCategoryLabel();

        // Estado con indicador visual.
        $status = $entity->getProcessingStatus();
        $statusLabels = [
            'pending' => '⏳ ' . $this->t('Pendiente'),
            'processing' => '🔄 ' . $this->t('Procesando'),
            'completed' => '✅ ' . $this->t('Completado'),
            'failed' => '❌ ' . $this->t('Error'),
        ];
        $row['status'] = $statusLabels[$status] ?? $status;

        $row['chunks'] = $entity->getChunkCount();

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
            ->sort('created', 'DESC');

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
