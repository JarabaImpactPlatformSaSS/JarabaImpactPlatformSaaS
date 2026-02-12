<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for JobApplication entities.
 */
class JobApplicationListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['candidate'] = $this->t('Candidato');
        $header['job'] = $this->t('Empleo');
        $header['status'] = $this->t('Estado');
        $header['applied_at'] = $this->t('Fecha solicitud');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['id'] = $entity->id();
        $row['candidate'] = $entity->get('candidate_id')->entity?->getDisplayName() ?? '-';
        $row['job'] = $entity->get('job_id')->entity?->label() ?? '-';
        $row['status'] = $entity->get('status')->value ?? 'pending';
        $row['applied_at'] = $entity->get('applied_at')->value
            ? \Drupal::service('date.formatter')->format($entity->get('applied_at')->value, 'short')
            : '';
        return $row + parent::buildRow($entity);
    }

}
