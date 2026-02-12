<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Lesson entities.
 */
class LessonListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Title');
        $header['course'] = $this->t('Course');
        $header['type'] = $this->t('Type');
        $header['duration'] = $this->t('Duration');
        $header['status'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['title'] = $entity->toLink();

        $course = $entity->get('course_id')->entity;
        $row['course'] = $course ? $course->label() : '-';

        $row['type'] = ucfirst($entity->get('lesson_type')->value ?? '-');
        $row['duration'] = $entity->getFormattedDuration();
        $row['status'] = $entity->get('status')->value ? $this->t('Published') : $this->t('Draft');

        return $row + parent::buildRow($entity);
    }

}
