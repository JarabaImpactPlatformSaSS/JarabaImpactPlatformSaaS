<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for Enrollment entity.
 */
interface EnrollmentInterface extends ContentEntityInterface, EntityChangedInterface
{

    /**
     * Gets the enrolled user ID.
     */
    public function getUserId(): int;

    /**
     * Gets the course ID.
     */
    public function getCourseId(): int;

    /**
     * Gets the course entity.
     */
    public function getCourse(): ?CourseInterface;

    /**
     * Gets the enrollment status.
     */
    public function getStatus(): string;

    /**
     * Sets the enrollment status.
     */
    public function setStatus(string $status): EnrollmentInterface;

    /**
     * Checks if enrollment is active.
     */
    public function isActive(): bool;

    /**
     * Checks if enrollment is completed.
     */
    public function isCompleted(): bool;

    /**
     * Gets progress percentage.
     */
    public function getProgressPercent(): float;

    /**
     * Sets progress percentage.
     */
    public function setProgressPercent(float $percent): EnrollmentInterface;

    /**
     * Gets enrollment type.
     */
    public function getEnrollmentType(): string;

    /**
     * Gets enrollment timestamp.
     */
    public function getEnrolledAt(): int;

    /**
     * Gets completion timestamp.
     */
    public function getCompletedAt(): ?int;

    /**
     * Marks enrollment as completed.
     */
    public function markCompleted(): EnrollmentInterface;

    /**
     * Checks if certificate was issued.
     */
    public function isCertificateIssued(): bool;

    /**
     * Sets certificate issued status.
     */
    public function setCertificateIssued(bool $issued, ?int $certificate_id = NULL): EnrollmentInterface;

}
