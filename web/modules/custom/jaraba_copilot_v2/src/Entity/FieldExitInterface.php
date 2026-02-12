<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a field exit entity type.
 */
interface FieldExitInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the exit type.
     *
     * @return string
     *   The exit type (interview, observation, survey, etc.).
     */
    public function getExitType(): string;

    /**
     * Gets the number of contacts in this exit.
     *
     * @return int
     *   Number of people contacted.
     */
    public function getContactsCount(): int;

    /**
     * Gets the key learnings from this exit.
     *
     * @return string
     *   Learnings text.
     */
    public function getLearnings(): string;

    /**
     * Gets whether the hypothesis was validated.
     *
     * @return bool|null
     *   TRUE if validated, FALSE if invalidated, NULL if not applicable.
     */
    public function getHypothesisValidated(): ?bool;

}
