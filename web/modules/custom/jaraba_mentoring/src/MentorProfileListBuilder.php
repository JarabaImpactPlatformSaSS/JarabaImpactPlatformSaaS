<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list builder for Mentor Profile entities.
 */
class MentorProfileListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['display_name'] = $this->t('Nombre');
        $header['sectors'] = $this->t('Sectores');
        $header['certification_level'] = $this->t('Nivel');
        $header['rating'] = $this->t('Rating');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_mentoring\Entity\MentorProfile $entity */
        $row['id'] = $entity->id();
        $row['display_name'] = $entity->toLink($entity->getDisplayName());

        // Sectores como badges.
        $sectors = $entity->get('sectors')->getValue();
        $sector_labels = array_map(fn($s) => $s['value'], $sectors);
        $row['sectors'] = implode(', ', $sector_labels);

        $row['certification_level'] = $entity->getCertificationLevel();
        $row['rating'] = number_format($entity->getAverageRating(), 1) . ' ⭐';
        $row['status'] = $entity->get('status')->value;

        return $row + parent::buildRow($entity);
    }

}
