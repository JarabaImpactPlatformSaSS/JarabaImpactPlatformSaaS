<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for CandidateSkill entities.
 */
interface CandidateSkillInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the skill term ID.
     */
    public function getSkillId(): ?int;

    /**
     * Gets the skill level.
     */
    public function getLevel(): string;

    /**
     * Gets years of experience with this skill.
     */
    public function getYearsExperience(): int;

    /**
     * Checks if this skill is verified.
     */
    public function isVerified(): bool;

}
