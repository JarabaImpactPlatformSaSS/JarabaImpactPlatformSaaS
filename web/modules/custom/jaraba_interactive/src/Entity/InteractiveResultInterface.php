<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for InteractiveResult entities.
 *
 * Defines the contract for interactive result entities used by
 * CompletionSubscriber and XApiEmitter services.
 */
interface InteractiveResultInterface extends EntityInterface
{

  /**
   * Obtiene el contenido interactivo asociado.
   *
   * @return \Drupal\jaraba_interactive\Entity\InteractiveContentInterface|null
   *   La entidad de contenido interactivo o NULL.
   */
  public function getInteractiveContent(): ?InteractiveContentInterface;

  /**
   * Obtiene la puntuacion del usuario.
   *
   * @return float
   *   La puntuacion obtenida.
   */
  public function getScore(): float;

  /**
   * Verifica si el usuario aprobo.
   *
   * @return bool
   *   TRUE si aprobo, FALSE en caso contrario.
   */
  public function hasPassed(): bool;

  /**
   * Obtiene el ID del propietario de la entidad.
   *
   * @return int
   *   El user ID del propietario.
   */
  public function getOwnerId();

}
