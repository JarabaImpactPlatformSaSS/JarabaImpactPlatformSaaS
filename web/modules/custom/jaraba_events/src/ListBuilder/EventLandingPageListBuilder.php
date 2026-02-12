<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de landing pages de evento en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/event-landing-pages.
 *
 * Lógica: Muestra columnas clave para gestión rápida: título,
 *   evento asociado, layout, estado de publicación y visitas.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class EventLandingPageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Título');
    $header['event_id'] = $this->t('Evento');
    $header['layout'] = $this->t('Layout');
    $header['is_published'] = $this->t('Publicada');
    $header['views_count'] = $this->t('Visitas');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $layout_labels = [
      'standard' => $this->t('Estándar'),
      'minimal' => $this->t('Minimalista'),
      'full_width' => $this->t('Ancho Completo'),
      'video_hero' => $this->t('Vídeo Hero'),
    ];

    $event = $entity->get('event_id')->entity;
    $layout = $entity->get('layout')->value;

    $row['title'] = $entity->get('title')->value ?? '';
    $row['event_id'] = $event ? $event->label() : '-';
    $row['layout'] = $layout_labels[$layout] ?? $layout;
    $row['is_published'] = $entity->get('is_published')->value ? $this->t('Sí') : $this->t('No');
    $row['views_count'] = (string) ($entity->get('views_count')->value ?? 0);
    return $row + parent::buildRow($entity);
  }

}
