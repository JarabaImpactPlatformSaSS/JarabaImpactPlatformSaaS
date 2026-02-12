<?php

declare(strict_types=1);

namespace Drupal\jaraba_heatmap\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa eventos de heatmap encolados por HeatmapCollectorService.
 *
 * Los eventos llegan desde el tracker JavaScript del frontend mediante
 * Beacon API y se almacenan temporalmente en la cola Redis. Este worker
 * los inserta en la tabla heatmap_events durante el procesamiento de cron,
 * desacoplando la recepción de datos (tiempo real) del almacenamiento
 * persistente (batch asíncrono).
 *
 * @QueueWorker(
 *   id = "jaraba_heatmap_events",
 *   title = @Translation("Heatmap Event Processor"),
 *   cron = {"time" = 30}
 * )
 *
 * Ref: Spec 20260130a §4.1 — HeatmapEventProcessor
 */
class HeatmapEventProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuración del plugin.
   * @param string $plugin_id
   *   ID del plugin.
   * @param mixed $plugin_definition
   *   Definición del plugin.
   * @param \Drupal\Core\Database\Connection $database
   *   Conexión a base de datos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del módulo.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('logger.factory')->get('jaraba_heatmap'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un único evento de heatmap e inserta en BD.
   *
   * Cada $data contiene los campos normalizados por HeatmapCollectorService:
   * tenant_id, session_id, page_path, event_type, x_percent, y_pixel,
   * viewport_width, viewport_height, scroll_depth, element_selector,
   * element_text, device_type, created_at.
   */
  public function processItem($data): void {
    try {
      $this->database->insert('heatmap_events')
        ->fields([
          'tenant_id' => (int) $data['tenant_id'],
          'session_id' => (string) $data['session_id'],
          'page_path' => (string) $data['page_path'],
          'event_type' => (string) $data['event_type'],
          'x_percent' => $data['x_percent'] ?? NULL,
          'y_pixel' => $data['y_pixel'] ?? NULL,
          'viewport_width' => (int) ($data['viewport_width'] ?? 0),
          'viewport_height' => (int) ($data['viewport_height'] ?? 0),
          'scroll_depth' => $data['scroll_depth'] ?? NULL,
          'element_selector' => $data['element_selector'] ?? NULL,
          'element_text' => $data['element_text'] ?? NULL,
          'device_type' => $data['device_type'] ?? 'desktop',
          'created_at' => (int) ($data['created_at'] ?? \Drupal::time()->getRequestTime()),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to process heatmap event: @message', [
        '@message' => $e->getMessage(),
      ]);
      // No relanzar excepción para evitar que el item se reencole
      // indefinidamente. El evento se pierde, pero se registra en el log.
    }
  }

}
