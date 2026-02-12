<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Group Resource entities.
 */
class GroupResourceListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Título');
        $header['type'] = $this->t('Tipo');
        $header['downloads'] = $this->t('Descargas');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_groups\Entity\GroupResource $entity */
        $row['id'] = $entity->id();
        $row['title'] = $entity->toLink();
        $row['type'] = $entity->getResourceType();
        $row['downloads'] = $entity->getDownloadCount();
        return $row + parent::buildRow($entity);
    }

}
