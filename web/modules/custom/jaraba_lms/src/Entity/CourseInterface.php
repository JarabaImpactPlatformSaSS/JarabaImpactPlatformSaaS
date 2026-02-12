<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for Course entity.
 */
interface CourseInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the course title.
     */
    public function getTitle(): string;

    /**
     * Sets the course title.
     */
    public function setTitle(string $title): CourseInterface;

    /**
     * Gets the machine name.
     */
    public function getMachineName(): string;

    /**
     * Gets the description.
     */
    public function getDescription(): ?string;

    /**
     * Gets the summary.
     */
    public function getSummary(): string;

    /**
     * Gets duration in minutes.
     */
    public function getDurationMinutes(): int;

    /**
     * Gets difficulty level.
     */
    public function getDifficultyLevel(): string;

    /**
     * Checks if course is published.
     */
    public function isPublished(): bool;

    /**
     * Sets published status.
     */
    public function setPublished(bool $published): CourseInterface;

    /**
     * Checks if course is premium.
     */
    public function isPremium(): bool;

    /**
     * Gets price (if applicable).
     */
    public function getPrice(): ?float;

    /**
     * Gets completion credits.
     */
    public function getCompletionCredits(): int;

    /**
     * Gets tenant ID.
     */
    public function getTenantId(): ?int;

    /**
     * Gets prerequisite course IDs.
     */
    public function getPrerequisites(): array;

    /**
     * Gets tag taxonomy term IDs.
     */
    public function getTags(): array;

}
