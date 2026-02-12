<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de relaciones partner en admin.
 */
class PartnerRelationshipListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['partner_name'] = $this->t('Partner');
        $header['partner_email'] = $this->t('Email');
        $header['partner_type'] = $this->t('Tipo');
        $header['access_level'] = $this->t('Nivel');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
        $row['partner_name'] = $entity->getPartnerName();
        $row['partner_email'] = $entity->getPartnerEmail();
        $row['partner_type'] = $entity->getPartnerType();
        $row['access_level'] = $entity->getAccessLevel();
        $row['status'] = $entity->getStatus();
        return $row + parent::buildRow($entity);
    }

}
