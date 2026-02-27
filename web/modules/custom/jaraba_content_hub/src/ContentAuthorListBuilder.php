<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of ContentAuthor entities.
 */
class ContentAuthorListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['display_name'] = $this->t('Name');
    $header['slug'] = $this->t('Slug');
    $header['is_active'] = $this->t('Active');
    $header['posts_count'] = $this->t('Articles');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_content_hub\Entity\ContentAuthorInterface $entity */
    $row['display_name'] = $entity->toLink();
    $row['slug'] = $entity->getSlug();
    $row['is_active'] = $entity->isActive() ? $this->t('Yes') : $this->t('No');
    $row['posts_count'] = $entity->getPostsCount();
    $row['created'] = \Drupal::service('date.formatter')
      ->format($entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}
