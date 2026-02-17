<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de resenas en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/servicios-reviews.
 *
 * Logica: Muestra columnas clave: Profesional (linked), Valoracion
 *   (estrellas), Comentario (truncado 80 chars), Estado y Fecha de creacion.
 */
class ReviewServiciosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['provider_id'] = $this->t('Profesional');
    $header['rating'] = $this->t('Valoracion');
    $header['comment'] = $this->t('Comentario');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'approved' => $this->t('Aprobada'),
      'rejected' => $this->t('Rechazada'),
    ];

    $provider = $entity->get('provider_id')->entity;
    $rating = (int) $entity->get('rating')->value;
    $comment = $entity->get('comment')->value ?? '';
    $status = $entity->get('status')->value;
    $created = $entity->get('created')->value;

    // Generar estrellas visuales.
    $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

    // Truncar comentario a 80 caracteres.
    $truncated_comment = mb_strlen($comment) > 80
      ? mb_substr($comment, 0, 80) . '...'
      : $comment;

    $row['provider_id'] = $provider ? $provider->toLink($provider->get('display_name')->value) : '-';
    $row['rating'] = $stars . ' (' . $rating . ')';
    $row['comment'] = $truncated_comment;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['created'] = $created ? \Drupal::service('date.formatter')->format((int) $created, 'short') : '-';
    return $row + parent::buildRow($entity);
  }

}
