<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * LIST BUILDER PARA CORRECCIONES DE IA.
 */
class TenantAiCorrectionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [];
        $header['title'] = $this->t('Corrección');
        $header['type'] = $this->t('Tipo');
        $header['status'] = $this->t('Estado');
        $header['hits'] = $this->t('Usos');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantAiCorrection $entity */
        $row = [];

        $row['title'] = $entity->getTitle();
        $row['type'] = $entity->getCorrectionTypeLabel();

        // Estado con indicador visual.
        $status = $entity->getStatus();
        $statusLabels = [
            'pending' => '⏳ ' . $this->t('Pendiente'),
            'applied' => '✅ ' . $this->t('Aplicada'),
            'rejected' => '❌ ' . $this->t('Rechazada'),
        ];
        $row['status'] = $statusLabels[$status] ?? $status;

        $row['hits'] = $entity->get('hit_count')->value ?? 0;

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
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
