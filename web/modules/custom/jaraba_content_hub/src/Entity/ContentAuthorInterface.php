<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for the ContentAuthor entity.
 *
 * Represents an editorial author profile within the Content Hub,
 * decoupled from Drupal user accounts. Allows per-tenant author
 * management with bio, avatar, and social links.
 *
 * Backported from jaraba_blog BlogAuthor and elevated to world-class.
 */
interface ContentAuthorInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the author's display name.
   *
   * @return string
   *   The public display name.
   */
  public function getDisplayName(): string;

  /**
   * Gets the URL slug.
   *
   * @return string
   *   The URL-friendly slug (e.g., 'jose-garcia').
   */
  public function getSlug(): string;

  /**
   * Sets the URL slug.
   *
   * @param string $slug
   *   The slug to set.
   *
   * @return static
   */
  public function setSlug(string $slug): static;

  /**
   * Gets the author biography.
   *
   * @return string
   *   The biography text.
   */
  public function getBio(): string;

  /**
   * Checks if the author is active.
   *
   * @return bool
   *   TRUE if the author profile is active.
   */
  public function isActive(): bool;

  /**
   * Gets the cached count of published articles.
   *
   * @return int
   *   The number of published articles by this author.
   */
  public function getPostsCount(): int;

  /**
   * Gets the tenant ID (Group entity) that owns this author.
   *
   * @return int|null
   *   The group entity ID, or NULL if not assigned.
   */
  public function getTenantId(): ?int;

  /**
   * Gets the social media links.
   *
   * @return array
   *   Associative array with keys: 'twitter', 'linkedin', 'website'.
   *   Only populated entries are returned.
   */
  public function getSocialLinks(): array;

}
