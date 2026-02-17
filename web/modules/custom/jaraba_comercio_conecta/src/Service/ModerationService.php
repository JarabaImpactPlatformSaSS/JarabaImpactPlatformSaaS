<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class ModerationService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.moderation');
  }

  public function addToQueue(string $entityType, int $entityId, string $moderationType, string $priority = 'normal'): bool {
    try {
      $valid_priorities = ['low', 'normal', 'high', 'urgent'];
      if (!in_array($priority, $valid_priorities)) {
        $priority = 'normal';
      }

      $storage = $this->entityTypeManager->getStorage('moderation_queue');

      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_type_ref', $entityType)
        ->condition('entity_id_ref', $entityId)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      if ((int) $existing > 0) {
        $this->logger->info('Ya existe moderacion pendiente para @type @id', [
          '@type' => $entityType,
          '@id' => $entityId,
        ]);
        return FALSE;
      }

      $queue_item = $storage->create([
        'title' => $entityType . ' #' . $entityId,
        'entity_type_ref' => $entityType,
        'entity_id_ref' => $entityId,
        'moderation_type' => $moderationType,
        'priority' => $priority,
        'status' => 'pending',
      ]);
      $queue_item->save();

      $this->logger->info('Elemento anadido a cola de moderacion: @type @id (prioridad: @priority)', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@priority' => $priority,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error anadiendo a cola de moderacion: @e', ['@e' => $e->getMessage()]);
      return FALSE;
    }
  }

  public function processModeration(int $queueId, string $decision, string $notes = ''): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('moderation_queue');
      $queue_item = $storage->load($queueId);
      if (!$queue_item) {
        return FALSE;
      }

      $valid_decisions = ['approved', 'rejected'];
      if (!in_array($decision, $valid_decisions)) {
        $this->logger->warning('Decision de moderacion no valida: @decision', ['@decision' => $decision]);
        return FALSE;
      }

      $queue_item->set('status', $decision);
      if ($notes && $queue_item->hasField('notes')) {
        $queue_item->set('notes', $notes);
      }
      $queue_item->save();

      $this->logger->info('Moderacion @id procesada: @decision', [
        '@id' => $queueId,
        '@decision' => $decision,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando moderacion @id: @e', [
        '@id' => $queueId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  public function getQueueStats(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('moderation_queue');

      $pending = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      $approved = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'approved')
        ->count()
        ->execute();

      $rejected = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'rejected')
        ->count()
        ->execute();

      return [
        'pending' => $pending,
        'approved' => $approved,
        'rejected' => $rejected,
        'total' => $pending + $approved + $rejected,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas de moderacion: @e', ['@e' => $e->getMessage()]);
      return [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total' => 0,
      ];
    }
  }

  public function assignModerator(int $queueId, int $userId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('moderation_queue');
      $queue_item = $storage->load($queueId);
      if (!$queue_item) {
        return FALSE;
      }

      $queue_item->set('assigned_to', $userId);
      $queue_item->set('status', 'under_review');
      $queue_item->save();

      $this->logger->info('Moderacion @id asignada a usuario @user', [
        '@id' => $queueId,
        '@user' => $userId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error asignando moderador: @e', ['@e' => $e->getMessage()]);
      return FALSE;
    }
  }

}
