<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para listar eventos de Línea de Vida.
 */
class LifeTimelineListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('Usuario');
        $header['title'] = $this->t('Título');
        $header['type'] = $this->t('Tipo');
        $header['date'] = $this->t('Fecha evento');
        $header['created'] = $this->t('Creado');

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_self_discovery\Entity\LifeTimeline $entity */
        $row['id'] = $entity->id();

        $owner = $entity->getOwner();
        $row['user'] = $owner ? $owner->getDisplayName() : $this->t('Desconocido');

        $row['title'] = $entity->get('title')->value;
        $row['type'] = $entity->getEventTypeLabel();
        $row['date'] = $entity->get('event_date')->value ?? '';

        $created = $entity->get('created')->value;
        $row['created'] = \Drupal::service('date.formatter')->format($created, 'short');

        return $row + parent::buildRow($entity);
    }

}
