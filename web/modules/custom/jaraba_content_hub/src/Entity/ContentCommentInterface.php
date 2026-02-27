<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interfaz para la entidad ContentComment.
 */
interface ContentCommentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Indica si el comentario esta aprobado.
   */
  public function isApproved(): bool;

  /**
   * Devuelve el ID del articulo comentado.
   */
  public function getArticleId(): ?int;

  /**
   * Devuelve el ID del comentario padre (NULL si es raiz).
   */
  public function getParentId(): ?int;

}
