<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\jaraba_agroconecta_core\Entity\AgroBatch;

/**
 * Servicio de Generación y Gestión de QR para AgroConecta.
 *
 * F6 — Doc 81.
 */
class QrAgroService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Genera el registro de un QR para un lote.
   */
  public function createBatchQr(AgroBatch $batch): \Drupal\Core\Entity\EntityInterface {
    $storage = $this->entityTypeManager->getStorage('qr_code_agro');
    
    /** @var \Drupal\Core\Url $url */
    $url = Url::fromRoute('jaraba_agroconecta.traceability.public', [
      'batch_code' => $batch->getBatchCode(),
    ], ['absolute' => TRUE]);

    $qr = $storage->create([
      'tenant_id' => $batch->get('tenant_id')->target_id,
      'target_entity_type' => 'agro_batch',
      'target_entity_id' => $batch->id(),
      'destination_url' => $url->toString(),
      'qr_type' => 'traceability',
      'status' => TRUE,
    ]);
    
    $qr->save();
    return $qr;
  }

  /**
   * Registra un evento de escaneo.
   */
  public function recordScan(int $qrId, array $data = []): void {
    $storage = $this->entityTypeManager->getStorage('qr_scan_event');
    $scan = $storage->create([
      'qr_id' => $qrId,
      'ip_address' => $data['ip'] ?? '',
      'user_agent' => $data['user_agent'] ?? '',
      'location_data' => json_encode($data['location'] ?? []),
      'created' => time(),
    ]);
    $scan->save();
  }

}
