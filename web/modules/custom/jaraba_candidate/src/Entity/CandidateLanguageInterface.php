<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for CandidateLanguage entities.
 */
interface CandidateLanguageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the language code (ISO 639-1).
     */
    public function getLanguageCode(): string;

    /**
     * Gets the language name.
     */
    public function getLanguageName(): string;

    /**
     * Gets the overall proficiency level (CEFR).
     */
    public function getProficiencyLevel(): string;

    /**
     * Checks if this is a native language.
     */
    public function isNative(): bool;

    /**
     * Gets the certification name.
     */
    public function getCertification(): ?string;

}
