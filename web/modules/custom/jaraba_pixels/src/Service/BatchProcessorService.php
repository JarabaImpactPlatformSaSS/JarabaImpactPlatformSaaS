<?php

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para procesar lotes de eventos de la cola.
 *
 * Lee eventos de Redis, los agrupa por plataforma,
 * y los envía en batch para optimizar llamadas API.
 */
class BatchProcessorService
{

    /**
     * Tamaño máximo del lote a procesar.
     */
    protected const DEFAULT_BATCH_SIZE = 100;

    /**
     * Máximo de reintentos por evento.
     */
    protected const MAX_RETRIES = 3;

    /**
     * Servicio de cola Redis.
     *
     * @var \Drupal\jaraba_pixels\Service\RedisQueueService
     */
    protected RedisQueueService $queue;

    /**
     * Dispatcher de pixels.
     *
     * @var \Drupal\jaraba_pixels\Service\PixelDispatcherService
     */
    protected PixelDispatcherService $dispatcher;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        RedisQueueService $queue,
        PixelDispatcherService $dispatcher,
        EntityTypeManagerInterface $entity_type_manager,
        $logger_factory,
    ) {
        $this->queue = $queue;
        $this->dispatcher = $dispatcher;
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger_factory->get('jaraba_pixels.batch');
    }

    /**
     * Procesa un lote de eventos de la cola.
     *
     * @param int $batch_size
     *   Tamaño máximo del lote.
     *
     * @return int
     *   Número de eventos procesados exitosamente.
     */
    public function process(int $batch_size = self::DEFAULT_BATCH_SIZE): int
    {
        if (!$this->queue->isAvailable()) {
            $this->logger->debug('Queue not available, skipping batch processing');
            return 0;
        }

        $events = $this->queue->dequeue($batch_size);

        if (empty($events)) {
            return 0;
        }

        $this->logger->info('Processing batch of @count events', [
            '@count' => count($events),
        ]);

        $processed = 0;
        $failed = [];

        foreach ($events as $event_data) {
            try {
                $success = $this->processEvent($event_data);

                if ($success) {
                    $processed++;
                } else {
                    $failed[] = $event_data;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error processing event @id: @message', [
                    '@id' => $event_data['event_id'] ?? 'unknown',
                    '@message' => $e->getMessage(),
                ]);
                $failed[] = $event_data;
            }
        }

        // Re-encolar eventos fallidos para reintento.
        foreach ($failed as $event_data) {
            $this->queue->requeue($event_data, self::MAX_RETRIES);
        }

        $this->logger->info('Batch complete: @processed success, @failed requeued', [
            '@processed' => $processed,
            '@failed' => count($failed),
        ]);

        return $processed;
    }

    /**
     * Procesa un evento individual.
     *
     * @param array $event_data
     *   Datos del evento.
     *
     * @return bool
     *   TRUE si se procesó correctamente.
     */
    protected function processEvent(array $event_data): bool
    {
        // Opción 1: Cargar la entidad original (si existe).
        if (!empty($event_data['entity_id'])) {
            try {
                $storage = $this->entityTypeManager->getStorage('analytics_event');
                $entity = $storage->load($event_data['entity_id']);

                if ($entity) {
                    $this->dispatcher->dispatch($entity);
                    return TRUE;
                }
            } catch (\Exception $e) {
                // Entidad puede haber sido eliminada, continuar con datos en cache.
                $this->logger->debug('Entity @id not found, using cached data', [
                    '@id' => $event_data['entity_id'],
                ]);
            }
        }

        // Opción 2: Dispatch directo con datos cacheados.
        return $this->dispatchFromCachedData($event_data);
    }

    /**
     * Dispatch directo usando datos cacheados (sin entidad).
     *
     * @param array $event_data
     *   Datos del evento cacheados.
     *
     * @return bool
     *   TRUE si se procesó correctamente.
     */
    protected function dispatchFromCachedData(array $event_data): bool
    {
        // Verificar datos mínimos requeridos.
        if (empty($event_data['tenant_id']) || empty($event_data['event_type'])) {
            return FALSE;
        }

        $tenantId = (int) $event_data['tenant_id'];

        try {
            $this->dispatcher->dispatchFromData($tenantId, $event_data);

            $this->logger->debug('Dispatched event @id from cache for tenant @tid', [
                '@id' => $event_data['event_id'] ?? 'unknown',
                '@tid' => $tenantId,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error dispatching cached event @id: @msg', [
                '@id' => $event_data['event_id'] ?? 'unknown',
                '@msg' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Agrupa eventos por plataforma para batch processing.
     *
     * @param array $events
     *   Array de eventos.
     *
     * @return array
     *   Eventos agrupados por plataforma.
     */
    public function groupByPlatform(array $events): array
    {
        $grouped = [
            'meta' => [],
            'google' => [],
            'linkedin' => [],
            'tiktok' => [],
        ];

        foreach ($events as $event) {
            $tenant_id = $event['tenant_id'] ?? 0;

            // Determinar plataformas activas para este tenant.
            // En V2.1 se optimizará con cache de credenciales.
            foreach (array_keys($grouped) as $platform) {
                $grouped[$platform][] = $event;
            }
        }

        return $grouped;
    }

    /**
     * Obtiene estadísticas del procesador.
     *
     * @return array
     *   Estadísticas del batch processor.
     */
    public function getStats(): array
    {
        return [
            'queue_available' => $this->queue->isAvailable(),
            'queue_length' => $this->queue->getQueueLength(),
            'batch_size' => self::DEFAULT_BATCH_SIZE,
            'max_retries' => self::MAX_RETRIES,
        ];
    }

}
