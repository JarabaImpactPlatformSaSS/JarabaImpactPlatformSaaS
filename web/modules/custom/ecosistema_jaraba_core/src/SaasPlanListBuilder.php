<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona un listado de entidades Plan SaaS.
 */
class SaasPlanListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['vertical'] = $this->t('Vertical');
        $header['price'] = $this->t('Precio/mes');
        $header['limits'] = $this->t('Límites');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink();

        $vertical = $entity->getVertical();
        $row['vertical'] = $vertical ? $vertical->getName() : $this->t('Todas');

        $price = $entity->getPriceMonthly();
        $row['price'] = $price > 0 ? number_format($price, 2) . ' €' : $this->t('Gratis');

        $limits = $entity->getLimits();
        $limit_parts = [];
        if (isset($limits['productores'])) {
            $limit_parts[] = $limits['productores'] == -1 ? '∞ prod.' : $limits['productores'] . ' prod.';
        }
        if (isset($limits['storage_gb'])) {
            $limit_parts[] = $limits['storage_gb'] == -1 ? '∞ GB' : $limits['storage_gb'] . ' GB';
        }
        $row['limits'] = implode(', ', $limit_parts);

        $row['status'] = $entity->get('status')->value ? $this->t('Activo') : $this->t('Inactivo');

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $build = parent::render();
        $build['table']['#empty'] = $this->t('No hay planes SaaS configurados. <a href=":url">Crear un plan</a>.', [
            ':url' => '/admin/structure/saas-plan/add',
        ]);
        return $build;
    }

}
