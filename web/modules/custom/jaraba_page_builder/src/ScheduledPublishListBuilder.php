<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder para la entidad ScheduledPublish.
 *
 * P1-05: Muestra la lista de publicaciones programadas con columnas
 * de estado, fecha, pagina y accion.
 */
class ScheduledPublishListBuilder extends EntityListBuilder {

  /**
   * El formateador de fechas.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
    );
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Titulo');
    $header['page'] = $this->t('Pagina');
    $header['action'] = $this->t('Accion');
    $header['scheduled_at'] = $this->t('Fecha programada');
    $header['schedule_status'] = $this->t('Estado');
    $header['author'] = $this->t('Creado por');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_page_builder\Entity\ScheduledPublish $entity */
    $page = $entity->getPageContent();

    $row['label'] = $entity->toLink();
    $row['page'] = $page ? $page->toLink() : $this->t('(eliminada)');

    $actionLabels = [
      'publish' => $this->t('Publicar'),
      'unpublish' => $this->t('Despublicar'),
    ];
    $row['action'] = $actionLabels[$entity->getAction()] ?? $entity->getAction();

    $scheduledAt = $entity->getScheduledTimestamp();
    $row['scheduled_at'] = $scheduledAt
      ? $this->dateFormatter->format($scheduledAt, 'short')
      : '';

    $statusLabels = [
      'pending' => $this->t('Pendiente'),
      'processing' => $this->t('Procesando'),
      'completed' => $this->t('Completada'),
      'failed' => $this->t('Error'),
      'cancelled' => $this->t('Cancelada'),
    ];
    $status = $entity->getScheduleStatus();
    $row['schedule_status'] = $statusLabels[$status] ?? $status;

    $owner = $entity->getOwner();
    $row['author'] = $owner ? $owner->getDisplayName() : '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('scheduled_at', 'ASC');
    return $query->execute();
  }

}
