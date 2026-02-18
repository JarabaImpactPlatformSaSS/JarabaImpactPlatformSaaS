<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * LIST BUILDER PARA FAQs DEL TENANT
 *
 * PROPÓSITO:
 * Muestra la lista de FAQs del tenant actual con filtros y acciones.
 * Usado en el dashboard de Knowledge Training.
 *
 * MULTI-TENANCY:
 * Filtra automáticamente por tenant actual.
 */
class TenantFaqListBuilder extends EntityListBuilder
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
        $header['question'] = $this->t('Pregunta');
        $header['category'] = $this->t('Categoría');
        $header['status'] = $this->t('Estado');
        $header['priority'] = $this->t('Prioridad');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantFaq $entity */
        $row = [];

        // Pregunta (truncada).
        $question = $entity->getQuestion();
        if (strlen($question) > 60) {
            $question = substr($question, 0, 57) . '...';
        }
        $row['question'] = $question;

        // Categoría con badge.
        $category = $entity->getCategory();
        $allowedValues = $entity->getFieldDefinition('category')->getSetting('allowed_values');
        $categoryLabel = $allowedValues[$category] ?? $category;
        $row['category'] = $categoryLabel;

        // Estado.
        $row['status'] = $entity->isPublished()
            ? $this->t('Publicada')
            : $this->t('Borrador');

        // Prioridad.
        $row['priority'] = $entity->get('priority')->value ?? 0;

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        // Obtener tenant actual.
        $tenantId = $this->getCurrentTenantId();

        if (!$tenantId) {
            return [];
        }

        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('tenant_id', $tenantId)
            ->sort('priority', 'DESC')
            ->sort('created', 'DESC');

        // Aplicar paginación.
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
