<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for CandidateEducation entities.
 */
interface CandidateEducationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the institution name.
     */
    public function getInstitution(): string;

    /**
     * Gets the degree.
     */
    public function getDegree(): string;

    /**
     * Gets the field of study.
     */
    public function getFieldOfStudy(): string;

    /**
     * Gets the start date timestamp.
     */
    public function getStartDate(): ?int;

    /**
     * Gets the end date timestamp (NULL if ongoing).
     */
    public function getEndDate(): ?int;

}
