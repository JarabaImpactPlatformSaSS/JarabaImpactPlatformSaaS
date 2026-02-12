<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for JobApplication entity.
 */
interface JobApplicationInterface extends ContentEntityInterface, EntityChangedInterface
{

    /**
     * Gets the job ID.
     */
    public function getJobId(): int;

    /**
     * Gets the job entity.
     */
    public function getJob(): ?JobPostingInterface;

    /**
     * Gets the candidate user ID.
     */
    public function getCandidateId(): int;

    /**
     * Gets the status.
     */
    public function getStatus(): string;

    /**
     * Sets the status.
     */
    public function setStatus(string $status): JobApplicationInterface;

    /**
     * Checks if application is active.
     */
    public function isActive(): bool;

    /**
     * Checks if candidate was hired.
     */
    public function isHired(): bool;

    /**
     * Gets match score.
     */
    public function getMatchScore(): ?float;

    /**
     * Sets match score.
     */
    public function setMatchScore(float $score): JobApplicationInterface;

    /**
     * Gets cover letter.
     */
    public function getCoverLetter(): ?string;

    /**
     * Gets application timestamp.
     */
    public function getAppliedAt(): int;

    /**
     * Marks as viewed by employer.
     */
    public function markAsViewed(): JobApplicationInterface;

    /**
     * Marks candidate as hired.
     */
    public function hire(?float $salary = NULL): JobApplicationInterface;

    /**
     * Rejects application.
     */
    public function reject(?string $reason = NULL, ?string $feedback = NULL): JobApplicationInterface;

}
