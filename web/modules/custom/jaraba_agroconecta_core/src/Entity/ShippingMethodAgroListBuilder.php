<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ShippingMethodAgro.
 */
class ShippingMethodAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Método');
        $header['type'] = $this->t('Cálculo');
        $header['rate'] = $this->t('Tarifa base');
        $header['free_from'] = $this->t('Gratis desde');
        $header['delivery'] = $this->t('Plazo');
        $header['cold'] = $this->t('Frío');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ShippingMethodAgro $entity */
        $typeLabels = [
            'flat_rate' => $this->t('Tarifa fija'),
            'weight_based' => $this->t('Por peso'),
            'price_based' => $this->t('Por precio'),
            'free' => $this->t('Gratis'),
        ];

        $row['name'] = $entity->label();
        $row['type'] = $typeLabels[$entity->getCalculationType()] ?? $entity->getCalculationType();
        $row['rate'] = number_format($entity->getBaseRate(), 2, ',', '.') . ' €';

        $free = $entity->getFreeThreshold();
        $row['free_from'] = $free > 0 ? number_format($free, 2, ',', '.') . ' €' : '—';

        $row['delivery'] = $entity->getDeliveryEstimate() ?: '—';
        $row['cold'] = $entity->requiresColdChain() ? '❄️' : '—';
        $row['status'] = $entity->isActive() ? $this->t('✅ Activo') : $this->t('⏸ Inactivo');

        return $row + parent::buildRow($entity);
    }

}
