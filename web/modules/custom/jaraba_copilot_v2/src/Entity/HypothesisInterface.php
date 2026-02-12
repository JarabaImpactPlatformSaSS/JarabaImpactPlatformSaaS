<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad Hypothesis.
 */
interface HypothesisInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el enunciado de la hipótesis.
     *
     * @return string
     *   Enunciado de la hipótesis.
     */
    public function getStatement(): string;

    /**
     * Obtiene el tipo de hipótesis.
     *
     * @return string
     *   Tipo (DESIRABILITY, FEASIBILITY, VIABILITY).
     */
    public function getType(): string;

    /**
     * Obtiene el bloque BMC asociado.
     *
     * @return string
     *   Bloque del Business Model Canvas.
     */
    public function getBmcBlock(): string;

    /**
     * Obtiene el estado de validación.
     *
     * @return string
     *   Estado (PENDING, VALIDATED, INVALIDATED, INCONCLUSIVE).
     */
    public function getValidationStatus(): string;

    /**
     * Obtiene la puntuación de importancia.
     *
     * @return int
     *   Puntuación 1-5.
     */
    public function getImportanceScore(): int;

    /**
     * Obtiene la puntuación de evidencia actual.
     *
     * @return int
     *   Puntuación 1-5.
     */
    public function getEvidenceScore(): int;

}
