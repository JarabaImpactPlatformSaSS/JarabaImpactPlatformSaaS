<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Group Discussion entities.
 */
class GroupDiscussionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Título');
        $header['category'] = $this->t('Categoría');
        $header['replies'] = $this->t('Respuestas');
        $header['views'] = $this->t('Vistas');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_groups\Entity\GroupDiscussion $entity */
        $row['id'] = $entity->id();
        $row['title'] = $entity->toLink();
        $row['category'] = $entity->getCategory();
        $row['replies'] = $entity->getReplyCount();
        $row['views'] = $entity->getViewCount();
        return $row + parent::buildRow($entity);
    }

}
