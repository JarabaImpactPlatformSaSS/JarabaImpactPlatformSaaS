<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para entradas del blog.
 */
class BlogPostListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Titulo');
    $header['category'] = $this->t('Categoria');
    $header['author'] = $this->t('Autor');
    $header['status'] = $this->t('Estado');
    $header['views'] = $this->t('Visitas');
    $header['created'] = $this->t('Fecha');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_blog\Entity\BlogPost $entity */
    $row['id'] = $entity->id();
    $row['title'] = $entity->getTitle();

    $category = $entity->get('category_id')->entity;
    $row['category'] = $category ? $category->getName() : '-';

    $author = $entity->get('author_id')->entity;
    $row['author'] = $author ? $author->getDisplayName() : '-';

    $row['status'] = $entity->getStatus();
    $row['views'] = $entity->getViewsCount();

    $created = $entity->get('created')->value;
    $row['created'] = \Drupal::service('date.formatter')->format((int) $created, 'short');

    return $row + parent::buildRow($entity);
  }

}
