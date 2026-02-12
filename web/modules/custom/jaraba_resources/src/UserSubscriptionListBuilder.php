<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of User Subscription entities.
 */
class UserSubscriptionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('Usuario');
        $header['plan'] = $this->t('Plan');
        $header['status'] = $this->t('Estado');
        $header['expires'] = $this->t('Expira');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_resources\Entity\UserSubscription $entity */
        $row['id'] = $entity->id();
        $row['user'] = $entity->getOwner()?->getDisplayName() ?? '-';
        $row['plan'] = $entity->getPlan()?->getName() ?? '-';
        $row['status'] = $entity->getSubscriptionStatus();
        $row['expires'] = $entity->getCurrentPeriodEnd() ?? 'N/A';
        return $row + parent::buildRow($entity);
    }

}
