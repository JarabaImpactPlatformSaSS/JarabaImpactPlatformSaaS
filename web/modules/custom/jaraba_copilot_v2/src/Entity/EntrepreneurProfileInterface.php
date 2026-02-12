<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad EntrepreneurProfile.
 */
interface EntrepreneurProfileInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el nombre del emprendedor.
     *
     * @return string
     *   Nombre del emprendedor.
     */
    public function getName(): string;

    /**
     * Obtiene el carril asignado (IMPULSO o ACELERA).
     *
     * @return string
     *   Carril asignado.
     */
    public function getCarril(): string;

    /**
     * Obtiene la puntuación total DIME.
     *
     * @return int
     *   Puntuación DIME (0-20).
     */
    public function getDimeScore(): int;

    /**
     * Obtiene la semana actual del programa.
     *
     * @return int
     *   Número de semana (0-12).
     */
    public function getCurrentProgramWeek(): int;

    /**
     * Obtiene la fase actual del programa.
     *
     * @return string
     *   Fase (INVENTARIO, VALIDACION, MVP, TRACCION).
     */
    public function getPhase(): string;

    /**
     * Obtiene los puntos de impacto acumulados.
     *
     * @return int
     *   Puntos de impacto.
     */
    public function getImpactPoints(): int;

    /**
     * Añade puntos de impacto.
     *
     * @param int $points
     *   Puntos a añadir.
     *
     * @return $this
     */
    public function addImpactPoints(int $points): self;

    /**
     * Obtiene los bloqueos emocionales detectados.
     *
     * @return array
     *   Array de bloqueos.
     */
    public function getDetectedBlockages(): array;

}
