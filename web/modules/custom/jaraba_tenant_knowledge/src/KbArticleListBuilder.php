<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * LIST BUILDER PARA ARTÍCULOS KB
 *
 * PROPÓSITO:
 * Muestra la lista de artículos de la base de conocimiento.
 * Usado en la administración y el dashboard de Knowledge Training.
 *
 * MULTI-TENANCY:
 * Filtra automáticamente por tenant actual.
 */
class KbArticleListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [];
        $header['title'] = $this->t('Título');
        $header['category'] = $this->t('Categoría');
        $header['status'] = $this->t('Estado');
        $header['views'] = $this->t('Vistas');
        $header['helpful'] = $this->t('Útil');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle $entity */
        $row = [];

        // Título (truncado).
        $title = $entity->getTitle();
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        $row['title'] = $title;

        // Categoría.
        $categoryId = $entity->getCategoryId();
        $categoryLabel = '-';
        if ($categoryId) {
            try {
                $category = \Drupal::entityTypeManager()
                    ->getStorage('kb_category')
                    ->load($categoryId);
                if ($category) {
                    $categoryLabel = $category->getName();
                }
            }
            catch (\Exception $e) {
                // Silenciar error si la categoría no existe.
            }
        }
        $row['category'] = $categoryLabel;

        // Estado.
        $statusLabels = [
            'draft' => $this->t('Borrador'),
            'published' => $this->t('Publicado'),
            'archived' => $this->t('Archivado'),
        ];
        $row['status'] = $statusLabels[$entity->getArticleStatus()] ?? $entity->getArticleStatus();

        // Vistas.
        $row['views'] = $entity->getViewCount();

        // Útil.
        $row['helpful'] = $entity->getHelpfulCount() . ' / ' . $entity->getNotHelpfulCount();

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
