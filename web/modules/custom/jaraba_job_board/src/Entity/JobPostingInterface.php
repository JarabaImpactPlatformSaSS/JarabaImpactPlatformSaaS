<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for JobPosting entity.
 */
interface JobPostingInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the job title.
     */
    public function getTitle(): string;

    /**
     * Gets the reference code.
     */
    public function getReferenceCode(): string;

    /**
     * Gets the status.
     */
    public function getStatus(): string;

    /**
     * Sets the status.
     */
    public function setStatus(string $status): JobPostingInterface;

    /**
     * Checks if job is published.
     */
    public function isPublished(): bool;

    /**
     * Publishes the job.
     */
    public function publish(): JobPostingInterface;

    /**
     * Closes the job.
     */
    public function close(): JobPostingInterface;

    /**
     * Gets the employer ID.
     */
    public function getEmployerId(): int;

    /**
     * Gets the location city.
     */
    public function getLocationCity(): string;

    /**
     * Gets the job type.
     */
    public function getJobType(): string;

    /**
     * Gets the remote type.
     */
    public function getRemoteType(): string;

    /**
     * Gets salary range.
     */
    public function getSalaryRange(): array;

    /**
     * Gets required skills.
     */
    public function getSkillsRequired(): array;

    /**
     * Checks if job is featured.
     */
    public function isFeatured(): bool;

    /**
     * Gets applications count.
     */
    public function getApplicationsCount(): int;

    /**
     * Increments applications count.
     */
    public function incrementApplicationsCount(): JobPostingInterface;

}
