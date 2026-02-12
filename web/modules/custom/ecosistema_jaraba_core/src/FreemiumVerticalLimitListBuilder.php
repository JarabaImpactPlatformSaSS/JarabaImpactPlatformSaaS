<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * ListBuilder para la entidad FreemiumVerticalLimit.
 *
 * Muestra un listado con las columnas relevantes para la gestion
 * de limites freemium por vertical, plan y feature.
 */
class FreemiumVerticalLimitListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('ID');
        $header['label'] = $this->t('Nombre');
        $header['vertical'] = $this->t('Vertical');
        $header['plan'] = $this->t('Plan');
        $header['feature_key'] = $this->t('Feature');
        $header['limit_value'] = $this->t('Limite');
        $header['expected_conversion'] = $this->t('Conv. esperada');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface $entity */
        $row['id'] = $entity->id();
        $row['label'] = $entity->label();
        $row['vertical'] = $entity->getVertical();
        $row['plan'] = $entity->getPlan();
        $row['feature_key'] = $entity->getFeatureKey();

        $limit = $entity->getLimitValue();
        if ($limit === -1) {
            $row['limit_value'] = $this->t('Ilimitado');
        }
        elseif ($limit === 0) {
            $row['limit_value'] = $this->t('No incluido');
        }
        else {
            $row['limit_value'] = $limit;
        }

        $conversion = $entity->getExpectedConversion();
        $row['expected_conversion'] = $conversion > 0 ? round($conversion * 100, 1) . '%' : '-';
        $row['status'] = $entity->status() ? $this->t('Activo') : $this->t('Inactivo');

        return $row + parent::buildRow($entity);
    }

}
