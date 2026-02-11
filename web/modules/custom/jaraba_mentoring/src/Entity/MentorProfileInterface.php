<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for Mentor Profile entities.
 */
interface MentorProfileInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the mentor's display name.
     */
    public function getDisplayName(): string;

    /**
     * Gets the mentor's headline.
     */
    public function getHeadline(): string;

    /**
     * Gets the mentor's hourly rate.
     */
    public function getHourlyRate(): float;

    /**
     * Gets the mentor's average rating.
     */
    public function getAverageRating(): float;

    /**
     * Checks if the mentor has completed Stripe onboarding.
     */
    public function hasStripeOnboarding(): bool;

    /**
     * Checks if the mentor is available for new clients.
     */
    public function isAvailable(): bool;

    /**
     * Gets the mentor's certification level.
     */
    public function getCertificationLevel(): string;

}
