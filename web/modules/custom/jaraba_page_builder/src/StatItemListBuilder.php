<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para StatItem.
 */
class StatItemListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['label'] = $this->t('Etiqueta');
        $header['value'] = $this->t('Valor');
        $header['suffix'] = $this->t('Sufijo');
        $header['weight'] = $this->t('Peso');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_page_builder\Entity\StatItem $entity */
        $row['label'] = $entity->getLabel();
        $row['value'] = $entity->getValue();
        $row['suffix'] = $entity->getSuffix();
        $row['weight'] = $entity->getWeight();
        return $row + parent::buildRow($entity);
    }

}
