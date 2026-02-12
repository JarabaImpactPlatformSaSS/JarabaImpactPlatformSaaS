<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para listar evaluaciones de fortalezas.
 */
class StrengthAssessmentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('Usuario');
        $header['top_strength'] = $this->t('Fortaleza principal');
        $header['created'] = $this->t('Fecha');

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_self_discovery\Entity\StrengthAssessment $entity */
        $row['id'] = $entity->id();

        $owner = $entity->getOwner();
        $row['user'] = $owner ? $owner->getDisplayName() : $this->t('Desconocido');

        $top = $entity->getTopStrength();
        $row['top_strength'] = $top['name'] ?? $this->t('N/A');

        $created = $entity->get('created')->value;
        $row['created'] = \Drupal::service('date.formatter')->format($created, 'short');

        return $row + parent::buildRow($entity);
    }

}
