<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list builder for Mentoring Package entities.
 */
class MentoringPackageListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Título');
        $header['mentor'] = $this->t('Mentor');
        $header['type'] = $this->t('Tipo');
        $header['price'] = $this->t('Precio');
        $header['published'] = $this->t('Publicado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_mentoring\Entity\MentoringPackage $entity */
        $row['id'] = $entity->id();
        $row['title'] = $entity->get('title')->value;

        $mentor = $entity->getMentor();
        $row['mentor'] = $mentor ? $mentor->getDisplayName() : '-';

        $row['type'] = $entity->get('package_type')->value;
        $row['price'] = '€' . number_format((float) $entity->get('price')->value, 0);
        $row['published'] = $entity->get('is_published')->value ? $this->t('Sí') : $this->t('No');

        return $row + parent::buildRow($entity);
    }

}
