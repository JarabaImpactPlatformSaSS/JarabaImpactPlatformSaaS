<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for the ContentArticle entity.
 */
interface ContentArticleInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the article title.
     */
    public function getTitle(): string;

    /**
     * Gets the URL slug.
     */
    public function getSlug(): string;

    /**
     * Gets the excerpt.
     */
    public function getExcerpt(): string;

    /**
     * Gets the answer capsule for GEO optimization.
     */
    public function getAnswerCapsule(): string;

    /**
     * Gets the publication status.
     */
    public function getPublicationStatus(): string;

    /**
     * Checks if the article is published.
     */
    public function isPublished(): bool;

    /**
     * Gets the reading time in minutes.
     */
    public function getReadingTime(): int;

    /**
     * Checks if the article was AI-generated.
     */
    public function isAiGenerated(): bool;

    /**
     * Gets the layout mode.
     *
     * @return string
     *   'legacy' for classic textarea, 'canvas' for visual editor.
     */
    public function getLayoutMode(): string;

    /**
     * Checks if the article uses the Canvas Editor.
     */
    public function isCanvasMode(): bool;

    /**
     * Gets the GrapesJS Canvas JSON data.
     */
    public function getCanvasData(): string;

    /**
     * Gets the rendered HTML from the Canvas.
     */
    public function getRenderedHtml(): string;

}
