<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list builder for Mentoring Session entities.
 */
class MentoringSessionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['engagement'] = $this->t('Engagement');
        $header['scheduled'] = $this->t('Fecha Programada');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_mentoring\Entity\MentoringSession $entity */
        $row['id'] = $entity->id();
        $row['engagement'] = $entity->get('engagement_id')->target_id;
        $row['scheduled'] = $entity->get('scheduled_start')->value ?? '-';
        $row['status'] = $entity->get('status')->value;

        return $row + parent::buildRow($entity);
    }

}
