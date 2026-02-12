<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ShippingZoneAgro.
 */
class ShippingZoneAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Zona');
        $header['country'] = $this->t('País');
        $header['regions'] = $this->t('Regiones');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ShippingZoneAgro $entity */
        $row['name'] = $entity->label();
        $row['country'] = $entity->getCountry();
        $regions = $entity->getRegions();
        $row['regions'] = !empty($regions) ? implode(', ', array_slice($regions, 0, 5)) . (count($regions) > 5 ? '…' : '') : $this->t('Todo');
        $row['status'] = $entity->isActive() ? $this->t('✅ Activa') : $this->t('⏸ Inactiva');
        return $row + parent::buildRow($entity);
    }

}
