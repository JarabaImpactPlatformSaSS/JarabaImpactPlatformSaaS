<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for Business Model Canvas entities.
 */
interface BusinessModelCanvasInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Gets the canvas title.
     */
    public function getTitle(): string;

    /**
     * Sets the canvas title.
     */
    public function setTitle(string $title): self;

    /**
     * Gets the canvas description.
     */
    public function getDescription(): ?string;

    /**
     * Gets the business sector.
     */
    public function getSector(): string;

    /**
     * Gets the business stage.
     */
    public function getBusinessStage(): string;

    /**
     * Gets the current version number.
     */
    public function getVersion(): int;

    /**
     * Increments the version number.
     */
    public function incrementVersion(): self;

    /**
     * Gets the completeness score (0-100).
     */
    public function getCompletenessScore(): float;

    /**
     * Gets the AI coherence score (0-100).
     */
    public function getCoherenceScore(): ?float;

    /**
     * Sets the AI coherence score.
     */
    public function setCoherenceScore(float $score): self;

    /**
     * Gets the canvas status.
     */
    public function getStatus(): string;

    /**
     * Sets the canvas status.
     */
    public function setStatus(string $status): self;

    /**
     * Checks if this is a template.
     */
    public function isTemplate(): bool;

    /**
     * Gets the list of collaborator UIDs.
     */
    public function getSharedWith(): array;

    /**
     * Adds a collaborator by UID.
     */
    public function addCollaborator(int $uid): self;

    /**
     * Gets the linked diagnostic ID.
     */
    public function getDiagnosticId(): ?int;

}
