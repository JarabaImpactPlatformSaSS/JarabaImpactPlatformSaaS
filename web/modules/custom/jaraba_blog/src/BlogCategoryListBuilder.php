<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para categorias del blog.
 */
class BlogCategoryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Nombre');
    $header['slug'] = $this->t('Slug');
    $header['posts_count'] = $this->t('Entradas');
    $header['active'] = $this->t('Activa');
    $header['weight'] = $this->t('Orden');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_blog\Entity\BlogCategory $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->getName();
    $row['slug'] = $entity->getSlug();
    $row['posts_count'] = $entity->getPostsCount();
    $row['active'] = $entity->isActive() ? $this->t('Si') : $this->t('No');
    $row['weight'] = $entity->get('weight')->value;

    return $row + parent::buildRow($entity);
  }

}
