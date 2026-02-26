<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Proporciona un listado de entidades Tenant.
 */
class TenantListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['vertical'] = $this->t('Vertical');
        $header['plan'] = $this->t('Plan');
        $header['domain'] = $this->t('Dominio');
        $header['status'] = $this->t('Estado');
        $header['created'] = $this->t('Creado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink();

        $vertical = $entity->getVertical();
        $row['vertical'] = $vertical ? $vertical->getName() : '-';

        $plan = $entity->getSubscriptionPlan();
        $row['plan'] = $plan ? $plan->getName() : '-';

        $row['domain'] = $entity->getDomain();

        $status = $entity->getSubscriptionStatus();
        $row['status'] = ['data' => $this->getStatusBadge($status)];

        $created = $entity->get('created')->value;
        $row['created'] = $created ? date('d/m/Y', $created) : '-';

        return $row + parent::buildRow($entity);
    }

    /**
     * Genera un badge de estado con color.
     *
     * @param string $status
     *   El estado del tenant.
     *
     * @return array
     *   Render array del badge.
     */
    protected function getStatusBadge(string $status): array
    {
        $labels = [
            TenantInterface::STATUS_PENDING => ['text' => $this->t('Pendiente'), 'color' => 'gray'],
            TenantInterface::STATUS_TRIAL => ['text' => $this->t('Trial'), 'color' => 'blue'],
            TenantInterface::STATUS_ACTIVE => ['text' => $this->t('Activo'), 'color' => 'green'],
            TenantInterface::STATUS_PAST_DUE => ['text' => $this->t('Pago pendiente'), 'color' => 'orange'],
            TenantInterface::STATUS_SUSPENDED => ['text' => $this->t('Suspendido'), 'color' => 'red'],
            TenantInterface::STATUS_CANCELLED => ['text' => $this->t('Cancelado'), 'color' => 'gray'],
        ];

        $label = $labels[$status] ?? ['text' => $status, 'color' => 'gray'];

        return [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $label['text'],
            '#attributes' => [
                'class' => ['badge', 'badge--' . $label['color']],
                'style' => 'padding: 2px 8px; border-radius: 4px; font-size: 12px;',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOperations(EntityInterface $entity)
    {
        $operations = parent::getDefaultOperations($entity);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $entity */

        // Ver productores del tenant.
        $operations['producers'] = [
            'title' => $this->t('Productores'),
            'weight' => 15,
            'url' => $entity->toUrl('canonical'),
        ];

        // Cambiar plan.
        $operations['change_plan'] = [
            'title' => $this->t('Cambiar Plan'),
            'weight' => 20,
            'url' => $entity->toUrl('edit-form'),
        ];

        return $operations;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $build = parent::render();

        // Añadir estilos inline para los badges.
        $build['#attached']['html_head'][] = [
            [
                '#type' => 'html_tag',
                '#tag' => 'style',
                '#value' => '
          .badge--green { background-color: #10B981; color: white; }
          .badge--blue { background-color: #3B82F6; color: white; }
          .badge--orange { background-color: #F59E0B; color: white; }
          .badge--red { background-color: #EF4444; color: white; }
          .badge--gray { background-color: #6B7280; color: white; }
        ',
            ],
            'tenant_list_styles',
        ];

        $build['table']['#empty'] = $this->t('No hay tenants registrados.');

        return $build;
    }

}
