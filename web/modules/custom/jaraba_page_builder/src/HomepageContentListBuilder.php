<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para HomepageContent.
 */
class HomepageContentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Título');
        $header['features'] = $this->t('Features');
        $header['stats'] = $this->t('Stats');
        $header['intentions'] = $this->t('Intenciones');
        $header['changed'] = $this->t('Modificado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_page_builder\Entity\HomepageContent $entity */
        $row['title'] = $entity->label();
        $row['features'] = count($entity->getFeatures());
        $row['stats'] = count($entity->getStats());
        $row['intentions'] = count($entity->getIntentions());
        $row['changed'] = \Drupal::service('date.formatter')
            ->format($entity->getChangedTime(), 'short');
        return $row + parent::buildRow($entity);
    }

}
