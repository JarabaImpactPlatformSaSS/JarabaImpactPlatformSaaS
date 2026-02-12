<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para autores del blog.
 */
class BlogAuthorListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['display_name'] = $this->t('Nombre');
    $header['posts_count'] = $this->t('Entradas');
    $header['active'] = $this->t('Activo');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_blog\Entity\BlogAuthor $entity */
    $row['id'] = $entity->id();
    $row['display_name'] = $entity->getDisplayName();
    $row['posts_count'] = $entity->getPostsCount();
    $row['active'] = $entity->isActive() ? $this->t('Si') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
