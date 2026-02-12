<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Membership Plan entities.
 */
class MembershipPlanListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['type'] = $this->t('Tipo');
        $header['price'] = $this->t('Precio');
        $header['interval'] = $this->t('Facturación');
        $header['featured'] = $this->t('Destacado');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_resources\Entity\MembershipPlan $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink();
        $row['type'] = $entity->getPlanType();
        $row['price'] = number_format($entity->getPrice(), 2) . ' €';
        $row['interval'] = $entity->getBillingInterval();
        $row['featured'] = $entity->get('is_featured')->value ? '⭐' : '-';
        $row['status'] = $entity->get('status')->value;
        return $row + parent::buildRow($entity);
    }

}
