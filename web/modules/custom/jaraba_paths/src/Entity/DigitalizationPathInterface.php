<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para DigitalizationPath.
 */
interface DigitalizationPathInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el título del itinerario.
     */
    public function getTitle(): string;

    /**
     * Obtiene el sector objetivo.
     */
    public function getTargetSector(): string;

    /**
     * Obtiene el nivel de madurez objetivo.
     */
    public function getTargetMaturityLevel(): ?string;

    /**
     * Obtiene la duración estimada en semanas.
     */
    public function getEstimatedWeeks(): int;

    /**
     * Verifica si el itinerario está publicado.
     */
    public function isPublished(): bool;

    /**
     * Verifica si el itinerario es destacado.
     */
    public function isFeatured(): bool;

}
