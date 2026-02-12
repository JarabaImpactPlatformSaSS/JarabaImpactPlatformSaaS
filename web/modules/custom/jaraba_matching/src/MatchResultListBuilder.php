<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * List builder for MatchResult entities.
 */
class MatchResultListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['job'] = $this->t('Job');
        $header['candidate'] = $this->t('Candidate');
        $header['score'] = $this->t('Score');
        $header['calculated'] = $this->t('Calculated');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['id'] = $entity->id();

        // Job reference
        $job = $entity->get('job_id')->entity;
        $row['job'] = $job ? $job->label() : '-';

        // Candidate reference
        $candidate = $entity->get('candidate_id')->entity;
        $row['candidate'] = $candidate ? $candidate->label() : '-';

        // Score
        $row['score'] = $entity->get('final_score')->value . '%';

        // Calculated date
        $row['calculated'] = $entity->get('calculated')->value
            ? \Drupal::service('date.formatter')->format($entity->get('calculated')->value, 'short')
            : '';

        return $row + parent::buildRow($entity);
    }

}
