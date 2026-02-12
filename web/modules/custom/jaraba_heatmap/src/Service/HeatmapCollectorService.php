<?php

namespace Drupal\jaraba_heatmap\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para recolección y almacenamiento de eventos de heatmap.
 *
 * Procesa el payload del tracker JavaScript y encola los eventos
 * normalizados para procesamiento asíncrono por HeatmapEventProcessor
 * QueueWorker. Opcionalmente, puede insertar directamente en BD
 * cuando la cola está deshabilitada (configuración use_queue).
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 * Ref: Spec 20260130a §4.1
 */
class HeatmapCollectorService
{

    /**
     * Conexión a base de datos.
     */
    protected Connection $database;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * State service.
     */
    protected StateInterface $state;

    /**
     * Queue factory.
     */
    protected QueueFactory $queueFactory;

    /**
     * Config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   Conexión a base de datos.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
     *   Factory de canales de log.
     * @param \Drupal\Core\State\StateInterface $state
     *   Servicio de estado.
     * @param \Drupal\Core\Queue\QueueFactory $queue_factory
     *   Factory de colas para encolado asíncrono.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   Factory de configuración.
     */
    public function __construct(
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory,
        StateInterface $state,
        QueueFactory $queue_factory,
        ConfigFactoryInterface $config_factory,
    ) {
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_heatmap');
        $this->state = $state;
        $this->queueFactory = $queue_factory;
        $this->configFactory = $config_factory;
    }

    /**
     * Procesa un payload de eventos del tracker.
     *
     * Normaliza los eventos del payload y los encola para procesamiento
     * asíncrono (por defecto) o los inserta directamente en BD si la
     * configuración use_queue está deshabilitada.
     *
     * @param array $payload
     *   Payload con estructura:
     *   - tenant_id: ID del tenant
     *   - session_id: ID de sesión del visitante
     *   - page: Path de la página
     *   - viewport: [w, h]
     *   - device: 'desktop', 'tablet', 'mobile'
     *   - events: Array de eventos con campos t, x, y, etc.
     *
     * @return int
     *   Número de eventos procesados.
     */
    public function processEvents(array $payload): int
    {
        $events = $payload['events'] ?? [];
        if (empty($events)) {
            return 0;
        }

        // Normalizar eventos del payload crudo.
        $normalizedEvents = $this->normalizePayload($payload);
        if (empty($normalizedEvents)) {
            return 0;
        }

        $count = count($normalizedEvents);

        // Encolar o insertar directamente según configuración.
        if ($this->useQueue()) {
            $queue = $this->queueFactory->get('jaraba_heatmap_events');
            foreach ($normalizedEvents as $event) {
                $queue->createItem($event);
            }
        }
        else {
            $this->insertEvents($normalizedEvents);
        }

        // Actualizar contador de estado.
        $total = $this->state->get('jaraba_heatmap.total_events', 0);
        $this->state->set('jaraba_heatmap.total_events', $total + $count);

        return $count;
    }

    /**
     * Normaliza el payload crudo en un array de eventos con campos completos.
     *
     * @param array $payload
     *   Payload del tracker JavaScript.
     *
     * @return array
     *   Array de eventos normalizados listos para inserción o encolado.
     */
    protected function normalizePayload(array $payload): array
    {
        $events = $payload['events'] ?? [];
        $tenant_id = (int) ($payload['tenant_id'] ?? 0);
        $session_id = $this->sanitizeString($payload['session_id'] ?? '', 64);
        $page_path = $this->sanitizeString($payload['page'] ?? '', 2048);
        $viewport_width = (int) ($payload['viewport']['w'] ?? 1280);
        $viewport_height = (int) ($payload['viewport']['h'] ?? 900);
        $device_type = $this->normalizeDevice($payload['device'] ?? 'desktop');
        $timestamp = time();

        $normalized = [];
        foreach ($events as $event) {
            // Validar tipo de evento.
            $type = $event['t'] ?? '';
            if (!in_array($type, ['click', 'move', 'scroll', 'visibility'])) {
                continue;
            }

            // Calcular posición X como porcentaje.
            $x_pixel = (float) ($event['x'] ?? 0);
            $x_percent = $viewport_width > 0 ? round($x_pixel / $viewport_width * 100, 2) : 0;
            $x_percent = max(0, min(100, $x_percent));

            // Y en píxeles absolutos.
            $y_pixel = (int) ($event['y'] ?? 0);

            // Scroll depth (solo para eventos scroll).
            $scroll_depth = NULL;
            if ($type === 'scroll') {
                $scroll_depth = (int) ($event['d'] ?? 0);
                $scroll_depth = max(0, min(100, $scroll_depth));
            }

            // Selector y texto del elemento (para clicks).
            $element_selector = NULL;
            $element_text = NULL;
            if ($type === 'click') {
                $element_selector = $this->sanitizeString($event['el'] ?? '', 512);
                $element_text = $this->sanitizeString($event['txt'] ?? '', 100);
            }

            $normalized[] = [
                'tenant_id' => $tenant_id,
                'session_id' => $session_id,
                'page_path' => $page_path,
                'event_type' => $type,
                'x_percent' => $x_percent,
                'y_pixel' => $y_pixel,
                'viewport_width' => $viewport_width,
                'viewport_height' => $viewport_height,
                'scroll_depth' => $scroll_depth,
                'element_selector' => $element_selector,
                'element_text' => $element_text,
                'device_type' => $device_type,
                'created_at' => $timestamp,
            ];
        }

        return $normalized;
    }

    /**
     * Inserta eventos normalizados directamente en la BD (fallback).
     *
     * @param array $normalizedEvents
     *   Array de eventos normalizados.
     *
     * @throws \Exception
     *   Si falla la inserción en BD.
     */
    protected function insertEvents(array $normalizedEvents): void
    {
        $insert_query = $this->database->insert('heatmap_events')
            ->fields([
                'tenant_id',
                'session_id',
                'page_path',
                'event_type',
                'x_percent',
                'y_pixel',
                'viewport_width',
                'viewport_height',
                'scroll_depth',
                'element_selector',
                'element_text',
                'device_type',
                'created_at',
            ]);

        foreach ($normalizedEvents as $event) {
            $insert_query->values($event);
        }

        try {
            $insert_query->execute();
        }
        catch (\Exception $e) {
            $this->logger->error('Error en batch insert de heatmap: @message', [
                '@message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Determina si se debe usar la cola para procesamiento asíncrono.
     *
     * @return bool
     *   TRUE para encolar (por defecto), FALSE para inserción directa.
     */
    protected function useQueue(): bool
    {
        $value = $this->configFactory->get('jaraba_heatmap.settings')->get('use_queue');
        return $value === NULL ? TRUE : (bool) $value;
    }

    /**
     * Sanitiza un string para almacenamiento seguro.
     *
     * @param string $value
     *   Valor a sanitizar.
     * @param int $max_length
     *   Longitud máxima.
     *
     * @return string
     *   String sanitizado.
     */
    protected function sanitizeString(string $value, int $max_length): string
    {
        $value = trim($value);
        $value = mb_substr($value, 0, $max_length);
        return $value;
    }

    /**
     * Normaliza el tipo de dispositivo.
     *
     * @param string $device
     *   Tipo de dispositivo del payload.
     *
     * @return string
     *   Tipo normalizado: desktop, tablet, mobile.
     */
    protected function normalizeDevice(string $device): string
    {
        $device = strtolower(trim($device));
        if (in_array($device, ['desktop', 'tablet', 'mobile'])) {
            return $device;
        }
        return 'desktop';
    }

}
