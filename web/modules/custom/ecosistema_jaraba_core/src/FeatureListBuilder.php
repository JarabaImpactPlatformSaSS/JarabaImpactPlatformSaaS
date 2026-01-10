<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * ListBuilder para la entidad Feature.
 */
class FeatureListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('Nombre');
        $header['id'] = $this->t('Machine name');
        $header['category'] = $this->t('Categoría');
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
        $row['category'] = $entity->getCategory();
        $row['status'] = $entity->status() ? $this->t('Habilitada') : $this->t('Deshabilitada');
        return $row + parent::buildRow($entity);
    }

}
