<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list builder for Mentoring Engagement entities.
 */
class MentoringEngagementListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['mentor'] = $this->t('Mentor');
        $header['mentee'] = $this->t('Emprendedor');
        $header['sessions'] = $this->t('Sesiones');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_mentoring\Entity\MentoringEngagement $entity */
        $row['id'] = $entity->id();
        $row['mentor'] = $entity->get('mentor_id')->target_id;
        $row['mentee'] = $entity->get('mentee_id')->target_id;
        $row['sessions'] = $entity->get('sessions_remaining')->value . '/' . $entity->get('sessions_total')->value;
        $row['status'] = $entity->get('status')->value;

        return $row + parent::buildRow($entity);
    }

}
