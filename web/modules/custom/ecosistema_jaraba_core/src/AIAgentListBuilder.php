<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * ListBuilder para la entidad AIAgent.
 */
class AIAgentListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('Nombre');
        $header['id'] = $this->t('Machine name');
        $header['service_id'] = $this->t('Servicio');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row['label'] = $entity->label();
        $row['id'] = $entity->id();
        $row['service_id'] = $entity->getServiceId() ?: $this->t('No configurado');
        $row['status'] = $entity->status() ? $this->t('Habilitado') : $this->t('Deshabilitado');
        return $row + parent::buildRow($entity);
    }

}
