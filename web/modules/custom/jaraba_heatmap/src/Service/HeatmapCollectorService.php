<?php

namespace Drupal\jaraba_heatmap\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para recolección y almacenamiento de eventos de heatmap.
 *
 * Procesa el payload del tracker JavaScript y realiza batch insert
 * en la tabla de eventos raw. Diseñado para alta throughput con
 * mínima latencia.
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 */
class HeatmapCollectorService
{

    /**
     * Conexión a base de datos.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * State service.
     *
     * @var \Drupal\Core\State\StateInterface
     */
    protected $state;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   Conexión a base de datos.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
     *   Factory de canales de log.
     * @param \Drupal\Core\State\StateInterface $state
     *   Servicio de estado.
     */
    public function __construct(
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory,
        StateInterface $state
    ) {
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_heatmap');
        $this->state = $state;
    }

    /**
     * Procesa un payload de eventos del tracker.
     *
     * @param array $payload
     *   Payload con estructura:
     *   - tenant_id: ID del tenant
     *   - session_id: ID de sesión del visitante
     *   - page: Path de la página
     *   - viewport: [width, height]
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

        // Extraer contexto común.
        $tenant_id = (int) ($payload['tenant_id'] ?? 0);
        $session_id = $this->sanitizeString($payload['session_id'] ?? '', 64);
        $page_path = $this->sanitizeString($payload['page'] ?? '', 2048);
        $viewport_width = (int) ($payload['viewport']['w'] ?? 1280);
        $viewport_height = (int) ($payload['viewport']['h'] ?? 900);
        $device_type = $this->normalizeDevice($payload['device'] ?? 'desktop');
        $timestamp = time();

        // Preparar batch insert.
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

        $count = 0;
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

            $insert_query->values([
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
            ]);

            $count++;
        }

        // Ejecutar batch insert.
        if ($count > 0) {
            try {
                $insert_query->execute();

                // Actualizar contador de estado.
                $total = $this->state->get('jaraba_heatmap.total_events', 0);
                $this->state->set('jaraba_heatmap.total_events', $total + $count);
            } catch (\Exception $e) {
                $this->logger->error('Error en batch insert de heatmap: @message', [
                    '@message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return $count;
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
