<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of MVP Hypothesis entities.
 */
class MvpHypothesisListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['hypothesis'] = $this->t('Hipótesis');
        $header['experiment'] = $this->t('Experimento');
        $header['status'] = $this->t('Resultado');
        $header['owner'] = $this->t('Propietario');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_business_tools\Entity\MvpHypothesis $entity */
        $row['id'] = $entity->id();
        $row['hypothesis'] = mb_substr($entity->getHypothesis(), 0, 60) . '...';
        $row['experiment'] = $entity->getExperimentType();
        $row['status'] = $entity->getResultStatus();
        $row['owner'] = $entity->getOwner()?->getDisplayName() ?? '-';
        return $row + parent::buildRow($entity);
    }

}
