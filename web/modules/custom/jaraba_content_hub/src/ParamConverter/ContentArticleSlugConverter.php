<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\ParamConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts a slug parameter to a ContentArticle entity.
 *
 * Enables slug-based routing for blog articles (e.g., /blog/mi-articulo).
 * Falls back to numeric ID lookup for backwards compatibility with old URLs.
 */
class ContentArticleSlugConverter implements ParamConverterInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a ContentArticleSlugConverter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (empty($value)) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('content_article');

    // If numeric, load by ID for backwards compatibility.
    if (is_numeric($value)) {
      $entity = $storage->load($value);
      if ($entity) {
        return $entity;
      }
    }

    // Load by slug.
    $entities = $storage->loadByProperties(['slug' => $value]);
    if (!empty($entities)) {
      return reset($entities);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] === 'content_article_slug';
  }

}
