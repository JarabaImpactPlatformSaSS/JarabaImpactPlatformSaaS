<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for CandidateExperience entities.
 */
interface CandidateExperienceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the company name.
     */
    public function getCompanyName(): string;

    /**
     * Gets the job title.
     */
    public function getJobTitle(): string;

    /**
     * Gets the start date timestamp.
     */
    public function getStartDate(): ?int;

    /**
     * Gets the end date timestamp (NULL if current).
     */
    public function getEndDate(): ?int;

    /**
     * Checks if this is the current position.
     */
    public function isCurrent(): bool;

    /**
     * Gets the description.
     */
    public function getDescription(): string;

    /**
     * Gets the location.
     */
    public function getLocation(): string;

}
