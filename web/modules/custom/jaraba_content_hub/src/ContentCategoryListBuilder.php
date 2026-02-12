<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of ContentCategory entities.
 */
class ContentCategoryListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Name');
        $header['slug'] = $this->t('Slug');
        $header['color'] = $this->t('Color');
        $header['articles'] = $this->t('Articles');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_content_hub\Entity\ContentCategory $entity */
        $row['name'] = $entity->toLink();
        $row['slug'] = $entity->getSlug();

        // Color swatch.
        $color = $entity->getColor();
        $row['color'] = [
            'data' => [
                '#markup' => '<span style="display:inline-block;width:20px;height:20px;background:' . $color . ';border-radius:4px;"></span> ' . $color,
            ],
        ];

        // Count articles in this category.
        $count = \Drupal::entityQuery('content_article')
            ->condition('category', $entity->id())
            ->accessCheck(TRUE)
            ->count()
            ->execute();
        $row['articles'] = $count;

        return $row + parent::buildRow($entity);
    }

}
