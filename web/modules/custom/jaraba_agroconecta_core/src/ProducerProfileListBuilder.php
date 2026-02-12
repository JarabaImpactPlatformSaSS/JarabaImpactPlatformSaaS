<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ProducerProfile.
 */
class ProducerProfileListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['farm_name'] = $this->t('Finca');
        $header['producer_name'] = $this->t('Productor');
        $header['location'] = $this->t('Ubicación');
        $header['production_type'] = $this->t('Tipo');
        $header['stripe'] = $this->t('Stripe');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProducerProfile $entity */
        $row['farm_name'] = $entity->get('farm_name')->value;
        $row['producer_name'] = $entity->get('producer_name')->value ?? '-';
        $row['location'] = $entity->get('location')->value ?? '-';
        $row['production_type'] = $entity->get('production_type')->value ?? '-';
        $row['stripe'] = $entity->hasStripeAccount() ? $this->t('✓ Conectado') : $this->t('✗ Sin cuenta');
        $row['status'] = $entity->isActive() ? $this->t('Activo') : $this->t('Inactivo');
        return $row + parent::buildRow($entity);
    }

}
