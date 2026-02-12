<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a journey state entity type.
 */
interface JourneyStateInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the avatar type.
     *
     * @return string
     *   The avatar type (e.g., 'productor', 'job_seeker', 'emprendedor').
     */
    public function getAvatarType(): string;

    /**
     * Gets the current journey state.
     *
     * @return string
     *   The journey state (discovery, activation, engagement, etc.).
     */
    public function getJourneyState(): string;

    /**
     * Sets the journey state.
     *
     * @param string $state
     *   The new journey state.
     *
     * @return $this
     */
    public function setJourneyState(string $state): self;

    /**
     * Gets the current step within the journey state.
     *
     * @return int
     *   The current step number.
     */
    public function getCurrentStep(): int;

    /**
     * Gets the list of completed steps.
     *
     * @return array
     *   Array of completed step numbers.
     */
    public function getCompletedSteps(): array;

    /**
     * Gets the user context data.
     *
     * @return array
     *   Context data including last_action, time_in_state, risk_score, etc.
     */
    public function getContext(): array;

    /**
     * Sets the user context data.
     *
     * @param array $context
     *   The context data.
     *
     * @return $this
     */
    public function setContext(array $context): self;

    /**
     * Gets pending triggers for IA intervention.
     *
     * @return array
     *   Array of pending trigger IDs.
     */
    public function getPendingTriggers(): array;

}
