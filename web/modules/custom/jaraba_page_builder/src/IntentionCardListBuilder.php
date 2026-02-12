<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para IntentionCard.
 */
class IntentionCardListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Título');
        $header['url'] = $this->t('URL');
        $header['icon'] = $this->t('Icono');
        $header['color_class'] = $this->t('Color');
        $header['weight'] = $this->t('Peso');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_page_builder\Entity\IntentionCard $entity */
        $row['title'] = $entity->getTitle();
        $row['url'] = $entity->getUrl();
        $row['icon'] = $entity->getIcon();
        $row['color_class'] = $entity->getColorClass();
        $row['weight'] = $entity->getWeight();
        return $row + parent::buildRow($entity);
    }

}
