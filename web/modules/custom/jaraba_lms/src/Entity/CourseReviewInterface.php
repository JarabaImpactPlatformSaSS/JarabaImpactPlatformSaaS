<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interfaz para la entidad CourseReview.
 */
interface CourseReviewInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Devuelve el ID del curso evaluado.
   */
  public function getCourseId(): ?int;

  /**
   * Indica si la matricula esta verificada.
   */
  public function isVerifiedEnrollment(): bool;

  /**
   * Devuelve el progreso (0-100) del alumno al momento de la resena.
   */
  public function getProgressAtReview(): int;

}
