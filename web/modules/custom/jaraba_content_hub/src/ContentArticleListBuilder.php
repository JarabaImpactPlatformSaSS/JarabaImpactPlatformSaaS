<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of ContentArticle entities.
 */
class ContentArticleListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Title');
        $header['category'] = $this->t('Category');
        $header['status'] = $this->t('Status');
        $header['author'] = $this->t('Author');
        $header['created'] = $this->t('Created');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $entity */
        $row['title'] = $entity->toLink();

        // Category.
        $category = $entity->get('category')->entity;
        $row['category'] = $category ? $category->label() : '-';

        // Status with badge styling.
        $status = $entity->getPublicationStatus();
        $status_labels = [
            'draft' => $this->t('Draft'),
            'review' => $this->t('In Review'),
            'scheduled' => $this->t('Scheduled'),
            'published' => $this->t('Published'),
            'archived' => $this->t('Archived'),
        ];
        $row['status'] = $status_labels[$status] ?? $status;

        // Author.
        $author = $entity->getOwner();
        $row['author'] = $author ? $author->getDisplayName() : '-';

        // Created date.
        $row['created'] = \Drupal::service('date.formatter')
            ->format($entity->get('created')->value, 'short');

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOperations(EntityInterface $entity): array
    {
        $operations = parent::getDefaultOperations($entity);

        // Add a "Publish" operation if not published.
        if ($entity->getPublicationStatus() !== 'published') {
            $operations['publish'] = [
                'title' => $this->t('Publish'),
                'weight' => 5,
                'url' => $entity->toUrl('edit-form'),
            ];
        }

        return $operations;
    }

}
