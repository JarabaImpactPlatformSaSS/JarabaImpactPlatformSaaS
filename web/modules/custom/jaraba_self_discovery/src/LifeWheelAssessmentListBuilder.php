<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para listar evaluaciones de Rueda de la Vida.
 *
 * PROPÓSITO:
 * Muestra una tabla administrativa con las evaluaciones creadas.
 */
class LifeWheelAssessmentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('Usuario');
        $header['average'] = $this->t('Promedio');
        $header['created'] = $this->t('Fecha');

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_self_discovery\Entity\LifeWheelAssessment $entity */
        $row['id'] = $entity->id();

        // Usuario propietario.
        $owner = $entity->getOwner();
        $row['user'] = $owner ? $owner->getDisplayName() : $this->t('Desconocido');

        // Promedio de puntuaciones.
        $row['average'] = $entity->getAverageScore() . '/10';

        // Fecha de creación.
        $created = $entity->get('created')->value;
        $row['created'] = \Drupal::service('date.formatter')->format($created, 'short');

        return $row + parent::buildRow($entity);
    }

}
