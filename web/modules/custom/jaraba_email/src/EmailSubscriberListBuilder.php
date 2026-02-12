<?php

declare(strict_types=1);

namespace Drupal\jaraba_email;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Email Subscriber entities.
 */
class EmailSubscriberListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['email'] = $this->t('Email');
        $header['name'] = $this->t('Name');
        $header['status'] = $this->t('Status');
        $header['engagement'] = $this->t('Engagement');
        $header['created'] = $this->t('Created');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['email'] = $entity->toLink($entity->getEmail());
        $row['name'] = trim(($entity->get('first_name')->value ?? '') . ' ' . ($entity->get('last_name')->value ?? ''));
        $row['status'] = $entity->get('status')->value ?? 'pending';
        $row['engagement'] = $entity->get('engagement_score')->value ?? 50;
        $row['created'] = $entity->get('created')->value
            ? \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short')
            : '-';
        return $row + parent::buildRow($entity);
    }

}
