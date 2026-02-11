<?php

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Site\Settings;
use Psr\Log\LoggerInterface;

/**
 * Servicio para encolar eventos de pixels en Redis.
 *
 * Implementa una cola FIFO para procesamiento asíncrono de eventos,
 * desacoplando la captura del evento del envío a plataformas externas.
 */
class RedisQueueService
{

    /**
     * Nombre de la cola en Redis.
     */
    protected const QUEUE_KEY = 'jaraba_pixels:queue';

    /**
     * TTL para mensajes en cola (24 horas).
     */
    protected const MESSAGE_TTL = 86400;

    /**
     * Cliente Redis.
     *
     * @var \Redis|null
     */
    protected $redis;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Indica si Redis está disponible.
     *
     * @var bool
     */
    protected bool $isConnected = FALSE;

    /**
     * Constructor.
     *
     * @param mixed $redis_factory
     *   Factory de Redis (puede ser null si Redis no está configurado).
     * @param mixed $logger_factory
     *   Factory de logger.
     */
    public function __construct(
        $redis_factory,
        $logger_factory,
    ) {
        $this->logger = $logger_factory->get('jaraba_pixels.queue');
        $this->initializeRedis($redis_factory);
    }

    /**
     * Inicializa la conexión a Redis.
     *
     * @param mixed $redis_factory
     *   Factory de Redis.
     */
    protected function initializeRedis($redis_factory): void
    {
        try {
            if ($redis_factory && method_exists($redis_factory, 'getClient')) {
                $this->redis = $redis_factory->getClient();
                $this->isConnected = $this->redis !== NULL;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Redis connection failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            $this->isConnected = FALSE;
        }
    }

    /**
     * Verifica si Redis está disponible.
     *
     * @return bool
     *   TRUE si Redis está conectado y disponible.
     */
    public function isAvailable(): bool
    {
        if (!$this->isConnected || !$this->redis) {
            return FALSE;
        }

        try {
            return $this->redis->ping() === TRUE || $this->redis->ping() === '+PONG';
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Encola un evento para procesamiento asíncrono.
     *
     * @param array $event_data
     *   Datos del evento a encolar.
     *
     * @return bool
     *   TRUE si se encoló correctamente, FALSE si falló.
     */
    public function enqueue(array $event_data): bool
    {
        if (!$this->isAvailable()) {
            return FALSE;
        }

        // Añadir metadata de cola.
        $event_data['enqueued_at'] = time();
        $event_data['retry_count'] = 0;

        $payload = json_encode($event_data);

        try {
            $result = $this->redis->rPush(self::QUEUE_KEY, $payload);

            if ($result) {
                $this->logger->debug('Event enqueued: @event_id', [
                    '@event_id' => $event_data['event_id'] ?? 'unknown',
                ]);
            }

            return $result > 0;
        } catch (\Exception $e) {
            $this->logger->error('Failed to enqueue event: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Desencola un lote de eventos para procesamiento.
     *
     * @param int $batch_size
     *   Número máximo de eventos a desencolar.
     *
     * @return array
     *   Array de eventos desencolados.
     */
    public function dequeue(int $batch_size = 100): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $events = [];

        try {
            // Usar LPOP en bucle para obtener el batch.
            for ($i = 0; $i < $batch_size; $i++) {
                $payload = $this->redis->lPop(self::QUEUE_KEY);

                if ($payload === FALSE || $payload === NULL) {
                    break;
                }

                $event = json_decode($payload, TRUE);
                if ($event) {
                    // Verificar TTL (descartar eventos muy antiguos).
                    $enqueued_at = $event['enqueued_at'] ?? 0;
                    if (time() - $enqueued_at < self::MESSAGE_TTL) {
                        $events[] = $event;
                    } else {
                        $this->logger->info('Discarded expired event: @event_id', [
                            '@event_id' => $event['event_id'] ?? 'unknown',
                        ]);
                    }
                }
            }

            if (count($events) > 0) {
                $this->logger->debug('Dequeued @count events', [
                    '@count' => count($events),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to dequeue events: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        return $events;
    }

    /**
     * Re-encola un evento para reintento.
     *
     * @param array $event_data
     *   Datos del evento a re-encolar.
     * @param int $max_retries
     *   Número máximo de reintentos.
     *
     * @return bool
     *   TRUE si se re-encoló, FALSE si superó max_retries.
     */
    public function requeue(array $event_data, int $max_retries = 3): bool
    {
        $retry_count = ($event_data['retry_count'] ?? 0) + 1;

        if ($retry_count > $max_retries) {
            $this->logger->warning('Event exceeded max retries: @event_id', [
                '@event_id' => $event_data['event_id'] ?? 'unknown',
            ]);
            return FALSE;
        }

        $event_data['retry_count'] = $retry_count;
        $event_data['last_retry_at'] = time();

        return $this->enqueue($event_data);
    }

    /**
     * Obtiene el número de eventos en cola.
     *
     * @return int
     *   Número de eventos pendientes.
     */
    public function getQueueLength(): int
    {
        if (!$this->isAvailable()) {
            return 0;
        }

        try {
            return (int) $this->redis->lLen(self::QUEUE_KEY);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Limpia la cola completamente (uso en tests/debug).
     *
     * @return bool
     *   TRUE si se limpió correctamente.
     */
    public function clear(): bool
    {
        if (!$this->isAvailable()) {
            return FALSE;
        }

        try {
            $this->redis->del(self::QUEUE_KEY);
            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Obtiene estadísticas de la cola.
     *
     * @return array
     *   Estadísticas de la cola.
     */
    public function getStats(): array
    {
        return [
            'available' => $this->isAvailable(),
            'queue_length' => $this->getQueueLength(),
            'queue_key' => self::QUEUE_KEY,
            'message_ttl' => self::MESSAGE_TTL,
        ];
    }

}
