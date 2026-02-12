<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for CandidateProfile entity.
 */
interface CandidateProfileInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the full name.
     */
    public function getFullName(): string;

    /**
     * Gets the professional headline.
     */
    public function getHeadline(): string;

    /**
     * Gets the professional summary.
     */
    public function getSummary(): string;

    /**
     * Gets availability status.
     */
    public function getAvailability(): string;

    /**
     * Checks if actively looking for jobs.
     */
    public function isActivelyLooking(): bool;

    /**
     * Gets profile completion percentage.
     */
    public function getCompletionPercent(): int;

    /**
     * Sets profile completion percentage.
     */
    public function setCompletionPercent(int $percent): CandidateProfileInterface;

    /**
     * Gets years of experience.
     */
    public function getExperienceYears(): int;

    /**
     * Gets highest education level.
     */
    public function getEducationLevel(): string;

    /**
     * Gets city.
     */
    public function getCity(): string;

    /**
     * Gets preferred job types.
     */
    public function getPreferredJobTypes(): array;

    /**
     * Gets preferred remote types.
     */
    public function getPreferredRemoteTypes(): array;

    /**
     * Gets salary expectation.
     */
    public function getSalaryExpectation(): ?float;

    /**
     * Checks if profile is public.
     */
    public function isPublic(): bool;

}
