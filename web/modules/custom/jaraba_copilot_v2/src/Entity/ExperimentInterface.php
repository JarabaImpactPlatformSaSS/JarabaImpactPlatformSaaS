<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad Experiment.
 */
interface ExperimentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el tipo de experimento (del catálogo de 44).
     *
     * @return string
     *   ID del tipo de experimento.
     */
    public function getExperimentType(): string;

    /**
     * Obtiene la hipótesis asociada.
     *
     * @return int|null
     *   ID de la hipótesis o NULL.
     */
    public function getHypothesisId(): ?int;

    /**
     * Obtiene el estado del experimento.
     *
     * @return string
     *   Estado (PLANNED, IN_PROGRESS, COMPLETED).
     */
    public function getStatus(): string;

    /**
     * Obtiene la decisión tomada tras el experimento.
     *
     * @return string|null
     *   Decisión (PERSEVERE, PIVOT, ZOOM_IN, ZOOM_OUT, KILL) o NULL.
     */
    public function getDecision(): ?string;

}
