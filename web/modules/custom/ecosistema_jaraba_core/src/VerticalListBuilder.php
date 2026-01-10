<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona un listado de entidades Vertical.
 */
class VerticalListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['machine_name'] = $this->t('Machine Name');
        $header['status'] = $this->t('Estado');
        $header['features'] = $this->t('Features');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink();
        $row['machine_name'] = $entity->getMachineName();
        $row['status'] = $entity->get('status')->value ? $this->t('Activa') : $this->t('Inactiva');

        $features = $entity->getEnabledFeatures();
        $row['features'] = implode(', ', array_slice($features, 0, 3));
        if (count($features) > 3) {
            $row['features'] .= ' (+' . (count($features) - 3) . ')';
        }

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOperations(EntityInterface $entity)
    {
        $operations = parent::getDefaultOperations($entity);

        $operations['tenants'] = [
            'title' => $this->t('Ver Tenants'),
            'weight' => 15,
            'url' => $entity->toUrl('collection')->setRouteParameter('vertical', $entity->id()),
        ];

        return $operations;
    }

}
