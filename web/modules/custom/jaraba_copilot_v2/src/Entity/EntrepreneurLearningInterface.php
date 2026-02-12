<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for the EntrepreneurLearning entity.
 */
interface EntrepreneurLearningInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the hypothesis that was tested.
     */
    public function getHypothesis(): string;

    /**
     * Gets the key insight from the experiment.
     */
    public function getKeyInsight(): string;

    /**
     * Gets whether the hypothesis was validated.
     *
     * @return bool|null
     *   TRUE if validated, FALSE if invalidated, NULL if inconclusive.
     */
    public function isValidated(): ?bool;

    /**
     * Gets the decision made (persevere, pivot, iterate).
     */
    public function getDecision(): string;

    /**
     * Gets the BMC block affected.
     */
    public function getBmcBlock(): string;

}
