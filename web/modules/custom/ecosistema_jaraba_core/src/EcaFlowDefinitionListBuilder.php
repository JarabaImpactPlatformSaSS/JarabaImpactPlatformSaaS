<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * ListBuilder para la entidad EcaFlowDefinition.
 *
 * Muestra el registro centralizado de flujos ECA con columnas para
 * dominio, trigger, modulo y estado de implementacion.
 */
class EcaFlowDefinitionListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('ID');
        $header['label'] = $this->t('Nombre');
        $header['domain'] = $this->t('Dominio');
        $header['trigger_event'] = $this->t('Trigger');
        $header['module'] = $this->t('Modulo');
        $header['implementation_status'] = $this->t('Implementacion');
        $header['status'] = $this->t('Activo');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\EcaFlowDefinitionInterface $entity */
        $status_labels = [
            'implemented' => $this->t('Implementado'),
            'partial' => $this->t('Parcial'),
            'pending' => $this->t('Pendiente'),
            'deprecated' => $this->t('Obsoleto'),
        ];

        $row['id'] = $entity->id();
        $row['label'] = $entity->label();
        $row['domain'] = $entity->getDomain();
        $row['trigger_event'] = $entity->getTriggerEvent();
        $row['module'] = $entity->getModule();
        $row['implementation_status'] = $status_labels[$entity->getImplementationStatus()] ?? $entity->getImplementationStatus();
        $row['status'] = $entity->status() ? $this->t('Si') : $this->t('No');
        return $row + parent::buildRow($entity);
    }

}
