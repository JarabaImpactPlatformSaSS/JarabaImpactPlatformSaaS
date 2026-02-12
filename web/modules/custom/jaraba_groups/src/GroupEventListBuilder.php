<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Group Event entities.
 */
class GroupEventListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Título');
        $header['type'] = $this->t('Tipo');
        $header['date'] = $this->t('Fecha');
        $header['attendees'] = $this->t('Inscritos');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_groups\Entity\GroupEvent $entity */
        $row['id'] = $entity->id();
        $row['title'] = $entity->toLink();
        $row['type'] = $entity->getEventType();
        $row['date'] = $entity->getStartDatetime() ?? '-';
        $row['attendees'] = $entity->getCurrentAttendees() . '/' . ($entity->getMaxAttendees() ?? '∞');
        $row['status'] = $entity->get('status')->value;
        return $row + parent::buildRow($entity);
    }

}
