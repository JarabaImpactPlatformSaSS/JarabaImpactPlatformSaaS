<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_agroconecta_core\Entity\AgroBatch;
use Drupal\jaraba_agroconecta_core\Entity\TraceEventAgro;
use Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface;

/**
 * Servicio de Trazabilidad Inmutable para AgroConecta.
 *
 * LÓGICA:
 * - Gestiona la cadena de hashes (SHA-256).
 * - Crea eventos automáticos vinculados a logística.
 * - Verifica la integridad de un lote.
 *
 * F6 — Doc 80.
 */
class AgroTraceabilityService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Añade un evento a la cadena de trazabilidad de un lote.
   */
  public function addEvent(int $batchId, string $type, string $description, array $data = []): TraceEventAgro {
    $batch = $this->entityTypeManager->getStorage('agro_batch')->load($batchId);
    if (!$batch) {
      throw new \InvalidArgumentException("Lote no encontrado.");
    }

    $last_event = $this->getLastEvent($batchId);
    $sequence = $last_event ? $last_event->getSequence() + 1 : 1;
    $previous_hash = $last_event ? $last_event->getEventHash() : 'GENESIS';

    /** @var \Drupal\jaraba_agroconecta_core\Entity\TraceEventAgro $event */
    $event = $this->entityTypeManager->getStorage('trace_event_agro')->create([
      'batch_id' => $batchId,
      'event_type' => $type,
      'description' => $description,
      'location' => $data['location'] ?? '',
      'actor' => $data['actor'] ?? '',
      'event_timestamp' => date('Y-m-d\TH:i:s'),
      'metadata' => json_encode($data['metadata'] ?? []),
      'previous_hash' => $previous_hash,
      'sequence' => $sequence,
      'shipment_id' => $data['shipment_id'] ?? NULL,
    ]);

    // Generar hash del evento actual.
    $hash_data = [
      $previous_hash,
      $type,
      $description,
      $sequence,
      $event->get('event_timestamp')->value,
    ];
    $event->set('event_hash', hash('sha256', implode('|', $hash_data)));
    $event->save();

    // Actualizar el hash de cabecera del lote.
    $batch->set('chain_hash', $event->getEventHash());
    $batch->save();

    return $event;
  }

  /**
   * Crea un evento de envío vinculado a un AgroShipment físico.
   */
  public function recordShipmentEvent(AgroShipmentInterface $shipment, int $batchId): TraceEventAgro {
    return $this->addEvent($batchId, 'envio', "Envío registrado: " . $shipment->getShipmentNumber(), [
      'shipment_id' => $shipment->id(),
      'actor' => 'Sistema Logístico',
      'metadata' => [
        'carrier' => $shipment->getCarrierId(),
        'tracking' => $shipment->getTrackingNumber(),
      ],
    ]);
  }

  /**
   * Obtiene el último evento de un lote.
   */
  protected function getLastEvent(int $batchId): ?TraceEventAgro {
    $storage = $this->entityTypeManager->getStorage('trace_event_agro');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('batch_id', $batchId)
      ->sort('sequence', 'DESC')
      ->range(0, 1)
      ->execute();

    return !empty($ids) ? $storage->load(reset($ids)) : NULL;
  }

}
