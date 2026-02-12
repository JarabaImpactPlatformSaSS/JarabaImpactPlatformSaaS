<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Pipeline de ingesta de eventos de uso.
 *
 * Punto de entrada para registrar eventos de consumo de recursos.
 * Valida, normaliza y persiste los eventos como entidades UsageEvent.
 */
class UsageIngestionService {

  /**
   * Campos requeridos para un evento de uso.
   */
  protected const REQUIRED_FIELDS = ['event_type', 'metric_name', 'quantity', 'tenant_id'];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ingesta un único evento de uso.
   *
   * @param array $data
   *   Datos del evento con claves: event_type, metric_name, quantity,
   *   tenant_id, y opcionalmente: unit, user_id, metadata, recorded_at.
   *
   * @return int|null
   *   ID de la entidad creada, o NULL si falla.
   */
  public function ingestEvent(array $data): ?int {
    try {
      // Validar campos requeridos.
      foreach (self::REQUIRED_FIELDS as $field) {
        if (empty($data[$field])) {
          $this->logger->warning('Evento de uso rechazado: campo requerido @field vacío.', [
            '@field' => $field,
          ]);
          return NULL;
        }
      }

      // Normalizar datos.
      $values = [
        'event_type' => (string) $data['event_type'],
        'metric_name' => (string) $data['metric_name'],
        'quantity' => (string) number_format((float) $data['quantity'], 4, '.', ''),
        'tenant_id' => (int) $data['tenant_id'],
        'recorded_at' => $data['recorded_at'] ?? time(),
      ];

      if (!empty($data['unit'])) {
        $values['unit'] = (string) $data['unit'];
      }

      if (!empty($data['user_id'])) {
        $values['user_id'] = (int) $data['user_id'];
      }

      if (!empty($data['metadata'])) {
        $values['metadata'] = is_string($data['metadata'])
          ? $data['metadata']
          : json_encode($data['metadata'], JSON_THROW_ON_ERROR);
      }

      // Crear entidad.
      $storage = $this->entityTypeManager->getStorage('usage_event');
      $entity = $storage->create($values);
      $entity->save();

      $this->logger->info('Evento de uso ingestado: @type/@metric para tenant @tenant (ID: @id).', [
        '@type' => $values['event_type'],
        '@metric' => $values['metric_name'],
        '@tenant' => $values['tenant_id'],
        '@id' => $entity->id(),
      ]);

      return (int) $entity->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Error ingestando evento de uso: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Ingesta un lote de eventos de uso.
   *
   * @param array $events
   *   Array de arrays de datos de eventos.
   *
   * @return int
   *   Número de eventos ingestados con éxito.
   */
  public function batchIngest(array $events): int {
    $count = 0;

    try {
      $transaction = $this->database->startTransaction();

      foreach ($events as $eventData) {
        $result = $this->ingestEvent($eventData);
        if ($result !== NULL) {
          $count++;
        }
      }

      $this->logger->info('Batch ingest completado: @count/@total eventos procesados.', [
        '@count' => $count,
        '@total' => count($events),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en batch ingest de eventos de uso: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $count;
  }

}
