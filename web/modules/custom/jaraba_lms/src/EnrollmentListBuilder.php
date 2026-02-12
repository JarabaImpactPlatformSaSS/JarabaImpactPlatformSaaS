<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Enrollment entities.
 */
class EnrollmentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('User');
        $header['course'] = $this->t('Course');
        $header['status'] = $this->t('Status');
        $header['progress'] = $this->t('Progress');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['id'] = $entity->id();
        $row['user'] = $entity->get('user_id')->entity?->getDisplayName() ?? '-';
        $row['course'] = $entity->get('course_id')->entity?->label() ?? '-';
        $row['status'] = $entity->get('status')->value ?? 'active';
        $row['progress'] = ($entity->get('progress')->value ?? 0) . '%';
        return $row + parent::buildRow($entity);
    }

}
