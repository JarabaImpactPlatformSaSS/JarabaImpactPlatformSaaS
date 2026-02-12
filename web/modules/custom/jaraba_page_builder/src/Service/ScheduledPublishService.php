<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_page_builder\Entity\ScheduledPublish;
use Psr\Log\LoggerInterface;

/**
 * Servicio para procesar publicaciones programadas del Page Builder.
 *
 * P1-05: Ejecuta las acciones de publicacion/despublicacion programadas
 * cuando ha llegado su fecha. Llamado desde hook_cron() del modulo.
 *
 * FLUJO:
 * 1. Cron invoca processScheduledPublishes().
 * 2. Se buscan ScheduledPublish con status='pending' y fecha <= ahora.
 * 3. Para cada una, se ejecuta la accion (publish/unpublish) sobre PageContent.
 * 4. Se marca como 'completed' o 'failed' segun resultado.
 *
 * SEGURIDAD:
 * - Verifica que la pagina referenciada existe y es accesible.
 * - Limita el procesamiento por lote (max 50 por ejecucion de cron).
 * - Marca como 'processing' para evitar doble ejecucion.
 */
class ScheduledPublishService {

  use StringTranslationTrait;

  /**
   * Maximo de programaciones a procesar por ejecucion de cron.
   */
  protected const BATCH_SIZE = 50;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Procesa todas las publicaciones programadas pendientes.
   *
   * @return int
   *   Numero de programaciones procesadas.
   */
  public function processScheduledPublishes(): int {
    $now = \Drupal::time()->getRequestTime();
    $nowFormatted = DrupalDateTime::createFromTimestamp($now)->format('Y-m-d\TH:i:s');

    $storage = $this->entityTypeManager->getStorage('scheduled_publish');

    // Buscar programaciones pendientes cuya fecha ya paso.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('schedule_status', ScheduledPublish::STATUS_PENDING)
      ->condition('scheduled_at', $nowFormatted, '<=')
      ->sort('scheduled_at', 'ASC')
      ->range(0, self::BATCH_SIZE)
      ->execute();

    if (empty($ids)) {
      return 0;
    }

    $processed = 0;
    $schedules = $storage->loadMultiple($ids);

    foreach ($schedules as $schedule) {
      /** @var \Drupal\jaraba_page_builder\Entity\ScheduledPublish $schedule */
      $this->processOne($schedule);
      $processed++;
    }

    if ($processed > 0) {
      $this->logger->info(
        'Processed @count scheduled publishes.',
        ['@count' => $processed]
      );
    }

    return $processed;
  }

  /**
   * Procesa una unica programacion.
   *
   * @param \Drupal\jaraba_page_builder\Entity\ScheduledPublish $schedule
   *   La programacion a procesar.
   */
  protected function processOne(ScheduledPublish $schedule): void {
    // Marcar como procesando para evitar doble ejecucion.
    $schedule->set('schedule_status', ScheduledPublish::STATUS_PROCESSING);
    $schedule->save();

    try {
      $page = $schedule->getPageContent();
      if (!$page) {
        $schedule->markFailed((string) $this->t('La pagina referenciada no existe o fue eliminada.'));
        $schedule->save();
        $this->logger->warning(
          'Scheduled publish @id: page not found.',
          ['@id' => $schedule->id()]
        );
        return;
      }

      $action = $schedule->getAction();

      if ($action === ScheduledPublish::ACTION_PUBLISH) {
        $page->setPublished();
      }
      elseif ($action === ScheduledPublish::ACTION_UNPUBLISH) {
        $page->setUnpublished();
      }

      // Crear nueva revision para trazabilidad.
      if ($page->getEntityType()->isRevisionable()) {
        $page->setNewRevision(TRUE);
        $page->setRevisionLogMessage(
          (string) $this->t('Publicacion programada: @action ejecutada automaticamente.', [
            '@action' => $action === ScheduledPublish::ACTION_PUBLISH
              ? $this->t('publicar')
              : $this->t('despublicar'),
          ])
        );
      }

      $page->save();
      $schedule->markCompleted();
      $schedule->save();

      $this->logger->info(
        'Scheduled @action executed for page @page (schedule @id)',
        [
          '@action' => $action,
          '@page' => $page->label(),
          '@id' => $schedule->id(),
        ]
      );
    }
    catch (\Exception $e) {
      $schedule->markFailed($e->getMessage());
      $schedule->save();

      $this->logger->error(
        'Scheduled publish @id failed: @error',
        [
          '@id' => $schedule->id(),
          '@error' => $e->getMessage(),
        ]
      );
    }
  }

  /**
   * Obtiene las proximas publicaciones programadas.
   *
   * Util para mostrar en el dashboard del Page Builder.
   *
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return \Drupal\jaraba_page_builder\Entity\ScheduledPublish[]
   *   Array de programaciones pendientes ordenadas por fecha.
   */
  public function getUpcoming(int $limit = 10): array {
    $storage = $this->entityTypeManager->getStorage('scheduled_publish');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('schedule_status', ScheduledPublish::STATUS_PENDING)
      ->sort('scheduled_at', 'ASC')
      ->range(0, $limit)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Cancela una programacion pendiente.
   *
   * @param \Drupal\jaraba_page_builder\Entity\ScheduledPublish $schedule
   *   La programacion a cancelar.
   *
   * @return bool
   *   TRUE si se cancelo, FALSE si no era posible.
   */
  public function cancel(ScheduledPublish $schedule): bool {
    if (!$schedule->isPending()) {
      return FALSE;
    }

    $schedule->markCancelled();
    $schedule->save();

    $this->logger->info(
      'Scheduled publish @id cancelled.',
      ['@id' => $schedule->id()]
    );

    return TRUE;
  }

}
