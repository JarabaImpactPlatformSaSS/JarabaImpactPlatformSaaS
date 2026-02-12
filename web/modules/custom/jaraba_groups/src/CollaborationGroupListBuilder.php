<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Collaboration Group entities.
 */
class CollaborationGroupListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['type'] = $this->t('Tipo');
        $header['members'] = $this->t('Miembros');
        $header['visibility'] = $this->t('Visibilidad');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_groups\Entity\CollaborationGroup $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink();
        $row['type'] = $entity->getGroupType();
        $row['members'] = $entity->getMemberCount();
        $row['visibility'] = $entity->getVisibility();
        $row['status'] = $entity->get('status')->value;
        return $row + parent::buildRow($entity);
    }

}
