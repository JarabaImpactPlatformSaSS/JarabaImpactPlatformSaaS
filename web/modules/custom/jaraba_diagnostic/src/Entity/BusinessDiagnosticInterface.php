<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad BusinessDiagnostic.
 *
 * Define los métodos de acceso para diagnósticos empresariales.
 */
interface BusinessDiagnosticInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el nombre del negocio.
     *
     * @return string
     *   Nombre del negocio.
     */
    public function getBusinessName(): string;

    /**
     * Obtiene el sector del negocio.
     *
     * @return string
     *   Sector (comercio, servicios, agro, etc.).
     */
    public function getBusinessSector(): string;

    /**
     * Obtiene la puntuación global del diagnóstico.
     *
     * @return float
     *   Puntuación 0-100.
     */
    public function getOverallScore(): float;

    /**
     * Obtiene el nivel de madurez digital.
     *
     * @return string
     *   Nivel (analogico, basico, conectado, digitalizado, inteligente).
     */
    public function getMaturityLevel(): string;

    /**
     * Obtiene la pérdida anual estimada.
     *
     * @return float
     *   Pérdida en euros.
     */
    public function getEstimatedLoss(): float;

    /**
     * Comprueba si el diagnóstico está completado.
     *
     * @return bool
     *   TRUE si está completado.
     */
    public function isCompleted(): bool;

    /**
     * Establece la puntuación global.
     *
     * @param float $score
     *   Puntuación 0-100.
     *
     * @return self
     */
    public function setOverallScore(float $score): self;

    /**
     * Establece el nivel de madurez.
     *
     * @param string $level
     *   Nivel de madurez.
     *
     * @return self
     */
    public function setMaturityLevel(string $level): self;

    /**
     * Establece la pérdida estimada.
     *
     * @param float $loss
     *   Pérdida en euros.
     *
     * @return self
     */
    public function setEstimatedLoss(float $loss): self;

    /**
     * Marca el diagnóstico como completado.
     *
     * @return self
     */
    public function markCompleted(): self;

}
