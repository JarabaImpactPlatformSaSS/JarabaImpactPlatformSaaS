<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * LIST BUILDER PARA VÍDEOS KB
 *
 * PROPÓSITO:
 * Muestra la lista de vídeos de la base de conocimiento.
 *
 * MULTI-TENANCY:
 * Filtra automáticamente por tenant actual.
 */
class KbVideoListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [];
        $header['title'] = $this->t('Título');
        $header['duration'] = $this->t('Duración');
        $header['status'] = $this->t('Estado');
        $header['views'] = $this->t('Vistas');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbVideo $entity */
        $row = [];

        // Título (truncado).
        $title = $entity->getTitle();
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        $row['title'] = $title;

        // Duración formateada.
        $row['duration'] = $entity->getFormattedDuration();

        // Estado.
        $statusLabels = [
            'draft' => $this->t('Borrador'),
            'published' => $this->t('Publicado'),
            'archived' => $this->t('Archivado'),
        ];
        $row['status'] = $statusLabels[$entity->getVideoStatus()] ?? $entity->getVideoStatus();

        // Vistas.
        $row['views'] = (int) ($entity->get('view_count')->value ?? 0);

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
            ->sort('created', 'DESC');

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
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
