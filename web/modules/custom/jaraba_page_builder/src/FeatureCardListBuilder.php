<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para FeatureCard.
 */
class FeatureCardListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Título');
        $header['badge'] = $this->t('Badge');
        $header['icon'] = $this->t('Icono');
        $header['weight'] = $this->t('Peso');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_page_builder\Entity\FeatureCard $entity */
        $row['title'] = $entity->getTitle();
        $row['badge'] = $entity->getBadge();
        $row['icon'] = $entity->getIcon();
        $row['weight'] = $entity->getWeight();
        return $row + parent::buildRow($entity);
    }

}
