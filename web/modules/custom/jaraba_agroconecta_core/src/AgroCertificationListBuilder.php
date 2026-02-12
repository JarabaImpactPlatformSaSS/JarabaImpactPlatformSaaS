<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para AgroCertification.
 */
class AgroCertificationListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Certificación');
        $header['certification_type'] = $this->t('Tipo');
        $header['certifier'] = $this->t('Organismo');
        $header['expiry_date'] = $this->t('Expiración');
        $header['valid'] = $this->t('Vigente');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCertification $entity */
        $row['name'] = $entity->get('name')->value;
        $row['certification_type'] = $entity->get('certification_type')->value ?? '-';
        $row['certifier'] = $entity->get('certifier')->value ?? '-';
        $row['expiry_date'] = $entity->get('expiry_date')->value ?? '-';
        $row['valid'] = $entity->isValid() ? $this->t('✓ Vigente') : $this->t('✗ Expirada');
        return $row + parent::buildRow($entity);
    }

}
